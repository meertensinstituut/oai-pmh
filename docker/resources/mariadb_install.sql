CREATE USER 'demo'@'%' IDENTIFIED BY 'demo';
CREATE DATABASE `oai-pmh`;
GRANT ALL PRIVILEGES ON `oai-pmh` . * TO 'demo'@'%';
