# Dynamic resolving `api` and `directories`

Both configurations of `directories` and `api` are supporting dynamic resolving with placeholders. You can use in `types` and `names` configuration following placeholders:

- `%vendorName%` - the vendor name: `inpsyde`
- `%projectName%` - the project name: `wp-translation-downloader`.
- `%packageName%` - the full name of the package: `inpsyde/wp-translation-downloader`.
- `%packageType%` - the type of package, like ´wordpress-core`.
- `%packageVersion%` - calls `$package->getPrettyVersion()` from Composer.

> :information_source: For resolving the API-Endpoint, the `%packageVersion%` will be set to `''` when containing a `dev-*`-version.
> This change was made due incompatibility of WordPress.org GlotPress API when sending a `dev-*`-version: https://api.wordpress.org/translations/core/1.0/?version=dev-master

Here is a short example of using the dynmiac placeholders:

```json
{
    "languageRootDir": "/public/languages/",
    "api": {
        "types": {
            "wordpress-plugin": "https://github.com/%vendorName%/%projectName%/releases/tag/%version%/"
        },
        "names": {
            "inpsyde/*": "https://github.com/%packageName%/"
        }
    },
    "directories": {
        "types": {
            "library": "/%vendorName%/%projectName%/"
        },
        "names": {
            "inpsyde/*": "inpsyde/language-folder/%packageName%/"
        }
    }
}
```