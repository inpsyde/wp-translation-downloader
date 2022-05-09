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
use Inpsyde\WpTranslationDownloader\PackageNameResolver;

class TranslatablePackage extends Package implements PackageInterface
{
    /**
     * Default types which are supported by the library.
     */
    public const TYPE_CORE = 'wordpress-core';
    public const TYPE_PLUGIN = 'wordpress-plugin';
    public const TYPE_THEME = 'wordpress-theme';
    public const TYPE_LIBRARY = 'library';

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
     * @param PackageInterface $package
     * @param string $directory
     * @param string $endpoint
     */
    public function __construct(PackageInterface $package, string $directory, string $endpoint)
    {
        parent::__construct($package->getName(), $package->getVersion(), $package->getPrettyVersion());

        $this->endpoint = $endpoint;
        $this->languageDirectory = $directory;
    }

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
