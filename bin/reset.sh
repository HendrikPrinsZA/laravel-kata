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

source $PATH_TO_REPO/.env

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
    mysql -h127.0.0.1 -uroot -p$DB_ROOT_PASSWORD -e "DROP DATABASE IF EXISTS $DB_TEST_DATABASE; CREATE DATABASE $DB_TEST_DATABASE;"
    mysql -h127.0.0.1 -uroot -p$DB_ROOT_PASSWORD -e "GRANT ALL PRIVILEGES ON *.* TO '$DB_USERNAME'@'%'; FLUSH PRIVILEGES;"

    php artisan migrate:refresh --seed --no-interaction --force
    php artisan migrate:refresh --database=$DB_TEST_DATABASE --seed --force --no-interaction
fi

if [ "${CI_MODE}" == "local" ]; then
    echo "Running in local"
    source $PATH_TO_REPO/.env
    composer install

    # Launch sail environment
    ./vendor/bin/sail down && ./vendor/bin/sail up -d --build

    # Generate app key
    ./vendor/bin/sail artisan key:generate --no-interaction

    docker exec -it kata-mysql mysql -uroot -p$DB_ROOT_PASSWORD -e <<-EOSQL
"DROP DATABASE IF EXISTS $DB_DATABASE; CREATE DATABASE $DB_DATABASE;"
"DROP DATABASE IF EXISTS $DB_TEST_DATABASE; CREATE DATABASE $DB_TEST_DATABASE;"
"GRANT ALL PRIVILEGES ON *.* TO '$DB_USERNAME'@'%'; FLUSH PRIVILEGES;"
EOSQL

    # docker exec -it kata-mysql mysql -uroot -p$DB_ROOT_PASSWORD -e "DROP DATABASE IF EXISTS laravel; CREATE DATABASE laravel;"
    # docker exec -it kata-mysql mysql -uroot -p$DB_ROOT_PASSWORD -e "DROP DATABASE IF EXISTS $DB_TEST_DATABASE; CREATE DATABASE $DB_TEST_DATABASE;"
    # docker exec -it kata-mysql mysql -uroot -p$DB_ROOT_PASSWORD -e "GRANT ALL PRIVILEGES ON *.* TO '$DB_USERNAME'@'%'; FLUSH PRIVILEGES;"
    ./vendor/bin/sail artisan migrate:refresh --seed --force --no-interaction
    ./vendor/bin/sail artisan migrate:refresh --database=$DB_TEST_DATABASE --seed --force --no-interaction

    ./vendor/bin/sail artisan test
fi

# TODO: Figure out how to best link storage os agnostic
# Generic commands
# php artisan storage:link
