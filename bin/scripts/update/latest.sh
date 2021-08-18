#!/bin/sh
cd ./.update/$1
if [ -n "$2" ]; then
    latest=$2;
else
    latest=$(git describe --tags `git rev-list --tags --max-count=1` 2>&1);
fi
git checkout $latest
cd ../../
mv ./.git_temp/.git ./.git
rm -Rf ./.git_temp
echo "true";