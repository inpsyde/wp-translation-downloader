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
use Composer\Util\RemoteFilesystem;
use Inpsyde\WpTranslationDownloader\Io;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackage;

class Downloader
{
    /**
     * @var Io
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
     * @param Io $io
     * @param Unzipper $unzipper
     * @param RemoteFilesystem $remoteFilesystem
     * @param Locker $locker
     * @param string $cacheRoot
     */
    public function __construct(
        Io $io,
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
     * @param TranslatablePackage $transPackage
     * @param array $allowedLanguages
     *
     * @return bool
     *
     * phpcs:disable
     */
    public function download(TranslatablePackage $transPackage, array $allowedLanguages)
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

        $this->io->writeOnVerbose(
            sprintf(
                '  - Endpoint: %s',
                $transPackage->apiEndpoint()
            )
        );

        $downloaded = 0;
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

                if ($this->locker->isLocked($projectName, $language, $lastUpdated)) {
                    $this->io->writeOnVerbose(
                        sprintf(
                            '    <info>[LOCKED]</info> %1$s | %2$s',
                            $language,
                            $lastUpdated
                        )
                    );
                    continue;
                }

                $this->downloadZipFile($zipFile, $packageUrl);

                // phpcs:disable NeutronStandard.Extract.DisallowExtract.Extract
                $this->unzipper->extract($zipFile, $directory);
                $this->io->write(
                    sprintf(
                        '    <info>✓</info> %s | %s',
                        $version,
                        $language
                    )
                );

                $this->locker->lock($projectName, $language, $lastUpdated);
                $downloaded++;
            } catch (\Throwable $exception) {
                $this->io->write(
                    sprintf(
                        '    <fg=red>✗</> %s | %s',
                        $version,
                        $language
                    )
                );
                $this->io->error($exception->getMessage());
            }
        }

        $this->io->write(
            sprintf(
                '    <fg=green>Downloaded %d translations.',
                $downloaded
            )
        );

        return true;
    }

    /**
     * @param string $zipFile
     * @param $packageUrl
     *
     * @return bool
     */
    private function downloadZipFile(string $zipFile, $packageUrl): bool
    {
        if (file_exists($zipFile)) {
            $this->io->writeOnVerbose(
                sprintf(
                    '    <info>[CACHED]</info> %s</info> ',
                    $zipFile
                )
            );

            return false;
        }

        $origin = $this->origin($packageUrl);
        $result = $this->remoteFilesystem->copy($origin, $packageUrl, $zipFile, false);

        return !!$result;
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
