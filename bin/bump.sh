#!/usr/bin/env bash

BRANCH="$(git rev-parse --abbrev-ref HEAD)"
if [[ "$BRANCH" != "main" ]]; then
  echo "Can only be run from 'main' branch, and you are currently on '$BRANCH'";
  exit 1;
fi


today=$(date '+%Y-%m-%d')
git pull origin main
git checkout -b bump/$today

rm composer.lock
./vendor/bin/sail composer upgrade
git add --all && git commit -m "Updated Composer dependencies"

rm package-lock.json
npm upgrade
# git add --all && git commit -m "Updated NPM dependencies"

# git push origin bump/$today
