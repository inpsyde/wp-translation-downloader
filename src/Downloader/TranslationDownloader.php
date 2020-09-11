<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader\Downloader;

use Composer\Config;
use Composer\Downloader\ZipDownloader;
use Composer\Util\Filesystem;
use Composer\Util\RemoteFilesystem;
use Inpsyde\WpTranslationDownloader\Io;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackage;

class TranslationDownloader
{

    /**
     * @var Io
     */
    private $io;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ZipDownloader
     */
    private $unzipper;

    /**
     * @var Filesystem
     */
    private $filesystem;

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
     * @param ZipDownloader $unzipper
     * @param Filesystem $filesystem
     * @param RemoteFilesystem $remoteFilesystem
     * @param string $cacheRoot
     */
    public function __construct(
        Io $io,
        ZipDownloader $unzipper,
        Filesystem $filesystem,
        RemoteFilesystem $remoteFilesystem,
        string $cacheRoot
    ) {

        $this->io = $io;
        $this->unzipper = $unzipper;
        $this->filesystem = $filesystem;
        $this->cacheRoot = $cacheRoot;
        $this->remoteFilesystem = $remoteFilesystem;
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

        foreach ($translations as $translation) {
            try {
                $packageUrl = $translation['package'];
                $language = $translation['language'];
                $version = $translation['version'];
                $fileName = sprintf(
                    '%1$s-%2$s-%3$s.%4$s',
                    $projectName,
                    $language,
                    $version,
                    pathinfo($packageUrl, PATHINFO_EXTENSION)
                );
                $zipFile = $this->cacheRoot.$fileName;

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

        $origin = RemoteFilesystem::getOrigin($packageUrl);
        $result = $this->remoteFilesystem->copy($origin, $packageUrl, $zipFile, false);

        return ! ! $result;
    }

    public function remove(TranslatablePackage $transPackage)
    {
        $directory = $transPackage->languageDirectory();
        $basePath = rtrim($directory, '/').'/'.$transPackage->projectName();

        foreach (glob("{$basePath}-*.{po,mo}", GLOB_BRACE) as $file) {
            try {
                $this->filesystem->unlink($file);
                $this->io->write(
                    sprintf(
                        "    - <info>[OK]</info> %s: deleted %s translation file.",
                        $transPackage->projectName(),
                        basename($file)
                    )
                );
            } catch (\Throwable $exception) {
                $this->io->error($exception->getMessage());
            }
        }

        return true;
    }
}
