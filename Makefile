
SRCS := $(shell find ./src ./tests -type f -not -path "*/resources/generated_*")

LOCAL_BASE_BRANCH ?= $(shell git show-branch | sed "s/].*//" | grep "\*" | grep -v "$$(git rev-parse --abbrev-ref HEAD)" | head -n1 | sed "s/^.*\[//")
ifeq ($(strip $(LOCAL_BASE_BRANCH)),)
	LOCAL_BASE_BRANCH := HEAD^
endif
BASE_BRANCH ?= $(LOCAL_BASE_BRANCH)

all: csfix static-analysis code-coverage
	@echo "Done."

vendor: composer.json
	composer update
	composer bump
	touch vendor

.PHONY: csfix
csfix: vendor
	vendor/bin/php-cs-fixer fix --verbose

.PHONY: static-analysis
static-analysis: vendor
	php -d zend.assertions=1 vendor/bin/phpstan analyse $(PHPSTAN_ARGS)

coverage/junit.xml: vendor $(SRCS) Makefile phpunit.xml
	chmod -fR u+rwX tests/resources/generated_* || true
	rm -fr tests/resources/generated_*
	php \
		-d zend.assertions=1 \
		-d open_basedir="$(realpath .)" \
		-d sys_temp_dir="$(realpath .)" \
		vendor/bin/phpunit \
		--coverage-xml=coverage/coverage-xml \
		--coverage-html=coverage/html \
		--log-junit=coverage/junit.xml \
		$(PHPUNIT_ARGS)

.PHONY: test
test: coverage/junit.xml

.PHONY: code-coverage
code-coverage: coverage/junit.xml
	echo "Base branch: $(BASE_BRANCH)"
	php -d zend.assertions=1 \
		vendor/bin/infection \
		--threads=$(shell nproc) \
		--git-diff-lines \
		--git-diff-base=$(BASE_BRANCH) \
		--skip-initial-tests \
		--coverage=coverage \
		--ignore-msi-with-no-mutations \
		--show-mutations \
		--verbose \
		--min-msi=100 \
		$(INFECTION_ARGS)

.PHONY: benchmark
benchmark: vendor
	php -d zend.assertions=1 \
		vendor/bin/phpbench run \
		--report=default \
		$(PHPBENCH_ARGS)
