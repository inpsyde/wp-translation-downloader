<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader;

use Composer\Cache;
use Composer\Composer;
use Composer\Downloader\ZipDownloader;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Util\Filesystem;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Config\PluginConfigurationBuilder;
use Inpsyde\WpTranslationDownloader\Downloader\TranslationDownloader;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackage;

final class Plugin implements PluginInterface, EventSubscriberInterface
{

    /**
     * @var IOInterface
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
     * @param Composer $composer
     * @param IOInterface $io
     *
     * @throws \RuntimeException
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;

        // initialize PluginConfiguration
        $extra = $composer->getPackage()->getExtra();
        $configBuilder = new PluginConfigurationBuilder($this->io);
        $this->pluginConfig = $configBuilder->build($extra);

        // initialize TranslatablePackageFactory
        $this->translatablePackageFactory = new TranslatablePackageFactory(
            $this->pluginConfig,
            new ApiEndpointResolver($this->pluginConfig)
        );

        // initialize TranslationDownloader
        $filesystem = new Filesystem();
        $this->translationDownloader = new TranslationDownloader(
            $io,
            $composer->getConfig(),
            new ZipDownloader($io, $composer->getConfig()),
            $filesystem,
            new Cache($this->io, $composer->getConfig()->get('cache-dir').'/translations')
        );

        $error = $this->pluginConfig->isValid();
        if ($error !== '') {
            $this->io->writeError($error);

            return;
        }

        foreach ($this->pluginConfig->directories() as $directory) {
            $filesystem->ensureDirectoryExists($directory);
        }
    }

    /**
     * @param Event $event
     *
     * @event post-update-cmd
     * @event post-install-cmd
     */
    public function onPostInstallAndUpdate(Event $event)
    {
        $packages = $event->getComposer()->getRepositoryManager()
            ->getLocalRepository()->getPackages();
        $allowedLanguages = $this->pluginConfig->allowedLanguages();

        $this->logo();

        foreach ($packages as $package) {
            $transPackage = $this->translatablePackageFactory->create($package);
            if ($transPackage === null) {
                continue;
            }
            $this->translationDownloader->download($transPackage, $allowedLanguages);
        }
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
        /** @var PackageInterface|TranslatablePackage $transPackage */
        $transPackage = $this->translatablePackageFactory->createFromOperation($event->getOperation());

        if ($transPackage === null) {
            return;
        }

        $allowedLanguages = $this->pluginConfig->allowedLanguages();
        $this->translationDownloader->remove($transPackage, $allowedLanguages);
    }

    /**
     * @param PackageEvent $event
     *
     * @throws \InvalidArgumentException
     *
     * @event post-package-install
     * @event post-package-update
     *
     * @deprecated this action will be removed in future.
     */
    public function onPackageUpdate(PackageEvent $event)
    {
        /** @var PackageInterface|TranslatablePackage $transPackage */

        $transPackage = $this->translatablePackageFactory->createFromOperation($event->getOperation());

        if ($transPackage === null) {
            return;
        }

        if ($this->pluginConfig->doExclude($transPackage->getName())) {
            $this->io->write('      [!] exclude '.$transPackage->getName());

            return;
        }

        $allowedLanguages = $this->pluginConfig->allowedLanguages();
        $this->translationDownloader->download($transPackage, $allowedLanguages);
    }

    /**
     * @return void
     */
    protected function logo(): void
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
