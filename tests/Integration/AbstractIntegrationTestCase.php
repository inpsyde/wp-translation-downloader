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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

abstract class AbstractIntegrationTestCase extends TestCase
{

    /**
     * @var string
     */
    protected $composerExecutable;

    /**
     * @var string
     */
    protected $fixturesDir = '';

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->composerExecutable = (new ExecutableFinder())->find('composer');
        $this->fixturesDir = realpath(__DIR__.'/../fixtures');
    }

    protected function setupTestCase(string $testCase): string
    {
        $testDirectory = $this->fixturesDir.'/'.$testCase.'/';
        $process = new Process(
            [
                $this->composerExecutable,
                'install',
                '--no-interaction',
            ],
            $testDirectory,
            null,
            null,
            // no timeout, its possible that installation/downloading
            // of translations takes some time.
            null

        );
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(
                'Could not run test case "'.$testCase.'"! '.$process->getOutput().PHP_EOL.
                $process->getErrorOutput().PHP_EOL.
                'While running '.$process->getCommandLine()
            );
        }

        // check, if composer installation did not fail.
        static::assertFileExists($testDirectory.'composer.lock');

        return $testDirectory;
    }
}