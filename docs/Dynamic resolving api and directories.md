# Dynamic resolving `api` and `directories`

Both configurations of `directories` and `api` are supporting dynamic resolving with placeholders. You can use in `types` and `names` configuration following placeholders:

- `%vendorName%` - the vendor name: `inpsyde`
- `%projectName%` - the project name: `wp-translation-downloader`.
- `%packageName%` - the full name of the package: `inpsyde/wp-translation-downloader`.
- `%packageType%` - the type of package, like ´wordpress-core`.
- `%packageVersion%` - calls `$package->getPrettyVersion()` from Composer.

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