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

echo "PATH_TO_REPO is '${PATH_TO_REPO}'"
echo "Listing files..."
ls -la $PATH_TO_REPO

# Always load the example env
if [ ! -f "$PATH_TO_REPO/.env" ]; then
    cp "$PATH_TO_REPO/.env.example" "$PATH_TO_REPO/.env"
fi

# Append the railway env
if [ "${CI_MODE}" == "railway" ]; then
    echo "Running in Railway"
    echo $'\n# Railway\n' >> $PATH_TO_REPO/.env
    cat $PATH_TO_REPO/.env.railway >> $PATH_TO_REPO/.env
    source $PATH_TO_REPO/.env

    php artisan migrate --seed --no-interaction --force

    # TODO: Sync main production database to start with some base data
    # - Performance, test with some load (exchange rates 20 years back)
fi

if [ "${CI_MODE}" == "circleci" ]; then
    echo "Running in CircliCI"
    echo $'\n# CircliCI\n' >> $PATH_TO_REPO/.env
    echo "DB_HOST_OVERRIDE=127.0.0.1" >> "$PATH_TO_REPO/.env"
    source $PATH_TO_REPO/.env

    composer install
    mysql -h127.0.0.1 -uroot -proot_password -e "DROP DATABASE IF EXISTS testing; CREATE DATABASE testing;"
    mysql -h127.0.0.1 -uroot -proot_password -e "GRANT ALL PRIVILEGES ON *.* TO 'sail'@'%'; FLUSH PRIVILEGES;"

    php artisan migrate:refresh --seed --no-interaction --force
    php artisan migrate:refresh --database=testing --seed --force --no-interaction
fi

if [ "${CI_MODE}" == "local" ]; then
    source $PATH_TO_REPO/.env
    composer install
    docker exec -it kata-mysql mysql -uroot -proot_password -e "DROP DATABASE IF EXISTS laravel; CREATE DATABASE laravel;"
    docker exec -it kata-mysql mysql -uroot -proot_password -e "DROP DATABASE IF EXISTS testing; CREATE DATABASE testing;"
    docker exec -it kata-mysql mysql -uroot -proot_password -e "GRANT ALL PRIVILEGES ON *.* TO 'sail'@'%'; FLUSH PRIVILEGES;"
    ./vendor/bin/sail artisan migrate:refresh --seed --force --no-interaction
    ./vendor/bin/sail artisan migrate:refresh --database=testing --seed --force --no-interaction
fi

# TODO: Figure out how to best link storage os agnostic
# Generic commands
# php artisan storage:link
