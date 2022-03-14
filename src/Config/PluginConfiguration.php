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

use Inpsyde\WpTranslationDownloader\Package;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackage;

final class PluginConfiguration
{
    public const KEY = 'wp-translation-downloader';

    /**
     * @var array
     */
    public const SUPPORTED_PACKAGES = [
        Package\TranslatablePackage::TYPE_CORE => Package\WpCorePackage::class,
        Package\TranslatablePackage::TYPE_PLUGIN => Package\WpPluginPackage::class,
        Package\TranslatablePackage::TYPE_THEME => Package\WpThemePackage::class,
        Package\TranslatablePackage::TYPE_LIBRARY => Package\LibraryPackage::class,
    ];
    public const API_BY_NAME = 'names';
    public const API_BY_TYPE = 'types';
    /**
     * @var array
     */
    private const DEFAULTS = [
        'auto-run' => true,
        'excludes' => [],
        'languages' => [],
        'directory' => '',
        'directories' => [],
        'api' => [
            self::API_BY_NAME => [],
            self::API_BY_TYPE => [
                // phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
                Package\TranslatablePackage::TYPE_CORE => 'https://api.wordpress.org/translations/core/1.0/?version=%packageVersion%',
                Package\TranslatablePackage::TYPE_PLUGIN => 'https://api.wordpress.org/translations/plugins/1.0/?slug=%projectName%&version=%packageVersion%',
                Package\TranslatablePackage::TYPE_THEME => 'https://api.wordpress.org/translations/themes/1.0/?slug=%projectName%&version=%packageVersion%',
            ],
        ],
    ];

    /**
     * @var array
     */
    private $config = [];

    public function __construct(array $config)
    {
        $config = array_replace_recursive(self::DEFAULTS, $config);

        $languageRoot = getcwd() . '/';
        if ($config['directory'] !== '') {
            $languageRoot .= $config['directory'] . '/';
        }

        $dirs = [
            TranslatablePackage::TYPE_CORE => $languageRoot,
            TranslatablePackage::TYPE_PLUGIN => $languageRoot . 'plugins/',
            TranslatablePackage::TYPE_THEME => $languageRoot . 'themes/',
            TranslatablePackage::TYPE_LIBRARY => $languageRoot . 'library/',
        ];

        $config['auto-run'] = (bool) ($config['auto-run'] ?? true);
        $config['directory'] = $languageRoot;
        $config['directories'] = $dirs;
        $config['excludes'] = $this->prepareExcludes($config['excludes']);

        $this->config = $config;
    }

    /**
     * @param array $excludes
     *
     * @return string
     */
    private function prepareExcludes(array $excludes): string
    {
        if (count($excludes) < 1) {
            return '';
        }

        $rules = array_map([$this, 'prepareRegex'], $excludes);

        return '/' . implode('|', $rules) . '/';
    }

    /**
     * @param string $packageType
     *
     * @return string
     */
    public function directory(string $packageType = 'wordpress-core'): string
    {
        if (! isset($this->config['directories'][$packageType])) {
            return '';
        }

        return $this->config['directories'][$packageType];
    }

    /**
     * @return array
     */
    public function directories(): array
    {
        return $this->config['directories'];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function doExclude(string $name): bool
    {
        $excludes = $this->excludes();
        if ($excludes === '') {
            return false;
        }

        return preg_match($excludes, $name) === 1;
    }

    /**
     * @return string
     */
    public function excludes(): string
    {
        return $this->config['excludes'];
    }

    /**
     * @return string
     */
    public function isValid(): string
    {
        if (count($this->allowedLanguages()) < 1) {
            // phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
            return '<fg=red>extra.wp-translation-downloader.languages has to be configured as non empty array in your composer.json</>';
        }

        return '';
    }

    /**
     * @return array
     */
    public function allowedLanguages(): array
    {
        return $this->config['languages'];
    }

    /**
     * @param string $packageType
     *
     * @return bool
     */
    public function isPackageTypeSupported(string $packageType): bool
    {
        return isset(self::SUPPORTED_PACKAGES[$packageType]);
    }

    /**
     * @param string $packType
     *
     * @return string
     */
    public function packageTypeClass(string $packType): string
    {
        return self::SUPPORTED_PACKAGES[$packType];
    }

    public function api(): array
    {
        return $this->config['api'];
    }

    public function apiBy(string $type): array
    {
        return $this->config['api'][$type] ?? [];
    }

    /**
     * Replaces from the configuration.json file the packageName with placeholder to valid regex.
     *
     * @param string $input
     *
     * @return string
     * @example inpsyde/wp-*    =>  (inpsyde\/wp-.+)
     *
     */
    public function prepareRegex(string $input): string
    {
        return '(' . str_replace(['*', '/'], ['.+', '\/'], $input) . ')';
    }

    /**
     * @return bool
     */
    public function autorun(): bool
    {
        return $this->config['auto-run'];
    }
}
