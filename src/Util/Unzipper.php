<?php

// phpcs:disable
/*
 * This file is part of the Assets package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Inpsyde\WpTranslationDownloader\Util;

use Composer\IO\IOInterface;
use Composer\Util\Platform;
use Composer\Util\ProcessExecutor;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Class Unzipper
 *
 * This file contains some code copied from Composer v1 `RemoteFilesystem`,
 * since v2 had a lot of breaking changes which does not allow us
 * anymore to really use it without providing either a PackageInterface
 * or implementing checks for Composer v1 and v2.
 *
 * @package Inpsyde\WpTranslationDownloader\Util
 */
class Unzipper
{

    /**
     * @var bool
     */
    private static $isWindows;

    /**
     * @var bool
     */
    private static $hasSystemUnzip;

    /**
     * @var bool
     */
    private static $hasZipArchive;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var ProcessExecutor
     */
    private $process;

    /**
     * @var \ZipArchive
     */
    private $zipArchiveObject;

    /**
     * Unzipper constructor.
     *
     * @param IOInterface $io
     */
    public function __construct(IOInterface $io)
    {
        $this->io = $io;
        $this->process = new ProcessExecutor($io);

        $finder = new ExecutableFinder;

        self::$isWindows = Platform::isWindows();
        self::$hasZipArchive = class_exists('ZipArchive');
        self::$hasSystemUnzip = (bool) $finder->find('unzip');
    }

    /**
     * extract $file to $path
     *
     * @param string $file File to extract
     * @param string $path Path where to extract file
     *
     * @return bool Success status
     */
    public function extract(string $file, string $path): bool
    {
        // Each extract calls its alternative if not available or fails
        if (self::$isWindows) {
            return $this->extractWithZipArchive($file, $path, false);
        }

        return $this->extractWithSystemUnzip($file, $path, false);
    }

    /**
     * extract $file to $path with "unzip" command
     *
     * @param string $file File to extract
     * @param string $path Path where to extract file
     * @param bool $isLastChance If true it is called as a fallback and should throw an exception
     *
     * @return bool   Success status
     *
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     */
    protected function extractWithSystemUnzip(string $file, string $path, bool $isLastChance): bool
    {
        if (! self::$hasZipArchive) {
            // Force Exception throwing if the Other alternative is not available
            $isLastChance = true;
        }

        if (! self::$hasSystemUnzip && ! $isLastChance) {
            // This was call as the favorite extract way, but is not available
            // We switch to the alternative
            return $this->extractWithZipArchive($file, $path, true);
        }

        $processError = null;
        // When called after a ZipArchive failed, perhaps there is some files to overwrite
        $overwrite = $isLastChance
            ? '-o'
            : '';

        $command = 'unzip -qq '.$overwrite.' '.ProcessExecutor::escape($file).' -d '.ProcessExecutor::escape($path);

        try {
            if (0 === $exitCode = $this->process->execute($command, $ignoredOutput)) {
                return true;
            }

            $processError = new \RuntimeException(
                'Failed to execute ('.$exitCode.') '.$command."\n\n".$this->process->getErrorOutput()
            );
        } catch (\Exception $e) {
            $processError = $e;
        }

        if ($isLastChance) {
            throw $processError;
        }

        $this->io->writeError('    '.$processError->getMessage());
        $this->io->writeError(
            '    The archive may contain identical file names with different capitalization (which fails on case insensitive filesystems)'
        );
        $this->io->writeError('    Unzip with unzip command failed, falling back to ZipArchive class');

        return $this->extractWithZipArchive($file, $path, true);
    }

    /**
     * extract $file to $path with ZipArchive
     *
     * @param string $file File to extract
     * @param string $path Path where to extract file
     * @param bool $isLastChance If true it is called as a fallback and should throw an exception
     *
     * @return bool   Success status
     *
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     */
    protected function extractWithZipArchive(string $file, string $path, bool $isLastChance): bool
    {
        if (! self::$hasSystemUnzip) {
            // Force Exception throwing if the Other alternative is not available
            $isLastChance = true;
        }

        if (! self::$hasZipArchive && ! $isLastChance) {
            // This was call as the favorite extract way, but is not available
            // We switch to the alternative
            return $this->extractWithSystemUnzip($file, $path, true);
        }

        $processError = null;
        $zipArchive = $this->zipArchiveObject
            ?: new \ZipArchive();

        try {
            if (true === ($retval = $zipArchive->open($file))) {
                $extractResult = $zipArchive->extractTo($path);

                if (true === $extractResult) {
                    $zipArchive->close();

                    return true;
                }

                $processError = new \RuntimeException(
                    rtrim(
                        "There was an error extracting the ZIP file, it is either corrupted or using an invalid format.\n"
                    )
                );
            } else {
                $processError = new \UnexpectedValueException(
                    rtrim($this->errorMessage($retval, $file)."\n"),
                    $retval
                );
            }
        } catch (\ErrorException $e) {
            $processError = new \RuntimeException(
                'The archive may contain identical file names with different capitalization (which fails on case insensitive filesystems): '
                . $e->getMessage(), 0, $e
            );
        } catch (\Exception $e) {
            $processError = $e;
        }

        if ($isLastChance) {
            throw $processError;
        }

        $this->io->writeError('    ' . $processError->getMessage());
        $this->io->writeError('    Unzip with ZipArchive class failed, falling back to unzip command');

        return $this->extractWithSystemUnzip($file, $path, true);
    }

    /**
     * Give a meaningful error message to the user.
     *
     * @param int $retval
     * @param string $file
     *
     * @return string
     *
     * phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
     */
    protected function errorMessage(int $retval, string $file): string
    {
        switch ($retval) {
            case ZipArchive::ER_EXISTS:
                return sprintf("File '%s' already exists.", $file);
            case ZipArchive::ER_INCONS:
                return sprintf("Zip archive '%s' is inconsistent.", $file);
            case ZipArchive::ER_INVAL:
                return sprintf("Invalid argument (%s)", $file);
            case ZipArchive::ER_MEMORY:
                return sprintf("Malloc failure (%s)", $file);
            case ZipArchive::ER_NOENT:
                return sprintf("No such zip file: '%s'", $file);
            case ZipArchive::ER_NOZIP:
                return sprintf("'%s' is not a zip archive.", $file);
            case ZipArchive::ER_OPEN:
                return sprintf("Can't open zip file: %s", $file);
            case ZipArchive::ER_READ:
                return sprintf("Zip read error (%s)", $file);
            case ZipArchive::ER_SEEK:
                return sprintf("Zip seek error (%s)", $file);
            default:
                return sprintf(
                    "'%s' is not a valid zip archive, got error code: %s",
                    $file,
                    $retval
                );
        }
    }
}
