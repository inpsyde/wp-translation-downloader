<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader;

use Composer\Cache;
use Composer\Composer;
use Composer\Downloader\ZipDownloader;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Util\Filesystem;
use Inpsyde\WpTranslationDownloader\Command\CleanUpCommand;
use Inpsyde\WpTranslationDownloader\Command\DownloadCommand;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Config\PluginConfigurationBuilder;
use Inpsyde\WpTranslationDownloader\Downloader\TranslationDownloader;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackage;

final class Plugin implements
    PluginInterface,
    EventSubscriberInterface,
    Capable,
    CommandProvider
{

    /**
     * @var Io
     */
    private $io;

    /**
     * @var TranslationDownloader
     */
    private $translationDownloader;

    /**
     * @var PluginConfiguration
     */
    private $pluginConfig;

    /**
     * @var TranslatablePackageFactory
     */
    private $translatablePackageFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Subscribe to Composer events.
     *
     * @return array The events and callbacks.
     *
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoGetter
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     */
    public static function getSubscribedEvents()
    {
        return [
            "post-install-cmd" => [
                ['onPostInstallAndUpdate', 0],
            ],
            "post-update-cmd" => [
                ['onPostInstallAndUpdate', 0],
            ],
            'post-package-uninstall' => [
                ['onPackageUninstall', 0],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getCapabilities(): array
    {
        return [CommandProvider::class => __CLASS__];
    }

    /**
     * @return array
     */
    public function getCommands(): array
    {
        return [
            new DownloadCommand(),
            new CleanUpCommand(),
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
        $this->io = new Io($io);

        /** @var Cache $cache */
        $cache = new Cache($io, $composer->getConfig()->get('cache-dir').'/translations');
        /** @var ZipDownloader $zipDownloader */
        $zipDownloader = new ZipDownloader($io, $composer->getConfig());

        /** @var Filesystem $filesystem */
        $this->filesystem = new Filesystem();

        // initialize PluginConfiguration
        $this->pluginConfig = PluginConfigurationBuilder::build($composer->getPackage()->getExtra());

        // initialize TranslatablePackageFactory
        $this->translatablePackageFactory = new TranslatablePackageFactory(
            $this->pluginConfig,
            new ApiEndpointResolver($this->pluginConfig)
        );

        // initialize TranslationDownloader
        $this->translationDownloader = new TranslationDownloader(
            $this->io,
            $composer->getConfig(),
            $zipDownloader,
            $this->filesystem,
            $cache
        );

        $error = $this->pluginConfig->isValid();
        if ($error !== '') {
            $this->io->error($error);

            return;
        }

        $this->ensureDirectories();
    }

    /**
     * @param Event $event
     *
     * @event post-update-cmd
     * @event post-install-cmd
     */
    public function onPostInstallAndUpdate(Event $event)
    {
        if (! $this->pluginConfig->autorun()) {
            return;
        }

        $packages = $event->getComposer()->getRepositoryManager()
            ->getLocalRepository()->getPackages();

        $this->doUpdatePackages($packages);
    }

    /**
     * @param PackageEvent $event
     *
     * @throws \InvalidArgumentException
     *
     * @event post-package-uninstall
     */
    public function onPackageUninstall(PackageEvent $event)
    {
        /** @var PackageInterface|TranslatablePackage|null $transPackage */
        $transPackage = $this->translatablePackageFactory->createFromOperation($event->getOperation());
        if ($transPackage) {
            $this->translationDownloader->remove($transPackage);
        }
    }

    /**
     * @param PackageInterface[] $packages
     */
    public function doUpdatePackages(array $packages)
    {
        $this->io->logo();

        if (count($packages) < 1) {
            $this->io->error('No packages found to process.');
        }

        $allowedLanguages = $this->pluginConfig->allowedLanguages();

        foreach ($packages as $package) {
            $transPackage = $this->translatablePackageFactory->create($package);
            if ($transPackage === null) {
                continue;
            }
            $this->translationDownloader->download($transPackage, $allowedLanguages);
        }
    }

    /**
     * @return void
     */
    public function doCleanUpDirectories()
    {
        $this->io->logo();
        $this->io->write('Starting to empty the directories...');
        foreach ($this->pluginConfig->directories() as $directory) {
            try {
                $this->filesystem->emptyDirectory($directory);
                $this->io->write(sprintf('  <info>✓</info> %s', $directory));
            } catch (\Throwable $exception) {
                $this->io->write(sprintf('  <fg=red>✗</> %s', $directory));
                $this->io->error($exception->getMessage());
            }
        }
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    /**
     * @throws \RuntimeException
     */
    private function ensureDirectories()
    {
        foreach ($this->pluginConfig->directories() as $directory) {
            $this->filesystem->ensureDirectoryExists($directory);
        }
    }
}
