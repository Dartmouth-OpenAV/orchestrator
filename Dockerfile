FROM ubuntu:24.04

RUN apt-get update --fix-missing

# fixing timezone
RUN DEBIAN_FRONTEND=noninteractive apt-get install tzdata -y
ENV TZ=America/New_York
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# utilities
RUN DEBIAN_FRONTEND=noninteractive apt-get install screen htop telnet w3m vim-tiny jq -y

# web server
RUN DEBIAN_FRONTEND=noninteractive apt-get install apache2 php php-curl php-xml libapache2-mod-php -y
RUN rm /var/www/html/index.html
COPY _var_www_html /var/www/html
RUN chown -Rf www-data:www-data /var/www/html
RUN a2enmod rewrite
COPY _etc_apache2_sites-available_default.conf /etc/apache2/sites-available/000-default.conf

# memcached
RUN DEBIAN_FRONTEND=noninteractive apt-get install php-memcached memcached libmemcached-tools -y

COPY _start.sh /start.sh
RUN chmod 550 /start.sh

ARG VERSION="unknown"
RUN echo -n "${VERSION}" > /var/version

ENTRYPOINT /start.sh