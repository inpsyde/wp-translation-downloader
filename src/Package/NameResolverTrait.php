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

trait NameResolverTrait
{
    /**
     * Splits a packageName into vendorName and projectName
     *
     * @param string $packageName
     * @return array{string, string}
     *
     * @example inpsyde/google-tag-manager => ["inpsyde", "google-tag-manager"]
     * @example inpsyde-google-tag-manager => ["", "inpsyde-google-tag-manager"]
     */
    private function resolveName(string $packageName): array
    {
        $packageNamePieces = explode('/', $packageName, 2);

        if (count($packageNamePieces) < 2) {
            return ['', $packageNamePieces[0]];
        }

        $projectName = str_replace('/', '-', $packageNamePieces[1] ?? '');

        return [$packageNamePieces[0] ?? '', $projectName];
    }
}
