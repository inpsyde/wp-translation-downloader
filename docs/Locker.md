# Locker

WP Translation Downloader will generate on installation a `wp-translation-downloader.lock` file. This file will contain all installed packages with translations by languages like following:

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

On second install WP Translation Downloader will check if for a given packageName a translation is available and determine if the translation is locked.

A locked translations will be identified by following:

1. The current packageName-language entry will be searched in `.lock`-file.
2. If available, then following checks will be done:
    - The lock version is greater or equal then current version.
    - The lock lastUpdated is greater or equal then current lastUpdated.

--> If both of those checks are matching, then the current packageName-language is locked and will **not** updated.

--> If one of those is **not** matching, then the current packageName-language is **not** locked and will be updated.

