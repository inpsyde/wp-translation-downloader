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

namespace Inpsyde\WpTranslationDownloader\Config;

use Composer\Package\CompletePackage;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackageInterface;
use Inpsyde\WpTranslationDownloader\Util\FnMatcher;

/**
 * @psalm-type virtual-package = array{name: string, type: string, version?: int|float|string}
 */
final class PluginConfiguration
{
    /**
     * Configuration selectors for "api" and "directory".
     */
    public const BY_NAME = 'names';
    public const BY_TYPE = 'types';

    public const AUTO_RUN = 'auto-run';
    public const EXCLUDES = 'excludes';
    public const LANGUAGES = 'languages';
    public const LANGUAGES_ROOT_DIR = 'languageRootDir';
    public const DIRECTORIES = 'directories';
    public const API = 'api';
    public const VIRTUAL_PACKAGES = 'virtual-packages';

    private const TYPE_CORE = TranslatablePackageInterface::TYPE_CORE;
    private const TYPE_PLUGIN = TranslatablePackageInterface::TYPE_PLUGIN;
    private const TYPE_THEME = TranslatablePackageInterface::TYPE_THEME;
    private const TYPE_LIBRARY = TranslatablePackageInterface::TYPE_LIBRARY;

    private const WPORG_API_PREFIX = 'https://api.wordpress.org/translations';
    private const API_CORE_URI = '/core/1.0/?version=%packageVersion%';
    private const API_PLUGIN_URI = '/plugins/1.0/?slug=%projectName%&version=%packageVersion%';
    private const API_THEME_URI = '/themes/1.0/?slug=%projectName%&version=%packageVersion%';

    /**
     * @var array
     */
    private const DEFAULTS = [
        self::AUTO_RUN => true,
        self::EXCLUDES => [],
        self::LANGUAGES => [],
        self::LANGUAGES_ROOT_DIR => null,
        self::DIRECTORIES => [
            self::BY_NAME => [],
            self::BY_TYPE => [
                self::TYPE_CORE => '',
                self::TYPE_PLUGIN => 'plugins',
                self::TYPE_THEME => 'themes',
                self::TYPE_LIBRARY => 'library',
            ],
        ],
        self::API => [
            self::BY_NAME => [],
            self::BY_TYPE => [
                self::TYPE_CORE => self::WPORG_API_PREFIX . self::API_CORE_URI,
                self::TYPE_PLUGIN => self::WPORG_API_PREFIX . self::API_PLUGIN_URI,
                self::TYPE_THEME => self::WPORG_API_PREFIX . self::API_THEME_URI,
            ],
        ],
        self::VIRTUAL_PACKAGES => [],
    ];

    /**
     * @var array{
     *  auto-run: bool,
     *  excludes: list<string>,
     *  languages: list<string>,
     *  languageRootDir: string,
     *  directories: array<string, array<string, string>>,
     *  api: array<string, array<string, string>>,
     *  virtual-packages: list<PackageInterface>
     * }
     */
    private $config;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param array $config
     * @param Filesystem|null $filesystem
     */
    public function __construct(array $config, ?Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?? new Filesystem();

        /**
         * @var array{
         *  auto-run: bool,
         *  excludes: array<string>,
         *  languages: list<string>,
         *  languageRootDir?: string,
         *  directories: array<string, array<string, string>>,
         *  api: array<string, array<string, string>>,
         *  virtual-packages: array<virtual-package>
         * } $config
         */
        $config = array_replace_recursive(self::DEFAULTS, $config);

        $this->config = [
            self::AUTO_RUN => $config[self::AUTO_RUN],
            self::EXCLUDES => $this->prepareExcludes($config),
            self::LANGUAGES => $config[self::LANGUAGES],
            self::LANGUAGES_ROOT_DIR => $this->prepareLanguageRoot($config),
            self::DIRECTORIES => $config[self::DIRECTORIES],
            self::API => $config[self::API],
            self::VIRTUAL_PACKAGES => $this->prepareVirtualPackages($config),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function directoriesByName(): array
    {
        return $this->config[self::DIRECTORIES][self::BY_NAME] ?? [];
    }

    /**
     * @return array<string, string>
     */
    public function directoriesByType(): array
    {
        return $this->config[self::DIRECTORIES][self::BY_TYPE] ?? [];
    }

    /**
     * @return string
     */
    public function languageRootDir(): string
    {
        return $this->config[self::LANGUAGES_ROOT_DIR];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function shouldExclude(string $name): bool
    {
        return FnMatcher::isMatchingAny($this->config[self::EXCLUDES], $name);
    }

    /**
     * @return list<string>
     */
    public function allowedLanguages(): array
    {
        return $this->config[self::LANGUAGES];
    }

    /**
     * @return array<string, string>
     */
    public function endpointsByName(): array
    {
        return $this->config[self::API][self::BY_NAME] ?? [];
    }

    /**
     * @return array<string, string>
     */
    public function endpointsByType(): array
    {
        return $this->config[self::API][self::BY_TYPE] ?? [];
    }

    /**
     * @return bool
     */
    public function autorun(): bool
    {
        return $this->config[self::AUTO_RUN];
    }

    /**
     * @return list<PackageInterface>
     */
    public function virtualPackages(): array
    {
        return $this->config[self::VIRTUAL_PACKAGES];
    }

    /**
     * Resolve the "root" directory for languages with back compat
     * to previous version where "directory" was a string value as root.
     *
     * @param array $config
     * @return string
     */
    private function prepareLanguageRoot(array $config): string
    {
        $root = getcwd();

        // version 2.0 supported ["directory" => "/path/"]
        // version 2.1 supports ["languageRootDir" => "/path"]
        $dir = $config[self::LANGUAGES_ROOT_DIR] ?? $config['directory'] ?? '';

        is_string($dir) or $dir = '';
        $dir = trim($dir, "\\/");

        return $this->filesystem->normalizePath("{$root}/{$dir}") . '/';
    }

    /**
     * @param array $config
     * @return list<string>
     */
    private function prepareExcludes(array $config): array
    {
        /** @var array<string> $excludes */
        $excludes = $config[self::EXCLUDES];
        if ($excludes === []) {
            return [];
        }

        return array_values($excludes);
    }

    /**
     * @param array $config
     * @return list<PackageInterface>
     */
    private function prepareVirtualPackages(array $config): array
    {
        /**
         * @var array<virtual-package> $packages
         */
        $packages = $config[self::VIRTUAL_PACKAGES];
        if ($packages === []) {
            return [];
        }

        $loaded = [];
        foreach ($packages as $packageData) {
            $prettyVersion = $packageData['version'] ?? '';
            $version = (string)$prettyVersion;

            $package = new CompletePackage($packageData['name'], $version, $version);
            $package->setType($packageData['type']);

            $loaded[] = $package;
        }

        return $loaded;
    }
}
