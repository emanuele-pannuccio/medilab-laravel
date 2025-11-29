FROM php:8.2-alpine

COPY --from=node:20-alpine /usr/local/bin /usr/local/bin
COPY --from=node:20-alpine /usr/local/lib/node_modules /usr/local/lib/node_modules

WORKDIR /var/www/html

RUN apk add --no-cache libzip-dev zip shadow \
    autoconf \
    g++ \
    make \
    librdkafka-dev \
    && docker-php-ext-install pdo pdo_mysql zip bcmath pcntl

RUN pecl install rdkafka && docker-php-ext-enable rdkafka

RUN usermod --uid 1000 www-data

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY --chown=www-data:www-data . .

RUN chmod u+x /var/www/html/entrypoint.sh

EXPOSE 8000

USER www-data

ENTRYPOINT ["sh","/var/www/html/entrypoint.sh" ]
