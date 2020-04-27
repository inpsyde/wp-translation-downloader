<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader\Tests\Unit\Config;

use Inpsyde\WpTranslationDownloader\Config\PluginConfiguration;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackage;
use PHPUnit\Framework\TestCase;

class PluginConfigurationTest extends TestCase
{

    /**
     * @throws \Throwable
     */
    public function testBasic()
    {
        $testee = new PluginConfiguration([]);

        static::assertEmpty($testee->allowedLanguages());
        static::assertEmpty($testee->apiBy(PluginConfiguration::API_BY_NAME));
        static::assertNotEmpty($testee->apiBy(PluginConfiguration::API_BY_TYPE));
        static::assertNotEmpty($testee->isValid());
        static::assertTrue($testee->autorun());
    }

    /**
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

    public function provideExcludes()
    {
        yield [
            ['inpsyde/*'],
            [
                'inpsyde/google-tag-manager' => true,
                'wpackagist-plugin/wordpress-seo' => false,
            ],
        ];

        yield [
            ['inpsyde/google-tag-manager', 'wpackagist-plugins/*'],
            [
                'inpsyde/google-tag-manager' => true,
                'wpackagist-plugins/wordpress-seo' => true,
                'wpackagist-themes/twentytwenty' => false,
                'inpsyde/multilingualpress' => false,
            ],
        ];
    }

    public function testPackageTypeSupport()
    {
        $testee = new PluginConfiguration([]);

        static::assertTrue($testee->isPackageTypeSupported(TranslatablePackage::TYPE_PLUGIN));
        static::assertTrue($testee->isPackageTypeSupported(TranslatablePackage::TYPE_LIBRARY));
        static::assertTrue($testee->isPackageTypeSupported(TranslatablePackage::TYPE_CORE));
        static::assertTrue($testee->isPackageTypeSupported(TranslatablePackage::TYPE_THEME));
        static::assertFalse($testee->isPackageTypeSupported('foo'));
    }

    public function testApiNames()
    {
        $expected = ['foo' => 'bar'];
        $apiInput = [
            'api' => [
                PluginConfiguration::API_BY_NAME => $expected,
            ],
        ];
        $testee = new PluginConfiguration($apiInput);

        $apiResult = $testee->apiBy(PluginConfiguration::API_BY_NAME);

        static::assertSame($expected, $apiResult);
    }

    public function testApiReplaceType()
    {
        $expected = 'foo';
        $apiInput = [
            'api' => [
                PluginConfiguration::API_BY_TYPE => [
                    TranslatablePackage::TYPE_PLUGIN => $expected,
                ],
            ],
        ];

        $testee = new PluginConfiguration($apiInput);

        $apiResult = $testee->apiBy(PluginConfiguration::API_BY_TYPE);
        static::assertSame($expected, $apiResult[TranslatablePackage::TYPE_PLUGIN]);
    }
}