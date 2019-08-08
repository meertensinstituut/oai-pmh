#!/bin/bash

# docker kill $(docker ps -q)
docker-compose down
docker container prune -f
docker ps -a
