# Commands

You can also use WP Translation Downloader via Composer CLI. Following commands are available:

## Download translations

**Command:**
```shell
composer wp-translation-downloader:download [--packages=<package list>]
```

Downloads for packages translations.

By default, when no packages are passed via `--packages` flag, _all_ installed packages will be 
processed.

Note: `--packages` flag must be a comma separated list of package names, with support glob patterns,
e.g. `--packages="foo/bar,inpsyde/*"`.

## Clean-up Translations

**Command:** 
```shell
composer wp-translation-downloader:clean-up
```

Removes all files from languages directories.
