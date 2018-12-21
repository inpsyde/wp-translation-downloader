<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader\Package;

abstract class BasePackage implements TranslateablePackage
{

    /**
     * @see TranslationPackageInterface::TYPE_*
     *
     * @var string
     */
    protected $type;

    /**
     * The name of the plugin/theme/core
     *
     * @var string
     */
    protected $name;

    /**
     * The plugin/theme/core version to load translations.
     *
     * @var string
     */
    protected $version;

    /**
     * The URL-Endpoint to load translations.
     *
     * @var string
     */
    protected $apiUrl;

    /**
     * All translations from the API.
     *
     * @var array
     */
    protected $translations = [];

    /**
     * @var string
     */
    private $directory;

    /**
     * @param string $name
     * @param string $version
     *
     * @return string
     */
    abstract protected function prepareApiUrl(string $name, string $version): string;

    /**
     * BasePackage constructor.
     *
     * @param string $name
     * @param string $type
     * @param string $version
     * @param string $directory
     */
    public function __construct(string $name, string $type, string $version, string $directory)
    {
        $this->type = $type;
        $this->name = $this->prepareName($name);
        $this->version = $version;
        $this->directory = $directory;
        $this->apiUrl = $this->prepareApiUrl($this->name, $this->version);

        $this->translations = $this->loadTranslations();
    }

    protected function prepareName(string $packageName): string
    {
        $pieces = explode('/', $packageName);

        if (count($pieces) !== 2) {
            return '';
        }

        return $pieces[1];
    }

    protected function loadTranslations(): array
    {
        $result = @file_get_contents($this->apiUrl);
        if (! $result) {
            return [];
        }

        $result = json_decode($result, true);
        if (! isset($result['translations']) || count($result['translations']) < 1) {
            return [];
        }

        return $result['translations'];
    }

    public function type(): string
    {
        return $this->type;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function apiUrl(): string
    {
        return $this->apiUrl;
    }

    public function directory(): string
    {
        return $this->directory;
    }

    public function translations(array $allowedLanguages = []): array
    {
        if (count($allowedLanguages) === 0) {
            return $this->translations;
        }

        return array_filter(
            $this->translations,
            function (array $trans) use ($allowedLanguages) {
                return in_array($trans['language'], $allowedLanguages, true);
            }
        );
    }
}
