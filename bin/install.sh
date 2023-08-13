#!/usr/bin/env bash

# Install WordPress Files
if [ -d ./wp ]; then
    rm -rf ./wp
fi

mkdir ./wp

docker run --rm -v ./wp:/var/www/html -u $(id -u):$(id -g) wordpress:cli wp core download --path=/var/www/html

rm -rf ./wp/wp-content/plugins/*

