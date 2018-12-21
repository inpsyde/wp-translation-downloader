<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Package\PackageInterface;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Package;

class TranslateablePackageFactory
{

    const PACKAGES = [
        Package\TranslateablePackage::TYPE_CORE => Package\WpCorePackage::class,
        Package\TranslateablePackage::TYPE_PLUGIN => Package\WpPluginPackage::class,
        Package\TranslateablePackage::TYPE_THEME => Package\WpThemePackage::class,
    ];

    /**
     * @param UninstallOperation|UpdateOperation|InstallOperation|OperationInterface $operation
     * @param PluginConfiguration $config
     *
     * @throws \InvalidArgumentException
     *
     * @return null|Package\TranslateablePackage
     */
    public static function create(
        OperationInterface $operation,
        PluginConfiguration $config
    ): ?Package\TranslateablePackage {

        /** @var PackageInterface $package */
        $package = ($operation instanceof UpdateOperation)
            ? $operation->getTargetPackage()
            : $operation->getPackage();

        $type = $package->getType();
        if (! isset(self::PACKAGES[$type])) {
            return null;
        }

        /** @var Package\TranslateablePackage $transPackage */
        $class = self::PACKAGES[$type];

        return new $class($package, $config->directory($type));
    }
}
