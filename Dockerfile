FROM php:8.4-fpm-alpine

# Install nginx + required PHP extensions in one layer
RUN apk add --no-cache \
        nginx \
        gettext \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
    && docker-php-ext-enable pdo_mysql mysqli

# Copy app
COPY . /app
WORKDIR /app

# Copy configs
COPY nginx.conf /etc/nginx/nginx.conf.template
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Sessions dir
RUN mkdir -p /tmp/sessions /app/logs

EXPOSE 8080

CMD ["/start.sh"]
