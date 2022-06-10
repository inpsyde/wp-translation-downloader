# Configuration

The following configuration properties are available:

| Name                | Type           | Required | Description                                                                      |
|---------------------|----------------|----------|----------------------------------------------------------------------------------|
| `auto-run`          | `bool`         |          | By default `true`. If `false`, the Plugin will not run on install/update command |
| `languages`         | `list<string>` | x        | The iso codes you want to download                                               |
| `excludes`          | `list<string>` |          | Array of excluded package names                                                  |
| `api.names`         | `array`        |          | Array of package names mapped to a Glotpress API endpoint                        |
| `api.types`         | `array`        |          | Array of package types mapped to a Glotpress API endpoint                        |
| `directory`         | `string`       |          | :warning: **deprecated:** The relative path to the `languages` directory.        |
| `languageRootDir`   | `string`       | x        | The relative path to the `languages` directory. Replaces deprecated `directory`. |
| `directories.names` | `array`        | x        | Array of package names mapped to `language`sub-folders.                          |
| `directories.types` | `array`        | x        | Array of package types mapped to `language` sub-folder.                          |
| `virtual-packages`  | `array`        |          | An array of objects with `name`, `type` and optionally `version`.                |

> **[!] Note:** You can use `*` as wildcard in the `exclude`, `api.names`, `api.types`, `directories.names`
> and `directories.types` properties.

The configuration object has to be placed in `composer.json` in the `extra.wp-translation-downloader` property.

## Configuration in `composer.json`

Following is the minimum configuration to download translations from the WordPress.org API:

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

For better readability and portability, it is also possible to use a different file which contains 
only the WP Translation Downloader configuration (everything that would go in the 
`extra.wp-translation-downloader` object).

One use case could be to reuse the same configuration for many websites that are located in the 
same parent folder.

For this it's necessary to use the configuration `"extra.wp-translation-downloader"` in 
`composer.json` to set the path of the custom file. The path must be relative to the folder 
containing `composer.json`:

```json
{
    "extra": {
        "wp-translation-downloader": "./wp-translation-downloader.json"
    }
}
```

An interesting application is to place the configuration file in its own Composer package and then
referenced by path:

```json
{
    "extra": {
        "wp-translation-downloader": "./vendor/acme/translation-downloader-config/config.json"
    }
}
```

## Exclude specific packages

To exclude specific packages you can use the following configuration:

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

| Package                             | Type               | Downloaded                    |
|-------------------------------------|--------------------|-------------------------------|
| `johnpbloch/wordpress`              | `wordpress-core`   | Yes                           |
| `inpsyde/wp-translation-downloader` | `composer-plugin`  | No: unsupported package type  |
| `inpsyde/google-tag-manager`        | `wordpress-plugin` | No: matching with `"exclude"` |
| `wpackagist-plugin/wordpress-seo`   | `wordpress-plugin` | Yes                           |

## API - Custom Glotpress API endpoints

WP Translation Downloader supports custom [Glotpress](https://github.com/GlotPress/GlotPress-WP) 
installations if you want to install e.g. private plugins or themes or if you don't want to use the 
official translation for a package.

The Glotpress APIs are mapped to package names or package types via the 
`"api.names"` and `"api.types"` configuration.

In those configurations, is possible to define custom endpoint URLs leveraging following placeholders:

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
| `inpsyde/wp-translation-downloader` | Skipped: unsupported package type (composer-plugin)                    |
| `wpackagist-plugin/wordpress-seo`   | `https://my-glotpress-instance.tld/plugins/wordpress-seo?version=13.0` |
| `wpackagist-theme/twentytwenty`     | `https://my-glotpress-instance.tld/theme/twentytwenty?version=1.1`     |

**[!] Notes:**

1. The `api` list is precessed top to bottom, the first matching result is used
2. `api.names` take precedence over `api.types`. Take the following configuration:

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
matches first. To make it match, it is necessary to invert the order.

More info on dynamic resolving URLs with placeholders can be found [here](./Dynamic%20resolving%20api%20and%20directories.md)

## `languageRootDir` and `directories`

WP Translation Downloader supports custom language directory locations for your package.

The sub-folders within `languageRootDir` are mapped to package names or package types via the 
`"directories.names"`and `"directories.types"` objects. 

The default package type to sub-folders map is summarized in the following table:

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

With the above configuration any package having `library` type will have translations installed in
`./public/languages/some-folder/`.
And any package having `custom-type` type will have translations installed in `./public/languages/custom/`.
(Paths relative to root package's root folder).

### Configuration by package name

Target directories can be configured by package name, for example:

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

With the above configuration the package `my/package-name` will have translations installed in
`./public/languages/some-folder/` (relative to root package's root folder).

### Dynamic configuration

The `directories` configuration, both by name and by type, supports dynamic resolving with placeholders 
as better described [here](./Dynamic%20resolving%20api%20and%20directories.md).


## Virtual Packages

It might be desirable to install translations for packages that are *not* required in Composer.
One example might be a hosting pre-installing WordPress/plugins/themes or dockerized environments.

In such cases, WP Translation Downloader support "virtual packages" in configuration: a bare-minimum
packages definition enough for WP Translation Downloader to know what translations to download.

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

Each object in `virtual-packages` array supports following keys:

| Key       | Required | Type     |
|-----------|----------|----------|
| `name`    | x        | `string` |
| `type`    | x        | `string` |
| `version` |          | `string` |

The `name` and `type` properties are those that normally would be defined in package's `composer.json`.

The version is optional considering that wp.org API does not require a version to be passed,
assuming latest version if that is not passed.

In any case, all the three properties, if defined and not empty, will be used when building the
API endpoint (no matter if default or customized by name/type).
