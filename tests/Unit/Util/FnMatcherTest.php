<?php

declare(strict_types=1);

namespace Inpsyde\WpTranslationDownloader\Tests\Unit\Util;

use Inpsyde\WpTranslationDownloader\Util\FnMatcher;
use PHPUnit\Framework\TestCase;

class FnMatcherTest extends TestCase
{
    /**
     * @test
     * @dataProvider isMatchingDataProvider
     */
    public function testIsMatching(string $pattern, string $subject, bool $expected): void
    {
        static::assertSame($expected, FnMatcher::isMatching($pattern, $subject));
    }

    /**
     * @return array
     */
    public function isMatchingDataProvider(): array
    {
        return [
            0 => ['*/*', 'meh/meh', true],
            1 => ['*/*', 'meh', true],
            2 => ['*', 'meh', true],
            3 => ['foo/bar', 'foo/bar', true],
            4 => ['foo', 'foo', true],
            5 => ['foo/bar', 'Foo/Bar', true],
            6 => ['foo/*', 'Foo/Bar', true],
            7 => ['foo/B*', 'Foo/Bar', true],
            8 => ['foo/B*z', 'Foo/Bar', false],
            9 => ['f*o/B*r', 'Foo/Bar', true],
            10 => ['*/Bar', 'Foo/Bar', true],
            11 => ['*/Foo', 'Foo/Bar', false],
            12 => ['*Foo/B*r', 'Foo/Bar', true],
            13 => ['*/Foo/B*r', 'Foo/Bar', false],
            14 => ['foo-*/*', 'foo/bar', false],
            15 => ['foo-*/*', 'foo-x/bar', true],
        ];
    }
}
