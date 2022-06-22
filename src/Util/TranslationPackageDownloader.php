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

use Composer\Downloader\DownloadManager;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Composer\Util\Loop;
use Composer\Util\SyncHelper;
use Symfony\Component\Finder\Finder;

class TranslationPackageDownloader
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
     * @var array<string, bool>
     */
    private $directories = [];

    /**
     * @var Loop
     */
    private $loop;

    /**
     * @var DownloadManager
     */
    private $downloaderManager;

    /**
     * @param Loop $loop
     * @param DownloadManager $downloaderManager
     * @param IOInterface $io
     * @param Filesystem $filesystem
     */
    public function __construct(
        Loop $loop,
        DownloadManager $downloaderManager,
        IOInterface $io,
        Filesystem $filesystem
    ) {

        $this->loop = $loop;
        $this->downloaderManager = $downloaderManager;
        $this->io = $io;
        $this->filesystem = $filesystem;
    }

    /**
     * @param PackageInterface $package
     * @param string $path
     * @return bool
     */
    public function download(PackageInterface $package, string $path): bool
    {
        try {
            $distUrl = $package->getDistUrl();
            if (!$distUrl) {
                throw new \Error("Package URL '{$distUrl}' is invalid.");
            }

            // Composer downloader will empty the target path.
            // When target does not exist, that's irrelevant, and we can unpack directly there.
            if (!file_exists($path)) {
                return $this->directDownload($package, $path, $distUrl);
            }

            if (!is_dir($path)) {
                throw new \Error("Could not use '{$path}' as target for unpacking '{$distUrl}'.");
            }

            // If here, target path is an existing directory. We can't use Composer downloader
            // to download directly in target path, or it will delete every existing file in it.
            // So we first unpack in a temporary folder and then move unpacked files from the temp
            // dir to final target dir. That's surely slower, but necessary.
            $tempDir = $this->downloadInTempDir($package, $path, $distUrl);

            return $this->moveFilesFromTempDir($tempDir, $path);
        } catch (\Throwable $throwable) {
            $this->io->writeError('    ' . $throwable->getMessage());

            return false;
        } finally {
            if (isset($tempDir)) {
                $this->filesystem->removeDirectory($tempDir);
            }
        }
    }

    /**
     * @param PackageInterface $package
     * @param string $path
     * @param string $distUrl
     * @return bool
     */
    private function directDownload(PackageInterface $package, string $path, string $distUrl): bool
    {
        $this->ensureDirectoryExists($path);
        $this->io->debug("Downloading and unpacking '{$distUrl}' in new directory '{$path}'...");

        SyncHelper::downloadAndInstallPackageSync(
            $this->loop,
            $this->downloaderManager->getDownloader($package->getDistType() ?? ''),
            $path,
            $package
        );

        return true;
    }

    /**
     * @param PackageInterface $package
     * @param string $targetPath
     * @param string $distUrl
     * @return string
     */
    private function downloadInTempDir(
        PackageInterface $package,
        string $targetPath,
        string $distUrl
    ): string {

        $tempDir = dirname($targetPath) . '/~tmp' . bin2hex(random_bytes(8));
        $this->io->debug("Archive target path '{$targetPath}' is an existing directory.");
        $this->io->debug("Downloading and unpacking '{$distUrl}' in the temp dir: '{$tempDir}'.");
        $this->filesystem->ensureDirectoryExists($tempDir);
        SyncHelper::downloadAndInstallPackageSync(
            $this->loop,
            $this->downloaderManager->getDownloader($package->getDistType() ?? ''),
            $tempDir,
            $package
        );
        $this->ensureDirectoryExists($targetPath);

        return $tempDir;
    }

    /**
     * @param string $tempDir
     * @param string $targetPath
     * @return bool
     */
    private function moveFilesFromTempDir(string $tempDir, string $targetPath): bool
    {
        $finder = Finder::create()->in($tempDir)->ignoreVCS(true)->files();
        $this->io->debug("Copying unpacked files from temp dir '{$tempDir}' to '{$targetPath}'.");

        $errors = 0;
        foreach ($finder as $item) {
            $relative = $item->getRelativePathname();
            $fullTargetPath = $this->filesystem->normalizePath("{$targetPath}/{$relative}");
            $this->ensureDirectoryExists(dirname($fullTargetPath));
            $sourcePath = $item->getPathname();
            if (file_exists($fullTargetPath)) {
                $this->debug(" - removing existing '{$fullTargetPath}'...");
                $this->filesystem->remove($fullTargetPath);
            }
            $this->debug(" - moving '{$sourcePath}' to '{$fullTargetPath}'...");
            $this->filesystem->copy($sourcePath, $fullTargetPath) or $errors++;
        }

        return $errors === 0;
    }

    /**
     * @param string $path
     * @return void
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (!isset($this->directories[$path])) {
            $this->filesystem->ensureDirectoryExists($path);
            $this->directories[$path] = true;
        }
    }

    /**
     * @param string $message
     * @return void
     */
    private function debug(string $message): void
    {
        $this->io->write("     {$message}", true, IOInterface::DEBUG);
    }
}
