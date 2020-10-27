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

namespace Inpsyde\WpTranslationDownloader\Tests\Integration;

/**
 * Testing some default configuration based on the README.md example
 * by just downloading WordPress + german translation-set.
 *
 * @see ../../fixtures/default-configuration/composer.json
 *
 * @package Inpsyde\WpTranslationDownloader\Tests\Integration
 */
class DefaultConfigurationTest extends AbstractIntegrationTestCase
{

    /**
     * @test
     */
    public function testDefaultConfigurationTest(): void
    {
        $testDirectory = $this->setupTestCase('default-configuration');

        static::assertFileExists($testDirectory.'languages');
        // default folders
        static::assertFileExists($testDirectory.'languages/library');
        static::assertFileExists($testDirectory.'languages/plugins');
        static::assertFileExists($testDirectory.'languages/themes');
        // downloaded german translation file
        static::assertFileExists($testDirectory.'languages/de_DE.mo');
    }
}
