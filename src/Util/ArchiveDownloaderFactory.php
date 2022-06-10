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

use Composer\Composer;
use Composer\Downloader\DownloaderInterface;
use Composer\Downloader\FileDownloader;
use Composer\IO\ConsoleIO;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Util\Filesystem;
use Composer\Util\SyncHelper;

class ArchiveDownloaderFactory
{
    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var \Composer\Downloader\DownloadManager
     */
    private $downloadManager;

    /**
     * @var \Composer\Util\Loop|null
     */
    private $loop;

    /**
     * @param IOInterface $io
     * @param Composer $composer
     * @param Filesystem $filesystem
     */
    public function __construct(
        IOInterface $io,
        Composer $composer,
        Filesystem $filesystem
    ) {

        $this->io = $io;
        $this->downloadManager = $composer->getDownloadManager();
        /** @psalm-suppress RedundantCondition */
        if (is_callable([$composer, 'getLoop']) && class_exists(SyncHelper::class)) {
            $this->loop = $composer->getLoop();
        }
        $this->filesystem = $filesystem;
    }

    /**
     * @param string $archiveType
     * @return ArchiveDownloader
     */
    public function create(string $archiveType): ArchiveDownloader
    {
        $downloader = $this->factoryDownloader($archiveType);

        return $this->loop
            ? ArchiveDownloader::viaLoop($this->loop, $downloader, $this->io, $this->filesystem)
            : ArchiveDownloader::forV1($downloader, $this->io, $this->filesystem);
    }

    /**
     * @param string $type
     * @return DownloaderInterface
     */
    private function factoryDownloader(string $type): DownloaderInterface
    {
        $downloader = $this->downloadManager->getDownloader($type);
        if (!($downloader instanceof FileDownloader) || $this->io->isVeryVerbose()) {
            return $downloader;
        }

        // When it's not very verbose we silence FileDownloader ConsoleIO

        static $replacer;
        $replacer or $replacer = function (): void {
            if ($this->io instanceof ConsoleIO) {
                $this->io = new NullIO();
            }
        };

        /** @var \Closure $replacer */
        $replacerBound = \Closure::bind($replacer, $downloader, FileDownloader::class);
        $replacerBound and $replacerBound();

        return $downloader;
    }
}
