<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader\Config;

use Inpsyde\WpTranslationDownloader\Package\TranslatablePackage;

final class PluginConfiguration
{

    public const KEY = 'wp-translation-downloader';
    /**
     * @var array
     */
    const DEFAULTS = [
        'excludes' => [],
        'languages' => [],
        'directory' => '',
        'directories' => [],
        'api' => [],
    ];

    /**
     * @var array
     */
    private $config = [];

    public function __construct(array $config)
    {
        $config = array_merge(self::DEFAULTS, $config);

        $languageRoot = getcwd().'/';
        if ($config['directory'] !== '') {
            $languageRoot .= $config['directory'].'/';
        }

        $dirs = [
            TranslatablePackage::TYPE_CORE => $languageRoot,
            TranslatablePackage::TYPE_PLUGIN => $languageRoot.'plugins/',
            TranslatablePackage::TYPE_THEME => $languageRoot.'themes/',
            TranslatablePackage::TYPE_LIBRARY => $languageRoot.'plugins/'
        ];

        $config['directory'] = $languageRoot;
        $config['directories'] = $dirs;
        $config['excludes'] = $this->prepareExcludes($config['excludes']);

        $this->config = $config;
    }

    private function prepareExcludes(array $excludes): string
    {
        if (count($excludes) < 1) {
            return '';
        }

        $rules = array_map([$this, 'prepareRegex'], $excludes);

        return '/'.implode('|', $rules).'/';
    }

    public function directory(string $packageType = 'wordpress-core'): string
    {
        if (! isset($this->config['directories'][$packageType])) {
            return '';
        }

        return $this->config['directories'][$packageType];
    }

    public function directories(): array
    {
        return $this->config['directories'];
    }

    public function doExclude(string $name): bool
    {
        $excludes = $this->excludes();
        if ($excludes === '') {
            return false;
        }

        return preg_match($excludes, $name) === 1;
    }

    public function excludes(): string
    {
        return $this->config['excludes'];
    }

    public function isValid(): string
    {
        if (count($this->allowedLanguages()) < 1) {
            return '<fg=red>extra.wp-translation-downloader.languages has to be configured as non empty array in your composer.json</>';
        }

        return '';
    }

    public function allowedLanguages(): array
    {
        return $this->config['languages'];
    }

    /**
     * Find a matching configured API Endpoint for the current Package
     *
     * @param string $packageName
     *
     * @return string|null
     */
    public function apiForPackage(string $packageName): ?string
    {
        foreach ($this->api() as $apiPackage => $endpoint) {
            $pattern = '/'.$this->prepareRegex($apiPackage).'/';
            if (preg_match($pattern, $packageName) === 1) {
                return $endpoint;
            };
        }

        return null;
    }

    public function api(): array
    {
        return $this->config['api'];
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
    private function prepareRegex(string $input): string
    {
        return '('.str_replace(['*', '/'], ['.+', '\/'], $input).')';
    }
}
