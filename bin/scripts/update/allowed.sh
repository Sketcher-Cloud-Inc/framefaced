#!/bin/sh
if [[ -z $(git status -s) ]]; then
    echo "true";
else
    echo "false"
fi