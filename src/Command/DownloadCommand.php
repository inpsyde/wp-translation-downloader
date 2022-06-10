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
use Composer\Package\PackageInterface;
use Inpsyde\WpTranslationDownloader\Plugin;
use Inpsyde\WpTranslationDownloader\Util\FnMatcher;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadCommand extends BaseCommand
{
    use ErrorFormatterTrait;
    use ComposerGetterTrait;

    public const OPTION_PACKAGES = 'packages';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('wp-translation-downloader:download')
            ->setDescription("Downloads packages' translations.")
            ->addOption(
                self::OPTION_PACKAGES,
                null,
                InputOption::VALUE_OPTIONAL,
                'One or more comma-separated name for packages to download. Accepts glob patterns.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $composer = $this->obtainComposerFromCommand($this);

            $plugin = new Plugin();
            $plugin->activate($composer, $this->getIO());

            $packagesToProcess = $this->optionPackagesToProcess($input);

            // If we have package names (or patterns) passed to the command, let's restrict to
            // those packages.
            $packages = $this->filterPackages(
                $packagesToProcess,
                ...$plugin->availablePackages($composer)
            );

            $plugin->doUpdatePackages(...$packages);

            return 0;
        } catch (\Throwable $throwable) {
            $this->writeError($output, $throwable->getMessage());

            return 1;
        }
    }

    /**
     * @param list<string> $packagesToProcess
     * @param list<PackageInterface> $packages
     * @return list<PackageInterface>
     *
     * @no-named-arguments
     */
    private function filterPackages(array $packagesToProcess, PackageInterface ...$packages): array
    {
        if (!$packagesToProcess) {
            return $packages;
        }

        $filtered = [];
        foreach ($packages as $package) {
            if (FnMatcher::isMatchingAny($packagesToProcess, $package->getName())) {
                $filtered[$package->getUniqueName()] = $package;
            }
        }

        return array_values($filtered);
    }

    /**
     * Formats input arg to an array of packages.
     *
     * @param InputInterface $input
     *
     * @return list<string>
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @example "package1, package2, package3" => ["package1", "package2", "package3"]
     *
     */
    private function optionPackagesToProcess(InputInterface $input): array
    {
        $packageNames = trim((string)$input->getOption(self::OPTION_PACKAGES));
        if (!$packageNames) {
            return [];
        }

        $packagesToProcess = explode(',', $packageNames);
        $parsed = [];
        foreach ($packagesToProcess as $packageToProcess) {
            $package = trim($packageToProcess);
            $package and $parsed[$package] = 1;
        }

        return array_keys($parsed);
    }
}
