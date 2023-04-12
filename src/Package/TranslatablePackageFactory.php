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
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Package\PackageInterface;
use Composer\Util\HttpDownloader;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Util\FnMatcher;

class TranslatablePackageFactory
{
    use NameResolverTrait;

    /**
     * @var PluginConfiguration
     */
    protected $pluginConfiguration;

    /**
     * @var HttpDownloader
     */
    protected $downloader;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @param PluginConfiguration $pluginConfiguration
     */
    public function __construct(
        PluginConfiguration $pluginConfiguration,
        HttpDownloader $downloader,
        IOInterface $io
    ) {

        $this->pluginConfiguration = $pluginConfiguration;
        $this->downloader = $downloader;
        $this->io = $io;
    }

    /**
     * @param UninstallOperation|UpdateOperation|InstallOperation|OperationInterface $operation
     *
     * @return null|TranslatablePackageInterface
     * @throws \InvalidArgumentException
     */
    public function createFromOperation(
        OperationInterface $operation
    ): ?TranslatablePackageInterface {

        /**
         * @var PackageInterface $package
         * @psalm-suppress PossiblyUndefinedMethod
         */
        $package = ($operation instanceof UpdateOperation)
            ? $operation->getTargetPackage()
            : $operation->getPackage();

        return $this->create($package);
    }

    /**
     * @param PackageInterface $package
     * @return TranslatablePackageInterface|null
     */
    public function create(PackageInterface $package): ?TranslatablePackageInterface
    {
        try {
            $directory = $this->resolveDirectory($package);
            if (! $directory) {
                return null;
            }

            $endpointData = $this->resolveEndpoint($package);
            if ($endpointData === null) {
                return null;
            }

            [$endpoint, $endpointType] = $endpointData;

            $jsonFile = new JsonFile($endpoint, $this->downloader, $this->io);
            $translations = $jsonFile->read();
            if (! $translations) {
                return null;
            }

            return new TranslatablePackage(
                $package,
                $directory,
                $endpoint,
                $endpointType,
                (array) ($translations['translations'] ?? [])
            );
        } catch (\Throwable $exception) {
            $this->io->error($exception->getMessage());

            return null;
        }
    }

    /**
     * Resolves for a given Package the api endpoint to download translations.
     *
     * @param PackageInterface $package
     * @return array{string, string|null}|null
     */
    public function resolveEndpoint(PackageInterface $package): ?array
    {
        $byName = $this->pluginConfiguration->endpointsByName();
        $byType = $this->pluginConfiguration->endpointsByType();

        $endpointData = $this->findByName($package->getName(), $byName)
            ?? $this->findByType($package->getType(), $byType);

        if ($endpointData === null) {
            return null;
        }

        $endpointUrl = $this->replacePlaceholders($endpointData['url'], $package);
        $endpointUrl = $this->removeEmptyQueryParams($endpointUrl);

        return [$endpointUrl, $endpointData['type']];
    }

    /**
     * Resolves for a given Package the directory.
     *
     * @param PackageInterface $package
     * @return string|null
     */
    public function resolveDirectory(PackageInterface $package): ?string
    {
        $byName = $this->pluginConfiguration->directoriesByName();
        $byType = $this->pluginConfiguration->directoriesByType();

        $directoryData = $this->findByName($package->getName(), $byName)
            ?? $this->findByType($package->getType(), $byType);

        if ($directoryData === null) {
            return null;
        }

        $directory = trim($directoryData['url'], "\\/");
        $resolvedDir = $this->pluginConfiguration->languageRootDir();

        if ($directory !== '') {
            $resolvedDir .= "{$directory}/";
        }

        return $this->replacePlaceholders($resolvedDir, $package, true);
    }

    /**
     * Removes empty query params from a given URL. This is necessary since WP will
     * fail when sending ?version= to Glotpress API:
     *
     * ✗   https://api.wordpress.org/translations/core/1.0/?version=
     * ✓   https://api.wordpress.org/translations/core/1.0/?version=5.9
     *
     * @param string $url
     * @return string
     */
    protected function removeEmptyQueryParams(string $url): string
    {
        $urlSplit = explode('?', $url);
        $query = $urlSplit[1] ?? '';

        if ($query === '') {
            return $url;
        }

        parse_str($query, $parameters);
        $cleanedParams = array_filter($parameters);

        $base = $urlSplit[0];
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
     * @return string
     */
    protected function replacePlaceholders(
        string $input,
        PackageInterface $package,
        bool $allowDevVersion = false
    ): string {

        [$vendorName, $projectName] = $this->resolveName($package->getName());

        $version = $package->getPrettyVersion();
        if (!$allowDevVersion && strpos($version, 'dev-') === 0) {
            $version = '';
        }

        $replacements = [
            '%vendorName%' => $vendorName,
            '%projectName%' => $projectName,
            '%packageName%' => $package->getName(),
            '%packageType%' => $package->getType(),
            '%packageVersion%' => $version,
            '%packageDistReference%' => $package->getDistReference(),
            '%packageDistSha1Checksum%' => $package->getDistSha1Checksum(),
            '%packageSourceReference%' => $package->getSourceReference(),
            '%packageUniqueName%' => $package->getUniqueName(),
        ];

        return strtr($input, $replacements);
    }

    /**
     * @param string $packageName
     * @param array<string, mixed> $byName
     * @return array{url:string, type: string|null}|null
     */
    protected function findByName(string $packageName, array $byName): ?array
    {
        foreach ($byName as $name => $value) {
            if (!FnMatcher::isMatching($name, $packageName)) {
                continue;
            }
            if (is_string($value)) {
                return ['url' => $value, 'type' => null];
            }
            is_object($value) and $value = (array)$value;
            if (!is_array($value) || !is_string($value['url'] ?? null)) {
                continue;
            }
            /** @var string $url */
            $url = $value['url'];
            /** @var string|null $type */
            $type = is_string($value['type'] ?? null) ? $value['type'] : null;

            return compact('url', 'type');
        }

        return null;
    }

    /**
     * @param string $packageType
     * @param array<string, mixed> $byType
     * @return array{url:string, type: string|null}|null
     */
    protected function findByType(string $packageType, array $byType): ?array
    {
        $value = $byType[$packageType] ?? null;
        if (is_string($value)) {
            return ['url' => $value, 'type' => null];
        }

        is_object($value) and $value = (array)$value;
        if (!is_array($value) || !is_string($value['url'] ?? null)) {
            return null;
        }

        /** @var string $url */
        $url = $value['url'];
        /** @var string|null $type */
        $type = is_string($value['type'] ?? null) ? $value['type'] : null;

        return compact('url', 'type');
    }
}
