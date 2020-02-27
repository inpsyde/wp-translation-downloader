<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader\Downloader;

use Composer\Cache;
use Composer\Config;
use Composer\Downloader\ZipDownloader;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackage;

class TranslationDownloader
{

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ZipDownloader
     */
    private $zipDownloader;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Cache
     */
    private $cache;

    public function __construct(
        IOInterface $io,
        Config $config,
        ZipDownloader $zipDownloader,
        Filesystem $filesystem,
        Cache $cache
    ) {

        $this->io = $io;
        $this->config = $config;
        $this->zipDownloader = $zipDownloader;
        $this->filesystem = $filesystem;
        $this->cache = $cache;

        if ($this->cache->gcIsNecessary()) {
            $this->cache->gc($config->get('cache-files-ttl'), $config->get('cache-files-maxsize'));
        }
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
                '  Found %d translations for %s on %s...',
                count($translations),
                $projectName,
                $transPackage->apiEndpoint()
            )
        );

        foreach ($translations as $translation) {
            $package = $translation['package'];
            $language = $translation['language'];
            $version = $translation['version'];
            $fileName = $projectName.'-'.$version.'-'.basename($package);
            $zipFile = $this->cache->getRoot().$fileName;

            // only download file if it not exist.
            file_exists($zipFile)
            && $this->io->isVerbose()
            && $this->io->write(
                sprintf(
                    '    - <info>Cache</info> %s %s: Using cached translation file %s</info> ',
                    $projectName,
                    $version,
                    $zipFile
                )
            );

            if (! file_exists($zipFile) && ! copy($package, $zipFile)) {
                $this->io->writeError(
                    sprintf(
                        '    - <error>[ERROR]</error> %s %s: Could not download and write "%s"</>',
                        $projectName,
                        $version,
                        $package
                    )
                );
                continue;
            }

            try {
                // phpcs:disable NeutronStandard.Extract.DisallowExtract.Extract
                $this->zipDownloader->extract($zipFile, $directory);
                $this->io->write(
                    sprintf(
                        '    - <info>[OK]</info> %s | %s.',
                        $version,
                        $language
                    )
                );
            } catch (\Throwable $exception) {
                $this->io->writeError(
                    sprintf(
                        '    - <error>[ERROR]</error>Could not unzip translation files. %s | %s</>',
                        $version,
                        $language
                    )
                );
                $this->io->writeError($exception->getMessage());
            }
        }

        return true;
    }

    public function remove(TranslatablePackage $transPackage, array $allowedLanguages)
    {
        $directory = $transPackage->languageDirectory();
        $translations = $transPackage->translations($allowedLanguages);

        foreach ($translations as $translation) {
            $language = $translation['language'];
            $files = [
                $directory.$transPackage->projectName().'-'.$language.'.mo',
                $directory.$transPackage->projectName().'-'.$language.'.po',
            ];
            foreach ($files as $file) {
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
                    $this->io->writeError($exception->getMessage());
                }
            }
        }

        return true;
    }
}
