<?php

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
