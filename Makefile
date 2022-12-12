.PHONY: $(filter-out vendor bin/tools/phpstan/vendor bin/tools/cs-fixer/vendor %,$(MAKECMDGOALS))

.DEFAULT_GOAL := help

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
	DOCKER_COMPOSE=COMPOSE_DOCKER_CLI_BUILD=1 DOCKER_BUILDKIT=1 docker-compose
else
	DOCKER_COMPOSE=COMPOSE_DOCKER_CLI_BUILD=1 DOCKER_BUILDKIT=1 docker compose
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

ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
$(eval $(RUN_ARGS):;@:)

help:
	@fgrep -h "###" $(MAKEFILE_LIST) | fgrep -v fgrep | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

validate: fixcs sca test database-validate-mapping ### Run fixcs, sca, full test suite and validate migrations

test: vendor ### Run PHPUnit tests suite
	@${DC_EXEC} -e USE_ORM=${USE_ORM} -e USE_ODM=${USE_ODM} ${PHP} vendor/bin/simple-phpunit --configuration ${PHPUNIT_CONFIG_FILE} $(ARGS)

fixcs: bin/tools/cs-fixer/vendor ### Run PHP-CS-Fixer
	@${DC_EXEC} -e PHP_CS_FIXER_IGNORE_ENV=1 ${PHP} php -d 'xdebug.mode=off' bin/tools/cs-fixer/vendor/friendsofphp/php-cs-fixer/php-cs-fixer --no-interaction --diff -v fix

bin/tools/cs-fixer/vendor: bin/tools/cs-fixer/composer.json bin/tools/cs-fixer/composer.lock
	@$(MAKE) --no-print-directory docker-start
	@$(MAKE) --no-print-directory vendor
	@$(MAKE) --no-print-directory composer bin cs-fixer install

sca: bin/tools/phpstan/vendor ### Run static analysis
	@${DOCKER_PHP} php -d 'xdebug.mode=off' bin/tools/phpstan/vendor/phpstan/phpstan/phpstan analyse

bin/tools/phpstan/vendor: bin/tools/phpstan/composer.json bin/tools/phpstan/composer.lock
	@$(MAKE) --no-print-directory docker-start
	@$(MAKE) --no-print-directory vendor
	@$(MAKE) --no-print-directory composer bin phpstan install

database-generate-migration: vendor database-drop-schema ### Generate new migration based on mapping in Zenstruck\Foundry\Tests\Fixtures\Entity
	@${DOCKER_PHP} vendor/bin/doctrine-migrations migrations:migrate --no-interaction --allow-no-migration # first, let's load into db existing migrations
	@${DOCKER_PHP} vendor/bin/doctrine-migrations migrations:diff --no-interaction
	@${DOCKER_PHP} vendor/bin/doctrine-migrations migrations:migrate --no-interaction # load the new migration
	@${DOCKER_PHP} bin/doctrine orm:validate-schema

database-validate-mapping: vendor database-drop-schema ### Validate mapping in Zenstruck\Foundry\Tests\Fixtures\Entity
	@${DOCKER_PHP} vendor/bin/doctrine-migrations migrations:migrate --no-interaction --allow-no-migration
	@${DOCKER_PHP} bin/doctrine orm:validate-schema

database-drop-schema: vendor ### Drop database schema
	@${DOCKER_PHP} bin/doctrine orm:schema-tool:drop --force
	@${DOCKER_PHP} vendor/bin/doctrine-migrations migrations:sync-metadata-storage # prevents the next command to fail if migrations table does not exist
	@${DOCKER_PHP} bin/doctrine dbal:run-sql "TRUNCATE doctrine_migration_versions" --quiet

vendor: composer.json $(wildcard composer.lock) $(wildcard .env)
	@$(MAKE) --no-print-directory docker-start
	@${DC_EXEC} -e SYMFONY_REQUIRE=${SYMFONY_REQUIRE} ${PHP} php -d 'xdebug.mode=off' /usr/bin/composer update --prefer-dist

DOCKER_IS_UP = $(shell $(DOCKER_COMPOSE) ps | grep "${PHP}" | grep "Up")

docker-start: ### Build and run containers
	@if [ -z "${DOCKER_IS_UP}" ]; then \
		./docker/build.sh load "${PHP_VERSION}" ; \
		$(DOCKER_COMPOSE) up --detach --no-build --remove-orphans mysql mongo "${PHP}" ; \
		echo "" ; \
		$(DOCKER_COMPOSE) ps ; \
		echo "" ; \
		${DOCKER_PHP} php -v ; \
		echo "" ; \
	fi

docker-stop: ### Stop containers
	@$(DOCKER_COMPOSE) stop

docker-purge: docker-stop ### Purge containers
	@$(DOCKER_COMPOSE) down --volumes

composer: ### Run composer command
	@${DC_EXEC} ${PHP} php -d 'xdebug.mode=off' /usr/bin/composer $(ARGS)

clear: ### Start from a fresh install (needed if vendors have already been installed with another php version)
	rm -rf composer.lock bin/tools/phpstan/vendor/ bin/tools/cs-fixer/vendor/ vendor/

%: # black hole to prevent extra args warning
	@.
