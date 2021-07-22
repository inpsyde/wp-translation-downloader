<?php

/*
 * This file is part of the WP Translation Downloader package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Inpsyde\WpTranslationDownloader\Util;

use Composer\Json\JsonFile;
use Composer\Util\Filesystem;
use Inpsyde\WpTranslationDownloader\Io;

class Locker
{
    public const LOCK_FILE = 'wp-translation-downloader.lock';

    /**
     * @var Io
     */
    private $io;

    /**
     * @var JsonFile
     */
    private $file;

    /**
     * @var array
     */
    private $lockedData = [];

    /**
     * Locker constructor.
     *
     * @param Io $io
     * @param string $projectRoot
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Io $io, string $projectRoot)
    {
        $this->io = $io;
        $this->file = new JsonFile($projectRoot . self::LOCK_FILE);
        $this->loadLockData();
    }

    /**
     * Check if a translation project for the current language is locked.
     *
     * @param string $projectName
     * @param string $language
     * @param string $lastUpdated
     * @param string $version
     *
     * @return bool
     */
    public function isLocked(string $projectName, string $language, string $lastUpdated, string $version): bool
    {
        // phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
        $lockedData = $this->lockedData[$projectName]['translations'][$language] ?? null;
        if (!$lockedData) {
            return false;
        }

        $lockedLastUpdated = $lockedData['updated'] ?? null;
        $lockedVersion = $lockedData['version'] ?? null;
        if (!$lockedLastUpdated && !$lockedVersion) {
            return false;
        }

        // A project with a given language is locked when...
        //
        //  -> lockedLastUpdated is greater or equal the lastUpdated
        //  -> lockedVersion is greater or equal the current version
        $checks = [
            strtotime($lockedLastUpdated) >= strtotime($lastUpdated),
            version_compare($lockedVersion, $version, '>='),
        ];
        $isLocked = !in_array(false, $checks, true);

        if ($isLocked) {
            $this->io->writeOnVerbose(
                sprintf(
                    '    <info>[LOCKED]</info> %1$s | %2$s | %3$s',
                    $language,
                    $lastUpdated,
                    $version
                )
            );
            // When a project is locked, then we want to add it again
            // to the lock-file.
            $this->addProjectLock($projectName, $language, $lastUpdated, $version);

            return true;
        }

        return false;
    }

    /**
     * Lock a translation project for a given language.
     *
     * @param string $projectName
     * @param string $language
     * @param string $lastUpdated
     * @param string $version
     */
    public function addProjectLock(string $projectName, string $language, string $lastUpdated, string $version): bool
    {
        if (!isset($this->lockedData[$projectName])) {
            $this->lockedData[$projectName] = [
                'translations' => [],
            ];
        }

        $this->lockedData[$projectName]['translations'][$language] = [
            'updated' => $lastUpdated,
            'version' => $version,
        ];

        return true;
    }

    /**
     * Remove a given translation project by name from lock data.
     *
     * @param string $projectName
     *
     * @return bool
     */
    public function removeProjectLock(string $projectName): bool
    {
        if (!isset($this->lockedData[$projectName])) {
            return false;
        }

        unset($this->lockedData[$projectName]);

        return true;
    }

    /**
     * Writing lock-file into filesystem.
     *
     * @return bool
     */
    public function writeLockFile(): bool
    {
        try {
            $this->io->write(
                sprintf("\n<info>Writing new lock data to %s<info>", $this->file->getPath())
            );
            $this->file->write($this->lockedData);

            return true;
        } catch (\Throwable $exception) {
            $this->io->error($exception->getMessage());

            return false;
        }
    }

    /**
     * Remove lock-file from filesystem.
     *
     * @return bool
     */
    public function removeLockFile(): bool
    {
        $this->io->write(
            sprintf("\n<info>Lock file %s was removed.<info>", $this->file->getPath())
        );

        return (new Filesystem())->remove($this->file->getPath());
    }

    /**
     * @return bool
     */
    private function loadLockData(): bool
    {
        try {
            if (!$this->file->exists()) {
                $this->io->writeOnVerbose(
                    sprintf('<error>No %s found.</error>', $this->file->getPath())
                );

                return false;
            }

            $this->lockedData = $this->file->read();

            $this->io->writeOnVerbose(
                sprintf('<info>Successfully loaded %s.</info>', $this->file->getPath())
            );

            return true;
        } catch (\Throwable $exception) {
            $this->io->writeOnVerbose($exception->getMessage());

            return false;
        }
    }

    /**
     * @return array
     */
    public function lockData(): array
    {
        return $this->lockedData;
    }
}
