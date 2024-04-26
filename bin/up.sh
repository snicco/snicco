#!/usr/bin/env bash

IMAGE=${1:-"mysql:8.0.28"}
#(mariadb:latest-snicco), only [a-zA-Z0-9][a-zA-Z0-9_.-]
CONTAINER_NAME=$(echo "$IMAGE-snicco" | tr ":" "-")
MYSQL_PORT=${2:-3306}

# Check if the container exists
if [ "$(docker ps -aq -f name="$CONTAINER_NAME")" ]; then
  # Stop the container
  docker stop "$CONTAINER_NAME"
  docker rm "$CONTAINER_NAME"
fi

docker run -p "$MYSQL_PORT":3306 --name "$CONTAINER_NAME" -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=snicco_1 -d "$IMAGE"