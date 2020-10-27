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
 * Testing if setting
 *
 *      "auto-run": false
 *
 * does not install anything and just creating an empty "languages" folder.
 *
 * @see ../fixtures/disable-autorun/composer.json
 *
 * @package Inpsyde\WpTranslationDownloader\Tests\Integration
 */
class AutoRunDisabledTest extends AbstractIntegrationTestCase
{

    /**
     * @test
     */
    public function testAutoRunDisabled(): void
    {
        $testDirectory = $this->setupTestCase('disable-autorun');

        static::assertFileExists($testDirectory.'languages');
        // No files should be downloaded
        static::assertFileNotExists($testDirectory.'languages/de_DE.mo');
    }
}
