#!/usr/bin/env bash

: '
# Init Script
Convenient script to ensure environment is ready to go

## Objectives
- Ensure dependencies are installed
- Auto configure for new + existing environments
- Ensure it is executable without interactions (for CI/CD)

## Wishlist
- Identify and flag new dependency configs, maybe by comparing .env with .env.example
- Optimise and introduce as a git hook
- Auto spin up environment with sail
- Ensure all dependencies are available, like `composer`, `php`, etc
'

PATH_TO_SCRIPT_DIR="$( cd -- "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
PATH_TO_REPO="$PATH_TO_SCRIPT_DIR/../"

echo "PATH_TO_SCRIPT_DIR=$PATH_TO_SCRIPT_DIR"
echo "PATH_TO_REPO=$PATH_TO_REPO"

if [ ! -f "$PATH_TO_REPO/.env" ]; then
  echo "Copied .env.example to .env"
  cp "$PATH_TO_REPO/.env.example" "$PATH_TO_REPO/.env"
  php -r "file_exists('.env') || copy('.env.example', '.env');"
  php artisan key:generate
fi

source $PATH_TO_REPO/.env

# Install composer dependencies
# TODO: Investigate why local is so much quicker, sees environmental
# ./vendor/bin/sail composer install
composer install

# Migrate main database
./vendor/bin/sail artisan migrate --seed --no-interaction --force

# Migrate testing database
./vendor/bin/sail artisan migrate --database=testing --seed --force --no-interaction
