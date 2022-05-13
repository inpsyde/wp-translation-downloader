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

use Composer\Downloader\ZipDownloader;
use Composer\IO\IOInterface;
use Composer\Util\RemoteFilesystem;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackageInterface;

class Downloader
{
    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var ZipDownloader
     */
    private $unzipper;

    /**
     * @var Locker
     */
    private $locker;

    /**
     * @var string
     */
    private $cacheRoot;

    /**
     * @var RemoteFilesystem
     */
    private $remoteFilesystem;

    /**
     * TranslationDownloader constructor.
     *
     * @param IOInterface $io
     * @param Unzipper $unzipper
     * @param RemoteFilesystem $remoteFilesystem
     * @param Locker $locker
     * @param string $cacheRoot
     */
    public function __construct(
        IOInterface $io,
        Unzipper $unzipper,
        RemoteFilesystem $remoteFilesystem,
        Locker $locker,
        string $cacheRoot
    ) {
        $this->io = $io;
        $this->unzipper = $unzipper;
        $this->remoteFilesystem = $remoteFilesystem;
        $this->locker = $locker;
        $this->cacheRoot = $cacheRoot;
    }

    /**
     * @param TranslatablePackageInterface $transPackage
     * @param array $allowedLanguages
     *
     * @return bool
     *
     * phpcs:disable
     */
    public function download(TranslatablePackageInterface $transPackage, array $allowedLanguages)
    {
        $directory = $transPackage->languageDirectory();
        $translations = $transPackage->translations($allowedLanguages);
        $projectName = $transPackage->projectName();

        $this->io->write(
            sprintf(
                '  <info>%2$s:</info> found %1$d translations',
                count($translations),
                $projectName
            )
        );

        $this->io->write(
            sprintf(
                '  - Endpoint: %s',
                $transPackage->apiEndpoint()
            ),
            true,
            IOInterface::VERBOSE
        );

        $downloaded = $locked = 0;
        foreach ($translations as $translation) {
            try {
                $packageUrl = $translation['package'];
                $language = $translation['language'];
                $lastUpdated = $translation['updated'];
                $version = $translation['version'];
                $fileName = sprintf(
                    '%1$s-%2$s-%3$s.%4$s',
                    $projectName,
                    $language,
                    $version,
                    pathinfo($packageUrl, PATHINFO_EXTENSION)
                );
                $zipFile = $this->cacheRoot . $fileName;

                if ($this->locker->isLocked($projectName, $language, $lastUpdated, $version)) {
                    $locked++;
                    continue;
                }

                $this->downloadZipFile($zipFile, $packageUrl, $lastUpdated);

                // phpcs:disable NeutronStandard.Extract.DisallowExtract.Extract
                $this->unzipper->extract($zipFile, $directory);
                $this->io->write(
                    sprintf(
                        '    <info>✓</info> %s | %s',
                        $version,
                        $language
                    )
                );

                $this->locker->addProjectLock($projectName, $language, $lastUpdated, $version);
                $downloaded++;
            } catch (\Throwable $exception) {
                $this->io->write(
                    sprintf(
                        '    <fg=red>✗</> %s | %s',
                        $version,
                        $language
                    )
                );
                $this->io->writeError($exception->getMessage());
            }
        }

        $this->io->write(
            sprintf(
                '    <options=bold>Stats:</> %1$d downloads, %2$d locked.',
                $downloaded,
                $locked
            )
        );

        return true;
    }

    /**
     * Downloads the zipFile if not exists yet or the file was updated in the meantime.
     *
     * @param string $zipFile
     * @param string $packageUrl
     * @param string $lastUpdated date time in format yyyy-mm-dd hh:ii:ss
     *
     * @return bool
     */
    private function downloadZipFile(string $zipFile, string $packageUrl, string $lastUpdated): bool
    {
        $lastUpdated = new \DateTimeImmutable($lastUpdated);

        if (file_exists($zipFile) && filemtime($zipFile) >= $lastUpdated->getTimestamp()) {
            $this->io->write(
                sprintf(
                    '    <info>[CACHED]</info> %s</info> ',
                    $zipFile
                )
            );

            return false;
        }

        $origin = $this->origin($packageUrl);
        $result = $this->remoteFilesystem->copy($origin, $packageUrl, $zipFile, false);

        if ($result === false) {
            return false;
        }

        // set the "updated" time as file time.
        return touch($zipFile, $lastUpdated->getTimestamp());
    }

    /**
     * Internal helper to detect the origin of an URL for RemoteFilesystem.
     *
     * @param string $url
     *
     * @return string
     */
    private function origin(string $url): string
    {
        if (0 === strpos($url, 'file://')) {
            return $url;
        }

        $origin = (string) parse_url($url, PHP_URL_HOST);
        if ($port = parse_url($url, PHP_URL_PORT)) {
            $origin .= ':' . $port;
        }

        if ($origin === '') {
            $origin = $url;
        }

        return $origin;
    }
}
