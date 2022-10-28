# Locker

After having downloaded translations, WP Translation Downloader generates a `wp-translation-downloader.lock` 
file. It will contain information about all installed package translations.

This is how the file's content looks like:

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
result in locked packages _not_ being installed again.

In fact, WP Translation Downloader will:

1. Check if the target package name is available in the lock file
2. If so, check the locked package data for information about the target language
3. If so, check if:
    - The locked `version` is greater than or equal to the current package version
    - The locked `updated` is greater than or equal to the translation `lastUpdated` information coming from GlotPress

If **all** the above checks pass, the package is considered locked and not installed/updated, 
otherwise it will be downloaded as usual.

## Lock file version control

Please note, no check is made if the translation files of the locked package are _actually_ 
present in the target directory. For that reason, `wp-translation-downloader.lock` **should be
git-ignored** if the translation files are git-ignored.
