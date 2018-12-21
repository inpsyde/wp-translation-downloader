<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader;

use Composer\Composer;
use Composer\Downloader\ZipDownloader;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Util\Filesystem;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Package\CorePackage;
use Inpsyde\WpTranslationDownloader\Package\PluginPackage;
use Inpsyde\WpTranslationDownloader\Package\ThemePackage;
use Inpsyde\WpTranslationDownloader\Package\TranslationPackageInterface;

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

    const PACKAGES = [
        TranslationPackageInterface::TYPE_CORE => CorePackage::class,
        TranslationPackageInterface::TYPE_PLUGIN => PluginPackage::class,
        TranslationPackageInterface::TYPE_THEME => ThemePackage::class,
    ];

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @param Filesystem|null $filesystem
     *
     * @throws \RuntimeException
     */
    public function activate(Composer $composer, IOInterface $io, Filesystem $filesystem = null)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->config = PluginConfiguration::fromExtra($composer->getPackage()->getExtra());
        $this->filesystem = $filesystem ?? new Filesystem();
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

    public function onUninstall(PackageEvent $event)
    {
        $transPackage = TranslationPackageFactory::create($event->getOperation(), $this->config);
        if ($transPackage === null) {
            return;
        }

        if (! $transPackage->hasTranslations($this->config->allowedLanguages())) {
            return;
        }

        $this->deleteTranslations($transPackage);
    }

    public function onUpdate(PackageEvent $event)
    {
        $transPackage = TranslationPackageFactory::create($event->getOperation(), $this->config);
        if ($transPackage === null) {
            return;
        }

        if ($this->config->doExclude($transPackage->name())) {
            return;
        }

        if (! $transPackage->hasTranslations($this->config->allowedLanguages())) {
            return;
        }

        $this->downloadTranslations($transPackage);
    }

    private function deleteTranslations(TranslationPackageInterface $transPackage)
    {
        $allowedLanguages = $this->config->allowedLanguages();
        $directory = $transPackage->directory();

        $translations = $transPackage->translations($allowedLanguages);
        foreach ($translations as $translation) {
            $language = $translation['language'];
            $files = [
                $directory.$transPackage->name().'-'.$language.'.mo',
                $directory.$transPackage->name().'-'.$language.'.po',
            ];
            foreach ($files as $file) {
                if (file_exists($file)) {
                    if (unlink($file)) {
                        $this->io->write(
                            sprintf(
                                "    - <info>[OK]</info> %s: deleted %s translation file.",
                                $transPackage->name(),
                                basename($file)
                            )
                        );
                    };
                }
            }
        }
    }

    private function downloadTranslations(TranslationPackageInterface $transPackage)
    {
        $allowedLanguages = $this->config->allowedLanguages();
        $directory = $transPackage->directory();

        $translations = $transPackage->translations($allowedLanguages);
        foreach ($translations as $translation) {
            $package = $translation['package'];
            $language = $translation['language'];
            $version = $translation['version'];
            $zipFile = sys_get_temp_dir().'/'.$transPackage->name().'-'.basename($package);

            if (! copy($package, $zipFile)) {
                $this->io->writeError(
                    sprintf(
                        '    - <error>[ERROR]</error> %s %s: Could not download and write "%s"</>',
                        $transPackage->name(),
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
                        $transPackage->name(),
                        $version,
                        $language
                    )
                );
            } catch (\Throwable $exception) {
                $this->io->writeError(
                    sprintf(
                        '    - <error>[ERROR]</error> %s %s %s: Could not unzip translation files.</>',
                        $transPackage->name(),
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
