<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader\Config;

use Inpsyde\WpTranslationDownloader\Package\TranslationPackageInterface;

final class PluginConfiguration
{

    const KEY = 'wp-translation-downloader';
    /**
     * @var array
     */
    const DEFAULTS = [
        'excludes' => [],
        'languages' => [],
        'directory' => '',
        'directories' => [],
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
            TranslationPackageInterface::TYPE_CORE => $languageRoot,
            TranslationPackageInterface::TYPE_PLUGIN => $languageRoot.'plugins/',
            TranslationPackageInterface::TYPE_THEME => $languageRoot.'themes/',
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

        $rules = array_map(
            function (string $rule): string {
                return '('.str_replace(['*', '/'], ['.+', '\/'], $rule).')';
            },
            $excludes
        );

        return '/'.implode('|', $rules).'/';
    }

    /**
     * @param array $extra
     *
     * @return PluginConfiguration
     */
    public static function fromExtra(array $extra): self
    {
        if (! isset($extra[self::KEY])) {
            return new static([]);
        }

        return new static($extra[self::KEY]);
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
            return '<fg=red>extra.language-downloader.languages has to be configured as non empty array in your composer.json</>';
        }

        return '';
    }

    public function allowedLanguages(): array
    {
        return $this->config['languages'];
    }
}
