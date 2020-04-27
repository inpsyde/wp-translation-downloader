<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader\Command;

use Composer\Command\BaseCommand;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Inpsyde\WpTranslationDownloader\Plugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadCommand extends BaseCommand
{

    use ErrorFormatterTrait;

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('wp-translation-downloader:download')
            ->setDescription('Downloads for all packages languages.');
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

            /** @var PackageInterface[] $packages */
            $packages = $composer->getRepositoryManager()
                ->getLocalRepository()->getPackages();

            $plugin = new Plugin();
            $plugin->activate($composer, $io);
            $plugin->doUpdatePackages($packages);

            return 0;
        } catch (\Throwable $throwable) {
            $this->writeError($output, $throwable->getMessage());

            return 1;
        }
    }
}