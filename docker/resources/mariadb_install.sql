CREATE USER 'demo'@'%' IDENTIFIED BY 'demo';
GRANT ALL PRIVILEGES ON `oai-pmh` . * TO 'demo'@'%';
CREATE DATABASE `oai-pmh`;
