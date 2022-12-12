#!/bin/sh

# Build PHP image with buildx
# usage: ./docker/build.sh [load|push] [8.0|8.1|8.2]

set -e

build() {
    ACTION=${1}
    PHP_VERSION=${2}

    DOCKER_IMAGE_TAG="ghcr.io/zenstruck/foundry/php:${PHP_VERSION}"

    # From inside the containers, docker host ip is different in linux and macos
    UNAME=$(uname -s)
    if [ "${UNAME}" = "Linux" ]; then
        XDEBUG_HOST="172.17.0.1"
    elif [ "${UNAME}" = "Darwin" ]; then
        XDEBUG_HOST="host.docker.internal"
    fi

    docker pull ${DOCKER_IMAGE_TAG} || true

    DOCKER_BUILDKIT=1 docker buildx build \
        --tag "${DOCKER_IMAGE_TAG}" \
        --cache-from "${DOCKER_IMAGE_TAG}" \
        --build-arg BUILDKIT_INLINE_CACHE=1 \
        --build-arg UID="$(id -u)" \
        --build-arg PHP_VERSION="${PHP_VERSION}" \
        --build-arg XDEBUG_HOST="${XDEBUG_HOST}" \
        --file docker/Dockerfile \
        "--${ACTION}" \
        .
}

main() {
    ACTION="${1:-load}"
    PHP_VERSION="${2:-8.0}"

    if [ "${ACTION}" != 'push' ] && [ "${ACTION}" != 'load' ]; then
        echo "Error: action \"${1}\" invalid. Allowed actions are push|load"
        exit 1;
    fi

    if [ "${PHP_VERSION}" != '8.0' ] && [ "${PHP_VERSION}" != '8.1' ] && [ "${PHP_VERSION}" != '8.2' ]; then
        echo "Error: given php version \"${2}\" invalid. Only 8.0, 8.1 and 8.2 are accepted."
        exit 1;
    fi

    # ensure default builder is used, it is needed to benefit from cache layering
    docker buildx use default

    build "${ACTION}" "${PHP_VERSION}"
}

main "$@"
