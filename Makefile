.PHONY: $(filter-out vendor bin/tools/phpstan/vendor bin/tools/cs-fixer/vendor,$(MAKECMDGOALS))

.DEFAULT_GOAL := help

# DB variables
MYSQL_URL="mysql://root:root@mysql:3306/zenstruck_foundry?charset=utf8"
MONGO_URL="mongodb://mongo:mongo@mongo:27017/mongo?compressors=disabled&amp;gssapiServiceName=mongodb&authSource=mongo"

# Default test context variables
USE_FOUNDRY_BUNDLE=1
USE_ORM=1
USE_ODM=1
USE_DAMA_DOCTRINE_TEST_BUNDLE=1
SYMFONY_REQUIRE=6.0.*

# Override test context variables with `.env` file
ifneq (,$(wildcard .env))
	include .env
	export $(shell sed 's/=.*//' .env)
endif

ifeq ($(USE_DAMA_DOCTRINE_TEST_BUNDLE),1)
	PHPUNIT_CONFIG_FILE='phpunit-dama-doctrine.xml.dist'
else
	PHPUNIT_CONFIG_FILE='phpunit.xml.dist'
endif

# Define docker executable
ifeq ($(shell docker --help | grep "compose"),)
	DOCKER_COMPOSE_BIN=docker-compose
else
	DOCKER_COMPOSE_BIN=docker compose
endif

# Create special context for CI
INTERACTIVE:=$(shell [ -t 0 ] && echo 1)
ifdef INTERACTIVE
	CI='false'
	DOCKER_COMPOSE=$(DOCKER_COMPOSE_BIN)
	DC_EXEC=$(DOCKER_COMPOSE) exec -e USE_FOUNDRY_BUNDLE=${USE_FOUNDRY_BUNDLE} -e DATABASE_URL=${MYSQL_URL} -e MONGO_URL=${MONGO_URL}
else
	CI='true'
	DOCKER_COMPOSE=$(DOCKER_COMPOSE_BIN) -f docker-compose.yaml -f docker-compose.ci.yaml
	# CI needs to be ran in no-tty mode
	DC_EXEC=$(DOCKER_COMPOSE) exec -e USE_FOUNDRY_BUNDLE=${USE_FOUNDRY_BUNDLE} -e DATABASE_URL=${MYSQL_URL} -e MONGO_URL=${MONGO_URL} -T
endif

DOCKER_PHP=${DC_EXEC} php

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

validate: fixcs sca test database-validate-mapping ### Run fixcs, sca, full test suite and validate migrations

test: docker-start vendor ### Run PHPUnit tests suite
	@$(eval filter ?= '.')
	@${DC_EXEC} -e USE_ORM=${USE_ORM} -e USE_ODM=${USE_ODM} php vendor/bin/simple-phpunit --configuration ${PHPUNIT_CONFIG_FILE} --filter=$(filter)

fixcs: docker-start bin/tools/cs-fixer/vendor ### Run PHP-CS-Fixer
	@${DOCKER_PHP} bin/tools/cs-fixer/vendor/friendsofphp/php-cs-fixer/php-cs-fixer --no-interaction --diff -v fix

bin/tools/cs-fixer/vendor: vendor bin/tools/cs-fixer/composer.json bin/tools/cs-fixer/composer.lock
	@${DOCKER_PHP} composer bin cs-fixer install

sca: docker-start bin/tools/phpstan/vendor ### Run static analysis
	@${DOCKER_PHP} bin/tools/phpstan/vendor/phpstan/phpstan/phpstan analyse

bin/tools/phpstan/vendor: vendor bin/tools/phpstan/composer.json $(wildcard bin/tools/phpstan/composer.lock)
	@${DOCKER_PHP} composer bin phpstan install

database-generate-migration: docker-start vendor database-drop-schema ### Generate new migration based on mapping in Zenstruck\Foundry\Tests\Fixtures\Entity
	@${DOCKER_PHP} vendor/bin/doctrine-migrations migrations:migrate --no-interaction --allow-no-migration # first, let's load into db existing migrations
	@${DOCKER_PHP} vendor/bin/doctrine-migrations migrations:diff --no-interaction
	@${DOCKER_PHP} vendor/bin/doctrine-migrations migrations:migrate --no-interaction # load the new migration
	@${DOCKER_PHP} bin/doctrine orm:validate-schema

database-validate-mapping: docker-start vendor database-drop-schema ### Validate mapping in Zenstruck\Foundry\Tests\Fixtures\Entity
	@${DOCKER_PHP} vendor/bin/doctrine-migrations migrations:migrate --no-interaction --allow-no-migration
	@${DOCKER_PHP} bin/doctrine orm:validate-schema

database-drop-schema: docker-start vendor ### Drop database schema
	@${DOCKER_PHP} bin/doctrine orm:schema-tool:drop --force
	@${DOCKER_PHP} vendor/bin/doctrine-migrations migrations:sync-metadata-storage # prevents the next command to fail if migrations table does not exist
	@${DOCKER_PHP} bin/doctrine dbal:run-sql "TRUNCATE doctrine_migration_versions" --quiet

vendor:  composer.json $(wildcard composer.lock) $(wildcard .env)
	@${DC_EXEC} -e SYMFONY_REQUIRE=${SYMFONY_REQUIRE} php composer update --prefer-dist

UID = $(shell id -u)
DOCKER_IS_UP = $(shell $(DOCKER_COMPOSE) ps | grep "Up (healthy)")

docker-start: ### Build and run containers
ifeq ($(DOCKER_IS_UP),)
ifeq ($(CI),'false')
	@$(DOCKER_COMPOSE) build  --build-arg UID="${UID}" --build-arg XDEBUG_HOST="${XDEBUG_HOST}"
	@$(DOCKER_COMPOSE) up --detach --remove-orphans
else
	@$(DOCKER_COMPOSE) up --detach --no-build
endif
	@$(DOCKER_COMPOSE) ps
endif

docker-stop: ### Stop containers
	@$(DOCKER_COMPOSE) stop

docker-purge: docker-stop ### Purge containers
	@$(DOCKER_COMPOSE) down --volumes

ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
$(eval $(RUN_ARGS):;@:)

composer: ### Run composer command
	@${DC_EXEC} php composer $(ARGS)

clear: ### Start from a fresh install (needed if vendors have already been installed with another php version)
	rm -rf composer.lock bin/tools/phpstan/vendor/ bin/tools/cs-fixer/vendor/ vendor/
