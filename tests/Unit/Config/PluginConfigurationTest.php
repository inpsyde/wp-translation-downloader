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

namespace Inpsyde\WpTranslationDownloader\Tests\Unit\Config;

use Composer\Package\PackageInterface;
use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackageInterface;
use PHPUnit\Framework\TestCase;

class PluginConfigurationTest extends TestCase
{
    /**
     * @test
     */
    public function testBasic(): void
    {
        $configuration = new PluginConfiguration([]);

        static::assertEmpty($configuration->allowedLanguages());
        static::assertEmpty($configuration->virtualPackages());
        static::assertTrue($configuration->autorun());

        // Default API configuration
        static::assertEmpty($configuration->endpointsByName());
        static::assertNotEmpty($configuration->endpointsByType());

        // Default directory configuration
        static::assertNotEmpty($configuration->languageRootDir());
        static::assertEmpty($configuration->directoriesByName());
        static::assertNotEmpty($configuration->directoriesByType());
    }

    /**
     * @test
     */
    public function testVirtualPackages(): void
    {
        $expected = [];
        for ($i = 1; $i < random_int(3, 8); $i++) {
            $expected[] = [
                'name' => sprintf('foo/name-%d', $i),
                'type' => sprintf('type-%d', $i),
                'version' => sprintf('%d.0.0', $i),
            ];
        }

        $configuration = new PluginConfiguration(
            [PluginConfiguration::VIRTUAL_PACKAGES => $expected]
        );

        $virtualPackages = $configuration->virtualPackages();
        static::assertSame(count($expected), count($virtualPackages));

        foreach ($virtualPackages as $i => $virtualPackage) {
            static::assertTrue($virtualPackage instanceof PackageInterface);
            static::assertSame($expected[$i]['name'], $virtualPackage->getName());
            static::assertSame($expected[$i]['type'], $virtualPackage->getType());
            static::assertSame($expected[$i]['version'], $virtualPackage->getVersion());
        }
    }

    /**
     * @test
     * @dataProvider provideExcludes
     */
    public function testExcludes(array $excludes, array $expectedResults): void
    {
        $configuration = new PluginConfiguration([PluginConfiguration::EXCLUDES => $excludes]);

        foreach ($expectedResults as $packageName => $expected) {
            static::assertSame(
                $expected,
                $configuration->shouldExclude($packageName),
                sprintf(
                    'Tested %s to be %s',
                    $packageName,
                    $expected
                        ? 'excluded'
                        : 'not excluded'
                )
            );
        }
    }

    /**
     * @return \Generator
     */
    public function provideExcludes(): \Generator
    {
        yield 'Exclude wildcard' => [
            ['inpsyde/*'],
            [
                'inpsyde/google-tag-manager' => true,
                'wpackagist-plugin/wordpress-seo' => false,
            ],
        ];

        yield 'Exclude specific and wildcard' => [
            ['inpsyde/google-tag-manager', 'wpackagist-plugins/*'],
            [
                'inpsyde/google-tag-manager' => true,
                'wpackagist-plugins/wordpress-seo' => true,
                'wpackagist-themes/twentytwenty' => false,
                'inpsyde/multilingualpress' => false,
            ],
        ];
    }

    /**
     * @test
     */
    public function testApiNames(): void
    {
        $expected = ['foo' => 'bar'];
        $apiInput = [
            PluginConfiguration::API => [
                PluginConfiguration::BY_NAME => $expected,
            ],
        ];
        $configuration = new PluginConfiguration($apiInput);

        $apiResult = $configuration->endpointsByName();

        static::assertSame($expected, $apiResult);
    }

    /**
     * @test
     */
    public function testApiReplaceType(): void
    {
        $expected = 'foo';
        $apiInput = [
            PluginConfiguration::API => [
                PluginConfiguration::BY_TYPE => [
                    TranslatablePackageInterface::TYPE_PLUGIN => $expected,
                ],
            ],
        ];

        $configuration = new PluginConfiguration($apiInput);

        $apiResult = $configuration->endpointsByType();
        static::assertSame($expected, $apiResult[TranslatablePackageInterface::TYPE_PLUGIN]);
    }

    /**
     * @test
     */
    public function testDirectories(): void
    {
        $expectedPlugin = '1';
        $expectedCore = '2';
        $expectedTheme = '3';
        $expectedLibrary = '4';
        $expectedCustom = '5';

        $input = [
            PluginConfiguration::DIRECTORIES => [
                PluginConfiguration::BY_TYPE => [
                    TranslatablePackageInterface::TYPE_CORE => $expectedCore,
                    TranslatablePackageInterface::TYPE_PLUGIN => $expectedPlugin,
                    TranslatablePackageInterface::TYPE_THEME => $expectedTheme,
                    TranslatablePackageInterface::TYPE_LIBRARY => $expectedLibrary,
                    'custom' => $expectedCustom,
                ],
            ],
        ];
        $configuration = new PluginConfiguration($input);

        $dirs = $configuration->directoriesByType();

        static::assertSame($expectedCore, $dirs[TranslatablePackageInterface::TYPE_CORE]);
        static::assertSame($expectedLibrary, $dirs[TranslatablePackageInterface::TYPE_LIBRARY]);
        static::assertSame($expectedPlugin, $dirs[TranslatablePackageInterface::TYPE_PLUGIN]);
        static::assertSame($expectedTheme, $dirs[TranslatablePackageInterface::TYPE_THEME]);
        static::assertSame($expectedCustom, $dirs['custom']);
    }
}
