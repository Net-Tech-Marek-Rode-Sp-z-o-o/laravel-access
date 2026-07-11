FROM php:8.5-cli

RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev libicu-dev git unzip \
 && docker-php-ext-install pdo_pgsql intl \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /pkg
