<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader\Package;

use Composer\Package\Package;
use Composer\Package\PackageInterface;

final class WpPluginPackage extends Package implements TranslatablePackage
{

    use TranslatablePackageTrait;

    public function __construct(PackageInterface $package, string $directory, string $endpoint)
    {
        parent::__construct($package->getName(), $package->getVersion(), $package->getPrettyVersion());

        $this->endpoint = $endpoint;
        $this->languageDirectory = $directory;
    }
}
