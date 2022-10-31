# Support Composer types

WP Translation Downloader will map the `type` of Composer packages, defined in their `composer.json` file 
to folders within WordPress installation `languages` folder.

## WordPress types

The following WordPress-specific types are supported out-of-the-box:

- `worpdress-plugin`
- `wordpress-theme`
- `wordpress-core`

Their translation files will be placed in the folders WordPress expect them to be in.

## `library` type

WP Translation Downloader also supports Composer packages having the `library` type.

Translation files will be downloaded to `WP_LANG_DIR . '/library/'`.

To enable this behavior, `"api.types"` [configuration](./Configuration.md) must be set.

### Loading library translations

WordPress has [functions](https://developer.wordpress.org/?s=_textdomain&post_type%5B%5D=wp-parser-function) 
to load translations for its "standard" types, but it does not provide helpers for the "library" type.

A simple yet flexible helper function (that mimics core functions) could look like so:

```php
<?php
function prefix_load_library_textdomain(string $domain, string $library_lang_path = ''): bool
{
    $locale = apply_filters('library_locale', determine_locale(), $domain);
    $mo_file = "{$domain}-{$locale}.mo";

    // Try to load from the site languages' directory first.
    if (load_textdomain($domain, WP_LANG_DIR . "/library/{$mo_file}")) {
        return true;
    }
    
    // Now try to load from library languages folder, if provided
    if ($library_lang_path) {
        return (bool)load_textdomain($domain, trailingslashit($library_lang_path) . $mo_file);
    }

    return false;
}
```