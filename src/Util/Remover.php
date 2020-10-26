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

use Composer\Util\Filesystem;
use Inpsyde\WpTranslationDownloader\Io;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackage;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Remover
{

    /**
     * @var Io
     */
    private $io;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Remover constructor.
     *
     * @param Io $io
     * @param Filesystem $filesystem
     */
    public function __construct(Io $io, Filesystem $filesystem)
    {
        $this->io = $io;
        $this->filesystem = $filesystem;
    }

    public function remove(TranslatablePackage $transPackage): bool
    {
        $pattern = sprintf("~^%s-.+?\.(?:po|mo|json)$~i", $transPackage->projectName());

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
                        $transPackage->projectName(),
                        $file->getBasename()
                    )
                );
            } catch (\Throwable $exception) {
                $this->io->error($exception->getMessage());
            }
        }

        return true;
    }
}
