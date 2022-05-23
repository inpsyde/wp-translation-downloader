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
use Composer\Util\Filesystem;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackageInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Remover
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
     * @var Locker
     */
    private $locker;

    /**
     * Remover constructor.
     *
     * @param IOInterface $io
     * @param Filesystem $filesystem
     */
    public function __construct(
        IOInterface $io,
        Filesystem $filesystem,
        Locker $locker
    ) {
        $this->io = $io;
        $this->filesystem = $filesystem;
        $this->locker = $locker;
    }

    public function remove(TranslatablePackageInterface $transPackage): bool
    {
        try {
            $projectName = $transPackage->projectName();
            $pattern = sprintf("~^%s-.+?\.(?:po|mo|json)$~i", $projectName);

            $files = Finder::create()
                ->in($transPackage->languageDirectory())
                ->ignoreUnreadableDirs()
                ->ignoreVCS(true)
                ->ignoreDotFiles(true)
                ->depth('== 0')
                ->files()
                ->filter(
                    static function (SplFileInfo $info) use ($pattern): bool {
                        return (bool) preg_match($pattern, $info->getFilename());
                    }
                );

            foreach ($files as $file) {
                try {
                    $this->filesystem->unlink($file->getPathname());
                    $this->io->write(
                        sprintf(
                            "    - <info>[OK]</info> %s: deleted %s translation file.",
                            $projectName,
                            $file->getBasename()
                        )
                    );
                } catch (\Throwable $exception) {
                    $this->io->writeError($exception->getMessage());
                }
            }

            $this->locker->removeProjectLock($projectName);

            return true;
        } catch (\Throwable $exception) {
            $this->io->writeError($exception->getMessage(), true, IOInterface::VERBOSE);

            return false;
        }
    }
}
