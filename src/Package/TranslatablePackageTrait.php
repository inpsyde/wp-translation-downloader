<?php

/*
 * This file is part of the Assets package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Inpsyde\WpTranslationDownloader\Package;

use Inpsyde\WpTranslationDownloader\PackageNameResolver;

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

    /**
     * @var bool
     */
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
            static function (array $trans) use ($allowedLanguages): bool {
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

        $apiUrl = $this->apiEndpoint();
        if ($apiUrl === '') {
            return false;
        }

        $result = @file_get_contents($this->apiEndpoint());
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
     * @see TranslatablePackage::apiEndpoint()
     *
     */
    public function apiEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * @return string
     * @see TranslatablePackage::projectName()
     *
     */
    public function projectName(): string
    {
        if (! $this->projectName) {
            [$vendorName, $projectName] = PackageNameResolver::resolve($this->getName());
            $this->projectName = $projectName;
        }

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
}
