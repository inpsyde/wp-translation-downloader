## Support Composer types

The WP Translation Downloader will map the `type`-field in composer.json to folders within your WordPress installation `languages`-folder.

### WordPress types

WordPress specific types like following:

- `worpdress-plugin`
- `wordpress-theme`
- `wordpress-core`

are supported out of the box and will be placed in the corresponding folder defined by WordPress.

### type `library`
WP Translation Downloader also supports Composer packages of type `library`. Translation files will be downloaded to `WP_LANG_DIR . '/library/'`. To enable it, you have to set the `"api.types"` object accordingly (see above).

To access the translations in your library, including a fallback, you can add a helper function like this:
```php
<?php
namespace Acme;

function loadLibraryTextDomain(string $domain, string $libraryLangPath): bool
{
    $locale = apply_filters('library_locale', determine_locale(), $domain);
    $moFile = "{$domain}-{$locale}.mo";

    // Try to load from the languages directory first.
    if (load_textdomain($domain, WP_LANG_DIR . "/library/{$moFile}")) {
        return true;
    }

    return (bool)load_textdomain($domain, trailingslashit($libraryLangPath) . $moFile);
}
```