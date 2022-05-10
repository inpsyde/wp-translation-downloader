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

namespace Inpsyde\WpTranslationDownloader\Tests\Unit;

use Composer\Package\PackageInterface;
use Inpsyde\WpTranslationDownloader\PackageNameResolver;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackage;
use PHPUnit\Framework\TestCase;

class PackageNameResolverTest extends TestCase
{

    /**
     * @dataProvider providePackageNames
     *
     * @param string $input
     * @param array $expected
     *
     * @test
     */
    public function testResolve(string $input, array $expected): void
    {
        static::assertSame($expected, PackageNameResolver::resolve($input));
    }

    public function providePackageNames(): \Generator
    {
        yield [
            'inpsyde/google-tag-manager',
            ['inpsyde', 'google-tag-manager'],
        ];

        yield [
            'inpsyde-google-tag-manager',
            ['', 'inpsyde-google-tag-manager']
        ];
    }
}
