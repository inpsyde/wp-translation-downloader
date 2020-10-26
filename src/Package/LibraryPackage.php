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

namespace Inpsyde\WpTranslationDownloader\Package;

use Composer\Package\Package;

final class LibraryPackage extends Package implements TranslatablePackage
{
    use TranslatablePackageTrait;

    public function __construct(Package $package, string $directory, string $endpoint)
    {
        parent::__construct($package->getName(), $package->getVersion(), $package->getPrettyVersion());

        $this->endpoint = $endpoint;
        $this->languageDirectory = $directory;
    }
}
