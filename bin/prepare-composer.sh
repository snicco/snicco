#!/usr/bin/env bash

NEXT_VERSION=$1

./vendor/bin/monorepo-builder release "$NEXT_VERSION" --verbose
