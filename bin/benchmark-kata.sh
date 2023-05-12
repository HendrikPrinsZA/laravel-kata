#!/usr/bin/env bash

if [ "${CI_MODE}" == "circleci" ]; then
    php artisan kata:run --all

    exitCode=$?
    if [ $exitCode -ne 0 ]; then
        echo "Error: Kata run failed"
        exit $exitCode
    fi

    exit 0
fi

./vendor/bin/sail artisan kata:run --all

exitCode=$?
if [ $exitCode -ne 0 ]; then
    echo "Error: Kata run failed"
    exit $exitCode
fi

exit 0
