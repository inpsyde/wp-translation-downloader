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

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Package\PackageInterface;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Package;

class TranslatablePackageFactory
{
    /**
     * @var PluginConfiguration
     */
    protected $pluginConfiguration;

    /**
     * @var ApiEndpointResolver
     */
    protected $apiEndpointResolver;

    /**
     * @var DirectoryResolver
     */
    protected $directoryResolver;

    /**
     * TranslatablePackageFactory constructor.
     *
     * @param PluginConfiguration $pluginConfiguration
     * @param ApiEndpointResolver $apiEndpointResolver
     * @param DirectoryResolver $directoryResolver
     */
    public function __construct(
        PluginConfiguration $pluginConfiguration,
        ApiEndpointResolver $apiEndpointResolver,
        DirectoryResolver $directoryResolver
    ) {

        $this->pluginConfiguration = $pluginConfiguration;
        $this->apiEndpointResolver = $apiEndpointResolver;
        $this->directoryResolver = $directoryResolver;
    }

    /**
     * @param UninstallOperation|UpdateOperation|InstallOperation|OperationInterface $operation
     *
     * @return null|Package\TranslatablePackage
     * @throws \InvalidArgumentException
     */
    public function createFromOperation(OperationInterface $operation): ?Package\TranslatablePackage
    {
        /** @var PackageInterface $package */
        $package = ($operation instanceof UpdateOperation)
            ? $operation->getTargetPackage()
            : $operation->getPackage();

        return $this->create($package);
    }

    /**
     * @param PackageInterface $package
     *
     * @return Package\TranslatablePackage|null
     */
    public function create(PackageInterface $package): ?Package\TranslatablePackage
    {
        $directory = $this->directoryResolver->resolve($package);
        if (!$directory) {
            return null;
        }

        $endpoint = $this->apiEndpointResolver->resolve($package);
        if ($endpoint === null) {
            return null;
        }

        return new Package\TranslatablePackage($package, $directory, $endpoint);
    }
}
