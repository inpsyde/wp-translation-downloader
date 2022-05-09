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
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\DirectoryResolver;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackage;
use PHPUnit\Framework\TestCase;

class DirectoryResolverTest extends TestCase
{
    /**
     * @dataProvider provideData
     *
     * @param array $input
     * @param array $packages
     *
     * @test
     */
    public function testBasic(array $input, array $packages): void
    {
        $pluginConfiguration = new PluginConfiguration($input);

        $testee = new DirectoryResolver($pluginConfiguration);
        foreach ($packages as $package) {
            $packageStub = \Mockery::mock(PackageInterface::class);
            $packageStub->expects('getName')->andReturn($package['name']);
            $packageStub->expects('getType')->andReturn($package['type']);
            $packageStub->expects('getPrettyVersion')->andReturn($package['version'] ?? '1.0');

            static::assertSame(
                $package['expected'],
                $testee->resolve($packageStub)
            );
        }
    }

    public function provideData(): \Generator
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
     */
    public function testReplacingPlaceholders(): void
    {
        $api = [
            'directories' => [
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

        $expected = getcwd()
            . DIRECTORY_SEPARATOR
            . "{$expectedVendor}-{$expectedProjectName}-{$expectedPackageName}-{$expectedType}-{$expectedVersion}"
            . DIRECTORY_SEPARATOR;

        $packageStub = \Mockery::mock(PackageInterface::class);
        $packageStub->expects('getName')->andReturn($expectedPackageName);
        $packageStub->expects('getType')->andReturn($expectedType);
        $packageStub->expects('getPrettyVersion')->andReturn($expectedVersion);

        $pluginConfiguration = new PluginConfiguration($api);

        $testee = new DirectoryResolver($pluginConfiguration);

        static::assertSame($expected, $testee->resolve($packageStub));
    }
}
