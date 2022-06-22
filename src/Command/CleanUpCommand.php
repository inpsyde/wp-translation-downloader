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

namespace Inpsyde\WpTranslationDownloader\Command;

use Composer\Command\BaseCommand;
use Inpsyde\WpTranslationDownloader\Plugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanUpCommand extends BaseCommand
{
    use ErrorFormatterTrait;
    use ComposerGetterTrait;

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('wp-translation-downloader:clean-up')
            ->setDescription('Empties all language folders.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $plugin = new Plugin();
            $plugin->activate($this->obtainComposerFromCommand($this), $this->getIO());
            $plugin->doCleanUpDirectories();

            return 0;
        } catch (\Throwable $throwable) {
            $this->writeError($output, $throwable->getMessage());

            return 1;
        }
    }
}
