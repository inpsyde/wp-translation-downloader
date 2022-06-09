<?php

declare(strict_types=1);

namespace Inpsyde\WpTranslationDownloader\Tests\Unit\Config;

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
                PluginConfiguration::LANGUAGES => ["de_DE"],
            ],
        ];

        $result = $this->factoryBuilder()->build($extra);
        static::assertInstanceOf(PluginConfiguration::class, $result);
    }

    /**
     * @test
     * @dataProvider provideValidValidateSchema
     * @dataProvider provideInvalidValidateSchema
     */
    public function testValidateSchema(array $input, bool $expected): void
    {
        static::assertSame($expected, $this->factoryBuilder()->validateSchema($input));
    }

    /**
     * @return \Generator
     */
    public function provideValidValidateSchema(): \Generator
    {
        yield 'Minimum requirement' => [
            [PluginConfiguration::LANGUAGES => ['de_DE']],
            true,
        ];

        yield 'languages - multiple ones' => [
            [PluginConfiguration::LANGUAGES => ['de_DE', 'de_CH']],
            true,
        ];

        yield 'languageRootDir' => [
            [PluginConfiguration::LANGUAGES => ['de_DE'], 'languageRootDir' => '/foo/bar/'],
            true,
        ];

        yield 'directories.names' => [
            [
                PluginConfiguration::LANGUAGES => ['de_DE'],
                PluginConfiguration::DIRECTORIES => [
                    PluginConfiguration::BY_NAME => ['inpsyde/google-tag-manager' => '/foo/'],
                ],
            ],
            true,
        ];

        yield 'directories.types' => [
            [
                PluginConfiguration::LANGUAGES => ['de_DE'],
                PluginConfiguration::DIRECTORIES => [
                    PluginConfiguration::BY_NAME => ['wordpress-plugin' => '/foo/'],
                ],
            ],
            true,
        ];

        yield 'api.names' => [
            [
                PluginConfiguration::LANGUAGES => ['de_DE'],
                PluginConfiguration::API => [
                    PluginConfiguration::BY_NAME => [
                        'inpsyde/google-tag-manager' => 'https://www.inpsyde.com/',
                    ],
                ],
            ],
            true,
        ];

        yield 'api.names with custom type' => [
            [
                PluginConfiguration::LANGUAGES => ['de_DE'],
                PluginConfiguration::API => [
                    PluginConfiguration::BY_NAME => [
                        'inpsyde/google-tag-manager' => [
                            'url' => 'https://www.inpsyde.com/',
                            'type' => 'tar',
                        ],
                    ],
                ],
            ],
            true,
        ];

        yield 'api.types' => [
            [
                PluginConfiguration::LANGUAGES => ['de_DE'],
                PluginConfiguration::API => [
                    PluginConfiguration::BY_TYPE => [
                        'wordpress-plugin' => 'https://www.inpsyde.com/',
                    ],
                ],
            ],
            true,
        ];

        yield 'excludes' => [
            [
                PluginConfiguration::LANGUAGES => ['de_DE'],
                PluginConfiguration::EXCLUDES => ['inpsyde/google-tag-manager'],
            ],
            true,
        ];

        yield 'virtual-packages' => [
            [
                PluginConfiguration::LANGUAGES => ['de_DE'],
                PluginConfiguration::VIRTUAL_PACKAGES => [
                    [
                        'name' => 'wordpress/core',
                        'type' => 'wordpress-core',
                    ],
                ],
            ],
            true,
        ];
    }

    /**
     * @return \Generator
     */
    public function provideInvalidValidateSchema(): \Generator
    {
        yield 'Empty input' => [
            [],
            false,
        ];

        yield 'Missing languages' => [
            [PluginConfiguration::LANGUAGES_ROOT_DIR => '/foo/'],
            false,
        ];

        yield 'Incorrect languageRootDir' => [
            [
                PluginConfiguration::LANGUAGES => ['de_DE'],
                PluginConfiguration::LANGUAGES_ROOT_DIR => false,
            ],
            false,
        ];

        yield 'Incorrect virtual-packages - missing name' => [
            [
                PluginConfiguration::LANGUAGES => ['de_DE'],
                PluginConfiguration::VIRTUAL_PACKAGES => [['type' => 'wordpress-core']],
            ],
            false,
        ];

        yield 'Incorrect virtual-packages - missing type' => [
            [
                PluginConfiguration::LANGUAGES => ['de_DE'],
                PluginConfiguration::VIRTUAL_PACKAGES => [['name' => 'wordpress/core']],
            ],
            false,
        ];

        yield 'api.names with incorrect schema' => [
            [
                PluginConfiguration::LANGUAGES => ['de_DE'],
                PluginConfiguration::API => [
                    PluginConfiguration::BY_NAME => [
                        'inpsyde/google-tag-manager' => [
                            'url' => 'https://www.inpsyde.com/',
                            'ext' => 'tar',
                        ],
                    ],
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
