<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Package\PackageInterface;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Package;

class TranslatablePackageFactory
{

    const PACKAGES = [
        Package\TranslatablePackage::TYPE_CORE => Package\WpCorePackage::class,
        Package\TranslatablePackage::TYPE_PLUGIN => Package\WpPluginPackage::class,
        Package\TranslatablePackage::TYPE_THEME => Package\WpThemePackage::class,
    ];

    /**
     * @param UninstallOperation|UpdateOperation|InstallOperation|OperationInterface $operation
     * @param PluginConfiguration $config
     *
     * @throws \InvalidArgumentException
     *
     * @return null|Package\TranslatablePackage
     */
    public static function create(
        OperationInterface $operation,
        PluginConfiguration $config
    ): ?Package\TranslatablePackage {

        /** @var PackageInterface $package */
        $package = ($operation instanceof UpdateOperation)
            ? $operation->getTargetPackage()
            : $operation->getPackage();

        $type = $package->getType();
        if (! isset(self::PACKAGES[$type])) {
            return null;
        }

        /** @var Package\TranslatablePackage $transPackage */
        $class = self::PACKAGES[$type];

        return new $class($package, $config->directory($type));
    }
}
