<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
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
        $this->fixturesDir = __DIR__.'/../../tests/fixtures';
    }

    /**
     * @Given I am using the fixtures :fixtureFolder
     *
     * @param $fixtureFolder
     */
    public function iAmUsingTheFixtures($fixtureFolder)
    {
        $this->currentTestCase = $fixtureFolder;
        $this->currentTestDirectory = $this->fixturesDir.'/'.$fixtureFolder.'/';
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

        $this->runComposer([$command]);
    }

    /**
     * @Then I should see in console :output
     *
     * @param $output
     */
    public function iShouldSeeInConsole($output)
    {
        Assert::assertStringContainsString($output, $this->currentOutput);
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
     * @Then I should see the file :file does not exist
     *
     * @param $file
     */
    public function iShouldSeeNoFileExists($file){
        Assert::assertFileNotExists($this->currentTestDirectory.$file);
    }

    /**
     * @param array $commands
     *
     * @throws \Throwable
     */
    private function runComposer(array $commands): void
    {
        array_unshift($commands, $this->composerExecutable);

        $process = new Process(
            $commands,
            $this->currentTestDirectory,
            null,
            null,
            // no timeout, its possible that installation/downloading
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
}
