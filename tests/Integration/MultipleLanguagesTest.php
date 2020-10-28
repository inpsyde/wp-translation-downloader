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
 *
 * Testing, if we're able to download WordPress + multiple languages
 * and additionally an not found language will not break anything.
 *
 * @see ../../fixtures/multiple-languages/composer.json
 *
 * @package Inpsyde\WpTranslationDownloader\Tests\Integration
 */
class MultipleLanguagesTest extends AbstractIntegrationTestCase
{

    /**
     * @test
     */
    public function testMultipleLanguages(): void
    {
        [$testDirectory, $output] = $this->setupTestCase('multiple-languages');

        static::assertStringContainsString('wordpress-core: found 2 translations', $output);
        static::assertFileExists($testDirectory.'languages');
        static::assertFileExists($testDirectory.'languages/de_DE.mo');
        static::assertFileExists($testDirectory.'languages/en_GB.mo');
    }
}
