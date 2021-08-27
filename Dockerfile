FROM alpine:3.14.1

RUN apk update && apk upgrade && \
    apk add --no-cache supervisor php8 php8-fpm nginx

## Setup configuration files
COPY ./docker/php/php.ini /etc/php8/php.ini
COPY ./docker/php/fpm.conf /etc/php8/php-fpm.conf
COPY ./docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/supervisord/supervisord.conf /etc/supervisor/supervisord.conf

## Add default nginx user & group
RUN addgroup -S www && adduser -S www -G www

## Create working directory
RUN mkdir /app
COPY ./dist /app

EXPOSE 80 443
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
WORKDIR /app