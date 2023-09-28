# OAI-PMH

PHP based OAI-PMH DataProvider, configurable and extendable to communicate with different types of archives.

Abstract DataProviders are available for

* MySQL databases
* [Broker](https://meertensinstituut.github.io/broker/)

To use the system with your own resources, a specific [DataProviderModule](https://github.com/meertensinstituut/oai-pmh/tree/master/src/lib/dataProviderModule) has to be created, extending one of the existing (abstract) DataProviders. Multiple examples are available, and also a Docker configuration generating a fully functional MySQL based demonstration of the OAI-PMH DataProvider.

### Build and run
#### Build Docker Image
```console
cd web
docker build -t isebel-oai-pmh .
```
#### Run Docker Image
There are 2 ways to run the docker image, dev and prod. 
The dev mounts local folder to `/var/www/html` in the container while the prod runs totoally in the container. 

```console
# prod
docker compose up -d

# dev
docker compose -f docker-compose-dev.yml up -d
```
