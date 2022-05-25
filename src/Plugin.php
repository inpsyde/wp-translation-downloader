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

namespace Inpsyde\WpTranslationDownloader;

use Composer\Cache;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Util\Filesystem;
use Composer\Util\RemoteFilesystem;
use Inpsyde\WpTranslationDownloader\Command\CleanCacheCommand;
use Inpsyde\WpTranslationDownloader\Command\CleanUpCommand;
use Inpsyde\WpTranslationDownloader\Command\DownloadCommand;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Config\PluginConfigurationBuilder;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackageInterface;
use Inpsyde\WpTranslationDownloader\Util\Downloader;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackageFactory;
use Inpsyde\WpTranslationDownloader\Util\Locker;
use Inpsyde\WpTranslationDownloader\Util\Remover;
use Inpsyde\WpTranslationDownloader\Util\Unzipper;

final class Plugin implements
    PluginInterface,
    EventSubscriberInterface,
    Capable,
    CommandProvider
{
    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var Downloader
     */
    private $downloader;

    /**
     * @var Remover
     */
    private $remover;

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
     * @var Locker
     */
    private $locker;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var bool
     */
    private $booted = false;

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
            new CleanCacheCommand(),
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
        $this->io = $io;
        $config = $composer->getConfig();

        $this->cache = new Cache($this->io, $config->get('cache-dir') . '/translations');
        if (!$this->cache->isEnabled()) {
            $this->io->error("Composer Cache folder is not enabled.");

            return false;
        }

        $this->filesystem = new Filesystem();

        $rootDir = getcwd() . '/';

        /** @var Locker $locker */
        $this->locker = new Locker($this->io, $rootDir);

        $pluginConfigBuilder = new PluginConfigurationBuilder($this->io);
        /** @var PluginConfiguration|null pluginConfig */
        $this->pluginConfig = $pluginConfigBuilder->build($composer->getPackage()->getExtra());

        if ($this->pluginConfig === null) {
            return;
        }

        $this->translatablePackageFactory = new TranslatablePackageFactory($this->pluginConfig);

        $this->downloader = new Downloader(
            $this->io,
            new Unzipper($this->io),
            new RemoteFilesystem($this->io, $config),
            $this->locker,
            $this->cache->getRoot()
        );
        $this->remover = new Remover(
            $this->io,
            $this->filesystem,
            $this->locker
        );

        if ($this->cache->gcIsNecessary()) {
            $this->cache->gc($config->get('cache-files-ttl'), $config->get('cache-files-maxsize'));
        }

        $this->ensureDirectoryExists($this->pluginConfig->languageRootDir());
    }

    /**
     * @param Event $event
     *
     * @event post-update-cmd
     * @event post-install-cmd
     */
    public function onPostInstallAndUpdate(Event $event)
    {
        if ($this->pluginConfig === null) {
            return;
        }

        if (!$this->pluginConfig->autorun()) {
            // phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
            $this->io->write(
                '<info>Configuration "auto-run" is set to "false". You need to run wp-translation-downloader manually.</info>'
            );

            return;
        }

        $packages = $this->availablePackages($event->getComposer());

        $this->doUpdatePackages($packages);
    }

    /**
     * @param PackageInterface[] $packages
     */
    public function doUpdatePackages(array $packages)
    {
        if ($this->pluginConfig === null) {
            return;
        }

        $this->logo();

        $allowedLanguages = $this->pluginConfig->allowedLanguages();
        // We keep track of package which are already
        // processed, to skip duplicate entries in $packages.
        $processedPackages = [];
        // We keep track of folders which are already
        // created, to skip duplicated is_dir() calls.
        $processedFolders = [];

        /** @var PackageInterface $package */
        foreach ($packages as $package) {
            $packageName = $package->getName();
            if ($this->pluginConfig->doExclude($packageName)) {
                continue;
            }

            $transPackage = isset($processedPackages[$packageName])
                ? null
                : $this->translatablePackageFactory->create($package);
            if ($transPackage === null) {
                continue;
            }

            $languageDir = $transPackage->languageDirectory();
            if (!isset($processedFolders[$languageDir])) {
                $this->ensureDirectoryExists($languageDir);
                $processedFolders[$languageDir] = true;
            }

            $this->downloader->download($transPackage, $allowedLanguages);
            $processedPackages[$packageName] = true;
        }

        $this->locker->writeLockFile();
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
        if ($this->pluginConfig === null) {
            return;
        }

        /** @var PackageInterface|TranslatablePackageInterface|null $transPackage */
        $transPackage = $this->translatablePackageFactory->createFromOperation($event->getOperation());
        if ($transPackage) {
            $this->remover->remove($transPackage);
        }
    }

    /**
     * @return void
     */
    public function doCleanUpDirectories()
    {
        try {
            if ($this->pluginConfig === null) {
                return;
            }

            $this->logo();
            $this->io->write('Starting to empty the directories...');
            $directory = $this->pluginConfig->languageRootDir();
            $this->filesystem->emptyDirectory($directory);
            $this->io->write(sprintf('  <info>✓</info> %s', $directory));
        } catch (\Throwable $exception) {
            $this->io->write(sprintf('  <fg=red>✗</> %s', $directory));
            $this->io->writeError($exception->getMessage());
        }

        $this->locker->removeLockFile();
    }

    /**
     * @return void
     */
    public function doCleanCache()
    {
        try {
            if ($this->pluginConfig === null) {
                return;
            }
            $this->logo();
            $this->io->write('Starting to clean cache directory.');
            $this->cache->clear()
                ? $this->io->write('<info>Cache folder was emptied successfully.</info>')
                : $this->io->writeError('Could not empty cache dir.');
        } catch (\Throwable $exception) {
            $this->io->writeError($exception->getMessage());
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

    private function ensureDirectoryExists(string $dir): bool
    {
        try {
            $this->filesystem->ensureDirectoryExists($dir);

            return true;
        } catch (\Throwable $exception) {
            $this->io->error($exception->getMessage());

            return false;
        }
    }

    /**
     * Returns all available packages defined in composer.json
     * and wp-translation-downloader.virtual-packages configuration.
     *
     * @param Composer $composer
     *
     * @return PackageInterface[]
     */
    public function availablePackages(Composer $composer): array
    {
        /** @var PackageInterface[] $packages */
        $packages = $composer->getRepositoryManager()
            ->getLocalRepository()->getPackages();

        // Add root package.
        $packages[] = $composer->getPackage();

        // Add virtual packages from wp-translation-downloader config.
        foreach ($this->pluginConfig->virtualPackages() as $package) {
            $packages[] = $package;
        }

        return $packages;
    }

    /**
     * @return void
     */
    public function logo(): void
    {
        // phpcs:disable
        $logo = <<<LOGO
    <fg=white;bg=green>                        </>
    <fg=white;bg=green>        Inpsyde         </>
    <fg=white;bg=green>                        </>
    <fg=magenta>WP Translation Downloader</>
LOGO;
        // phpcs:enable

        $this->io->write("\n{$logo}\n");
    }
}
