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

use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackage;
use PHPUnit\Framework\TestCase;

class PluginConfigurationTest extends TestCase
{
    /**
     * @test
     */
    public function testBasic(): void
    {
        $testee = new PluginConfiguration([]);

        static::assertEmpty($testee->allowedLanguages());
        static::assertTrue($testee->autorun());

        // Default API configuration
        static::assertEmpty($testee->apiBy(PluginConfiguration::BY_NAME));
        static::assertNotEmpty($testee->apiBy(PluginConfiguration::BY_TYPE));
        static::assertNotEmpty($testee->isValid());

        // Default directory configuration
        static::assertNotEmpty($testee->languageRootDir());
        static::assertEmpty($testee->directoryBy(PluginConfiguration::BY_NAME));
        static::assertNotEmpty($testee->directoryBy(PluginConfiguration::BY_TYPE));
    }

    /**
     * @test
     * @dataProvider provideExcludes
     * @throws \Throwable
     */
    public function testExcludes(array $excludes, array $expectedResults)
    {
        $testee = new PluginConfiguration(['excludes' => $excludes]);

        foreach ($expectedResults as $packageName => $expected) {
            static::assertSame(
                $expected,
                $testee->doExclude($packageName),
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
            'api' => [
                PluginConfiguration::BY_NAME => $expected,
            ],
        ];
        $testee = new PluginConfiguration($apiInput);

        $apiResult = $testee->apiBy(PluginConfiguration::BY_NAME);

        static::assertSame($expected, $apiResult);
    }

    /**
     * @test
     */
    public function testApiReplaceType(): void
    {
        $expected = 'foo';
        $apiInput = [
            'api' => [
                PluginConfiguration::BY_TYPE => [
                    TranslatablePackage::TYPE_PLUGIN => $expected,
                ],
            ],
        ];

        $testee = new PluginConfiguration($apiInput);

        $apiResult = $testee->apiBy(PluginConfiguration::BY_TYPE);
        static::assertSame($expected, $apiResult[TranslatablePackage::TYPE_PLUGIN]);
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
            'directories' => [
                PluginConfiguration::BY_TYPE => [
                    TranslatablePackage::TYPE_CORE => $expectedCore,
                    TranslatablePackage::TYPE_PLUGIN => $expectedPlugin,
                    TranslatablePackage::TYPE_THEME => $expectedTheme,
                    TranslatablePackage::TYPE_LIBRARY => $expectedLibrary,
                    'custom' => $expectedCustom,
                ],
            ],
        ];
        $testee = new PluginConfiguration($input);

        $directories = $testee->directoryBy(PluginConfiguration::BY_TYPE);

        static::assertSame($expectedCore, $directories[TranslatablePackage::TYPE_CORE]);
        static::assertSame($expectedLibrary, $directories[TranslatablePackage::TYPE_LIBRARY]);
        static::assertSame($expectedPlugin, $directories[TranslatablePackage::TYPE_PLUGIN]);
        static::assertSame($expectedTheme, $directories[TranslatablePackage::TYPE_THEME]);
        static::assertSame($expectedCustom, $directories['custom']);
    }
}
