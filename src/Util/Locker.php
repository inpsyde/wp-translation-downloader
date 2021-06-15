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

use Composer\Json\JsonFile;
use Inpsyde\WpTranslationDownloader\Io;

class Locker
{
    public const LOCK_FILE = 'wp-translation-downloader.lock';

    /**
     * @var Io
     */
    private $io;

    /**
     * @var JsonFile
     */
    private $file;

    /**
     * @var array
     */
    private $lockedData = [];

    /**
     * Locker constructor.
     *
     * @param Io $io
     * @param string $projectRoot
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Io $io, string $projectRoot)
    {
        $this->io = $io;
        $this->file = new JsonFile($projectRoot . self::LOCK_FILE);
        $this->loadLockData();
    }

    /**
     * Detects if a translation project for the current language is locked.
     *
     * @param string $projectName
     * @param string $language
     * @param string $lastUpdated
     *
     * @return bool
     */
    public function isLocked(string $projectName, string $language, string $lastUpdated): bool
    {
        // phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
        $lockedLastUpdated = $this->lockedData[$projectName]['translations'][$language]['updated'] ?? null;
        if (!$lockedLastUpdated) {
            return false;
        }

        // When lockedLastUpdated is greater or equal the lastUpdated, then
        // no updates are available -> isLocked.
        return strtotime($lockedLastUpdated) >= strtotime($lastUpdated);
    }

    /**
     * @return bool
     */
    private function loadLockData(): bool
    {
        try {
            if (!$this->file->exists()) {
                $this->io->writeOnVerbose(
                    sprintf('<info>No %s found.</info>', $this->file->getPath())
                );

                return false;
            }

            $this->lockedData = $this->file->read();

            $this->io->writeOnVerbose(
                sprintf('<info>Successfully loaded %s.</info>', $this->file->getPath())
            );

            return true;
        } catch (\Throwable $exception) {
            $this->io->writeOnVerbose($exception->getMessage());

            return false;
        }
    }

    /**
     * Lock a translation project for a given language.
     *
     * @param string $projectName
     * @param string $language
     * @param string $lastUpdated
     *
     * @throws \UnexpectedValueException
     */
    public function lock(string $projectName, string $language, string $lastUpdated)
    {
        if (!isset($this->lockedData[$projectName])) {
            $this->lockedData[$projectName] = [
                'translations' => [],
            ];
        }

        $this->lockedData[$projectName]['translations'][$language] = ['updated' => $lastUpdated];

        $this->file->write($this->lockedData);
    }
}
