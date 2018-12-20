# WordPress translation downloader

Composer plugin to download translations from wordpress.org API.

## Installation

```
composer require inpsyde/wp-translation-downloader
```

## Usage
After successfully configuration it will automatically download on `composer install` the `.po/.mo`-files from official wordpress.org API.

## Configuration

Following configurations are available:

|name|type|required|description|
|---|---|---|---|
|languages|array|x|The iso codes you want to download|
|directory|string|x|The relative path to the `languages` directory|
|excludes|array| |an optional array to exclude specific packages|

*[!] Note:* You can use `*` as wildcard to exclude multiple packages.

The configuration should be placed into your `composer.json` into the `extra` node with key `wp-translation-downloader`.

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