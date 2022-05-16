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
use Composer\Composer;
use Composer\IO\IOInterface;
use Inpsyde\WpTranslationDownloader\Plugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanCacheCommand extends BaseCommand
{
    use ErrorFormatterTrait;

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('wp-translation-downloader:clean-cache')
            ->setDescription('Empties the cache folder.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            /** @var Composer $composer */
            $composer = $this->getComposer(true, false);

            /** @var IOInterface $io */
            $io = $this->getIO();

            $plugin = new Plugin();
            $plugin->activate($composer, $io);
            $plugin->doCleanCache();

            return 0;
        } catch (\Throwable $throwable) {
            $this->writeError($output, $throwable->getMessage());

            return 1;
        }
    }
}
