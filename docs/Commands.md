# Commands

You can also use WP Translation Downloader via composer cli. Following commands are available:

## Download translations

**Command:** `composer wp-translation-downloader:download [--packages=...]`

Downloads for all listed packages the translations. By default, when no packages are defined, all packages from composer.json will be processed.

## Clean-up Translations

**Command:** `composer wp-translation-downloader:clean-up`

Removes all files from languages directories.

## Clean cache

**Command:** `composer wp-translation-downloader:clean-cache`

WP Translation Downloader uses the Composer Cache which can be usally cleared via `composer clear-cache` command. This command will also delete all dependencies cached which is far from ideal. Therefor, we provide a command to just clean the cache dir: