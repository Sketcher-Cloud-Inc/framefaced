#!/bin/sh
rm -Rf ./.update/$1/
mv ./.git ./.git_temp
cd ./.update/
git clone $2
chmod -R 777 ./$1
if [ -d "./$1" ] 
then
    echo "true" 
else
    echo "false"
fi