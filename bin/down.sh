#!/usr/bin/env bash

IMAGE=${1:-"mysql:8.0.28"}
#(mariadb:latest-snicco), only [a-zA-Z0-9][a-zA-Z0-9_.-]
CONTAINER_NAME=$(echo "$IMAGE-snicco" | tr ":" "-")

# Check if the container exists
if [ "$(docker ps -aq -f name="$CONTAINER_NAME")" ]; then
  # Stop the container
  docker stop "$CONTAINER_NAME"
  docker rm "$CONTAINER_NAME"
fi