#!/usr/bin/env bash

PATH_TO_SCRIPT_DIR="$( cd -- "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
PATH_TO_REPO="$PATH_TO_SCRIPT_DIR/../"
source $PATH_TO_REPO/.env

if [ "${CI_MODE}" == "circleci" ]; then
    echo "LK_RUN_MODE=benchmark" >> "$PATH_TO_REPO/.env"
    php -d xdebug.mode=profile artisan kata:run --all
else
    ./vendor/bin/sail artisan kata:run --all
fi

exitCode=$?
if [ $exitCode -ne 0 ]; then
    echo "Error: Kata run failed"
    exit $exitCode
fi

exit 0
