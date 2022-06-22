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

use Composer\Package\Package;
use Composer\Package\PackageInterface;

class TranslatablePackage extends Package implements TranslatablePackageInterface
{
    use NameResolverTrait;

    /**
     * All translations from the API.
     *
     * @var list<ProjectTranslation>
     */
    protected $translations = [];

    /**
     * @var string|null
     */
    protected $projectName = null;

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
     * @var string|null
     */
    private $endpointFileType;

    /**
     * @param PackageInterface $package
     * @param string $directory
     * @param string $endpoint
     * @param string|null $endpointFileType
     */
    public function __construct(
        PackageInterface $package,
        string $directory,
        string $endpoint,
        ?string $endpointFileType = null
    ) {

        parent::__construct(
            $package->getName(),
            $package->getVersion(),
            $package->getPrettyVersion()
        );

        // Type is not set by constructor, so we have to set it manually
        // in case we want to access it again.
        // Otherwise, it will fall back to "library".
        $this->type = $package->getType();
        $this->endpoint = $endpoint;
        $this->languageDirectory = $directory;
        $this->endpointFileType = $endpointFileType;
    }

    /**
     * {@inheritDoc}
     */
    public function translations(array $allowedLanguages = []): array
    {
        $this->loadTranslations();

        if (count($allowedLanguages) === 0) {
            return $this->translations;
        }

        $filtered = [];
        foreach ($this->translations as $translation) {
            if (in_array($translation->language(), $allowedLanguages, true)) {
                $filtered[] = $translation;
            }
        }

        return $filtered;
    }

    /**
     * @return bool
     */
    protected function loadTranslations(): bool
    {
        if ($this->translationLoaded) {
            return false;
        }
        $this->translationLoaded = true;

        $apiUrl = $this->apiEndpoint();
        if ($apiUrl === '') {
            return false;
        }

        $result = $this->readEndpointContent($apiUrl);
        $translations = is_array($result) ? ($result['translations'] ?? null) : null;
        if (!is_array($translations) || (count($translations) < 1)) {
            return false;
        }

        $this->translations = $this->parseTranslations($translations);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function apiEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * {@inheritDoc}
     */
    public function projectName(): string
    {
        if ($this->projectName === null) {
            [, $projectName] = $this->resolveName($this->getName());
            $this->projectName = $projectName;
        }

        return $this->projectName;
    }

    /**
     * {@inheritDoc}
     */
    public function languageDirectory(): string
    {
        return $this->languageDirectory;
    }

    /**
     * @param string $apiUrl
     * @return array|null
     */
    protected function readEndpointContent(string $apiUrl): ?array
    {
        $result = @file_get_contents($apiUrl);
        if (!$result) {
            return null;
        }

        $result = json_decode($result, true);

        return is_array($result) ? $result : null;
    }

    /**
     * @param array $translationsData
     * @return list<ProjectTranslation>
     */
    protected function parseTranslations(array $translationsData): array
    {
        $validTranslations = [];
        foreach ($translationsData as $translationData) {
            $translation = is_array($translationData)
                ? ProjectTranslation::load($translationData, $this->projectName())
                : null;
            if ($translation && $translation->isValid()) {
                $this->endpointFileType and $translation->withFileType($this->endpointFileType);
                $validTranslations[] = $translation;
            }
        }

        return $validTranslations;
    }
}
