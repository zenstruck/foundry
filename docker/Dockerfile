FROM php:7.4-cli

COPY --from=composer:2.3 /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y \
RUN set -eux ; \
    apt-get update ; \
    apt-get install --no-install-recommends -y \
        $PHPIZE_DEPS \
        git \
        curl \
        zip \
        unzip \
        libicu-dev \
        git \
        curl \
        unzip \
        procps \
        dialog \
        apt-utils \
        libpq-dev \
    ; \
    docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql; \
    docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql; \
    pecl install -f mongodb-1.10.0; \
    pecl clear-cache ; \
    docker-php-ext-enable mongodb ; \
    apt-get remove -y $PHPIZE_DEPS zlib1g-dev ; \
    rm -rf /var/lib/apt/lists/* ; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false
