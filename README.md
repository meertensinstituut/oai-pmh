# OAI-PMH

PHP based OAI-PMH DataProvider, configurable and extendable to communicate with different types of archives.

Abstract DataProviders are available for

* MySQL databases
* [Broker](https://meertensinstituut.github.io/broker/)

To use the system with your own resources, a specific [DataProviderModule](https://github.com/meertensinstituut/oai-pmh/tree/master/src/lib/dataProviderModule) has to be created, extending one of the existing (abstract) DataProviders. Multiple examples are available, and also a Docker configuration generating a fully functional MySQL based demonstration of the OAI-PMH DataProvider.

- Create a `db` folder 
- To build and run the Docker image

```console
./start.sh
```
This will start three docker containers: `web`, `db` and `phpmyadmin`. 

- In the `web/src/config` create your config file
- Soft link it to `config.inc.php` in the same folder
- go to `http://localhost:8080` login using username: `demo` and password: `demo`; 
- Import data

This will provide a website on port 80 on the ip of docker host with a running OAI-PMH DataProvider on an also included MySQL demo database `oai-pmh` with username `demo` and password `demo`.

