<?php # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader\Package;

interface TranslateablePackage
{

    const TYPE_CORE = 'wordpress-core';
    const TYPE_PLUGIN = 'wordpress-plugin';
    const TYPE_THEME = 'wordpress-theme';

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
    public function apiUrl(): string;

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
