# Locker

After having downloaded translations, WP Translation Downloader generates a `wp-translation-downloader.lock` 
file. It will contain information about all installed packages translations.

Here's how its content looks like:

```json
{
    "{packageName}": {
        "translations": {
            "{language}": {
                "updated": "{date}",
                "version": "{version}"
            }
        }
    }
}
```

Using WP Translation Downloader to downloaded translations in the presence of that lock file will
result in locked packages _not_ be installed again.

In fact, WP Translation Downloader will:

1. Check if target package name is available in the lock file
2. if so, check if the info for target language is present in the locked package data
3. If so, check:
    - locked version is greater or equal to current version
    - locked `updated` is greater or equal to translation's `lastUpdated` info coming from Glotpress

If **all** the above checks above pass, the package is considered locked and not installed/updated, 
otherwise will be downloaded as usual.

## Lock file version control

Please note no check is made if the locked packages' translation files are _actually_ 
present in the target directory. For that reason `wp-translation-downloader.lock` **should be
git-ignored** if the translation files are git-ignored.
