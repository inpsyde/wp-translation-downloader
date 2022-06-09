<?php

namespace Inpsyde\WpTranslationDownloader\Tests\Behat;

use Behat\Behat\Context\Context;
use Composer\Util\Filesystem;
use Composer\Util\ProcessExecutor;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use PHPUnit\Framework\Assert;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
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
     * @var string
     */
    protected $currentTestDirectory;

    /**
     * @var string
     */
    protected $currentTestCase;

    /**
     * @var string
     */
    protected $currentOutput;

    public function __construct()
    {
        $this->composerExecutable = (new ExecutableFinder())->find('composer');
        $this->fixturesDir = __DIR__.'/../fixtures';
    }

    /**
     * @Given /^I am using the fixtures "([^"]+)"( without cleanup)?$/
     *
     * @param $fixtureFolder
     * @param $noCleanup
     */
    public function iAmUsingTheFixtures($fixtureFolder, $noCleanup = null)
    {
        $this->currentTestCase = $fixtureFolder;
        $this->currentTestDirectory = $this->fixturesDir.'/'.$fixtureFolder.'/';

        $noCleanup or $this->cleanupFixtureFolder($this->currentTestDirectory);
    }

    /**
     * @When /^I run composer ([^"]*)$/
     *
     * @param $command
     */
    public function iRunComposerCommand($command)
    {
        if ($command === 'install') {
            $this->runComposer(
                [
                    'install',
                    '--no-interaction',
                    '--no-progress',
                ]
            );
            Assert::assertFileExists($this->currentTestDirectory.'composer.lock');

            return;
        }

        if (!file_exists($this->currentTestDirectory.'composer.lock')) {
            fwrite(STDOUT, "\nInstalling Composer dependencies before executing '{$command}'...");
            $this->iRunComposerCommand('install');
        }

        $this->runComposer([$command]);
    }

    /**
     * @Then I should see in console :output
     *
     * @param $output
     */
    public function iShouldSeeInConsole($output)
    {
        $consoleOutput = str_replace(['\r', '\n'], ' ', $this->currentOutput);
        $consoleOutput = trim(preg_replace('~\s+~', ' ', $consoleOutput) ?? '');

        Assert::assertStringContainsString($output, $consoleOutput);
    }

    /**
     * @Then I should see the file/folder :fileOrFolder exists
     *
     * @param $fileOrFolder
     */
    public function iShouldSeeTheFileOrFolderExists($fileOrFolder)
    {
        Assert::assertFileExists($this->currentTestDirectory.$fileOrFolder);
    }

    /**
     * @Then I should see the file/folder :fileOrFolder does not exist
     *
     * @param $fileOrFolder
     */
    public function iShouldSeeTheFileOrFolderDoesNotExist($fileOrFolder)
    {
        Assert::assertFileNotExists($this->currentTestDirectory.$fileOrFolder);
    }

    /**
     * @param array $commands
     *
     * @throws \Throwable
     */
    private function runComposer(array $commands): void
    {
        array_unshift($commands, $this->composerExecutable);
        $commands[] = '--no-ansi';

        $process = new Process(
            $commands,
            $this->currentTestDirectory,
            null,
            null,
            // no timeout, it's possible that installation/downloading
            // of translations takes some time.
            null

        );
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(
                'Could not run TestCase "'.$this->currentTestDirectory.'"! '.$process->getOutput().PHP_EOL.
                $process->getErrorOutput().PHP_EOL.
                'While running '.$process->getCommandLine()
            );
        }

        $this->currentOutput = $process->getOutput();
    }

    /**
     * When running tests locally, it might happen to run the same test again, and that might fail
     * due to a previous execution, if we don't reset the folder to its initial status.
     *
     * @param string $folder
     * @return void
     */
    private function cleanupFixtureFolder(string $folder): void
    {
        $ciDetector = new \OndraM\CiDetector\CiDetector();
        if ($ciDetector->isCiDetected()) {
            // Do not bother cleaning up folder in CI when we assume a clean state.
            return;
        }

        // These are the files that we expect in a folder, should match whatever is _not_
        // git-ignored inside fixture folders.
        $doNotDelete = [
            'composer.json',
            'wp-translation-downloader.json',
        ];

        $finder = Finder::create()->in($folder)
            ->ignoreVCS(true)
            ->ignoreUnreadableDirs()
            ->depth(0)
            ->filter(function (\SplFileInfo $info) use ($folder, $doNotDelete): bool {
                foreach ($doNotDelete as $relativePath) {
                    if (realpath("{$folder}/{$relativePath}") === $info->getRealPath()) {
                        return false;
                    }
                }
                return true;
            });

        $filesystem = new Filesystem();
        foreach ($finder as $item) {
            $filesystem->remove($item->getRealPath());
        }
    }
}
