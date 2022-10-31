# Configuration

The following configuration properties are available:

| Name                | Type           | Required | Description                                                                              |
|---------------------|----------------|----------|------------------------------------------------------------------------------------------|
| `auto-run`          | `bool`         |          | Default: `true`. If `false`, the plugin will not run on Composer install/update command. |
| `languages`         | `list<string>` | x        | The languages ISO codes (locales) to download.                                           |
| `excludes`          | `list<string>` |          | Array of excluded package names.                                                         |
| `api.names`         | `array`        |          | Array of package names mapped to a GlotPress API endpoint.                               |
| `api.types`         | `array`        |          | Array of package types mapped to a GlotPress API endpoint.                               |
| `directory`         | `string`       |          | :warning: **deprecated:** The relative path to the `languages` directory.                |
| `languageRootDir`   | `string`       | x        | The relative path to the `languages` directory. Replaces deprecated `directory`.         |
| `directories.names` | `array`        | x        | Array of package names mapped to `language` sub-folders.                                 |
| `directories.types` | `array`        | x        | Array of package types mapped to `language` sub-folder.                                  |
| `virtual-packages`  | `array`        |          | An array of objects with `name`, `type` and optionally `version`.                        |

> :information_source: **Note:** The `*` wildcard to target multiple package names is supported for 
> `exclude`, `api.names` and `directories.names`.

The configuration object has to be placed in the `composer.json` file in the `extra.wp-translation-downloader` 
property.

## Configuration in `composer.json`

The following is the minimum configuration to download translations from the WordPress.org API:

```json
{
    "name": "vendor/my-package",
    "extra": {
        "wp-translation-downloader": {
            "languages": [
                "de_DE"
            ],
            "languageRootDir": "public/wp-content/languages"
        }
    }
}
```

## Configuration in custom file

For better readability and portability, it is also possible to use a different file to hold just
WP Translation Downloader configuration, so everything that would go in the 
`extra.wp-translation-downloader` object.

That enables the reuse of the same configuration for multiple websites located under the same parent 
folder.

If you store the configuration in a separate file, you have to set `"extra.wp-translation-downloader"` in the
`composer.json` file relative to the folder of the `composer.json` file.

For example:

```json
{
    "extra": {
        "wp-translation-downloader": "./wp-translation-downloader.json"
    }
}
```

An interesting usage of this functionality is to place the configuration file in its own 
Composer package and then referencing to its path in the root package:

```json
{
    "extra": {
        "wp-translation-downloader": "./vendor/acme/translation-downloader-config/config.json"
    }
}
```

## Exclude specific packages

It is possible to exclude packages from being processed. Take the following example:

```json
{
    "name": "vendor/my-package",
    "require": {
        "inpsyde/wp-translation-downloader": "dev-master",
        "roots/wordpress": "5.3.*@stable",
        "inpsyde/google-tag-manager": "1.0",
        "wpackagist-plugin/wordpress-seo": "13.0"
    },
    "extra": {
        "wp-translation-downloader": {
            "languages": [
                "de_DE"
            ],
            "excludes": [
                "inpsyde/*"
            ],
            "languageRootDir": "public/wp-content/languages"
        }
    }
}
```

The behavior of the above configuration is summarized in the following matrix:

| Package                             | Type               | Downloaded?                  |
|-------------------------------------|--------------------|------------------------------|
| `johnpbloch/wordpress`              | `wordpress-core`   | Yes                          |
| `inpsyde/wp-translation-downloader` | `composer-plugin`  | No: unsupported package type |
| `inpsyde/google-tag-manager`        | `wordpress-plugin` | No: matching `"exclude"`     |
| `wpackagist-plugin/wordpress-seo`   | `wordpress-plugin` | Yes                          |

## API - Custom GlotPress API endpoints

WP Translation Downloader supports custom [GlotPress](https://github.com/GlotPress/GlotPress-WP) 
installations. That is useful when installing _private_ plugins or themes which can't use "official" 
wp.org translation channels.

Custom GlotPress API endpoints are "resolved" from package names or types using the 
`"api.names"` and `"api.types"` configurations.

In those configurations, it is possible to define custom endpoint URLs leveraging the following placeholders:

| Placeholder        | Description                                                 |
|--------------------|-------------------------------------------------------------|
| `%projectName%`    | Project name, e.g. `"wordpress-seo"`                        |
| `%vendorName%`     | Vendor name, e.g. `"wpackagist-plugin"`                     |
| `%packageName%`    | Full package name, e.g. `"wpackagist-plugin/wordpress-seo"` |
| `%packageType%`    | Package type, e.g. `"wordpress-plugin"`                     |
| `%packageVersion%` | Package version, e.g. `"13.0"`                              |

Example `composer.json` file:

```json
{
    "name": "vendor/my-package",
    "require": {
        "inpsyde/wp-translation-downloader": "dev-master",
        "johnpbloch/wordpress": "5.3.*@stable",
        "wpackagist-plugin/wordpress-seo": "13.0",
        "wpackagist-theme/twentytwenty": "1.1"
    },
    "extra": {
        "wp-translation-downloader": {
            "languages": [
                "de_DE"
            ],
            "languageRootDir": "public/wp-content/languages",
            "api": {
                "names": {
                    "johnpbloch/wordpress": "https://my-glotpress-instance.tld/core/%packageVersion%",
                    "wpackagist-plugin/*": "https://my-glotpress-instance.tld/plugins/%projectName%?version=%packageVersion%"
                },
                "types": {
                    "wordpress-theme": "https://my-glotpress-instance.tld/theme/%projectName%?version=%packageVersion%"
                }
            }
        }
    }
}
```

The behavior caused by the configuration above is summarized in the following matrix:

| Package                             | API URL                                                                |
|-------------------------------------|------------------------------------------------------------------------|
| `johnpbloch/wordpress`              | `https://my-glotpress-instance.tld/core/5.3`                           |
| `inpsyde/wp-translation-downloader` | Skipped: unsupported package type (`composer-plugin`)                  |
| `wpackagist-plugin/wordpress-seo`   | `https://my-glotpress-instance.tld/plugins/wordpress-seo?version=13.0` |
| `wpackagist-theme/twentytwenty`     | `https://my-glotpress-instance.tld/theme/twentytwenty?version=1.1`     |

Read more about [dynamic resolving URLs with placeholders](./Dynamic%20resolving%20api%20and%20directories.md)

:information_source: **Notes:**

1. `api.names` takes precedence over `api.types`.
2. The `api.names` list is processed top-to-bottom, the first matching result is used. Take the 
   following configuration:

```json
{
    "api": {
        "names": {
            "wpackagist-plugin/*": "https://my-glotpress-instance.tld/plugins/%1$s?version=%2$s",
            "wpackagist-plugin/wordpress-seo": "https://someting-different.tld/…"
        }
    }
}
```

The API endpoint for `wpackagist-plugin/wordpress-seo` won't match because `wpackagist-plugin/*`
matches first. To make it match, it is necessary to reverse the order.

## `languageRootDir` and `directories`

WP Translation Downloader supports custom target directories for translations files.

Sub-folders within the languages root folder (configured in `languageRootDir`) are mapped from package names or 
types via, respectively, the `"directories.names"` and `"directories.types"` objects. 

The default package type to sub-folders mapping is summarized in the following table:

| Package type       | Sub-folder  | Note                                                                     |
|--------------------|-------------|--------------------------------------------------------------------------|
| `wordpress-core`   | `/`         | WP core translations will be placed directly into `languageRootDir`      |
| `wordpress-plugin` | `/plugins/` |                                                                          | 
| `wordpress-theme`  | `/themes/`  |                                                                          | 
| `library`          | `/library/` | Not supported by WP, but we will place those also into `languageRootDir` |

### Configuration by package type

Target directories can be configured by package type, for example:

```json
{
    "languageRootDir": "public/languages/",
    "directories": {
        "types": {
            "library": "/some-folder/",
            "custom-type": "/custom/"
        }
    }
}
```

By using the above configuration, any package with the type `library` will have translations installed into
`./public/languages/some-folder/`.
And any package with the type `custom-type` will have translations installed into `./public/languages/custom/`.
(Paths are relative to the root folder of the root package).

### Configuration by package name

Target directories can be configured by the package name, for example:

```json
{
    "languageRootDir": "public/languages/",
    "directories": {
        "names": {
            "my/package-name": "/some-folder/"
        }
    }
}
```

By using the above configuration, the package `my/package-name` will have translations installed into
`./public/languages/some-folder/` (relative to the root folder of the root package).

### Dynamic configuration

The `directories` configuration, both by name and by type, supports 
[dynamic resolving via placeholders](./Dynamic%20resolving%20api%20and%20directories.md).



## Virtual Packages

It might be desirable to install translations for packages that are *not* required in Composer.
One example might be a hosting pre-installed WordPress/plugins/themes, or dockerized environments.

In such scenarios, WP Translation Downloader support "virtual packages" in the configuration: a 
bare-minimum packages definition enough for WP Translation Downloader to know what translations 
to download.

Take the following example:

```json
{
    "languageRootDir": "public/languages/",
    "languages": ["de_DE"],
    "virtual-packages": [
        {
            "name": "wordpress/wordpress",
            "type": "wordpress-core",
            "version": "5.8"
        }
    ]
}
```

Each object in the `virtual-packages` array supports the following keys:

| Key       | Required | Type     |
|-----------|----------|----------|
| `name`    | x        | `string` |
| `type`    | x        | `string` |
| `version` |          | `string` |

The `name` and `type` properties are those that normally would be defined in `composer.json` file of the package.

The `version` property is optional, considering that wp.org API does not require a version to be 
passed, assuming latest version, if none is passed.

In any case, all three properties (if defined and not empty), will be used when building the
API endpoint (no matter if default or customized by name/type).
