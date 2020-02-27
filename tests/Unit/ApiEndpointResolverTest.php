<?php declare(strict_types=1); # -*- coding: utf-8 -*-

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
        yield [
            // default API
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
    }
}