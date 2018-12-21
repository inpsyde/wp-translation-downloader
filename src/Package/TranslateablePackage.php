<?php # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader\Package;

interface TranslateablePackage
{

    const TYPE_CORE = 'wordpress-core';
    const TYPE_PLUGIN = 'wordpress-plugin';
    const TYPE_THEME = 'wordpress-theme';

    public function name(): string;

    public function type(): string;

    public function version(): string;

    public function apiUrl(): string;

    public function directory(): string;

    public function translations(array $allowedLanguages = []): array;
}
