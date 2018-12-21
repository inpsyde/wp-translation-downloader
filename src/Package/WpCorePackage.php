<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader\Package;

use Composer\Package\Package;

final class WpCorePackage extends Package implements TranslateablePackage
{

    use TranslateablePackageTrait;

    public function __construct(Package $package, string $directory)
    {
        parent::__construct($package->getName(), $package->getVersion(), $package->getPrettyVersion());

        $this->projectName = $this->prepareProjectName($this->getName());
        $this->languageDirectory = $directory;
    }

    public function apiUrl(): string
    {
        return sprintf(
            'https://api.wordpress.org/translations/core/1.0/?version=%1$s',
            $this->getPrettyVersion()
        );
    }
}
