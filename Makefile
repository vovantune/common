.ONESHELL:
.PHONY: help

H1=echo === ${1} ===
TAB=echo "\t"

help:
	@$(call H1,Application)
	$(TAB) make install - composer install
	$(TAB) make update - composer install и накатка миграций
	$(TAB) make js-doc - генератор JS документации для ValueObject
	@$(call H1,Test)
	$(TAB) make cs - проверка качества кода
	$(TAB) make cs-fix - автоматическое исправление Code Style
	$(TAB) make test - прогон тестов
	$(call H1,DB)
	$(TAB) make migrate-exec - накатка миграций
	$(TAB) make migrate-add name=MigrationFileName - добавление нового файла миграций
	$(TAB) make migrate-rollback - откат последней миграций
	$(TAB) make migrate-status - статус миграций
	$(TAB) make entity-builder - формируем/обновляем сущности

install:
	./composer.phar install -n

cs:
	./composer.phar cs-check

cs-fix:
	./composer.phar cs-fix

test: install
	./composer.phar test

phpmd-check:
	./composer.phar phpmd-check
