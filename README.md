# WordPress Translation Downloader

[![Version](https://img.shields.io/packagist/v/inpsyde/wp-translation-downloader.svg)](https://packagist.org/packages/inpsyde/wp-translation-downloader)
[![Status](https://img.shields.io/badge/status-active-brightgreen.svg)](https://github.com/inpsyde/wp-translation-downloader)
[![Downloads](https://img.shields.io/packagist/dt/inpsydewp-translation-downloader.svg)](https://packagist.org/packages/inpsyde/wp-translation-downloader)
[![License](https://img.shields.io/packagist/l/inpsyde/wp-translation-downloader.svg)](https://packagist.org/packages/inpsyde/wp-translation-downloader)


Composer plugin to download translations from the wordpress.org API.

## Installation

```
composer require inpsyde/wp-translation-downloader
```

## Usage
After successful configuration, the `.po`/`.mo` files are automatically downloaded from the official wordpress.org API on `composer install`. Also, translation files are deleted when a package is removed on `composer install`.
## Configuration

The following configurations are available:

|name|type|required|description|
|---|---|---|---|
|languages|array|x|The iso codes you want to download|
|directory|string|x|The relative path to the `languages` directory|
|excludes|array| |An optional array for excluding certain packages|

*[!] Note:* You can use `*` as a placeholder to exclude multiple packages.

The configuration should be added to your `composer.json` in the `extra` property with the `wp-translation-downloader` key.

**Example:**

```json
"wp-translation-downloader": {
    "languages": [
        "de_DE"
    ],
    "directory": "public/wp-content/languages",
    "excludes": ["inpsyde/*"]
}
```

## License

Copyright (c) Inpsyde GmbH.
