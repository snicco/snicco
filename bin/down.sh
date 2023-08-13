#!/usr/bin/env bash

CONTAINER_NAME="mysql-snicco"

# Check if the container exists
if [ "$(docker ps -aq -f name=$CONTAINER_NAME)" ]; then
  # Stop the container
  docker stop $CONTAINER_NAME
  docker rm $CONTAINER_NAME
fi