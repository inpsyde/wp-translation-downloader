# Locker

After having downloaded translations, WP Translation Downloader generates a `wp-translation-downloader.lock` 
file. It will contain information about all installed packages translations.

Here's that file's content looks like:

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
2. if so, check the locked package data contains info for the target language
3. If so, check:
    - locked `version` is greater or equal to current package's version
    - locked `updated` is greater or equal to translation's `lastUpdated` info coming from GlotPress

If **all** the above checks pass, the package is considered locked and not installed/updated, 
otherwise will be downloaded as usual.

## Lock file version control

Please note no check is made if the locked packages' translation files are _actually_ 
present in the target directory. For that reason `wp-translation-downloader.lock` **should be
git-ignored** if the translation files are git-ignored.
