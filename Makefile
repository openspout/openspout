DOCKER_PHP_EXEC := docker compose run --rm php

SRCS := $(shell find ./src ./tests -type f -not -path "*/resources/generated_*")

LOCAL_BASE_BRANCH ?= $(shell git show-branch | sed "s/].*//" | grep "\*" | grep -v "$$(git rev-parse --abbrev-ref HEAD)" | head -n1 | sed "s/^.*\[//")
ifeq ($(strip $(LOCAL_BASE_BRANCH)),)
	LOCAL_BASE_BRANCH := HEAD^
endif
BASE_BRANCH ?= $(LOCAL_BASE_BRANCH)

all: csfix static-analysis code-coverage
	@echo "Done."

.env: /etc/passwd /etc/group Makefile
	printf "USER_ID=%s\nGROUP_ID=%s\n" `id --user "${USER}"` `id --group "${USER}"` > .env

vendor: .env docker-compose.yml Dockerfile composer.json
	docker compose build --pull
	$(DOCKER_PHP_EXEC) composer update
	$(DOCKER_PHP_EXEC) composer bump
	touch --no-create $@

.PHONY: csfix
csfix: vendor
	$(DOCKER_PHP_EXEC) vendor/bin/php-cs-fixer fix --verbose

.PHONY: static-analysis
static-analysis: vendor
	$(DOCKER_PHP_EXEC) php -d zend.assertions=1 vendor/bin/phpstan analyse --memory-limit=256M $(PHPSTAN_ARGS)

coverage/ok: vendor $(SRCS) Makefile phpunit.xml
	chmod -fR u+rwX tests/resources/generated_* || true
	rm -fr tests/resources/generated_*
	($(DOCKER_PHP_EXEC) php \
		-d zend.assertions=1 \
		-d pcov.enabled=1 \
		-d open_basedir="$(realpath .)" \
		-d sys_temp_dir="$(realpath .)" \
		vendor/bin/phpunit \
		$(PHPUNIT_ARGS) \
		&& touch $@)

.PHONY: test
test: coverage/ok

.PHONY: code-coverage
code-coverage: coverage/ok
	echo "Base branch: $(BASE_BRANCH)"
	$(DOCKER_PHP_EXEC) php -d zend.assertions=1 \
		-d pcov.enabled=1 \
		vendor/bin/infection run \
		--threads=$(shell nproc) \
		--git-diff-lines \
		--git-diff-base=$(BASE_BRANCH) \
		--skip-initial-tests \
		--initial-tests-php-options="'-d' 'pcov.enabled=1'" \
		--coverage=coverage \
		--show-mutations \
		--verbose \
		--ignore-msi-with-no-mutations \
		--min-msi=100 \
		$(INFECTION_ARGS)
		
.PHONY: clean
clean:
	git clean -dfX

.PHONY: benchmark
benchmark: vendor
	php -d zend.assertions=1 \
		vendor/bin/phpbench run \
		--report=default \
		$(PHPBENCH_ARGS)
