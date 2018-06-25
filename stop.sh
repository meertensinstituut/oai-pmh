#!/bin/bash

# docker kill $(docker ps -q)
docker kill oai-pmh_phpmyadmin_1
docker kill oai-pmh_web_1
docker kill oai-pmh_db_1
docker container prune -f
docker ps -a
