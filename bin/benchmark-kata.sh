#!/usr/bin/env bash

if [ "${CI_MODE}" == "circleci" ]; then
    php artisan kata:run
    exit 0
fi

./vendor/bin/sail artisan kata:run
exit 0
