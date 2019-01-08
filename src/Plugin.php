<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader;

use Composer\Composer;
use Composer\Downloader\ZipDownloader;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Util\Filesystem;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Package\TranslateablePackage;

final class Plugin implements PluginInterface, EventSubscriberInterface
{

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ZipDownloader
     */
    private $zipDownloader;

    /**
     * @var PluginConfiguration
     */
    private $config;

    /**
     * Subscribe to Composer events.
     *
     * @return array The events and callbacks.
     */
    public static function getSubscribedEvents()
    {
        return [
            'post-package-install' => [
                ['onUpdate', 0],
            ],
            'post-package-update' => [
                ['onUpdate', 0],
            ],
            'post-package-uninstall' => [
                ['onUninstall', 0],
            ],
        ];
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     *
     * @throws \RuntimeException
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->config = PluginConfiguration::fromExtra($composer->getPackage()->getExtra());
        $this->filesystem = new Filesystem();
        $this->zipDownloader = new ZipDownloader($io, $composer->getConfig());

        $error = $this->config->isValid();
        if ($error !== '') {
            $this->io->writeError($error);

            return;
        }

        foreach ($this->config->directories() as $directory) {
            $this->filesystem->ensureDirectoryExists($directory);
        }
    }

    public function onUninstall(PackageEvent $event)
    {
        /** @var PackageInterface|TranslateablePackage $transPackage */
        $transPackage = TranslateablePackageFactory::create($event->getOperation(), $this->config);

        if ($transPackage === null) {
            return;
        }

        $allowedLanguages = $this->config->allowedLanguages();
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
                }
            }
        }
    }

    public function onUpdate(PackageEvent $event)
    {
        /** @var PackageInterface|TranslateablePackage $transPackage */
        $transPackage = TranslateablePackageFactory::create($event->getOperation(), $this->config);

        if ($transPackage === null) {
            return;
        }

        if ($this->config->doExclude($transPackage->getName())) {
            $this->io->write('      [!] exclude '.$transPackage->getName());

            return;
        }

        $cacheDir = $this->composer->getConfig()->get('cache-dir');
        $allowedLanguages = $this->config->allowedLanguages();
        $directory = $transPackage->languageDirectory();
        $translations = $transPackage->translations($allowedLanguages);

        foreach ($translations as $translation) {
            $package = $translation['package'];
            $language = $translation['language'];
            $version = $translation['version'];

            $zipFile = $cacheDir.'/'.$transPackage->projectName().'-'.basename($package);

            if (! copy($package, $zipFile)) {
                $this->io->writeError(
                    sprintf(
                        '    - <error>[ERROR]</error> %s %s: Could not download and write "%s"</>',
                        $transPackage->projectName(),
                        $version,
                        $package
                    )
                );
                continue;
            }

            try {
                $this->zipDownloader->extract($zipFile, $directory);
                $this->io->write(
                    sprintf(
                        '    - <info>[OK]</info> Downloaded translation files | plugin %s | version %s | language %s.',
                        $transPackage->projectName(),
                        $version,
                        $language
                    )
                );
            } catch (\Throwable $exception) {
                $this->io->writeError(
                    sprintf(
                        '    - <error>[ERROR]</error> %s %s %s: Could not unzip translation files.</>',
                        $transPackage->projectName(),
                        $version,
                        $language
                    )
                );
                $this->io->writeError($exception->getMessage());
            }

            $this->filesystem->remove($zipFile);
        }
    }
}
