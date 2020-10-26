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

namespace Inpsyde\WpTranslationDownloader;

use Composer\IO\IOInterface;

class Io
{

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * Io constructor.
     *
     * @param IOInterface $io
     */
    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    public function write(string ...$messages)
    {
        foreach ($messages as $message) {
            $this->io->write($message);
        }
    }

    public function writeOnVerbose(string ...$messages)
    {
        $this->io->isVerbose() && $this->write(...$messages);
    }

    /**
     * @param string ...$messages
     */
    public function info(string ...$messages)
    {
        foreach ($messages as $message) {
            $this->write("<info>{$message}</info>");
        }
    }

    /**
     * @param string ...$messages
     */
    public function infoOnVerbose(string ...$messages)
    {
        $this->io->isVerbose() && $this->info(...$messages);
    }

    /**
     * @param string ...$messages
     */
    public function error(string ...$messages)
    {
        foreach ($messages as $message) {
            $this->io->write("<error>[ERROR]</error> {$message}");
        }
    }

    /**
     * @param string ...$messages
     */
    public function errorOnVerbose(string ...$messages)
    {
        $this->io->isVerbose() && $this->error(...$messages);
    }

    /**
     * @return void
     */
    public function logo(): void
    {
        // phpcs:disable
        $logo = <<<LOGO
    <fg=white;bg=green>                        </>
    <fg=white;bg=green>        Inpsyde         </>
    <fg=white;bg=green>                        </>
    <fg=magenta>WP Translation Downloader</>
LOGO;
        // phpcs:enable

        $this->io->write("\n{$logo}\n");
    }
}
