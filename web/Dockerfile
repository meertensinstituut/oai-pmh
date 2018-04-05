FROM ubuntu:16.04

USER root
WORKDIR "/root"

RUN apt-get update && apt-get install -y  mariadb-client php-mysql php-mysqli locales net-tools vim software-properties-common unzip apache2 php php-mysqli php-curl php-sqlite3 php-xml libapache2-mod-php && \
    locale-gen en_US.UTF-8 en_US && update-locale LANG=en_US.UTF-8 LANGUAGE=en_US:en
    
RUN service apache2 stop && \
    echo "ServerName localhost" | tee /etc/apache2/conf-available/fqdn.conf && \
    a2enconf fqdn && \
    rm -rf /var/www/html/* && \
    chmod -R 755 /var/www/html 
       
# RUN printf "#!/bin/bash\nservice mysql start\nservice apache2 start\ntail -f /dev/null\necho \"okokok\"" >> /start.sh
RUN printf "#!/bin/bash\nservice apache2 start\ntail -f /dev/null\necho \"okokok\"" >> /start.sh
RUN cat /start.sh
RUN chmod 755 /start.sh
ENTRYPOINT ["/start.sh"]

