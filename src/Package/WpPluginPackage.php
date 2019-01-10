<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader\Package;

use Composer\Package\Package;

final class WpPluginPackage extends Package implements TranslateablePackage
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
            'https://api.wordpress.org/translations/plugins/1.0/?slug=%1$s&version=%2$s',
            $this->projectName(),
            $this->getPrettyVersion()
        );
    }
}