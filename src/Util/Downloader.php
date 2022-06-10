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

namespace Inpsyde\WpTranslationDownloader\Util;

use Composer\IO\IOInterface;
use Composer\Package\CompletePackage;
use Composer\Util\Filesystem;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackageInterface;
use Inpsyde\WpTranslationDownloader\Package\ProjectTranslation;

class Downloader
{
    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var Locker
     */
    private $locker;

    /**
     * @var TranslationPackageDownloader
     */
    private $downloader;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param IOInterface $io
     * @param Locker $locker
     * @param TranslationPackageDownloader $downloader
     * @param Filesystem $filesystem
     */
    public function __construct(
        IOInterface $io,
        Locker $locker,
        TranslationPackageDownloader $downloader,
        Filesystem $filesystem
    ) {

        $this->io = $io;
        $this->locker = $locker;
        $this->downloader = $downloader;
        $this->filesystem = $filesystem;
    }

    /**
     * @param TranslatablePackageInterface $transPackage
     * @param list<string> $allowedLanguages
     * @param \stdClass $globalCollector
     */
    public function download(
        TranslatablePackageInterface $transPackage,
        array $allowedLanguages,
        \stdClass $globalCollector
    ): void {

        $projectName = $transPackage->projectName();
        $translations = $transPackage->translations($allowedLanguages);
        $endpoint = $transPackage->apiEndpoint();

        if (!$this->countTranslations($projectName, $translations, $endpoint)) {
            return;
        }

        $collector = (object)['downloaded' => 0, 'locked' => 0, 'errors' => 0];
        $directory = $this->filesystem->normalizePath($transPackage->languageDirectory());

        foreach ($translations as $translation) {
            $this->downloadTranslation($translation, $directory, $collector);
        }

        /** @psalm-suppress MixedOperand */
        $globalCollector->downloaded += $collector->downloaded;
        /** @psalm-suppress MixedOperand */
        $globalCollector->locked += $collector->locked;
        /** @psalm-suppress MixedOperand */
        $globalCollector->errors += $collector->errors;

        $this->printStatsMessage($collector);
    }

    /**
     * @param ProjectTranslation $translation
     * @param string $directory
     * @param \stdClass $collector
     * @return void
     */
    private function downloadTranslation(
        ProjectTranslation $translation,
        string $directory,
        \stdClass $collector
    ): void {

        $packageDesc = sprintf(
            "<fg=magenta>%s</> | %s",
            $translation->language() ?? '',
            $translation->version() ?? ''
        );

        try {
            if ($this->locker->isLocked($translation)) {
                /** @psalm-suppress MixedOperand */
                $collector->locked++;
                return;
            }

            $languagePackage = $this->createTranslationPackage($translation);
            if (!$this->downloader->download($languagePackage, $directory)) {
                $this->packageError('Download error', $collector, $packageDesc);
                return;
            }

            $this->packageSuccess($collector, $packageDesc);
            $this->locker->lockTranslation($translation);
        } catch (\Throwable $exception) {
            $this->packageError($exception->getMessage(), $collector, $packageDesc);
        }
    }

    /**
     * @param ProjectTranslation $translation
     * @return CompletePackage
     */
    private function createTranslationPackage(ProjectTranslation $translation): CompletePackage
    {
        $distUrl = $translation->packageUrl();
        if (!filter_var($distUrl, FILTER_VALIDATE_URL)) {
            // The URL has a file extension, but it looks wrong.
            $name = $translation->projectName();
            throw new \Error("Invalid translations URL for project '{$name}'");
        }

        /** @var string $distUrl */

        $version = $translation->version() ?? '';

        $package = new CompletePackage($translation->fullyQualifiedName(), $version, $version);
        $package->setDescription($translation->description());
        $package->setType('wp-translation-package');
        $package->setDistType($this->determineProjectDistType($translation));
        $package->setDistUrl($distUrl);

        return $package;
    }

    /**
     * @param ProjectTranslation $translation
     * @return string
     */
    private function determineProjectDistType(ProjectTranslation $translation): string
    {
        $distType = $translation->distType();
        if (!$distType) {
            $name = $translation->projectName();
            throw new \Error("Invalid translations file type for project '{$name}'");
        }

        return $distType;
    }

    /**
     * @param string $projectName
     * @param array $translations
     * @param string $endpoint
     * @return bool
     */
    private function countTranslations(
        string $projectName,
        array $translations,
        string $endpoint
    ): bool {

        $count = count($translations);

        if ($count < 1) {
            $this->io->write("  - <info>{$projectName}</info>: found no translations.");
            $this->io->write('', true, IOInterface::VERBOSE);

            return false;
        }

        $label = $count === 1 ? 'one translation' : sprintf('%d translations', $count);
        $this->io->write("  - <info>{$projectName}</info>: found {$label}. Installing...");
        $this->io->write("    <fg=yellow>Endpoint</>: {$endpoint}", true, IOInterface::VERBOSE);

        return true;
    }

    /**
     * @param \stdClass $collector
     * @param string $packageDesc
     * @return void
     */
    private function packageSuccess(\stdClass $collector, string $packageDesc): void
    {
        $this->io->write("    <info>✓</info> {$packageDesc}");
        /** @psalm-suppress MixedOperand */
        $collector->downloaded++;
    }

    /**
     * @param string $message
     * @param \stdClass $collector
     * @param string $packageDesc
     * @return void
     */
    private function packageError(string $message, \stdClass $collector, string $packageDesc): void
    {
        $prefix = '    ';
        $packageDesc and $this->io->write($prefix . "<fg=red>✗</> {$packageDesc}");
        $this->io->writeError($prefix . $message);
        /** @psalm-suppress MixedOperand */
        $collector->errors++;
    }

    /**
     * @param \stdClass $collector
     * @return void
     */
    private function printStatsMessage(\stdClass $collector): void
    {
        $this->io->write(
            sprintf(
                "    <options=bold>Package's translations stats</>:" .
                " %d downloaded, %d locked, %d failed.\n",
                (int)$collector->downloaded,
                (int)$collector->locked,
                (int)$collector->errors
            ),
            true,
            IOInterface::VERBOSE
        );
    }
}
