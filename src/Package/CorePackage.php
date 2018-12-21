<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader\Package;

final class CorePackage extends BaseTranslationPackage
{

    protected function prepareApiUrl(string $name, string $version): string
    {
        return sprintf(
            'https://api.wordpress.org/translations/core/1.0/?version=%1$s',
            $version
        );
    }
}
