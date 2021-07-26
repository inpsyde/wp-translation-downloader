# WordPress Translation Downloader

[![Version](https://img.shields.io/packagist/v/inpsyde/wp-translation-downloader.svg)](https://packagist.org/packages/inpsyde/wp-translation-downloader)
[![Status](https://img.shields.io/badge/status-active-brightgreen.svg)](https://github.com/inpsyde/wp-translation-downloader)
[![Downloads](https://img.shields.io/packagist/dt/inpsyde/wp-translation-downloader.svg)](https://packagist.org/packages/inpsyde/wp-translation-downloader)
[![License](https://img.shields.io/packagist/l/inpsyde/wp-translation-downloader.svg)](https://packagist.org/packages/inpsyde/wp-translation-downloader)

Composer plugin to download translations from the WordPress.org API or from custom GlotPress installations.

## Composer v1 and v2
Since Composer v2 introduced various changes in public API, we had to separate some logic and refactor code in our Plugin.

To ensure no failures and best compatibility, we recommend following:

|Composer|WP Translation Downloader|
|---|---|
|1.x|1.x|
|2.x|2.x|
 
**[!]** Composer v1 does also work in WP Translation Downloader v2, but is not officially supported. We're recommending to stick with WP Translation Downloader v1 as defined in the matrix above.

## Installation

```
composer require inpsyde/wp-translation-downloader
```

## Documentation

1. [Configuration](./docs/Configuraton.md)
2. [Supported composer types](./docs/Supported-composer-types.md)
3. [Locker](./docs/Locker.md)
4. [Commands](./docs/Commands.md)

## License

Copyright (c) Inpsyde GmbH
