all: csfix static-analysis test
	@echo "Done."

vendor: composer.json
	composer update
	touch vendor

.PHONY: csfix
csfix: vendor
	vendor/bin/php-cs-fixer fix --verbose

.PHONY: static-analysis
static-analysis: vendor
	php -d zend.assertions=1 vendor/bin/phpstan analyse

.PHONY: test
test: vendor
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
		${arg}

.PHONY: code-coverage
code-coverage: test
	php -d zend.assertions=1 vendor/bin/infection \
		--threads=$(shell nproc) \
		--coverage=coverage \
		--skip-initial-tests

.PHONY: benchmark
benchmark: vendor
	php -d zend.assertions=1 vendor/bin/phpbench run --report=default ${arg}
