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

use Composer\Package\PackageInterface;

interface TranslatablePackageInterface extends PackageInterface
{
    /**
     * Default types which are supported by the library.
     */
    public const TYPE_CORE = 'wordpress-core';
    public const TYPE_PLUGIN = 'wordpress-plugin';
    public const TYPE_THEME = 'wordpress-theme';
    public const TYPE_LIBRARY = 'library';

    /**
     * Collection of translations filtered by allowed languages.
     *
     * @param list<string> $allowedLanguages
     * @return list<ProjectTranslation>
     */
    public function translations(array $allowedLanguages = []): array;

    /**
     * Endpoint URL to download translation files from Glotpress.
     *
     * @return string
     */
    public function apiEndpoint(): string;

    /**
     * The name of the package project.
     *
     * @return string
     */
    public function projectName(): string;

    /**
     * Directory where language files will be downloaded into.
     *
     * @return string
     *
     */
    public function languageDirectory(): string;
}
