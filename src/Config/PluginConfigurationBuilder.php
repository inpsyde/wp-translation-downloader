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

namespace Inpsyde\WpTranslationDownloader\Config;

use Composer\Json\JsonFile;

final class PluginConfigurationBuilder
{

    public const KEY = 'wp-translation-downloader';

    public static function build(array $extra = []): PluginConfiguration
    {
        if (! isset($extra[self::KEY])) {
            return new PluginConfiguration([]);
        }

        $config = $extra[self::KEY];

        if (is_array($config)) {
            return new PluginConfiguration($config);
        }

        $file = new JsonFile($config);

        if ($file->exists()) {
            return new PluginConfiguration($file->read());
        }

        return new PluginConfiguration([]);
    }
}
