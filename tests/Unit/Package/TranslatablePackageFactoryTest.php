<?php

declare(strict_types=1);

namespace Inpsyde\WpTranslationDownloader\Tests\Unit\Package;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Package;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackageFactory;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackageInterface;
use PHPUnit\Framework\TestCase;

class TranslatablePackageFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function testCreateFromOperation(): void
    {
        $pluginConfiguration = new PluginConfiguration([]);
        $packageFactory = new TranslatablePackageFactory($pluginConfiguration);

        $expectedName = 'inpsyde/google-tag-manager';
        $expectedType = 'wordpress-plugin';
        $expectedVersion = '1.0.0';

        $package = new Package($expectedName, $expectedVersion, $expectedVersion);
        $package->setType($expectedType);
        $result = $packageFactory->createFromOperation(new InstallOperation($package));

        static::assertInstanceOf(TranslatablePackageInterface::class, $result);
        static::assertSame($expectedName, $result->getName());
        static::assertSame($expectedType, $result->getType());
        static::assertSame($expectedVersion, $result->getPrettyVersion());
    }

    /**
     * @test
     * @dataProvider provideEndpointData
     */
    public function testResolveEndpoint(
        array $expectedApi,
        array $packageData,
        ?array $expected
    ): void {

        $loader = new ArrayLoader();
        $pluginConfiguration = new PluginConfiguration($expectedApi);

        $packageFactory = new TranslatablePackageFactory($pluginConfiguration);
        $package = $loader->load($packageData);

        static::assertSame($expected, $packageFactory->resolveEndpoint($package));
    }

    /**
     * @return \Generator
     */
    public function provideEndpointData(): \Generator
    {
        $plugin = TranslatablePackageInterface::TYPE_PLUGIN;

        yield "Default" => [
            [],
            [
                'name' => 'inpsyde/google-tag-manager',
                'version' => '1.0',
                'type' => $plugin,
            ],
            [
                'https://api.wordpress.org/translations/plugins/1.0/?slug=google-tag-manager&version=1.0',
                null,
            ],
        ];

        yield "Default unsupported type" => [
            [],
            [
                'name' => 'foo',
                'version' => '1.0',
                'type' => 'bar',
            ],
            null,
        ];

        yield "custom API URL for a matching name" => [
            [
                PluginConfiguration::API => [
                    PluginConfiguration::BY_NAME => [
                        'inpsyde/*' => 'https://inpsyde.com/%projectName%',
                    ],
                ],
            ],
            [
                'name' => 'inpsyde/google-tag-manager',
                'version' => '1.0',
                'type' => $plugin,
            ],
            [
                'https://inpsyde.com/google-tag-manager',
                null,
            ],
        ];

        yield "custom API URL and file type for a matching name" => [
            [
                PluginConfiguration::API => [
                    PluginConfiguration::BY_NAME => [
                        'inpsyde/*' => [
                            'url' => 'https://inpsyde.com/%projectName%.rar',
                            'type' => 'rar',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'inpsyde/google-tag-manager',
                'version' => '1.0',
                'type' => $plugin,
            ],
            [
                'https://inpsyde.com/google-tag-manager.rar',
                'rar',
            ],
        ];

        yield "custom API URL by type" => [
            [
                PluginConfiguration::API => [
                    PluginConfiguration::BY_TYPE => [
                        $plugin => 'https://inpsyde.com/%packageType%/%vendorName%/%projectName%',
                    ],
                ],
            ],
            [
                'name' => 'inpsyde/google-tag-manager',
                'version' => '1.0',
                'type' => $plugin,
            ],
            [
                "https://inpsyde.com/{$plugin}/inpsyde/google-tag-manager",
                null,
            ],
        ];

        yield "Test disable API" => [
            [
                PluginConfiguration::API => [
                    PluginConfiguration::BY_TYPE => [
                        $plugin => false,
                    ],
                ],
            ],
            [
                'name' => 'inpsyde/google-tag-manager',
                'version' => '1.0',
                'type' => $plugin,
            ],
            null,
        ];
    }

    /**
     * @test
     */
    public function testReplacingPlaceholders(): void
    {
        $api = [
            PluginConfiguration::API => [
                PluginConfiguration::BY_NAME => [
                    '*' => '%vendorName%'
                        . '-%projectName%'
                        . '-%packageName%'
                        . '-%packageType%'
                        . '-%packageVersion%',
                ],
            ],
        ];

        $expectedVendor = 'inpsyde';
        $expectedProjectName = 'google-tag-manager';
        $expectedPackageName = 'inpsyde/google-tag-manager';
        $expectedType = TranslatablePackageInterface::TYPE_PLUGIN;
        $expectedVersion = '1.0';

        $expectedUrl = sprintf(
            "%s-%s-%s-%s-%s",
            $expectedVendor,
            $expectedProjectName,
            $expectedPackageName,
            $expectedType,
            $expectedVersion
        );

        $package = new Package($expectedPackageName, $expectedVersion, $expectedVersion);
        $package->setType($expectedType);

        $pluginConfiguration = new PluginConfiguration($api);

        $packageFactory = new TranslatablePackageFactory($pluginConfiguration);

        static::assertSame([$expectedUrl, null], $packageFactory->resolveEndpoint($package));
    }

    /**
     * @test
     * @dataProvider provideDirectoryData
     */
    public function testResolveDirectory(array $input, array $packages): void
    {
        $pluginConfiguration = new PluginConfiguration($input);

        $packageFactory = new TranslatablePackageFactory($pluginConfiguration);
        foreach ($packages as $packageData) {
            $version = $packageData['version'] ?? '1.0';
            $package = new Package($packageData['name'], $version, $version);
            $package->setType($packageData['type']);

            static::assertSame(
                $packageData['expected'],
                $packageFactory->resolveDirectory($package)
            );
        }
    }

    /**
     * @return \Generator
     */
    public function provideDirectoryData(): \Generator
    {
        $cwd = str_replace('\\', '/', getcwd());

        yield 'Default' => [
            [],
            [
                [
                    'name' => 'inpsyde/google-tag-manager',
                    'type' => TranslatablePackageInterface::TYPE_PLUGIN,
                    'version' => '1.0',
                    'expected' => "{$cwd}/plugins/",
                ],
                [
                    'name' => 'foo',
                    'type' => 'bar',
                    'version' => '1.0',
                    'expected' => null,
                ],
            ],
        ];

        yield 'Custom type' => [
            [
                'directories' => [
                    PluginConfiguration::BY_TYPE => [
                        'custom' => 'custom-path',
                    ],
                ],
            ],
            [
                [
                    'name' => 'custom/package',
                    'type' => 'custom',
                    'version' => '1.0',
                    'expected' => "{$cwd}/custom-path/",
                ],
            ],
        ];

        yield 'Disable type' => [
            [
                'directories' => [
                    PluginConfiguration::BY_TYPE => [
                        TranslatablePackageInterface::TYPE_PLUGIN => false,
                    ],
                ],
            ],
            [
                [
                    'name' => 'inpsyde/google-tag-manager',
                    'type' => TranslatablePackageInterface::TYPE_PLUGIN,
                    'expected' => null,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideRemoveEmptyQueryParams
     */
    public function testRemoveEmptyQueryParams(string $expected, string $input): void
    {
        $packageFactory = new class extends TranslatablePackageFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct()
            {
            }

            public function removeEmptyQueryParams(string $url): string
            {
                return parent::removeEmptyQueryParams($url);
            }
        };

        static::assertSame($expected, $packageFactory->removeEmptyQueryParams($input));
    }

    /**
     * @return \Generator
     */
    public function provideRemoveEmptyQueryParams(): \Generator
    {
        yield 'no empty query param' => [
            "https://api.wordpress.org/translations/core/1.0/",
            "https://api.wordpress.org/translations/core/1.0/",
        ];

        yield 'empty query param' => [
            "https://api.wordpress.org/translations/core/1.0/",
            "https://api.wordpress.org/translations/core/1.0/?version=",
        ];

        yield 'multiple query params' => [
            "https://api.wordpress.org/translations/core/1.0/?foo=bar",
            "https://api.wordpress.org/translations/core/1.0/?foo=bar&version=",
        ];

        yield 'multiple query params 2' => [
            "https://api.wordpress.org/translations/core/1.0/?foo=bar",
            "https://api.wordpress.org/translations/core/1.0/?version=&foo=bar",
        ];

        yield 'multiple empty query params ' => [
            "https://api.wordpress.org/translations/core/1.0/?baz=baz",
            "https://api.wordpress.org/translations/core/1.0/?foo=&bar&baz=baz",
        ];
    }
}
