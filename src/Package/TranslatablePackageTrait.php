<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader\Package;

/**
 * @see TranslatablePackage
 */
trait TranslatablePackageTrait
{

    /**
     * All translations from the API.
     *
     * @var array
     */
    protected $translations = [];

    /**
     * @var string
     */
    protected $projectName;

    /**
     * @var string
     */
    protected $languageDirectory;

    /**
     * @var string
     */
    protected $endpoint;

    private $translationLoaded = false;

    /**
     * @param array $allowedLanguages
     *
     * @return array
     * @see TranslatablePackage::translations()
     *
     */
    public function translations(array $allowedLanguages = []): array
    {
        $this->loadTranslations();

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

    protected function loadTranslations(): bool
    {
        if ($this->translationLoaded) {
            return false;
        }
        $this->translationLoaded = [];

        $apiUrl = $this->apiUrl();
        if ($apiUrl === '') {
            return false;
        }

        $result = @file_get_contents($this->apiUrl());
        if (! $result) {
            return false;
        }

        $result = json_decode($result, true);
        if (! isset($result['translations']) || count($result['translations']) < 1) {
            return false;
        }

        $this->translations = $result['translations'];

        return true;
    }

    /**
     * @return string
     * @see TranslatablePackage::apiUrl()
     *
     */
    abstract function apiUrl(): string;

    /**
     * @return string
     * @see TranslatablePackage::projectName()
     *
     */
    public function projectName(): string
    {
        return $this->projectName;
    }

    /**
     * @return string
     * @see TranslatablePackage::languageDirectory()
     *
     */
    public function languageDirectory(): string
    {
        return $this->languageDirectory;
    }

    /**
     * Splits {vendor}/{projectName} and returns projectName.
     *
     * @param string $name
     *
     * @return string
     */
    protected function prepareProjectName(string $name): string
    {
        $pieces = explode('/', $name);

        if (count($pieces) !== 2) {
            return '';
        }

        return $pieces[1];
    }
}
