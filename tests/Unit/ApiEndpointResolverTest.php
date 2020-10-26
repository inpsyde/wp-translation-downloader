<?php

/*
 * This file is part of the Assets package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Inpsyde\WpTranslationDownloader\Tests\Unit;

use Composer\Package\PackageInterface;
use Inpsyde\WpTranslationDownloader\ApiEndpointResolver;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackage;
use PHPUnit\Framework\TestCase;

class ApiEndpointResolverTest extends TestCase
{

    /**
     * @dataProvider provideData
     *
     * @param array $expectedApi
     * @param array $packages
     *
     * @throws \Throwable
     */
    public function testBasic(array $expectedApi, array $packages)
    {
        $pluginConfiguration = new PluginConfiguration($expectedApi);

        $testee = new ApiEndpointResolver($pluginConfiguration);
        foreach ($packages as $package) {
            $packageStub = \Mockery::mock(PackageInterface::class);
            $packageStub->expects('getName')->andReturn($package['name']);
            $packageStub->expects('getType')->andReturn($package['type']);
            $packageStub->expects('getPrettyVersion')->andReturn($package['version']);

            static::assertSame(
                $package['expectedEndpoint'],
                $testee->resolve($packageStub)
            );
        }
    }

    public function provideData()
    {
        // Testing default API
        yield [
            [],
            [
                [
                    'name' => 'inpsyde/google-tag-manager',
                    'version' => '1.0',
                    'type' => TranslatablePackage::TYPE_PLUGIN,
                    'expectedEndpoint' => 'https://api.wordpress.org/translations/plugins/1.0/?slug=google-tag-manager&version=1.0',
                ],
                [
                    'name' => 'foo',
                    'version' => '1.0',
                    'type' => 'bar',
                    'expectedEndpoint' => null,
                ],
            ],
        ];

        // Testing a custom API for a matching "name"
        yield [
            [
                'api' => [
                    PluginConfiguration::API_BY_NAME => [
                        'inpsyde/*' => 'https://inpsyde.com/%projectName%',
                    ],
                ],
            ],
            [
                [
                    'name' => 'inpsyde/google-tag-manager',
                    'version' => '1.0',
                    'type' => TranslatablePackage::TYPE_PLUGIN,
                    'expectedEndpoint' => 'https://inpsyde.com/google-tag-manager',
                ],
            ],
        ];

        yield [
            [
                'api' => [
                    PluginConfiguration::API_BY_TYPE => [
                        TranslatablePackage::TYPE_PLUGIN => 'https://inpsyde.com/%packageType%/%vendorName%/%projectName%',
                    ],
                ],
            ],
            [
                [
                    'name' => 'inpsyde/google-tag-manager',
                    'version' => '1.0',
                    'type' => TranslatablePackage::TYPE_PLUGIN,
                    'expectedEndpoint' => "https://inpsyde.com/"
                        .TranslatablePackage::TYPE_PLUGIN
                        ."/inpsyde/google-tag-manager",
                ],
            ],
        ];
    }

    public function testReplacingPlaceholders()
    {
        $api = [
            'api' => [
                PluginConfiguration::API_BY_NAME => [
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

        $testee = new ApiEndpointResolver($pluginConfiguration);

        static::assertSame($expected, $testee->resolve($packageStub));
    }
}
