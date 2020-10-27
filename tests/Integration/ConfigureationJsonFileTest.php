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
 * Testing if the given example from README.md with configuration
 * via JSON-file runs and downloads WordPress + germany translation-set.
 *
 * @see ../../fixtures/configuration-json-file/composer.json
 *
 * @package Inpsyde\WpTranslationDownloader\Tests\Integration
 */
class ConfigureationJsonFileTest extends AbstractIntegrationTestCase
{

    /**
     *
     * @test
     */
    public function testConfigurationJsonFile(): void
    {
        $testDirectory = $this->setupTestCase('configuration-json-file');

        static::assertFileExists($testDirectory.'languages');
        static::assertFileExists($testDirectory.'languages/de_DE.mo');
    }
}