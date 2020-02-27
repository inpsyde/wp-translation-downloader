# WordPress Translation Downloader

[![Version](https://img.shields.io/packagist/v/inpsyde/wp-translation-downloader.svg)](https://packagist.org/packages/inpsyde/wp-translation-downloader)
[![Status](https://img.shields.io/badge/status-active-brightgreen.svg)](https://github.com/inpsyde/wp-translation-downloader)
[![Downloads](https://img.shields.io/packagist/dt/inpsydewp-translation-downloader.svg)](https://packagist.org/packages/inpsyde/wp-translation-downloader)
[![License](https://img.shields.io/packagist/l/inpsyde/wp-translation-downloader.svg)](https://packagist.org/packages/inpsyde/wp-translation-downloader)


Composer plugin to download translations from the wordpress.org API or via self-hosted GlotPress.

## Installation

```
composer require inpsyde/wp-translation-downloader
```

## Configuration

The following configurations are available:

|name|type|required|description|
|---|---|---|---|
|languages|array|x|The iso codes you want to download|
|directory|string|x|The relative path to the `languages` directory|
|excludes|array| |An optional array for excluding certain packages|
|api|array| |An optional array which maps the `packageName` to an API-Endpoint|

**[!] Note:** You can use `*` as a placeholder to exclude multiple packages.
**[!] Note 2:** You can use `*` as a placeholder to match an API for multiple packages.

The configuration should be added to your `composer.json` in the `extra` property with the `wp-translation-downloader` key..

### Default configuration
Following is the default configuration for `inpsyde/wp-translation-downloader` to download translations from WordPress.org API:

**composer.json**
```json
{
    "name": "vendor/my-package",
    "extra": {
        "wp-translation-downloader": {
            "languages": [
                "de_DE"
            ],
            "directory": "public/wp-content/languages",
        }
    }
}
```

### Default configuration - own configuration file
You can, if you have a lot of configurations, move the whole `wp-translation-downloader`-configuration to an own json-file and just provide the file path like this:

**composer.json**
```json
{
    "name": "vendor/my-package",
    "extra": {
        "wp-translation-downloader": "./wp-translation-downloader.json"
    }
}
```

**wp-translation-downloader.json**
```json
{
    "languages": [
        "de_DE"
    ],
    "directory": "public/wp-content/languages",
}
```

### Exclude specific packages
To exclude specific packages, like _"I want to exclude all WordPress Plugins/Themes/Mu-Plugins from vendor `inpsyde`"_ you can use following:

**composer.json**
```json
{
    "name": "vendor/my-package",
    "require": {
        "inpsyde/wp-translation-downloader": "~0.1",
        "johnpbloch/wordpress": "5.3.*@stable",
        "inpsyde/google-tag-manager": "1.0",
        "wpackagist-plugin/wordpress-seo": "13.0",
    },
    "extra": {
        "wp-translation-downloader": "./wp-translation-downloader.json"
    }
}
```

**wp-translation-downloader.json**
```json
{
    "languages": [
        "de_DE"
    ],
    "excludes": ["inpsyde/*"],
    "directory": "public/wp-content/languages"
}
```

This will map to following matrix:

|package|type|downloaded|
|---|---|---|
|`johnpbloch/wordpress`|wordpress-core|yes|
|`inpsyde/wp-translation-downloader`|composer-plugin|skipped - not matching packageType|
|`inpsyde/google-tag-manager`|wordpress-plugin|no - matching with "exclude"|
|`wpackagist-plugin/wordpress-seo`|wordpress-plugin|yes|
           

### Use external GlotPress API
If you have for example private Plugins/Themes or you don't want to use the official translation for a Package, then you can use an own GlotPress installation.

To use this, you can map same like the `exclude` one or multiple packages to a different Endpoint. You can add placeholders for the different package types:

|placeholder|description|
|---|---|
|`%projectName%` | the name without vendor - Example: "wordpress-seo" |
|`%vendorName%` | Example: "wpackagist-plugin" |
|`%packageName%` | full name of the package - Example: "wpackagist-plugin/wordpress-seo" |
|`%packageType%` | type of the package - Example: "wordpress-plugin" |
|`%packageVersion`| version of the package - Example: "13.0" |

The example for replacing those looks like following:

**composer.json**
```json
{
    "name": "vendor/my-package",
    "require": {
        "inpsyde/wp-translation-downloader": "~0.1",
        "johnpbloch/wordpress": "5.3.*@stable",
        "wpackagist-plugin/wordpress-seo": "13.0",
        "wpackagist-theme/twentytwenty": "1.1"
    },
    "extra": {
        "wp-translation-downloader": "./wp-translation-downloader.json"
    }
}
```

**wp-translation-downloader.json**
```json
{
    "languages": [
        "de_DE"
    ],
    "directory": "public/wp-content/languages",
    "api": {
        "names": {
            "johnpbloch/wordpress": "https://my-glotpress-instance.tld/core/%packageVersion%",
            "wpackagist-plugin/*": "https://my-glotpress-instance.tld/plugins/%projectName%?version=%packageVersion%"
        },
        "types": {
            "wordpress-theme": "https://my-glotpress-instance.tld/theme/%projectName%?version=%packageVersion%"
        }
}
```

This will map to following matrix:

|package|API Url|
|---|---|
|`johnpbloch/wordpress`|`https://my-glotpress-instance.tld/core/5.3`|
|`inpsyde/wp-translation-downloader`|skipped - not matching packageType|
|`wpackagist-plugin/wordpress-seo`|`https://my-glotpress-instance.tld/plugins/wordpress-seo?version=13.0`|
|`wpackagist-theme/twentytwenty`|`https://my-glotpress-instance.tld/theme/twentytwenty?version=1.1`|

**[!]** Be aware, the "api"-list checks for the first matching result from top to bottom. If you want to have a more specific match, then you need to move it on top:

```json
{
    "api": {
        "names": {
            "wpackagist-plugin/*": "https://my-glotpress-instance.tld/plugins/%1$s?version=%2$s",
            "wpackagist-plugin/wordpress-seo": "https://someting-different.tld/..."
        }
    },
}
```

The rule for `wpackagist-plugin/wordpress-seo` will not be executed, because the `wpackagist-plugin/*`-rule matches first. You need to have following order:

Also the matching "names" will be checked first. Afterwards it will be checked if there is a matching "types".

```json
{
    "api": {
        "names": {,
            "wpackagist-plugin/wordpress-seo": "https://someting-different.tld/...",        
            "wpackagist-plugin/*": "https://my-glotpress-instance.tld/plugins/%1$s?version=%2$s"
        }
    },
}
```

## Support for Composer type="library"
The `inpsyde/wp-translation-downloader` also supports Composer `Package::getType() === 'library'`. By default, nothing will be done, but if configured via API for example to a self-hosted GlotPress - then those translation will be downloaded into `WP_LANG_DIR . '/library/'`.

Those can be accessed in your library via: 

```php
<?php
load_textdomain( 
    'your-package', 
    sprintf(WP_LANG_DIR . '/library/your-package-%s.mo', determine_locale())
);
```


## License

Copyright (c) Inpsyde GmbH.
