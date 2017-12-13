FROM ubuntu:16.04
MAINTAINER Matthijs Brouwer, Meertens Institute

EXPOSE 80
  
USER root

WORKDIR "/root"

RUN mkdir phpmyadmin

ADD https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.zip /root/phpmyadmin 

RUN apt-get update && apt-get install -y locales software-properties-common unzip apache2 php php-mysqli php-curl php-sqlite3 libapache2-mod-php subversion && \
    locale-gen en_US.UTF-8 en_US && update-locale LANG=en_US.UTF-8 LANGUAGE=en_US:en
            
RUN apt-get install -y mariadb-server && mysql_install_db && service mysql start && svn export https://github.com/meertensinstituut/oai-pmh/trunk/docker/resources resources && \
    mysql < resources/mariadb_install.sql && gunzip resources/oai-pmh.sql.gz && mysql oai-pmh < resources/oai-pmh.sql
    
RUN service apache2 stop && \
    echo "ServerName localhost" | tee /etc/apache2/conf-available/fqdn.conf && \
    a2enconf fqdn && \
    rm -rf /var/www/html/* && \
    chmod -R 755 /var/www/html && \
    printf "echo\n" >> /start.sh && \
    printf "echo \"================ OAI-PMH Data Provider ==================================\"\n" >> /start.sh && \
    printf "echo \"  See https://github.com/meertensinstituut/oai-pmh/ for more information \"\n" >> /start.sh && \
    printf "echo \"=========================================================================\"\n" >> /start.sh && \
    printf "echo\n" >> /start.sh && \
    printf "service mysql start\nservice apache2 start\n" >> /start.sh && \
    chmod 755 /start.sh
    
RUN cd /var/www/html && unzip /root/phpmyadmin/*.zip && mv phpMyAdmin* phpMyAdmin 

RUN cd /var/www/html && svn export https://github.com/meertensinstituut/oai-pmh/trunk/src/ oaipmh &&\
    cp /root/resources/config.inc.php /var/www/html/oaipmh/config/ && chmod 777 /var/www/html/oaipmh/cache 
       

CMD bash -C '/start.sh'; 'bash'
