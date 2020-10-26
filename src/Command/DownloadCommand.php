<?php

/*
 * This file is part of the Assets package.
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
use Composer\Package\PackageInterface;
use Inpsyde\WpTranslationDownloader\Plugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadCommand extends BaseCommand
{
    use ErrorFormatterTrait;

    public const OPTION_PACKAGES = 'packages';

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('wp-translation-downloader:download')
            ->setDescription('Downloads for all packages languages.')
            ->addOption(
                self::OPTION_PACKAGES,
                null,
                InputOption::VALUE_OPTIONAL,
                'Define a one or multiple comma seperated packages to download.'
            );
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

            $packagesToProcess = $this->optionPackagesToProcess($input);
            $packages = $this->resolvePackages($packagesToProcess);

            $plugin = new Plugin();
            $plugin->activate($composer, $io);
            $plugin->doUpdatePackages($packages);

            return 0;
        } catch (\Throwable $throwable) {
            $this->writeError($output, $throwable->getMessage());

            return 1;
        }
    }

    /**
     * Formats input arg to an array of packages.
     *
     * @param InputInterface $input
     *
     * @return array
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @example package1,package2,package3 => ["package1", "package2", "package3"]
     *
     */
    private function optionPackagesToProcess(InputInterface $input): array
    {
        $packageNames = (string) $input->getOption(self::OPTION_PACKAGES);

        $packagesToProcess = explode(',', $packageNames);
        $packagesToProcess = array_unique($packagesToProcess);
        $packagesToProcess = array_filter($packagesToProcess);

        return $packagesToProcess;
    }

    /**
     * Searches for packages in composer.json by a given list of packageNames.
     *
     * @param array $packagesToProcess
     *
     * @return PackageInterface[]
     * @throws \RuntimeException
     */
    private function resolvePackages(array $packagesToProcess): array
    {
        /** @var Composer $composer */
        $composer = $this->getComposer(true, false);

        /** @var PackageInterface[] $packages */
        $packages = $composer->getRepositoryManager()
            ->getLocalRepository()->getPackages();

        if (count($packagesToProcess) > 0) {
            $packages = array_filter(
                $packages,
                static function (PackageInterface $package) use ($packagesToProcess): bool {
                    return in_array($package->getName(), $packagesToProcess, true);
                }
            );
        }

        return $packages;
    }
}
