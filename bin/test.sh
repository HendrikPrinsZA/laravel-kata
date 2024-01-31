#!/usr/bin/env bash

PATH_TO_SCRIPT_DIR="$( cd -- "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
PATH_TO_REPO="$PATH_TO_SCRIPT_DIR/../"
source $PATH_TO_REPO/.env

if [ "${CI_MODE}" == "circleci" ]; then
    echo "LK_RUN_MODE=benchmark" >> "$PATH_TO_REPO/.env"
    echo "EXCHANGE_RATE_API_HOST=$EXCHANGE_RATE_API_HOST" >> "$PATH_TO_REPO/.env"
    echo "EXCHANGE_RATE_API_KEY=$EXCHANGE_RATE_API_KEY" >> "$PATH_TO_REPO/.env"
fi

./vendor/bin/sail test --parallel
