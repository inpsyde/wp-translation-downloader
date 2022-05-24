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

namespace Inpsyde\WpTranslationDownloader\Package;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Package\PackageInterface;
use Inpsyde\WpTranslationDownloader\PackageNameResolver;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;

class TranslatablePackageFactory
{
    /**
     * @var PluginConfiguration
     */
    protected $pluginConfiguration;

    /**
     * TranslatablePackageFactory constructor.
     *
     * @param PluginConfiguration $pluginConfiguration
     */
    public function __construct(PluginConfiguration $pluginConfiguration)
    {
        $this->pluginConfiguration = $pluginConfiguration;
    }

    /**
     * @param UninstallOperation|UpdateOperation|InstallOperation|OperationInterface $operation
     *
     * @return null|TranslatablePackageInterface
     * @throws \InvalidArgumentException
     */
    public function createFromOperation(OperationInterface $operation): ?TranslatablePackageInterface
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
     * @return TranslatablePackageInterface|null
     */
    public function create(PackageInterface $package): ?TranslatablePackageInterface
    {
        $directory = $this->resolveDirectory($package);
        if (!$directory) {
            return null;
        }

        $endpoint = $this->resolveEndpoint($package);
        if ($endpoint === null) {
            return null;
        }

        return new TranslatablePackage($package, $directory, $endpoint);
    }

    /**
     * Resolves for a given Package the api endpoint to download translations.
     *
     * @param PackageInterface $package
     *
     * @return string|null
     */
    public function resolveEndpoint(PackageInterface $package): ?string
    {
        $packageName = $package->getName();
        $packageType = $package->getType();

        $byName = $this->pluginConfiguration->apiBy(PluginConfiguration::BY_NAME);
        $byType = $this->pluginConfiguration->apiBy(PluginConfiguration::BY_TYPE);

        $endpoint = $this->findByName($packageName, $byName) ?? $this->findByType($packageType, $byType);

        if ($endpoint === null) {
            return null;
        }

        $endpoint = $this->replacePlaceholders($endpoint, $package);

        return $this->removeEmptyQueryParams($endpoint);
    }

    /**
     * Resolves for a given Package the directory.
     *
     * @param PackageInterface $package
     *
     * @return string|null
     */
    public function resolveDirectory(PackageInterface $package): ?string
    {
        $packageName = $package->getName();
        $packageType = $package->getType();

        $byName = $this->pluginConfiguration->directoryBy(PluginConfiguration::BY_NAME);
        $byType = $this->pluginConfiguration->directoryBy(PluginConfiguration::BY_TYPE);

        $directory = $this->findByName($packageName, $byName) ?? $this->findByType($packageType, $byType);

        if ($directory === null) {
            return null;
        }

        $directory = trim($directory, "\\/");
        $resolvedDir = $this->pluginConfiguration->languageRootDir();

        if ($directory !== '') {
            $resolvedDir .= $directory . DIRECTORY_SEPARATOR;
        }

        return $this->replacePlaceholders($resolvedDir, $package, true);
    }

    /**
     * Removes empty query params from a given URL. This is necessary since WP will
     * fail when sending ?version= to GlotPress API:
     *
     * ✗   https://api.wordpress.org/translations/core/1.0/?version=
     * ✓   https://api.wordpress.org/translations/core/1.0/?version=5.9
     *
     * @param string $url
     *
     * @return string
     */
    protected function removeEmptyQueryParams(string $url): string
    {
        $parsedUrl = parse_url($url);
        $query = $parsedUrl['query'] ?? '';
        if ($query === '') {
            return $url;
        }

        parse_str($query, $parameters);
        $cleanedParams = array_filter($parameters);

        $base = strtok($url, '?');

        if (count($cleanedParams) > 0) {
            $base .= '?' . http_build_query($cleanedParams);
        }

        return $base;
    }

    /**
     * @param string $input
     * @param PackageInterface $package
     * @param bool $allowDevVersion If set to true it will replace %packageVersion% with "dev-*".
     *                              For api endpoints this is set to false.
     *                              It causes problems on https://api.wordpress.org/translations/
     *
     * @return string
     */
    protected function replacePlaceholders(
        string $input,
        PackageInterface $package,
        bool $allowDevVersion = false
    ): string {

        [$vendorName, $projectName] = PackageNameResolver::resolve($package->getName());

        $version = $package->getPrettyVersion();
        if (!$allowDevVersion && strpos($version, "dev-") === 0) {
            $version = '';
        }

        $replacements = [
            '%vendorName%' => $vendorName,
            '%projectName%' => $projectName,
            '%packageName%' => $package->getName(),
            '%packageType%' => $package->getType(),
            '%packageVersion%' => $version,
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $input
        );
    }

    protected function findByName(string $packageName, array $byName): ?string
    {
        $directory = null;
        foreach ($byName as $name => $dir) {
            $pattern = '/' . $this->pluginConfiguration->prepareRegex($name) . '/';
            if (preg_match($pattern, $packageName) === 1) {
                $directory = $dir;
                break;
            };
        }

        // phpcs:disable Squiz.PHP.CommentedOutCode.Found
        // In case, someone set ["name" => ["inpsyde/google-tag-manager" => false]]
        if ($directory === false) {
            $directory = null;
        }

        return $directory;
    }

    protected function findByType(string $packageType, array $byType): ?string
    {
        $directory = $byType[$packageType] ?? null;

        // phpcs:disable Squiz.PHP.CommentedOutCode.Found
        // In case, someone set ["type" => ["wordpress-plugin" => false]]
        if ($directory === false) {
            $directory = null;
        }

        return $directory;
    }
}
