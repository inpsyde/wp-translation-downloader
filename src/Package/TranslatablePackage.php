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

class TranslatablePackage extends Package implements PackageInterface
{
    use TranslatablePackageTrait;

    /**
     * Default types which are supported by the library.
     */
    public const TYPE_CORE = 'wordpress-core';
    public const TYPE_PLUGIN = 'wordpress-plugin';
    public const TYPE_THEME = 'wordpress-theme';
    public const TYPE_LIBRARY = 'library';
}
