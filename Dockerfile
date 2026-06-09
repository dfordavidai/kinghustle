FROM php:8.4-fpm-alpine

RUN apk add --no-cache nginx gettext \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && docker-php-ext-enable pdo_mysql mysqli \
    && mkdir -p /tmp/sessions \
    && chmod 777 /tmp/sessions \
    && mkdir -p /var/run/php

COPY . /app
WORKDIR /app
RUN mkdir -p /app/logs && chmod 755 /app/logs

COPY nginx.conf /etc/nginx/nginx.conf.template
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080
CMD ["/start.sh"]
