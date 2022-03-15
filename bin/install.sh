#!/usr/bin/env bash

if ! command -v composer &> /dev/null
then
    printf "composer could not be found\n"
    printf "Make sure composer is globally available on your machine https://getcomposer.org/doc/00-intro.md\n"
    exit 1
fi

if ! command -v npm &> /dev/null
then
    printf "npm could not be found\n"
    printf "Make sure npm is globally available on your machine https://docs.npmjs.com/downloading-and-installing-node-js-and-npm\n"
    exit 1
fi

composer install --no-interaction
npm install

# Todo: add option to install wordpress here.
