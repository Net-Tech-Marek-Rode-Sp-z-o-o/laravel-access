FROM php:8.5-cli

RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev libicu-dev git unzip \
 && docker-php-ext-install pdo_pgsql intl \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# The repo is bind-mounted from the host, so its files are owned by the host UID, not
# the container's root — tell git to trust it, and give composer a version so it does not
# probe git for one.
RUN git config --global --add safe.directory /pkg
ENV COMPOSER_ROOT_VERSION=dev-main

WORKDIR /pkg
