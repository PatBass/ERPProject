FROM debian:8

# Install Requirements
RUN apt-get update -qq && apt-get install -qqy \
    sudo \
    wget \
    curl \
    git \
    apt-utils \
    netcat-openbsd \
    acl \
    && echo "Europe/Paris" > /etc/timezone && dpkg-reconfigure -f noninteractive tzdata \
    && echo 'alias ll="ls -lah --color=auto"' >> /etc/bash.bashrc

# Variables Apache
ENV APACHE_RUN_USER  www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_PID_FILE  /var/run/apache2.pid
ENV APACHE_RUN_DIR   /var/run/apache2
ENV APACHE_LOCK_DIR  /var/lock/apache2
ENV APACHE_LOG_DIR   /var/log/apache2
ENV APACHE_USER_UID 0

ENV HOME /root

RUN echo "deb http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list
RUN echo "deb-src http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list
RUN wget http://www.dotdeb.org/dotdeb.gpg
RUN cat dotdeb.gpg | apt-key add -

# Java, PHP && Apache
RUN apt-get update -qq && apt-get install -qqy --force-yes \
    default-jre \
    php5 \
    php-apc \
    php5-cli \
    php5-xdebug \
    php5-intl \
    php5-mcrypt \
    php5-mysql \
    php5-curl \
    php5-gd \
    apache2 \
    #apache2-mpm-worker \
    apache2-utils \
    libapache2-mod-php5 \
    mysql-client

# Config Apache
ADD conf/apache/vhost.conf /etc/apache2/sites-available/web.conf
RUN a2enmod rewrite \
    && rm -f /etc/apache2/sites-enabled/default \
    && rm -f /etc/apache2/sites-enabled/000-default.conf \
    && rm -f /etc/apache2/sites-available/default \
    && rm -f /etc/apache2/sites-enabled/default-ssl \
    && rm -f /etc/apache2/sites-available/default-ssl \
    && rm -rf /var/www/html \
    && a2ensite web.conf

# Config PHP
ADD conf/php5/apc.ini /etc/php5/mods-available/apc.ini
ADD conf/php5/php.ini /etc/php5/apache2/php.ini
ADD conf/php5/php.ini /etc/php5/cli/php.ini

# Logs
RUN mkdir -p /var/log/apache2 \
    && chown -R www-data:www-data /var/log/apache2

# Alias
RUN echo "alias sf='php app/console'" >> /root/.bashrc
RUN echo "alias sfprod='php app/console --env=prod'" >> /root/.bashrc

# Sources
WORKDIR /var/www

EXPOSE 80

VOLUME ["/var/log/apache2", "/var/www"]

ADD script/start.sh /root/start.sh
RUN chmod +x /root/start.sh

CMD []
