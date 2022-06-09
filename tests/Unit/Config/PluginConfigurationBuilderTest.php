<?php

declare(strict_types=1);

namespace Inpsyde\WpTranslationDownloader\Tests\Unit\Config;

use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Config\PluginConfigurationBuilder;
use PHPUnit\Framework\TestCase;

class PluginConfigurationBuilderTest extends TestCase
{
    /**
     * @test
     */
    public function testConfigIsNullIfNoConfig(): void
    {
        $result = $this->factoryBuilder()->build([]);
        static::assertNull($result);
    }

    /**
     * @test
     */
    public function testBuildFromEmptyExtra(): void
    {
        $extra = [PluginConfigurationBuilder::KEY => []];
        $result = $this->factoryBuilder()->build($extra);

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

        $result = $this->factoryBuilder()->build($extra);
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
        static::assertSame($expected, $this->factoryBuilder()->validateSchema($input));
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

        yield 'api.names with custom type' => [
            [
                'languages' => ['de_DE'],
                'api' => [
                    'names' => [
                        'inpsyde/google-tag-manager' => [
                            'url' => 'https://www.inpsyde.com/',
                            'type' => 'tar'
                        ]
                    ]
                ],
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

        yield 'virtual-packages' => [
            ['languages' => ['de_DE'], 'virtual-packages' => [['name' => 'wordpress/core', 'type' => 'wordpress-core']]],
            true
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

        yield 'Incorrect virtual-packages - missing name' => [
            ['languages' => ['de_DE'], 'virtual-packages' => [['type' => 'wordpress-core']]],
            false
        ];

        yield 'Incorrect virtual-packages - missing type' => [
            ['languages' => ['de_DE'], 'virtual-packages' => [['name' => 'wordpress/core']]],
            false
        ];

        yield 'api.names with incorrect schema' => [
            [
                'languages' => ['de_DE'],
                'api' => [
                    'names' => [
                        'inpsyde/google-tag-manager' => [
                            'url' => 'https://www.inpsyde.com/',
                            'ext' => 'tar'
                        ]
                    ]
                ],
            ],
            false,
        ];
    }

    /**
     * @return PluginConfigurationBuilder
     */
    private function factoryBuilder(): PluginConfigurationBuilder
    {
        return new PluginConfigurationBuilder(new NullIO(), getenv('RESOURCES_DIR') ?: '');
    }
}