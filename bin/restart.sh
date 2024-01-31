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

# Always load the example env
if [ ! -f "$PATH_TO_REPO/.env" ]; then
    cp "$PATH_TO_REPO/.env.example" "$PATH_TO_REPO/.env"
fi

source $PATH_TO_REPO/.env

if [ "${CI_MODE}" == "circleci" ]; then
    echo "Running in CircliCI"

    echo $'\n# CircleCI\n' >> $PATH_TO_REPO/.env.new
    cat $PATH_TO_REPO/.env.circleci >> $PATH_TO_REPO/.env.new

    echo $'\n# Default env\n' >> $PATH_TO_REPO/.env.new
    cat $PATH_TO_REPO/.env >> $PATH_TO_REPO/.env.new

    mv $PATH_TO_REPO/.env.new $PATH_TO_REPO/.env
    source $PATH_TO_REPO/.env

    composer install
    mysql -h127.0.0.1 -uroot -p$DB_ROOT_PASSWORD -e "DROP DATABASE IF EXISTS $DB_DATABASE; CREATE DATABASE $DB_DATABASE;"
    mysql -h127.0.0.1 -uroot -p$DB_ROOT_PASSWORD -e "DROP DATABASE IF EXISTS $DB_TEST_DATABASE; CREATE DATABASE $DB_TEST_DATABASE;"
    mysql -h127.0.0.1 -uroot -p$DB_ROOT_PASSWORD -e "GRANT ALL PRIVILEGES ON *.* TO 'sail'@'%'; FLUSH PRIVILEGES;"

    # experimenting
    echo "Serving Laravel..."
    nohup php artisan serve &

    php artisan migrate:fresh --seed --no-interaction --force
    php artisan migrate:fresh --env=testing --database=$DB_TEST_DATABASE --seed --force --no-interaction
    exit 0
fi

echo "Running in local"

# Install dependencies
./vendor/bin/sail composer install

# Launch sail environment
./vendor/bin/sail down --rmi local -v
./vendor/bin/sail up -d --build

# Give mysql some time
echo "Sleeping for 9 seconds to give MySQL some time..."
sleep 9

docker exec -it kata-mysql mysql -uroot -p$DB_ROOT_PASSWORD -e "DROP DATABASE IF EXISTS $DB_DATABASE; CREATE DATABASE $DB_DATABASE;"
docker exec -it kata-mysql mysql -uroot -p$DB_ROOT_PASSWORD -e "DROP DATABASE IF EXISTS $DB_TEST_DATABASE; CREATE DATABASE $DB_TEST_DATABASE;"
docker exec -it kata-mysql mysql -uroot -p$DB_ROOT_PASSWORD -e "GRANT ALL PRIVILEGES ON *.* TO '$DB_USERNAME'@'%'; FLUSH PRIVILEGES;"

./vendor/bin/sail artisan migrate:fresh --seed --force --no-interaction
./vendor/bin/sail artisan migrate:fresh --env=testing --database=$DB_TEST_DATABASE --seed --force --no-interaction
