# https://hub.docker.com/_/nextcloud/
#FROM nextcloud:26-apache
#FROM nextcloud:27-apache
#FROM nextcloud:28-apache
#FROM nextcloud:29-apache
#FROM ghcr.io/pbek/nextcloud-docker-pre-apache:latest
#FROM nextcloud:30-apache
#FROM nextcloud:31-apache
FROM nextcloud:32-apache
#FROM ghcr.io/digital-blueprint/nextcloud-docker-pre-apache:latest

COPY entrypoint.sh /

RUN deluser www-data
RUN useradd -u 1000 -ms /bin/bash www-data
RUN usermod -a -G www-data www-data
RUN mkdir /var/www/deploy
