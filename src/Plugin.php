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

use Composer\Command\BaseCommand;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\CompletePackage;
use Composer\Package\PackageInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Util\Filesystem;
use Inpsyde\WpTranslationDownloader\Command\CleanCacheCommand;
use Inpsyde\WpTranslationDownloader\Command\CleanUpCommand;
use Inpsyde\WpTranslationDownloader\Command\DownloadCommand;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Config\PluginConfigurationBuilder;
use Inpsyde\WpTranslationDownloader\Util\ArchiveDownloaderFactory;
use Inpsyde\WpTranslationDownloader\Util\Downloader;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackageFactory;
use Inpsyde\WpTranslationDownloader\Util\Locker;
use Inpsyde\WpTranslationDownloader\Util\Remover;
use Inpsyde\WpTranslationDownloader\Util\TranslationPackageDownloader;

final class Plugin implements
    PluginInterface,
    EventSubscriberInterface,
    Capable,
    CommandProvider
{
    /**
     * @var IOInterface|null
     */
    private $io = null;

    /**
     * @var PluginConfiguration|null
     */
    private $pluginConfig = null;

    /**
     * @var TranslatablePackageFactory|null
     */
    private $translatablePackageFactory = null;

    /**
     * @var Filesystem|null
     */
    private $filesystem = null;

    /**
     * @var Locker|null
     */
    private $locker = null;

    /**
     * @var TranslationPackageDownloader|null
     */
    private $translationsDownloader = null;

    /**
     * Subscribe to Composer events.
     *
     * @return array<string, list<array{string, int}>> The events and callbacks.
     *
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
     */
    public static function getSubscribedEvents()
    {
        // phpcs:enable Inpsyde.CodeQuality.NoAccessors
        // phpcs:enable Inpsyde.CodeQuality.ReturnTypeDeclaration
        return [
            "post-install-cmd" => [
                ['onPostInstallAndUpdate', 0],
            ],
            "post-update-cmd" => [
                ["onPostInstallAndUpdate", 0],
            ],
            'post-package-uninstall' => [
                ["onPackageUninstall", 0],
            ],
        ];
    }

    /**
     * @return array<class-string, class-string>
     *
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors
     */
    public function getCapabilities(): array
    {
        // phpcs:enable Inpsyde.CodeQuality.NoAccessors
        return [CommandProvider::class => __CLASS__];
    }

    /**
     * @return non-empty-list<BaseCommand>
     *
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors
     */
    public function getCommands(): array
    {
        // phpcs:enable Inpsyde.CodeQuality.NoAccessors
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
        $this->filesystem = new Filesystem();

        $rootDir = $this->filesystem->normalizePath(getcwd() ?: '.') . '/';

        $this->locker = new Locker($this->io, $rootDir);

        $resourcedDir = $this->filesystem->normalizePath(dirname(__DIR__) . '/resources');
        $pluginConfigBuilder = new PluginConfigurationBuilder($this->io, $resourcedDir);
        $this->pluginConfig = $pluginConfigBuilder->build($composer->getPackage()->getExtra());

        if ($this->pluginConfig === null) {
            return;
        }

        $this->translatablePackageFactory = new TranslatablePackageFactory($this->pluginConfig);

        $this->translationsDownloader = new TranslationPackageDownloader(
            $composer->getLoop(),
            $composer->getDownloadManager(),
            $this->io,
            $this->filesystem
        );
    }

    /**
     * @param Event $event
     *
     * @event post-update-cmd
     * @event post-install-cmd
     */
    public function onPostInstallAndUpdate(Event $event): void
    {
        if ($this->pluginConfig === null) {
            return;
        }

        $this->assertActivated();

        if (!$this->pluginConfig->autorun()) {
            $this->io->write(
                '<info>Configuration "auto-run" is set to "false". '
                . 'You need to run wp-translation-downloader manually.</info>',
                true,
                IOInterface::VERBOSE
            );

            return;
        }

        $this->doUpdatePackages(...$this->availablePackages($event->getComposer()));
    }

    /**
     * @param PackageInterface ...$packages
     * @return void
     */
    public function doUpdatePackages(PackageInterface ...$packages): void
    {
        if ($this->pluginConfig === null) {
            return;
        }

        $this->assertActivated();

        $this->logo();

        $allowedLanguages = $this->pluginConfig->allowedLanguages();
        if (count($allowedLanguages) < 1) {
            $this->io->write('  Nothing to do: no translation languages defined.');

            return;
        }

        // We keep track of package which are already
        // processed, to skip duplicate entries in $packages.
        $processedPackages = [];

        $downloader = new Downloader(
            $this->io,
            $this->locker,
            $this->translationsDownloader,
            $this->filesystem
        );

        $collector = (object)['downloaded' => 0, 'locked' => 0, 'errors' => 0, 'packages' => 0];

        foreach ($packages as $package) {
            $packageName = $package->getName();
            if (
                isset($processedPackages[$packageName])
                || $this->pluginConfig->shouldExclude($packageName)
            ) {
                continue;
            }

            $translatablePackage = $this->translatablePackageFactory->create($package);
            if ($translatablePackage === null) {
                continue;
            }
            /** @psalm-suppress ArgumentTypeCoercion */
            $downloader->download($translatablePackage, $allowedLanguages, $collector);
            $processedPackages[$packageName] = true;
            $collector->packages++;
        }

        $this->printOverallStats($collector);

        $this->locker->writeLockFile();
    }

    /**
     * @param PackageEvent $event
     *
     * @throws \InvalidArgumentException
     *
     * @event post-package-uninstall
     */
    public function onPackageUninstall(PackageEvent $event): void
    {
        if ($this->pluginConfig === null) {
            return;
        }

        $this->assertActivated();

        $remover = new Remover($this->io, $this->filesystem, $this->locker);

        $operation = $event->getOperation();
        $translatablePackage = $this->translatablePackageFactory->createFromOperation($operation);
        if ($translatablePackage) {
            $remover->remove($translatablePackage);
        }
    }

    /**
     * @return void
     */
    public function doCleanUpDirectories(): void
    {
        if ($this->pluginConfig === null) {
            return;
        }

        $this->assertActivated();

        try {
            $this->logo();
            $this->io->write('Starting to empty the directories...');
            $directory = $this->pluginConfig->languageRootDir();
            $this->filesystem->emptyDirectory($directory);
            $this->io->write(sprintf('  <info>✓</info> %s', $directory));
        } catch (\Throwable $exception) {
            $this->io->write(sprintf('  <fg=red>✗</> %s', $directory ?? 'N/D'));
            $this->io->writeError($exception->getMessage());
        }

        $this->locker->removeLockFile();
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * Returns all available packages defined in composer.json
     * and wp-translation-downloader.virtual-packages configuration.
     *
     * @param Composer $composer
     *
     * @return non-empty-list<PackageInterface>
     */
    public function availablePackages(Composer $composer): array
    {
        /** @var PackageInterface[] $packages */
        $packages = $composer->getRepositoryManager()
            ->getLocalRepository()->getPackages();

        // Add root package.
        $packages[] = $this->packageForRoot($composer);

        // Add virtual packages from wp-translation-downloader config.
        $virtualPackages = $this->pluginConfig
            ? $this->pluginConfig->virtualPackages()
            : [];
        foreach ($virtualPackages as $package) {
            $packages[] = $package;
        }

        return array_values($packages);
    }

    /**
     * @param Composer $composer
     * @return PackageInterface
     */
    private function packageForRoot(Composer $composer): PackageInterface
    {
        $root = $composer->getPackage();
        // Composer < v2.0.2 used "No version set (parsed as 1.0.0)"
        // Composer >= v2.0.2 uses "1.0.0+no-version-set"
        $prettyVersion = strtolower(str_replace(' ', '-', $root->getPrettyVersion()));
        if (strpos($prettyVersion, 'no-version') === 0) {
            // A rare case root package has a real version, go for it
            return $root;
        }

        // Composer created a fake version for root package, we need to remove it, or we're going
        // to have issues passing `?version=1.0.0+no-version-set` to APIs, when the real version
        // is very different from that.
        // For plugins and themes, in theory we could parse main plugin file/style.css to check
        // version... maybe later. For now, we create a new package but with empty version

        $package = new CompletePackage($root->getName(), '', '');
        $package->setType($root->getType());
        $package->setDescription($root->getDescription());
        $package->setRequires($root->getRequires());
        $package->setDevRequires($root->getDevRequires());
        $package->setExtra($root->getExtra());

        return $package;
    }

    /**
     * @param \stdClass $collector
     * @return void
     */
    private function printOverallStats(\stdClass $collector): void
    {
        $this->assertActivated();
        $lines = $this->io->isVerbose() ? [] : [''];
        $lines[] = '  <options=bold>Overall stats</>: ';
        $lines[] = sprintf(
            "   - %d package%s processed\n" .
            "   - %d translation%s downloaded\n" .
            "   - %d translation%s locked\n" .
            "   - %d translation%s failed",
            (int)$collector->packages,
            $collector->packages === 1 ? '' : 's',
            (int)$collector->downloaded,
            $collector->downloaded === 1 ? '' : 's',
            (int)$collector->locked,
            $collector->locked === 1 ? '' : 's',
            (int)$collector->errors,
            $collector->errors === 1 ? '' : 's'
        );

        $this->io->write($lines);
    }

    /**
     * @return void
     */
    private function logo(): void
    {
        $this->assertActivated();

        $logo = 'Inpsyde';
        $catchline = 'WP Translation Downloader';
        $length = strlen($catchline);
        $padding = ($length - strlen($logo)) / 2;
        $paddingLeft = str_repeat(' ', (int)floor($padding));
        $paddingRight = str_repeat(' ', (int)ceil($padding));

        $lines = [
            '',
            "  <fg=green>{$paddingLeft}{$logo}{$paddingRight}</>  ",
            "  <fg=magenta>{$catchline}</>  ",
            '',
        ];

        $this->io->write($lines);
    }

    /**
     * @return void
     *
     * @psalm-assert IOInterface $this->io
     * @psalm-assert TranslatablePackageFactory $this->translatablePackageFactory
     * @psalm-assert Filesystem $this->filesystem
     * @psalm-assert Locker $this->locker
     * @psalm-assert TranslationPackageDownloader $this->translationsDownloader
     */
    private function assertActivated(): void
    {
        assert($this->io instanceof IOInterface);
        assert($this->translatablePackageFactory instanceof TranslatablePackageFactory);
        assert($this->filesystem instanceof Filesystem);
        assert($this->locker instanceof Locker);
        assert($this->translationsDownloader instanceof TranslationPackageDownloader);
    }
}
