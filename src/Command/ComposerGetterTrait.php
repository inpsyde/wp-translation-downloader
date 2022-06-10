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
use Composer\Composer;

trait ComposerGetterTrait
{
    /**
     * @param BaseCommand $command
     * @return Composer
     *
     * @psalm-suppress DeprecatedMethod
     * @psalm-suppress RedundantCondition
     */
    private function obtainComposerFromCommand(BaseCommand $command): Composer
    {
        /** @var Composer $composer */
        $composer = is_callable([$command, 'requireComposer'])
            ? $command->requireComposer(false)
            : $command->getComposer(true, false);

        return $composer;
    }
}
