#!/usr/bin/env bash

PATH_TO_SCRIPT_DIR="$( cd -- "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
PATH_TO_REPO="$PATH_TO_SCRIPT_DIR/.."
PATH_TO_STORAGE_REL="storage/logs/coverage"
PATH_TO_STORAGE="$PATH_TO_REPO/$PATH_TO_STORAGE_REL"

if [ ! -d $PATH_TO_STORAGE ]; then
    mkdir -p $PATH_TO_STORAGE
fi

./vendor/bin/sail test \
    --coverage-html=$PATH_TO_STORAGE_REL/html \
    --coverage-clover=$PATH_TO_STORAGE_REL/clover.xml \
    --coverage-text=$PATH_TO_STORAGE_REL/coverage.txt \
    --log-junit $PATH_TO_STORAGE_REL/junit.xml \
    --stop-on-failure

echo "See $PATH_TO_STORAGE/html/index.html"

