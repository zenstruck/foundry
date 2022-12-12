ARG PHP_VERSION

FROM php:${PHP_VERSION}-cli

COPY --from=composer:2.4 /usr/bin/composer /usr/bin/composer

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
        libcurl4-openssl-dev \
        pkg-config \
        libssl-dev \
    ; \
    docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql; \
    docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql; \
    pecl install -f mongodb-1.15; \
    pecl install xdebug ; \
    docker-php-ext-enable mongodb xdebug ; \
    pecl clear-cache ; \
    apt-get remove -y $PHPIZE_DEPS zlib1g-dev libcurl4-openssl-dev pkg-config libssl-dev ; \
    rm -rf /var/lib/apt/lists/* ; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false ; \
    composer global require --no-progress --no-scripts --no-plugins symfony/flex ; \
    composer global config --no-plugins allow-plugins.symfony/flex true

LABEL org.opencontainers.image.source="https://github.com/zenstruck/foundry"

# We want these last commands to be at the bottom of the file because they depend on the host
# And we want to benefit of the cache layering as much as possible

COPY docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Set user rights
ARG USER=docker

# this may be overridden to match host's machine user
ARG UID=1001
ARG XDEBUG_HOST="172.17.0.1"

RUN addgroup --system --gid ${UID} ${USER} ; \
    adduser --system --disabled-password --uid ${UID} --ingroup ${USER} ${USER} ; \
    mkdir -p /app/var ; \
    chown -R ${USER}:${USER} /app ; \
    sed -i "s/%xdebug_host%/${XDEBUG_HOST}/g" /usr/local/etc/php/conf.d/xdebug.ini

USER ${USER}

CMD tail -f /dev/null
