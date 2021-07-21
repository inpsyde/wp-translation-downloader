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

interface TranslatablePackage extends PackageInterface
{

    public const TYPE_CORE = 'wordpress-core';
    public const TYPE_PLUGIN = 'wordpress-plugin';
    public const TYPE_THEME = 'wordpress-theme';
    public const TYPE_LIBRARY = 'library';

    /**
     * The cleaned name of the project without vendor.
     *
     * @return string
     */
    public function projectName(): string;

    /**
     * The build URL to the api endpoint.
     *
     * @return string
     */
    public function apiEndpoint(): string;

    /**
     * Retrieve the path to the language directory.
     *
     * @return string
     */
    public function languageDirectory(): string;

    /**
     * Get all or filtered translations by allowed language(s).
     *
     * @param array $allowedLanguages
     *
     * @return array
     */
    public function translations(array $allowedLanguages = []): array;
}
