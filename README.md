# Usage

```sh
bin/console app:transform <input-statements-file> --format=<input-format>
```

# Deployment

Run `make install` from the development directory. It builds an isolated production copy of the app under `~/.local/opt/ynab-transformer` (`composer install --no-dev`) and installs a `ynab-transformer` wrapper into `~/.local/bin`. The wrapper forces `APP_ENV=prod` and runs the Symfony console, so production never breaks while you iterate on the source.

```sh
make install
ynab-transformer app:transform <input-statements-file> [--format=<input-format>]
```

User configuration lives in `~/.config/ynab-transformer/config.yaml`. The `rate_limit_whitelist` key (comma-separated IPs that bypass the upload rate limiter) is bridged by the wrapper to the `RATE_LIMIT_WHITELIST` environment variable.
