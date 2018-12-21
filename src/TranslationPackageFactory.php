<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Package\PackageInterface;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Package;

class TranslationPackageFactory
{

    const PACKAGES = [
        Package\TranslationPackageInterface::TYPE_CORE => Package\CorePackage::class,
        Package\TranslationPackageInterface::TYPE_PLUGIN => Package\PluginPackage::class,
        Package\TranslationPackageInterface::TYPE_THEME => Package\ThemePackage::class,
    ];

    /**
     * @param UninstallOperation|UpdateOperation|InstallOperation|OperationInterface $operation
     * @param PluginConfiguration $config
     *
     * @throws \InvalidArgumentException
     *
     * @return null|Package\TranslationPackageInterface
     */
    public static function create(
        OperationInterface $operation,
        PluginConfiguration $config
    ): ?Package\TranslationPackageInterface {

        /** @var PackageInterface $package */
        $package = ($operation instanceof UpdateOperation)
            ? $operation->getTargetPackage()
            : $operation->getPackage();

        $type = $package->getType();
        $name = $package->getName();

        if (! isset(self::PACKAGES[$type])) {
            return null;
        }

        /** @var Package\TranslationPackageInterface $transPackage */
        $class = self::PACKAGES[$type];

        return new $class(
            $name,
            $type,
            $package->getPrettyVersion(),
            $config->directory($type)
        );
    }
}
