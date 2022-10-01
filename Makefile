.PHONY: $(filter-out vendor bin/tools/psalm/vendor bin/tools/cs-fixer/vendor,$(MAKECMDGOALS))

MYSQL_URL="mysql://root:root@mysql:3306/zenstruck_foundry?charset=utf8"
POSTGRESQL_URL="postgresql://root:root@postgres:5432/zenstruck_foundry?charset=utf8&serverVersion=13"
MONGO_URL="mongodb://mongo:mongo@mongo:27017/mongo?compressors=disabled&amp;gssapiServiceName=mongodb&authSource=mongo"
SQLITE_URL="sqlite://./var/app.db"

ifeq ($(shell docker --help | grep "compose"),)
	DOCKER_COMPOSE=docker-compose
else
	DOCKER_COMPOSE=docker compose
endif

INTERACTIVE:=$(shell [ -t 0 ] && echo 1)
ifdef INTERACTIVE
	DC_EXEC=$(DOCKER_COMPOSE) exec
else
	DC_EXEC=$(DOCKER_COMPOSE) exec -T
endif

DOCKER_PHP=${DC_EXEC} php

.DEFAULT_GOAL := help

UID = $(shell id -u)

DOCKER_IS_UP = $(shell $(DOCKER_COMPOSE) ps | grep "Up (healthy)")

ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
$(eval $(RUN_ARGS):;@:)

# From inside the containers, docker host ip is different in linux and macos
UNAME_S := $(shell uname -s)
ifeq ($(UNAME_S),Linux)
    XDEBUG_HOST=172.17.0.1
endif
ifeq ($(UNAME_S),Darwin)
    XDEBUG_HOST=host.docker.internal
endif

help:
	@fgrep -h "###" $(MAKEFILE_LIST) | fgrep -v fgrep | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

validate: fixcs sca test-full ### Run fixcs, sca and full test suite

test-full: docker-start vendor ### Run full PHPunit (MySQL + Mongo)
	@$(eval filter ?= '.')
	@${DC_EXEC} -e USE_FOUNDRY_BUNDLE=1 -e DATABASE_URL=${MYSQL_URL} -e MONGO_URL=${MONGO_URL} php vendor/bin/simple-phpunit --configuration phpunit-dama-doctrine.xml.dist --filter=$(filter)

test-fast: docker-start vendor ### Run PHPunit with SQLite
	@$(eval filter ?= '.')
ifeq ($(shell which docker),)
	@DATABASE_URL=${SQLITE_URL} php vendor/bin/simple-phpunit --configuration phpunit-dama-doctrine.xml.dist --filter=$(filter)
else
	@${DC_EXEC} -e DATABASE_URL=${SQLITE_URL} php vendor/bin/simple-phpunit --configuration phpunit-dama-doctrine.xml.dist --filter=$(filter)
endif

test-mysql: docker-start vendor ### Run PHPunit with mysql
	@$(eval filter ?= '.')
	@${DC_EXEC} -e DATABASE_URL=${MYSQL_URL} php vendor/bin/simple-phpunit --configuration phpunit-dama-doctrine.xml.dist --filter=$(filter)

test-postgresql: docker-start vendor ### Run PHPunit with postgreSQL
	@$(eval filter ?= '.')
	@${DC_EXEC} -e DATABASE_URL=${POSTGRESQL_URL} php vendor/bin/simple-phpunit --configuration phpunit-dama-doctrine.xml.dist --filter=$(filter)

test-mongo: docker-start vendor ### Run PHPunit with Mongo
	@$(eval filter ?= '.')
	@${DC_EXEC} -e MONGO_URL=${MONGO_URL} php vendor/bin/simple-phpunit --configuration phpunit.xml.dist --filter=$(filter)

fixcs: docker-start bin/tools/cs-fixer/vendor ### Run PHP CS-Fixer
	@${DOCKER_PHP} bin/tools/cs-fixer/vendor/friendsofphp/php-cs-fixer/php-cs-fixer --no-interaction --diff -v fix

bin/tools/cs-fixer/vendor: vendor bin/tools/cs-fixer/composer.json bin/tools/cs-fixer/composer.lock
	@${DOCKER_PHP} composer bin cs-fixer update

sca: docker-start bin/tools/psalm/vendor ### Run Psalm
	@${DOCKER_PHP} bin/tools/psalm/vendor/vimeo/psalm/psalm --config=./psalm.xml

bin/tools/psalm/vendor: vendor bin/tools/psalm/composer.json bin/tools/psalm/composer.lock
	@${DOCKER_PHP} composer bin psalm update

vendor: composer.json $(wildcard composer.lock)
	@${DOCKER_PHP} composer update

docker-start: ### Build and run containers
ifeq ($(DOCKER_IS_UP),)
	@$(DOCKER_COMPOSE) build  --build-arg UID="${UID}" --build-arg XDEBUG_HOST="${XDEBUG_HOST}"
	@$(DOCKER_COMPOSE) up --detach --remove-orphans
	@$(DOCKER_COMPOSE) ps
endif

docker-stop: ### Stop containers
	@$(DOCKER_COMPOSE) stop

docker-purge: docker-stop ### Purge containers
	@$(DOCKER_COMPOSE) down --volumes

composer: ### Run composer command
	@${DC_EXEC} php composer $(ARGS)

clear: ### Start from a fresh install (needed if vendors have already been installed with another php version)
	rm -rf composer.lock bin/tools/psalm/vendor/ bin/tools/cs-fixer/vendor/ vendor/
