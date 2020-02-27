<?php declare(strict_types=1); # -*- coding: utf-8 -*-

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
