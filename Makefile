.DEFAULT_GOAL := help

SHELL=/bin/bash

# DB variables
MYSQL_URL="mysql://root:root@mysql:3306/zenstruck_foundry?charset=utf8"
MONGO_URL="mongodb://mongo:mongo@mongo:27017/mongo?compressors=disabled&amp;gssapiServiceName=mongodb&authSource=mongo"

# Default test context variables
USE_FOUNDRY_BUNDLE=1
USE_ORM=1
USE_ODM=1
USE_DAMA_DOCTRINE_TEST_BUNDLE=1
SYMFONY_REQUIRE=5.4.*
PHP_VERSION=8.0
PREFER_LOWEST=false

# Override test context variables with `.env` file
ifneq (,$(wildcard .env))
	include .env
	export $(shell sed 's/=.*//' .env)
endif

ifeq (${PREFER_LOWEST},1)
	COMPOSER_UPDATE_OPTIONS=--prefer-dist --prefer-lowest
else
	COMPOSER_UPDATE_OPTIONS=--prefer-dist
endif

DOCKER_PHP_CONTAINER_FLAG := docker/.makefile/.docker-containers-${PHP_VERSION}
PHPSTAN_BIN := bin/tools/phpstan/vendor/phpstan/phpstan/phpstan
PSALM_BIN := bin/tools/psalm/vendor/vimeo/psalm/psalm

ifeq ($(USE_DAMA_DOCTRINE_TEST_BUNDLE),1)
	PHPUNIT_CONFIG_FILE='phpunit-dama-doctrine.xml.dist'
else
	PHPUNIT_CONFIG_FILE='phpunit.xml.dist'
endif

# Define docker executable
ifeq ($(shell docker --help | grep "compose"),)
	DOCKER_COMPOSE := COMPOSE_DOCKER_CLI_BUILD=1 DOCKER_BUILDKIT=1 docker-compose
else
	DOCKER_COMPOSE := COMPOSE_DOCKER_CLI_BUILD=1 DOCKER_BUILDKIT=1 docker compose
endif

# Create special context for CI
INTERACTIVE:=$(shell [ -t 0 ] && echo 1)
ifdef INTERACTIVE
	DC_EXEC=$(DOCKER_COMPOSE) exec -e USE_FOUNDRY_BUNDLE=${USE_FOUNDRY_BUNDLE} -e DATABASE_URL=${MYSQL_URL} -e MONGO_URL=${MONGO_URL}
else
	# CI needs to be ran in no-tty mode
	DC_EXEC=$(DOCKER_COMPOSE) exec -e USE_FOUNDRY_BUNDLE=${USE_FOUNDRY_BUNDLE} -e DATABASE_URL=${MYSQL_URL} -e MONGO_URL=${MONGO_URL} -T
endif

PHP=php${PHP_VERSION}
DOCKER_PHP=${DC_EXEC} ${PHP}
DOCKER_PHP_WITHOUT_XDEBUG=${DOCKER_PHP} php -d 'xdebug.mode=off'

ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
$(eval $(RUN_ARGS):;@:)

.PHONY: help
help:
	@fgrep -h "###" $(MAKEFILE_LIST) | fgrep -v fgrep | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: validate
validate: sca psalm test database-validate-mapping ### Run sca, full test suite and validate migrations

.PHONY: test
test: vendor ### Run PHPUnit tests suite
	@$(MAKE) --no-print-directory docker-start-if-not-running
	@${DC_EXEC} -e USE_ORM=${USE_ORM} -e USE_ODM=${USE_ODM} ${PHP} vendor/bin/simple-phpunit --configuration ${PHPUNIT_CONFIG_FILE} $(ARGS)

.PHONY: sca
sca: phpstan ### Run static analysis

.PHONY: phpstan
phpstan: $(PHPSTAN_BIN)
	@$(MAKE) --no-print-directory docker-start-if-not-running
	@${DOCKER_PHP_WITHOUT_XDEBUG} $(PHPSTAN_BIN) analyse

$(PHPSTAN_BIN): vendor bin/tools/phpstan/composer.json bin/tools/phpstan/composer.lock
	@$(MAKE) --no-print-directory docker-start-if-not-running
	@${DOCKER_PHP_WITHOUT_XDEBUG} /usr/bin/composer bin phpstan install
	@touch -c $@ bin/tools/phpstan/composer.json bin/tools/phpstan/composer.lock

# Psalm is only used to validate factories generated thanks to make:factory command.
.PHONY: psalm
psalm: $(PSALM_BIN)
	@$(MAKE) --no-print-directory docker-start-if-not-running
	@${DOCKER_PHP_WITHOUT_XDEBUG} $(PSALM_BIN)

$(PSALM_BIN): vendor bin/tools/psalm/composer.json bin/tools/psalm/composer.lock
	@$(MAKE) --no-print-directory docker-start-if-not-running
	@${DOCKER_PHP_WITHOUT_XDEBUG} /usr/bin/composer bin psalm install
	@touch -c $@ bin/tools/psalm/composer.json bin/tools/psalm/composer.lock

.PHONY: database-generate-migration
database-generate-migration: database-drop-schema ### Generate new migration based on mapping in Zenstruck\Foundry\Tests\Fixtures\Entity
	@${DOCKER_PHP} vendor/bin/doctrine-migrations migrations:migrate --no-interaction --allow-no-migration # first, let's load into db existing migrations
	@${DOCKER_PHP} vendor/bin/doctrine-migrations migrations:diff --no-interaction
	@${DOCKER_PHP} vendor/bin/doctrine-migrations migrations:migrate --no-interaction # load the new migration
	@${DOCKER_PHP} bin/doctrine orm:validate-schema

.PHONY: database-validate-mapping
database-validate-mapping: database-drop-schema ### Validate mapping in Zenstruck\Foundry\Tests\Fixtures\Entity
	@${DOCKER_PHP} vendor/bin/doctrine-migrations migrations:migrate --no-interaction --allow-no-migration
	@${DOCKER_PHP} bin/doctrine orm:validate-schema

.PHONY: database-drop-schema
database-drop-schema: vendor ### Drop database schema
	@$(MAKE) --no-print-directory docker-start-if-not-running
	@${DOCKER_PHP} bin/doctrine orm:schema-tool:drop --force
	@${DOCKER_PHP} vendor/bin/doctrine-migrations migrations:sync-metadata-storage # prevents the next command to fail if migrations table does not exist
	@${DOCKER_PHP} bin/doctrine dbal:run-sql "TRUNCATE doctrine_migration_versions" --quiet

.PHONY: composer
composer: ### Run composer command
	@$(MAKE) --no-print-directory docker-start-if-not-running
	@${DOCKER_PHP_WITHOUT_XDEBUG} /usr/bin/composer $(ARGS)

vendor: $(DOCKER_PHP_CONTAINER_FLAG) composer.json $(wildcard composer.lock) $(wildcard .env)
	@$(MAKE) --no-print-directory docker-start-if-not-running
	@${DC_EXEC} -e SYMFONY_REQUIRE=${SYMFONY_REQUIRE} ${PHP} php -d 'xdebug.mode=off' /usr/bin/composer update ${COMPOSER_UPDATE_OPTIONS}
	@touch -c $@ composer.json .env composer.lock

.PHONY: docker-start-if-not-running
docker-start-if-not-running: ### some xxx
	@if [ -f "$(DOCKER_PHP_CONTAINER_FLAG)" ] ; then \
		if $(DOCKER_COMPOSE) ps -a | grep "${PHP}" | grep -q -v 'Up '; then \
		    $(MAKE) --no-print-directory docker-start; \
		fi; \
	fi

.PHONY: docker-start
docker-build: ### Build and start containers
	@rm -f $(DOCKER_PHP_CONTAINER_FLAG)
	@$(MAKE) --no-print-directory $(DOCKER_PHP_CONTAINER_FLAG)

.PHONY: docker-start
docker-start: ### Start containers
	@echo -e "\nStarting containers. This could take up to one minute.\n"
	@$(DOCKER_COMPOSE) up --detach --no-build --remove-orphans mysql mongo "${PHP}";

.PHONY: docker-stop
docker-stop: ### Stop containers
	@rm $(DOCKER_PHP_CONTAINER_FLAG)
	@$(DOCKER_COMPOSE) stop

.PHONY: docker-purge
docker-purge: docker-stop ### Purge containers
	@$(DOCKER_COMPOSE) down --volumes

$(DOCKER_PHP_CONTAINER_FLAG):
	@./docker/build.sh load "${PHP_VERSION}"
	@$(MAKE) --no-print-directory docker-start
	@echo ""
	@$(DOCKER_COMPOSE) ps
	@echo ""
	@${DOCKER_PHP} php -v
	@echo ""
	@mkdir -p docker/.makefile/
	@touch $@

.PHONY: clear
clear: ### Start from a fresh install (use it for troubleshooting)
	rm -rf composer.lock vendor/ bin/tools/*/vendor/ docker/.makefile

%: # black hole to prevent extra args warning
	@:
