<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader\Package;

final class ThemePackage extends BaseTranslationPackage
{

    protected function prepareApiUrl(string $name, string $version): string
    {
        return sprintf(
            'https://api.wordpress.org/translations/themes/1.0/?slug=%1$s&version=%2$s',
            $name,
            $version
        );
    }
}