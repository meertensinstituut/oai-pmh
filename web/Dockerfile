FROM registry.huc.knaw.nl/dockerhubproxy/library/php:7.3-apache

RUN apt-get update && apt-get install -y locales net-tools vim unzip mariadb-client gettext
RUN echo "en_US.UTF-8 UTF-8" >> /etc/locale.gen && locale-gen
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN ln -sf /usr/share/zoneinfo/Europe/Amsterdam /etc/localtime
COPY src /var/www/html
RUN chown -R www-data.www-data /var/www/html
