<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader\Package;

/**
 * @see TranslateablePackage
 */
trait TranslateablePackageTrait
{

    private $translationLoaded = false;

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
     * @see TranslateablePackage::apiUrl()
     *
     * @return string
     */
    abstract function apiUrl(): string;

    protected function loadTranslations(): bool
    {
        if ($this->translationLoaded) {
            return false;
        }
        $this->translationLoaded = [];

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
     * @see TranslateablePackage::translations()
     *
     * @param array $allowedLanguages
     *
     * @return array
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

    /**
     * @see TranslateablePackage::projectName()
     *
     * @return string
     */
    public function projectName(): string
    {
        return $this->projectName;
    }

    /**
     * @see TranslateablePackage::languageDirectory()
     *
     * @return string
     */
    public function languageDirectory(): string
    {
        return $this->languageDirectory;
    }
}
