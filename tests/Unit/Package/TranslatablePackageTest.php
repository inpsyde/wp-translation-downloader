<?php

declare(strict_types=1);

namespace Inpsyde\WpTranslationDownloader\Tests\Unit\Package;

use Composer\Package\Package;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackage;
use PHPUnit\Framework\TestCase;

class TranslatablePackageTest extends TestCase
{
    /**
     * @test
     * @dataProvider providePackageNames
     */
    public function testProjectName(string $input, string $expected): void
    {
        $package = new Package($input, '1.0.0.0', '1.0.0');
        $translatablePackage = new TranslatablePackage($package, __DIR__, 'https://example.com');

        static::assertSame($expected, $translatablePackage->projectName());
    }

    /**
     * @return \Generator
     */
    public function providePackageNames(): \Generator
    {
        yield [
            'inpsyde/google-tag-manager',
            'google-tag-manager',
        ];

        yield [
            'inpsyde-google-tag-manager',
            'inpsyde-google-tag-manager',
        ];

        yield [
            'inpsyde/this-is/pretty-wrong',
            'this-is-pretty-wrong',
        ];
    }
}
