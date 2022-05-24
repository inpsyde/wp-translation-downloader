<?php

declare(strict_types=1);

namespace Inpsyde\WpTranslationDownloader\Tests\Unit\Package;

use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\Package\PackageInterface;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackage;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackageFactory;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackageInterface;
use Inpsyde\WpTranslationDownloader\PackageNameResolver;
use PHPUnit\Framework\TestCase;

class TranslatablePackageFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function testCreateFromOperation(): void
    {
        $pluginConfiguration = new PluginConfiguration([]);
        $testee = new TranslatablePackageFactory($pluginConfiguration);

        $expectedName = 'inpsyde/google-tag-manager';
        $expectedType = 'wordpress-plugin';
        $expectedVersion = '1.0.0';

        $packageStub = \Mockery::mock(PackageInterface::class);
        $packageStub->expects('getName')->andReturn($expectedName);
        $packageStub->expects('getType')->andReturn($expectedType);
        $packageStub->expects('getPrettyVersion')->andReturn($expectedVersion);
        $packageStub->expects('getVersion')->andReturn($expectedVersion);

        $operationStub = \Mockery::mock(OperationInterface::class);
        $operationStub->expects('getPackage')->andReturn($packageStub);

        $result = $testee->createFromOperation($operationStub);

        static::assertInstanceOf(TranslatablePackageInterface::class, $result);
        static::assertSame($expectedName, $result->getName());
        static::assertSame($expectedType, $result->getType());
        static::assertSame($expectedVersion, $result->getPrettyVersion());
    }

    /**
     * @dataProvider provideEndpointData
     *
     * @param array $expectedApi
     * @param array $packages
     *
     * @test
     */
    public function testResolveEndpoint(array $expectedApi, array $packages): void
    {
        $pluginConfiguration = new PluginConfiguration($expectedApi);

        $testee = new TranslatablePackageFactory($pluginConfiguration);
        foreach ($packages as $package) {
            $packageStub = \Mockery::mock(PackageInterface::class);
            $packageStub->expects('getName')->andReturn($package['name']);
            $packageStub->expects('getType')->andReturn($package['type']);
            $packageStub->expects('getPrettyVersion')->andReturn($package['version']);

            static::assertSame(
                $package['expected'],
                $testee->resolveEndpoint($packageStub)
            );
        }
    }

    public function provideEndpointData(): \Generator
    {
        yield "Default" => [
            [],
            [
                [
                    'name' => 'inpsyde/google-tag-manager',
                    'version' => '1.0',
                    'type' => TranslatablePackage::TYPE_PLUGIN,
                    'expected' => 'https://api.wordpress.org/translations/plugins/1.0/?slug=google-tag-manager&version=1.0',
                ],
                [
                    'name' => 'foo',
                    'version' => '1.0',
                    'type' => 'bar',
                    'expected' => null,
                ],
            ],
        ];

        yield "custom API for a matching name" => [
            [
                'api' => [
                    PluginConfiguration::BY_NAME => [
                        'inpsyde/*' => 'https://inpsyde.com/%projectName%',
                    ],
                ],
            ],
            [
                [
                    'name' => 'inpsyde/google-tag-manager',
                    'version' => '1.0',
                    'type' => TranslatablePackage::TYPE_PLUGIN,
                    'expected' => 'https://inpsyde.com/google-tag-manager',
                ],
            ],
        ];

        yield "Test by type" => [
            [
                'api' => [
                    PluginConfiguration::BY_TYPE => [
                        TranslatablePackage::TYPE_PLUGIN => 'https://inpsyde.com/%packageType%/%vendorName%/%projectName%',
                    ],
                ],
            ],
            [
                [
                    'name' => 'inpsyde/google-tag-manager',
                    'version' => '1.0',
                    'type' => TranslatablePackage::TYPE_PLUGIN,
                    'expected' => "https://inpsyde.com/"
                        . TranslatablePackage::TYPE_PLUGIN
                        . "/inpsyde/google-tag-manager",
                ],
            ],
        ];

        yield "Test disable API" => [
            [
                'api' => [
                    PluginConfiguration::BY_TYPE => [
                        TranslatablePackage::TYPE_PLUGIN => false,
                    ],
                ],
            ],
            [
                [
                    'name' => 'inpsyde/google-tag-manager',
                    'version' => '1.0',
                    'type' => TranslatablePackage::TYPE_PLUGIN,
                    'expected' => null,
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function testReplacingPlaceholders(): void
    {
        $api = [
            'api' => [
                PluginConfiguration::BY_NAME => [
                    '*' => '%vendorName%-%projectName%-%packageName%-%packageType%-%packageVersion%',
                ],
            ],
        ];

        $expectedVendor = 'inpsyde';
        $expectedProjectName = 'google-tag-manager';
        $expectedPackageName = 'inpsyde/google-tag-manager';
        $expectedType = TranslatablePackage::TYPE_PLUGIN;
        $expectedVersion = '1.0';

        $expected = "{$expectedVendor}-{$expectedProjectName}-{$expectedPackageName}-{$expectedType}-{$expectedVersion}";

        $packageStub = \Mockery::mock(PackageInterface::class);
        $packageStub->expects('getName')->andReturn($expectedPackageName);
        $packageStub->expects('getType')->andReturn($expectedType);
        $packageStub->expects('getPrettyVersion')->andReturn($expectedVersion);

        $pluginConfiguration = new PluginConfiguration($api);

        $testee = new TranslatablePackageFactory($pluginConfiguration);

        static::assertSame($expected, $testee->resolveEndpoint($packageStub));
    }

    /**
     * @dataProvider provideDirectoryData
     *
     * @param array $input
     * @param array $packages
     *
     * @test
     */
    public function testResolveDirectory(array $input, array $packages): void
    {
        $pluginConfiguration = new PluginConfiguration($input);

        $testee = new TranslatablePackageFactory($pluginConfiguration);
        foreach ($packages as $package) {
            $packageStub = \Mockery::mock(PackageInterface::class);
            $packageStub->expects('getName')->andReturn($package['name']);
            $packageStub->expects('getType')->andReturn($package['type']);
            $packageStub->expects('getPrettyVersion')->andReturn($package['version'] ?? '1.0');

            static::assertSame(
                $package['expected'],
                $testee->resolveDirectory($packageStub)
            );
        }
    }

    public function provideDirectoryData(): \Generator
    {
        yield 'Default' => [
            [],
            [
                [
                    'name' => 'inpsyde/google-tag-manager',
                    'type' => TranslatablePackage::TYPE_PLUGIN,
                    'version' => '1.0',
                    'expected' => getcwd() . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR,
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
                    'expected' => getcwd() . DIRECTORY_SEPARATOR . 'custom-path' . DIRECTORY_SEPARATOR,
                ],
            ],
        ];

        yield 'Disable type' => [
            [
                'directories' => [
                    PluginConfiguration::BY_TYPE => [
                        TranslatablePackage::TYPE_PLUGIN => false,
                    ],
                ],
            ],
            [
                [
                    'name' => 'inpsyde/google-tag-manager',
                    'type' => TranslatablePackage::TYPE_PLUGIN,
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
        $testee = new class extends TranslatablePackageFactory {
            public function __construct()
            {
            }

            public function removeEmptyQueryParams(string $url): string
            {
                return parent::removeEmptyQueryParams($url);
            }
        };

        static::assertSame($expected, $testee->removeEmptyQueryParams($input));
    }

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