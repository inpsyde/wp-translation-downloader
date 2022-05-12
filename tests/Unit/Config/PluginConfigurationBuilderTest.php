<?php

declare(strict_types=1);

namespace Inpsyde\WpTranslationDownloader\Tests\Unit\Config;

use Composer\IO\IOInterface;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Config\PluginConfigurationBuilder;
use PHPUnit\Framework\TestCase;

class PluginConfigurationBuilderTest extends TestCase
{
    /**
     * @test
     */
    public function testBasic(): void
    {
        $ioStub = \Mockery::mock(IOInterface::class);
        $ioStub->allows('write');

        $result = (new PluginConfigurationBuilder($ioStub))->build([]);
        static::assertNull($result);
    }

    /**
     * @test
     */
    public function testBuildFromExtra(): void
    {
        $extra = [PluginConfigurationBuilder::KEY => []];

        $ioStub = \Mockery::mock(IOInterface::class);
        $ioStub->allows('writeError');

        $result = (new PluginConfigurationBuilder($ioStub))->build($extra);
        static::assertNull($result);
    }

    /**
     * @test
     */
    public function testBuildMinimalisticValid(): void
    {
        $extra = [
            PluginConfigurationBuilder::KEY => [
                "languages" => ["de_DE"],
            ],
        ];

        $ioStub = \Mockery::mock(IOInterface::class);

        $result = (new PluginConfigurationBuilder($ioStub))->build($extra);
        static::assertInstanceOf(PluginConfiguration::class, $result);
    }

    /**
     * @test
     * @dataProvider provideValidValidateSchema
     * @dataProvider provideInvalidValidateSchema
     *
     * @param array $input
     * @param bool $expected
     */
    public function testValidateSchema(array $input, bool $expected): void
    {
        $ioStub = \Mockery::mock(IOInterface::class);
        $ioStub->allows('writeError');

        static::assertSame(
            $expected,
            (new PluginConfigurationBuilder($ioStub))->validateSchema($input)
        );
    }

    /**
     * Collection of valid inputs.
     *
     * @return \Generator
     */
    public function provideValidValidateSchema(): \Generator
    {
        yield 'Minimum requirement' => [
            ['languages' => ['de_DE']],
            true,
        ];

        yield 'languages - multiple ones' => [
            ['languages' => ['de_DE', 'de_CH']],
            true,
        ];

        yield 'languageRootDir' => [
            ['languages' => ['de_DE'], 'languageRootDir' => '/foo/bar/'],
            true,
        ];

        yield 'directories.names' => [
            ['languages' => ['de_DE'], 'directories' => ['names' => ['inpsyde/google-tag-manager' => '/foo/']]],
            true,
        ];

        yield 'directories.types' => [
            ['languages' => ['de_DE'], 'directories' => ['names' => ['wordpress-plugin' => '/foo/']]],
            true,
        ];

        yield 'api.names' => [
            [
                'languages' => ['de_DE'],
                'api' => ['names' => ['inpsyde/google-tag-manager' => 'https://www.inpsyde.com/']],
            ],
            true,
        ];

        yield 'api.types' => [
            ['languages' => ['de_DE'], 'api' => ['types' => ['wordpress-plugin' => 'https://www.inpsyde.com/']]],
            true,
        ];

        yield 'excludes' => [
            ['languages' => ['de_DE'], 'excludes' => ['inpsyde/google-tag-manager']],
            true,
        ];
    }

    /**
     * Collection of different invalid inputs.
     *
     * @return \Generator
     */
    public function provideInvalidValidateSchema(): \Generator
    {
        yield 'Empty input' => [
            [],
            false,
        ];

        yield 'Missing languages' => [
            ['languageRootDir' => '/foo/'],
            false,
        ];

        yield 'Incorrect languageRootDir' => [
            ['languages' => ['de_DE'], 'languageRootDir' => false],
            false,
        ];
    }
}