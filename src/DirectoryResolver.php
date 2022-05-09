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

use Composer\Package\PackageInterface;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;

class DirectoryResolver
{
    /**
     * @var PluginConfiguration
     */
    protected $pluginConfiguration;

    /**
     * ApiEndpointResolver constructor.
     *
     * @param PluginConfiguration $pluginConfiguration
     */
    public function __construct(PluginConfiguration $pluginConfiguration)
    {
        $this->pluginConfiguration = $pluginConfiguration;
    }

    public function resolve(PackageInterface $package): ?string
    {
        $packageName = $package->getName();
        $packageType = $package->getType();

        $directory = false;
        // resolve directory by "name".
        $byName = $this->pluginConfiguration->directoryBy(PluginConfiguration::BY_NAME);
        foreach ($byName as $name => $dir) {
            $pattern = '/' . $this->pluginConfiguration->prepareRegex($name) . '/';
            if (preg_match($pattern, $packageName) === 1) {
                $directory = $dir;
                break;
            };
        }

        // resolve directory by "type"
        $byType = $this->pluginConfiguration->directoryBy(PluginConfiguration::BY_TYPE);
        $directory = $directory ?? $byType[$packageType] ?? false;
        // In case by "name" or "type" is "false" or not set we stop here.
        if ($directory === false) {
            return null;
        }

        $directory = trim($directory, DIRECTORY_SEPARATOR);
        $resolvedDir = $this->pluginConfiguration->languageRootDir();
        if ($directory !== '') {
            $resolvedDir .= $directory . DIRECTORY_SEPARATOR;
        }

        [$vendorName, $projectName] = PackageNameResolver::resolve($package->getName());
        $replacements = [
            '%vendorName%' => $vendorName,
            '%projectName%' => $projectName,
            '%packageName%' => $packageName,
            '%packageType%' => $packageType,
            '%packageVersion%' => $package->getPrettyVersion(),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $resolvedDir
        );
    }
}
