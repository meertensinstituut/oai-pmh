CREATE USER 'demo'@'%' IDENTIFIED BY 'oaipmh';
GRANT ALL PRIVILEGES ON `oai-pmh` . * TO 'demo'@'%';
CREATE DATABASE `oai-pmh`;
