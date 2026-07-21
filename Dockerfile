FROM php:8.4-fpm-alpine

RUN apk add --no-cache nginx gettext \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && docker-php-ext-enable pdo_mysql mysqli \
    && mkdir -p /tmp/sessions \
    && chmod 777 /tmp/sessions \
    && mkdir -p /var/run/php \
    && echo "clear_env = no" >> /usr/local/etc/php-fpm.d/www.conf

WORKDIR /app

COPY . /app

RUN chmod +x /app/start.sh

EXPOSE 8080

CMD ["/bin/sh", "/app/start.sh"]
