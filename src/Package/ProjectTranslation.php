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

class ProjectTranslation
{
    private const SUPPORTED_ARCHIVES = ['zip', 'rar', 'tar', 'gzip', 'xz'];
    private const SUPPORTED_FILES = ['mo', 'json'];
    private const EXTENSION_MAP = [
        '7z' => 'zip',
        '7zz' => 'zip',
        'tar.gz' => 'tar',
        'tgz' => 'tar',
        'tar.bz2' => 'tar',
    ];

    /**
     * @var string
     */
    private $projectName;

    /**
     * @var string|null
     */
    private $language;

    /**
     * @var string|null
     */
    private $version;

    /**
     * @var string|null
     */
    private $packageUrl;

    /**
     * @var string
     */
    private $lastUpdated;

    /**
     * @var string|null
     */
    private $fileType = null;

    /**
     * @var bool
     */
    private $valid;

    /**
     * @param array $translation
     * @param string $projectName
     * @return ProjectTranslation
     */
    public static function load(array $translation, string $projectName): ProjectTranslation
    {
        return new self($translation, $projectName);
    }

    /**
     * @param array $translation
     * @param string $projectName
     */
    private function __construct(array $translation, string $projectName)
    {
        $language = $translation['language'] ?? '';
        (is_string($language) && $language !== '') or $language = null;

        $version = $translation['version'] ?? '';
        (is_string($version) && $version !== '') or $version = null;

        $packageUrl = $translation['package'] ?? '';
        filter_var($packageUrl, FILTER_VALIDATE_URL) or $packageUrl = null;

        $lastUpdated = $translation['updated'] ?? '';
        is_string($lastUpdated) or $lastUpdated = '';

        $this->projectName = $projectName;
        $this->language = $language;
        $this->version = $version;
        $this->packageUrl = $packageUrl;
        $this->lastUpdated = $lastUpdated;
        $this->valid = $projectName && $language && $version && $packageUrl;
    }

    /**
     * @return string
     */
    public function projectName(): string
    {
        return $this->projectName;
    }

    /**
     * @return string|null
     */
    public function language(): ?string
    {
        return $this->language;
    }

    /**
     * @return string|null
     */
    public function version(): ?string
    {
        return $this->version;
    }

    /**
     * @return string|null
     */
    public function packageUrl(): ?string
    {
        return $this->packageUrl;
    }

    /**
     * @return string
     */
    public function lastUpdated(): string
    {
        return $this->lastUpdated;
    }

    /**
     * @return bool
     *
     * @psalm-assert-if-true non-empty-string $this->projectName
     * @psalm-assert-if-true non-empty-string $this->language
     * @psalm-assert-if-true non-empty-string $this->version
     * @psalm-assert-if-true non-empty-string $this->packageUrl
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * @param string $fileType
     * @return static
     */
    public function withFileType(string $fileType): ProjectTranslation
    {
        $this->fileType = $fileType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function distType(): ?string
    {
        $distType = $this->fileType;
        if (!is_string($distType)) {
            $distUrl = $this->packageUrl();
            if (!$distUrl) {
                return null;
            }
            if (preg_match('~\.((?:[a-z0-9_-]+\.)?[a-z0-9_-]+)$~i', $distUrl, $matches)) {
                $distType = $matches[1];
            }
        }
        $ext = strtolower($distType ?? 'zip');
        $distType = self::EXTENSION_MAP[$ext] ?? $ext;

        if (in_array($distType, self::SUPPORTED_ARCHIVES, true)) {
            return $distType;
        }

        if (in_array($distType, self::SUPPORTED_FILES, true)) {
            return 'file';
        }

        return null;
    }

    /**
     * We use "last updated" as part of the name string, this way Composer cache will handle it
     * properly discarding cached files with a different "last updated".
     *
     * @return string
     */
    public function fullyQualifiedName(): string
    {
        if (!$this->isValid()) {
            return '';
        }

        return (string)preg_replace(
            '~[^a-zA-Z0-9_/]~',
            '-',
            sprintf(
                'wp-translations-downloader/%s-%s-%s',
                $this->projectName(),
                $this->language() ?? '',
                $this->lastUpdated()
            )
        );
    }

    /**
     * @return string
     */
    public function description(): string
    {
        if (!$this->isValid()) {
            return '';
        }

        return sprintf(
            "%s's %s translation files. Last updated: %s.",
            $this->projectName(),
            $this->language() ?? '',
            $this->lastUpdated()
        );
    }
}
