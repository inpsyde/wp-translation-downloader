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

namespace Inpsyde\WpTranslationDownloader\Package;

use Composer\Package\Package;
use Composer\Package\PackageInterface;

final class WpCorePackage extends Package implements TranslatablePackage
{
    use TranslatablePackageTrait;

    public function __construct(PackageInterface $package, string $directory, string $endpoint)
    {
        parent::__construct($package->getName(), $package->getVersion(), $package->getPrettyVersion());

        $this->endpoint = $endpoint;
        $this->languageDirectory = $directory;
    }
}
