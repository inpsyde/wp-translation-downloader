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
use Composer\Json\JsonFile;
use Composer\Util\Filesystem;
use Inpsyde\WpTranslationDownloader\Package\ProjectTranslation;

class Locker
{
    public const LOCK_FILE = 'wp-translation-downloader.lock';

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var JsonFile
     */
    private $file;

    /**
     * @var array<
     *  string,
     *  array<string, array<string, array{'updated': 'string', 'version': 'string'}>>
     * >
     */
    private $lockedData = [];

    /**
     * Locker constructor.
     *
     * @param IOInterface $io
     * @param string $projectRoot
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(IOInterface $io, string $projectRoot)
    {
        $this->io = $io;
        $this->file = new JsonFile($projectRoot . self::LOCK_FILE);
        $this->loadLockData();
    }

    /**
     * Check if a translation project for the current language is locked.
     *
     * @param ProjectTranslation $translation
     * @return bool
     */
    public function isLocked(ProjectTranslation $translation): bool
    {
        $projectName = $translation->projectName();
        $language = $translation->language() ?? '';
        $lockedData = $this->lockedData[$projectName]['translations'][$language] ?? null;
        /** @psalm-suppress TypeDoesNotContainType */
        if (!is_array($lockedData) || !$lockedData) {
            return false;
        }

        $isLocked = $this->isLockedTranslation($lockedData, $translation);

        if ($isLocked) {
            $this->io->write(
                sprintf(
                    '    <info>[LOCKED]</info> %1$s | %2$s | %3$s',
                    $language,
                    $translation->lastUpdated(),
                    $translation->version() ?? ''
                ),
                true,
                IOInterface::VERBOSE
            );
            // When a project is locked, then we want to add it again
            // to the lock-file.
            $this->lockTranslation($translation);
        }

        return $isLocked;
    }

    /**
     * Lock a translation project for a given language.
     *
     * @param ProjectTranslation $translation
     * @return bool
     */
    public function lockTranslation(ProjectTranslation $translation): bool
    {
        $projectName = $translation->projectName();
        if (!is_array($this->lockedData[$projectName] ?? null)) {
            $this->lockedData[$projectName] = ['translations' => []];
        }
        if (!is_array($this->lockedData[$projectName]['translations'] ?? null)) {
            $this->lockedData[$projectName]['translations'] = [];
        }

        $language = $translation->language() ?? '';
        $this->lockedData[$projectName]['translations'][$language] = [
            'updated' => $translation->lastUpdated(),
            'version' => $translation->version() ?? '',
        ];

        return true;
    }

    /**
     * Remove a given translation project by name from lock data.
     *
     * @param string $projectName
     * @return bool
     */
    public function removeProjectLock(string $projectName): bool
    {
        if (!isset($this->lockedData[$projectName])) {
            return false;
        }

        unset($this->lockedData[$projectName]);

        return true;
    }

    /**
     * Writing lock-file into filesystem.
     *
     * @return bool
     */
    public function writeLockFile(): bool
    {
        try {
            $this->io->write(
                sprintf("\n  <info>Writing new lock data to %s<info>", $this->file->getPath())
            );
            $this->file->write($this->lockedData);

            return true;
        } catch (\Throwable $exception) {
            $this->io->error($exception->getMessage());

            return false;
        }
    }

    /**
     * Remove lock-file from filesystem.
     *
     * @return bool
     */
    public function removeLockFile(): bool
    {
        $this->io->write(
            sprintf("<info>Lock file %s was removed.<info>", $this->file->getPath())
        );

        return (new Filesystem())->remove($this->file->getPath());
    }

    /**
     * @return array
     */
    public function lockData(): array
    {
        return $this->lockedData;
    }

    /**
     * @return void
     */
    private function loadLockData(): void
    {
        try {
            if (!$this->file->exists()) {
                $this->io->write(
                    sprintf('  <info>No %s found.</info>', $this->file->getPath()),
                    true,
                    IOInterface::VERBOSE
                );

                return;
            }

            $this->lockedData = $this->file->read();

            $this->io->write(
                sprintf("  <info>Successfully loaded %s.</info>\n", $this->file->getPath()),
                true,
                IOInterface::VERBOSE
            );
        } catch (\Throwable $exception) {
            $this->io->write(
                $exception->getMessage(),
                true,
                IOInterface::VERBOSE
            );
        }
    }

    /**
     * A project with a given language is locked when:
     * - locked "last updated" is greater or equal to Translation's "last updated"
     * - locked "version" is greater or equal to Translation's version
     *
     * @param array $lockedData
     * @param ProjectTranslation $translation
     * @return bool
     */
    private function isLockedTranslation(array $lockedData, ProjectTranslation $translation): bool
    {
        $lockedLastUpdated = $lockedData['updated'] ?? null;
        is_string($lockedLastUpdated) or $lockedLastUpdated = null;

        $lockedVersion = $lockedData['version'] ?? null;
        is_string($lockedVersion) or $lockedVersion = null;

        if (($lockedLastUpdated === null) && ($lockedVersion === null)) {
            return false;
        }

        $lastUpdated = $translation->lastUpdated();
        $version = $translation->version();

        $wasNotUpdatedRecently = ($lockedLastUpdated !== null)
            && ($lastUpdated !== '')
            && (strtotime($lockedLastUpdated) >= strtotime($lastUpdated));

        $isNotNewVersion = ($lockedVersion !== null)
            && ($version !== null)
            && version_compare($lockedVersion, $version, '>=');

        return $wasNotUpdatedRecently && $isNotNewVersion;
    }
}
