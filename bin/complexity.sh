#!/usr/bin/env bash

TARGET_PATH=$1

if [ ! -f "$TARGET_PATH" ]; then
    echo "Error: File not found at '$TARGET_PATH'"
    exit 1
fi

./vendor/bin/phpmd $TARGET_PATH json cleancode,codesize,controversial,design,naming,unusedcode
