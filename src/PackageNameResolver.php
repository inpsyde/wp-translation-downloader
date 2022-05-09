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

namespace Inpsyde\WpTranslationDownloader;


class PackageNameResolver
{
    /**
     * Splits a packageName into vendorName and projectName
     *
     * @param string $packageName
     *
     * @return array
     * @example inpsyde/google-tag-manager      => ["inpsyde", "google-tag-manager"]
     * @example inpsyde-google-tag-manager      => ["", "inpsyde-google-tag-manager"]
     *
     */
    public static function resolve(string $packageName): array
    {
        $packageNamePieces = explode('/', $packageName);

        if (count($packageNamePieces) !== 2) {
            return ['', $packageNamePieces[0]];
        }

        return [$packageNamePieces[0] ?? '', $packageNamePieces[1] ?? ''];
    }
}
