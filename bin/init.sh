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
'

PATH_TO_SCRIPT_DIR="$( cd -- "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"

source $PATH_TO_SCRIPT_DIR/../.env

# Install composer dependencies
# TODO: Investigate why local is so much quicker, sees environmental
# ./vendor/bin/sail composer install
composer install

# Migrate main database
./vendor/bin/sail artisan migrate --seed --no-interaction --force

# Migrate testing database
./vendor/bin/sail artisan migrate --database=testing --seed --force --no-interaction

exit 0

# docker exec -it kata-mysql mysql -u root -e "CREATE USER 'root'@'192.%' IDENTIFIED BY '';GRANT ALL PRIVILEGES ON *.* TO 'root'@'192.%';FLUSH PRIVILEGES;"

docker exec -it kata-mysql mysql -u root -e "DROP DATABASE IF EXISTS testing; CREATE DATABASE testing;"

# DROP USER 'sail'@'%';
docker exec -it kata-mysql mysql -u root -e "CREATE USER 'sail'@'%' IDENTIFIED BY 'password'; GRANT ALL PRIVILEGES ON *.* TO 'sail'@'%'; FLUSH PRIVILEGES;"
