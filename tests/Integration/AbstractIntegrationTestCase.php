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

    /**
     * AbstractIntegrationTestCase constructor.
     *
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->composerExecutable = (new ExecutableFinder())->find('composer');
        $this->fixturesDir = realpath(__DIR__.'/../fixtures');
    }

    /**
     * @param string $testCase
     *
     * @return array
     *
     * @throws \Throwable
     */
    protected function setupTestCase(string $testCase): array
    {
        $testDirectory = $this->fixturesDir.'/'.$testCase.'/';
        $output = $this->runComposer(
            $testDirectory,
            [
                'install',
                '--no-interaction',
                '--no-progress',
            ]
        );

        // check, if composer installation did not fail.
        static::assertFileExists($testDirectory.'composer.lock');

        return [$testDirectory, $output];
    }

    /**
     * @param string $testDirectory
     * @param array $commands
     *
     * @return string
     *
     * @throws \Throwable
     */
    protected function runComposer(string $testDirectory, array $commands): string
    {
        array_unshift($commands, $this->composerExecutable);

        $process = new Process(
            $commands,
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

        return $process->getOutput();
    }
}
