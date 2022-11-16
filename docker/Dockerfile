FROM php:8.0-cli

COPY --from=composer:2.4 /usr/bin/composer /usr/bin/composer

COPY docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
ARG XDEBUG_HOST

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
        libcurl4-openssl-dev \
        pkg-config \
        libssl-dev \
    ; \
    sed -i "s/%xdebug_host%/${XDEBUG_HOST}/g" /usr/local/etc/php/conf.d/xdebug.ini; \
    docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql; \
    docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql; \
    pecl install -f mongodb-1.10.0; \
    pecl install xdebug ; \
    docker-php-ext-enable mongodb xdebug ; \
    pecl clear-cache ; \
    apt-get remove -y $PHPIZE_DEPS zlib1g-dev libcurl4-openssl-dev pkg-config libssl-dev ; \
    rm -rf /var/lib/apt/lists/* ; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false

# Set user rights
ARG USER=docker
# this may be overridden to match host's machine user
ARG UID=1001
RUN addgroup --system --gid ${UID} ${USER} \
    && adduser --system --disabled-password --uid ${UID} --ingroup ${USER} ${USER} \
    && mkdir -p /app/var \
    && chown -R ${USER}:${USER} /app
USER ${USER}

CMD tail -f /dev/null
