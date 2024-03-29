# Dynamic resolving `api` and `directories`

The `directories` and `api` configurations support dynamic resolving with placeholders, both in
their `types` and `names` sub-keys.

Here's the summary of all supported placeholders:

| Placeholder                | Description                                                                                          |
|----------------------------|------------------------------------------------------------------------------------------------------|
| `%projectName%`            | Project name, e.g. `"wp-translation-downloader"` for the package `inpsyde/wp-translation-downloader` |
| `%vendorName%`             | Vendor name, e.g. `"inpsyde"` for the package `inpsyde/wp-translation-downloader`                    |
| `%packageName%`            | Full package name, e.g. `"inpsyde/wp-translation-downloader"`                                        |
| `%packageType%`            | Package type, e.g. `"wordpress-plugin"`                                                              |
| `%packageVersion%`         | Package version, e.g. `"13.0"`                                                                       |
| `%packageDistReference%`   | The reference of the distribution archive. E.g. master, 1.0.0 or a commit hash                       |
| `%packageSourceReference%` | The repository reference. E.g. master, 1.0.0 or a commit hash                                        |
| `%packageUniqueName%`      | The package unique name, constructed from name and version                                           |

:information_source: **Notes:**

- `%packageVersion%` is resolved with the "pretty" package version (as Composer calls it)<sup>1</sup>
  that usually exactly matches the Git tag. In the case of ["virtual packages"](./Configuration.md#virtual-packages)
  it will match exactly what's defined in virtual package definition.
- When resolving the API endpoint, `%packageVersion%` will be set to an empty string when the
  package is installed from using a "dev" requirement like `dev-master`. The reason is that GlotPress 
  does not understand that kind of versioning (that's Composer-specific) and would return no results.
- When resolving the API endpoint, any query variable with an empty value will be removed.
  For example, having a URL like `http://example.com?ver=%packageVersion%` in the case of an empty 
  version will result in `http://example.com` and not `http://example.com?ver=`

## Usage example

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

---

<sup>1</sup> Composer defines two versions for each package: a "pretty" version that matches what's
defined in version control, and a "canonical" version that is obtained by "normalizing" the pretty
version. For example, a "pretty" version `1.0` corresponds to a `1.0.0.0` "canonical" version.