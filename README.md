# OAI-PMH

PHP based OAI-PMH DataProvider, configurable and extendable to communicate with different types of archives.

Abstract DataProviders are available for

* MySQL databases
* [Broker](https://meertensinstituut.github.io/broker/)

To use the system with your own resources, a specific [DataProviderModule](https://github.com/meertensinstituut/oai-pmh/tree/master/src/lib/dataProviderModule) has to be created, extending one of the existing (abstract) DataProviders. Multiple examples are available, and also a Docker configuration generating a fully functional MySQL based demonstration of the OAI-PMH DataProvider.

To build and run the Docker image

```console
docker build -t oai-pmh https://raw.githubusercontent.com/meertensinstituut/oai-pmh/master/docker/Dockerfile
docker run -t -i -p 8080:80 --name oai-pmh oai-pmh
```

This will provide a website on port 8080 on the ip of your docker host with a running OAI-PMH DataProvider on an also included MySQL demo database `oai-pmh` with username `demo` and password `demo`.

