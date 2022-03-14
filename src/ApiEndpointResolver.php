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

class ApiEndpointResolver
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

        $endpointsByName = $this->pluginConfiguration->apiBy(PluginConfiguration::API_BY_NAME);
        $endpointsByType = $this->pluginConfiguration->apiBy(PluginConfiguration::API_BY_TYPE);

        $apiEndpoint = null;
        foreach ($endpointsByName as $apiPackage => $endpoint) {
            $pattern = '/' . $this->pluginConfiguration->prepareRegex($apiPackage) . '/';
            if (preg_match($pattern, $packageName) === 1) {
                $apiEndpoint = $endpoint;
                break;
            };
        }

        $apiEndpoint = $apiEndpoint ?? $endpointsByType[$packageType] ?? null;
        if ($apiEndpoint === null) {
            return null;
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
            $apiEndpoint
        );
    }
}
