# Commands

WP Translation Downloader integrates with Composer, adding the following custom commands to it.

## Download translations

**Command:**
```shell
composer wp-translation-downloader:download [--packages=<package list>]
```

Downloads for package translations.

By default, when no packages are passed using the `--packages` flag, _all_ installed packages will be
processed.

Note: The `--packages` flag must be a comma-separated list of package names (glob patterns supported),
e.g. `--packages="foo/bar,inpsyde/*"`.

## Clean-up Translations

**Command:**
```shell
composer wp-translation-downloader:clean-up
```

Removes all files from language directories.