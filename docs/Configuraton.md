# Configuration

The following configuration properties are available:

| name                | type       | required | description                                                                      |
|---------------------|------------|----------|----------------------------------------------------------------------------------|
| `auto-run`          | `bool`     |          | By default `true`. If `false`, the Plugin will not run on install/update command |
| `languages`         | `string[]` | x        | The iso codes you want to download                                               |
| `excludes`          | `string[]` |          | Array of excluded package names                                                  |
| `api.names`         | `array`    |          | Array of package names mapped to a GlotPress API endpoint                        |
| `api.types`         | `array`    |          | Array of package types mapped to a GlotPress API endpoint                        |
| `directory`         | `string`   |          | :warning: **deprecated:** The relative path to the `languages` directory.        |
| `languageRootDir`   | `string`   | x        | The relative path to the `languages` directory - replaces `directory`-key.       |
| `directories.names` | `array`    | x        | Array of package names mapped to `language`sub-folders.                          |
| `directories.types` | `array`    | x        | Array of package types mapped to `language` sub-folder.                          |
| `virtual-packages`  | `array`    |          | An array of objects with `name`, `type` and optionally `version`.                |

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

For better readability and portability, it is also possible to use a different file which contains only the WP
Translation Downloader configuration (everything that would go in the `extra.wp-translation-downloader` object).

One use case could be to reuse the same configuration for many websites that are located in the same parent folder.

For this it's necessary to use the configuration `"extra.wp-translation-downloader"` in `composer.json` to set the path
of the custom file. The path must be relative to the folder containing `composer.json`:

```json
{
    "extra": {
        "wp-translation-downloader": "./wp-translation-downloader.json"
    }
}
```

This also allows you to load the configuration file from a custom Composer package and make it available to WP
Translation Manager by pointing to the file in the vendor folder:

```json
{
    "extra": {
        "wp-translation-downloader": "./vendor/my-company/wp-translation-downloader-shared/config.json"
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
        "johnpbloch/wordpress": "5.3.*@stable",
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

This will map to the following matrix:

| package                             | type               | downloaded                     |
|-------------------------------------|--------------------|--------------------------------|
| `johnpbloch/wordpress`              | `wordpress-core`   | yes                            |
| `inpsyde/wp-translation-downloader` | `composer-plugin`  | no - not matching package type |
| `inpsyde/google-tag-manager`        | `wordpress-plugin` | no - matching with `"exclude"` |
| `wpackagist-plugin/wordpress-seo`   | `wordpress-plugin` | yes                            |

## API - Custom GlotPress API endpoints

WP Translation Downloader supports custom [GlotPress](https://github.com/GlotPress/GlotPress-WP) installations if you
want to install e.g. private plugins or themes or if you don't want to use the official translation for a package.

The GlotPress APIs are mapped to package names or package types via the `"api.names"` and `"api.types"` objects.
The following placeholders are provided for this:

| placeholder        | description                                                 |
|--------------------|-------------------------------------------------------------|
| `%projectName%`    | project name, e.g. `"wordpress-seo"`                        |
| `%vendorName%`     | vendor name, e.g. `"wpackagist-plugin"`                     |
| `%packageName%`    | full package name, e.g. `"wpackagist-plugin/wordpress-seo"` |
| `%packageType%`    | package type, e.g. `"wordpress-plugin"`                     |
| `%packageVersion%` | package version, e.g. `"13.0"`                              |

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

This will map to the following matrix:

| package                             | API url                                                                |
|-------------------------------------|------------------------------------------------------------------------|
| `johnpbloch/wordpress`              | `https://my-glotpress-instance.tld/core/5.3`                           |
| `inpsyde/wp-translation-downloader` | skipped - not matching `packageType`                                   |
| `wpackagist-plugin/wordpress-seo`   | `https://my-glotpress-instance.tld/plugins/wordpress-seo?version=13.0` |
| `wpackagist-theme/twentytwenty`     | `https://my-glotpress-instance.tld/theme/twentytwenty?version=1.1`     |

**[!] Notes:**

1. The `"api"` list checks from top to bottom for the first matching result
2. Package names will be checked first. Then it is checked whether there is a matching package type:

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

The rule for `wpackagist-plugin/wordpress-seo` won't match because the `wpackagist-plugin/*` rule matches first. You'll
need to have following order:

```json
{
    "api": {
        "names": {
            "wpackagist-plugin/wordpress-seo": "https://someting-different.tld/…",
            "wpackagist-plugin/*": "https://my-glotpress-instance.tld/plugins/%1$s?version=%2$s"
        }
    }
}
```

## `languageRootDir` and `directories`

WP Translation Downloader supports custom language directory locations for your package.

The sub-folders within `languageRootDir` are mapped to package names or package types via the `"directories.names"`
and `"directories.types"` objects. By default, following package types will be mapped into following sub-folders:

| package type       | sub-folder  | note                                                                     |
|--------------------|-------------|--------------------------------------------------------------------------|
| `wordpress-core`   | `/`         | WP core translations will be placed directly into `languageRootDir`      |
| `wordpress-plugin` | `/plugins/` |                                                                          | 
| `wordpress-theme`  | `/themes/`  |                                                                          | 
| `library`          | `/library/` | Not supported by WP, but we will place those also into `languageRootDir` |

### Configure by `type`

To change the directories by type do following:

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

Which will resolve your path for type `library` into `/{root path}/public/languages/some-folder/` and for `custom-type`
into `/{root path}/public/languages/custom/`.

### Configure by `name`

To change the directory for a specific package by name you can do following:

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

This will place the package with `"name": "my/package-name"` in composer.json
into `/{root path}/public/languages/some-folder/`.


## Virtual Packages
Sometimes it is required to not define a dependency in `composer.json` via `require` or `require-dev` but still need the translations to be installed. That can be the case in the WordPress-world for hosting solutions or dockerized enviornments like https://hub.docker.com/_/wordpress which already shipping WordPress pre-installed.

To install translations for "pre-installed platform" dependencies we introduced `virtual-packages` as part of the `wp-translation-downloader`-configuration. The following example will download additionally to all `require`-dependencies of `composer.json` the WordPress core translations:

```json
{
    "languageRootDir": "public/languages/",
    "languages": ["de_DE"],
    "virtual-packages": [
        {
            "name": "johnpbloch/wordpress",
            "type": "wordpress-core",
            "version": "5.8"
        }
    ]
}
```

The configuration fields are following:

| field     | required | type     | description                                                                          |
|-----------|----------|----------|--------------------------------------------------------------------------------------|
| `name`    | x        | `string` | The packageName of your pre-installed dependency.                                    |
| `type`    | x        | `string` | The type which is usally defined in `composer.json` to resolve the correct endpoint. |
| `version` |          | `string` | The string in which version you want to install the translations.                    |

We allow to lock a version optionally, since WordPress.org Translation API resolves translations dynamically via `?version=`. When the field is being empty the latest translations will be downloaded.

| version string | endpoint                                                       |
|----------------|----------------------------------------------------------------|
| `"5.9"`        | https://api.wordpress.org/translations/core/1.0/?version=5.9   |
| `"5.9.3"`      | https://api.wordpress.org/translations/core/1.0/?version=5.9.3 |
| `""`           | https://api.wordpress.org/translations/core/1.0/               |
| _none set      | https://api.wordpress.org/translations/core/1.0/               |