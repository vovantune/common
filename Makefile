.ONESHELL:
.PHONY: help

H1=echo === ${1} ===
TAB=echo "\t"

help:
	@$(call H1,Application)
	$(TAB) make install - composer install
	@$(call H1,Test)
	$(TAB) make cs - проверка качества кода
	$(TAB) make cs-fix - автоматическое исправление Code Style
	$(TAB) make phpmd-check - проверка PHP Mess Detector
	$(TAB) make phpstan-check - проверка PHP Stan
	$(TAB) make test - прогон тестов

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

phpstan-check:
	./composer.phar phpstan-check
