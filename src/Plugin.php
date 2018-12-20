<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
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
     * @var PluginConfiguration
     */
    private $config;

    const PACKAGES = [
        TranslationPackageInterface::TYPE_CORE => CorePackage::class,
        TranslationPackageInterface::TYPE_PLUGIN => PluginPackage::class,
        TranslationPackageInterface::TYPE_THEME => ThemePackage::class,
    ];

    /**
     * Composer plugin activation.
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->config = PluginConfiguration::fromExtra($composer->getPackage()->getExtra());

        if ($this->config->isValid()) {
            $this->ensureDirectories($this->config->directories());
        }

        $this->io->write("WP translation downloader");
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
        /** @var UninstallOperation $operation */
        $operation = $event->getOperation();
        /** @var PackageInterface $package */
        $package = $operation->getPackage();
        $type = $package->getType();
        $name = $package->getName();

        if (! isset(self::PACKAGES[$type])) {
            return;
        }
        if ($this->config->doExclude($name)) {
            return;
        }

        /** @var TranslationPackageInterface $transPackage */
        $class = self::PACKAGES[$type];
        $transPackage = new $class(
            $name,
            $type,
            $package->getPrettyVersion(),
            $this->config->directory($type)
        );

        $this->deleteTranslations($transPackage);
    }

    /**
     * @param PackageEvent $event
     */
    public function onCreate(PackageEvent $event)
    {
        /** @var InstallOperation|UpdateOperation $operation */
        $operation = $event->getOperation();
        /** @var PackageInterface $package */
        $package = ($operation instanceof UpdateOperation)
            ? $operation->getTargetPackage()
            : $operation->getPackage();
        $type = $package->getType();
        $name = $package->getName();

        if (! isset(self::PACKAGES[$type])) {
            return;
        }

        if ($this->config->doExclude($name)) {
            return;
        }

        /** @var TranslationPackageInterface $transPackage */
        $class = self::PACKAGES[$type];
        $transPackage = new $class(
            $name,
            $type,
            $package->getPrettyVersion(),
            $this->config->directory($type)
        );

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
                                "- <info>[OK]</info> deleted %s language files.",
                                $transPackage->name()
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
            $version = $translation['version'];
            $zipFile = sys_get_temp_dir().'/'.$transPackage->name().'-'.basename($package);

            if (! copy($package, $zipFile)) {
                $this->io->writeError(
                    sprintf(
                        '<error>- [ERROR]</error> %s %s: Could not download and write "%s"</>',
                        $transPackage->name(),
                        $version,
                        $package
                    )
                );
                continue;
            }

            $zip = new \ZipArchive;
            $res = $zip->open($zipFile);
            if ($res === true) {
                $zip->extractTo($directory);
                $zip->close();

                $this->io->write(
                    sprintf(
                        '<info>- [OK]</info> Downloaded %s for version %s in %s.',
                        $transPackage->name(),
                        $version,
                        $translation['language']
                    )
                );
            }

            unlink($zipFile);
        }
    }

    private function ensureDirectories(array $dirs)
    {
        foreach ($dirs as $dir) {
            if (! file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
}
