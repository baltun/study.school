#include .env
#export

#sail=./vendor/bin/sail
#app_serv_container=$(APP_SERVICE)
app_serv_container=gsoft_php81

.PHONY: build
build:
	$(sail) build

.PHONY: up
up:
	$(sail) up -d --remove-orphans

.PHONY: down
down:
	$(sail) down --remove-orphans


.PHONY: composer-require
composer-require:
	docker exec -it $(app_serv_container) bash -c 'cd /var/www/STUDY/$(shell basename $(CURDIR))/; composer require $(filter-out $@,$(MAKECMDGOALS))

# открыть командную строку в контейнере с php
.PHONY: php
php:
	docker exec -it $(app_serv_container) bash

# выполнить команду artisan
.PHONY: artisan
artisan:
	docker exec -it $(app_serv_container) bash -c 'cd /var/www/STUDY/$(shell basename $(CURDIR))/; php artisan $(filter-out $@,$(MAKECMDGOALS))'

.PHONY: log
log_dev:
	tail -n500 ./storage/logs/laravel.log

# выполнить команду docker-compose на gsoft кластере
.PHONY: docker-compose
docker-compose:
	docker-compose -f ~/DockerDocuments/gsoft_lemp_stack_cluster/docker-compose.yml $(filter-out $@,$(MAKECMDGOALS))

# посмотреть текущие запущенные контейнеры из gsoft кластера
.PHONY: ps
ps:
	docker-compose -f ~/DockerDocuments/gsoft_lemp_stack_cluster/docker-compose.yml ps

# выполнить миграции
.PHONY: migrate
migrate:
	docker exec -it gsoft_php_7.2-fpm bash -c 'cd /var/www/$(shell basename $(CURDIR))/; php artisan migrate'

# откатить миграции

.PHONY: rollback
rollback:
	docker exec -it gsoft_php_7.2-fpm bash -c 'cd /var/www/$(shell basename $(CURDIR))/; php artisan migrate:rollback'

# установить новые модули из composer
composer_install:
.PHONY: composer-install
	docker exec -it $(app_serv_container) bash -c 'cd /var/www/STUDY/$(shell basename $(CURDIR))/; ./composer.phar install'

# сделать СТРАШНОЕ обновление из composer
.PHONY: composer-update
composer-update:
	docker exec -it $(app_serv_container) bash -c 'cd /var/www/STUDY/$(shell basename $(CURDIR))/; ./composer.phar update $(filter-out $@,$(MAKECMDGOALS))'

# сделать бэкап на сервере


# выполнить произвольную команду в контейнере
.PHONY: command
command:
	docker exec -it $(app_serv_container) bash -c 'cd /var/www/STUDY/$(shell basename $(CURDIR))/; $(filter-out $@,$(MAKECMDGOALS))'

# выполнить все тесты
.PHONY: tests
tests:
	docker exec -it $(app_serv_container) bash -c 'cd /var/www/STUDY/$(shell basename $(CURDIR))/; php artisan test -c phpunit.xml'
	#docker exec -it $(app_serv_container) bash -c 'cd /var/www/STUDY/$(shell basename $(CURDIR))/; php artisan test --do-not-cache-result -c phpunit.xml --testdox'

# выполнить один тест
.PHONY: test
test:
	docker exec -it $(app_serv_container) -c 'cd /var/www/$(shell basename $(CURDIR))/; ./vendor/bin/phpunit $(filter-out $@,$(MAKECMDGOALS))'

# создать новый сайт
.PHONY: site_create
site_create:
	sudo bash ./_site_create_new-docker-nginx.sh
