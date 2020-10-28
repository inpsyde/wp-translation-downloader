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

use Symfony\Component\Process\Process;

/**
 * Testing with auto-run=false to run download and cleanup commands.
 *
 * @see ../fixtures/commands/composer.json
 *
 * @package Inpsyde\WpTranslationDownloader\Tests\Integration
 */
class CommandsTest extends AbstractIntegrationTestCase
{

    /**
     * @test
     *
     * @return string   The test directory path.
     */
    public function testDownloadCommand(): string
    {
        [$testDirectory, $output] = $this->setupTestCase('commands');

        static::assertEmpty($output);
        static::assertFileExists($testDirectory.'languages');
        // No files should be downloaded
        static::assertFileNotExists($testDirectory.'languages/de_DE.mo');

        $output = $this->runComposer(
            $testDirectory,
            ['wp-translation-downloader:download']
        );

        static::assertStringContainsString('wordpress-core: found 1 translations', $output);
        static::assertFileExists($testDirectory.'languages/de_DE.mo');

        return $testDirectory;
    }

    /**
     * @param string $testDirectory
     *
     * @depends testDownloadCommand
     * @test
     */
    public function testCleanUpCommand(string $testDirectory): void
    {
        $output = $this->runComposer(
            $testDirectory,
            ['wp-translation-downloader:clean-up']
        );

        static::assertStringContainsString('Starting to empty the directories...', $output);
        static::assertFileNotExists($testDirectory.'languages/de_DE.mo');
    }
}
