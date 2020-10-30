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

use Inpsyde\WpTranslationDownloader\PackageNameResolver;
use PHPUnit\Framework\TestCase;

class PackageNameResolverTest extends TestCase
{

    /**
     * @dataProvider providePackageNames
     *
     * @param string $input
     * @param array $expected
     *
     * @throws \Throwable
     */
    public function testBasic(string $input, array $expected)
    {
        $testee = new PackageNameResolver();

        static::assertSame($expected, $testee->resolve($input));
    }

    public function providePackageNames()
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