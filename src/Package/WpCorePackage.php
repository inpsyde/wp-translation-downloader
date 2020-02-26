<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader\Package;

use Composer\Package\Package;

final class WpCorePackage extends Package implements TranslatablePackage
{

    use TranslatablePackageTrait;

    public function __construct(Package $package, string $directory, string $endpoint = null)
    {
        parent::__construct($package->getName(), $package->getVersion(), $package->getPrettyVersion());


        $this->endpoint = $endpoint ?? 'https://api.wordpress.org/translations/core/1.0/?version=%1$s';
        $this->projectName = $this->prepareProjectName($this->getName());
        $this->languageDirectory = $directory;
    }

    public function apiUrl(): string
    {
        return sprintf(
            $this->endpoint,
            $this->getPrettyVersion()
        );
    }
}
