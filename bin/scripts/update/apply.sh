#!/bin/sh
cd ./.update
cp -rlf -f ./$1/* ../
git add -A
git commit -m "update(framwork): Upgrading version to v$2"