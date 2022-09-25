.PHONY: help docker-start phpunit-full phpunit-mysql phpunit-mysql-without-dama phpunit-postgresql phpunit-postgresql-without-dama phpunit-mongo cs-fixer psalm

MYSQL_URL="mysql://root:root@127.0.0.1:3306/zenstruck_foundry?charset=utf8"
POSTGRESQL_URL="postgresql://root:root@127.0.0.1:5432/zenstruck_foundry?charset=utf8"
MONGO_URL="mongodb://mongo:mongo@127.0.0.1:27017/mongo?compressors=disabled&amp;gssapiServiceName=mongodb"
SQLITE_URL="sqlite://./var/app.db"

.DEFAULT_GOAL := help

DOCKER_PHP=docker-compose run --rm php

help:
	@fgrep -h "###" $(MAKEFILE_LIST) | fgrep -v fgrep | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

docker-start: ### create and run database containers
	docker-compose up -d --build

tests: cs-fixer psalm phpunit-full ### run full tests suite

phpunit-full: docker-start ### run full phpunit
	DATABASE_URL=${POSTGRESQL_URL} MONGO_URL=${MONGO_URL} vendor/bin/simple-phpunit --configuration phpunit-dama-doctrine.xml.dist

phpunit-fast: ### run phpunit with only sqlite with DAMADoctrineTestBundle activated
	DATABASE_URL=${SQLITE_URL} vendor/bin/simple-phpunit --configuration phpunit-dama-doctrine.xml.dist --stop-on-failure

phpunit-mysql-fast: docker-start ### run phpunit with only mysql with DAMADoctrineTestBundle activated
	DATABASE_URL=${MYSQL_URL} vendor/bin/simple-phpunit --configuration phpunit-dama-doctrine.xml.dist --stop-on-failure

phpunit-mysql: docker-start ### run phpunit with only mysql
	DATABASE_URL=${MYSQL_URL} vendor/bin/simple-phpunit

phpunit-postgresql-fast: docker-start ### run phpunit with only postgresgl with DAMADoctrineTestBundle activated
	DATABASE_URL=${POSTGRESQL_URL} vendor/bin/simple-phpunit --configuration phpunit-dama-doctrine.xml.dist --stop-on-failure

phpunit-postgresql: docker-start ### run phpunit with only postgresgl
	DATABASE_URL=${POSTGRESQL_URL} vendor/bin/simple-phpunit

phpunit-mongo: docker-start ### run phpunit with only mongo
	MONGO_URL=${MONGO_URL} vendor/bin/simple-phpunit

psalm: bin/tools/psalm/vendor/vimeo/psalm/psalm ### run psalm
	${DOCKER_PHP} bin/tools/psalm/vendor/vimeo/psalm/psalm

bin/tools/psalm/vendor/vimeo/psalm/psalm: vendor/autoload.php
	${DOCKER_PHP} composer bin psalm install

cs-fixer: bin/tools/cs-fixer/vendor/friendsofphp/php-cs-fixer/php-cs-fixer ### run PHP CS-Fixer
	${DOCKER_PHP} bin/tools/cs-fixer/vendor/friendsofphp/php-cs-fixer/php-cs-fixer --no-interaction --diff -v fix --config=.php-cs-fixer.dist.php

bin/tools/cs-fixer/vendor/friendsofphp/php-cs-fixer/php-cs-fixer: vendor/autoload.php
	${DOCKER_PHP} composer bin cs-fixer install

vendor/autoload.php: docker-start
	${DOCKER_PHP} composer install
