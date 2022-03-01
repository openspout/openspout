# Spout

[![Latest Stable Version](https://poser.pugx.org/openspout/openspout/v/stable)](https://packagist.org/packages/openspout/openspout)
[![Build Status](https://github.com/openspout/openspout/workflows/ci/badge.svg?branch=main)](https://github.com/openspout/openspout/actions)
[![Coverage Status](https://coveralls.io/repos/github/openspout/openspout/badge.svg?branch=main)](https://coveralls.io/github/openspout/openspout?branch=main)
[![Total Downloads](https://poser.pugx.org/openspout/openspout/downloads)](https://packagist.org/packages/openspout/openspout)

OpenSpout is a PHP library to read and write spreadsheet files (CSV, XLSX and ODS), in a fast and scalable way.
Unlike other file readers or writers, it is capable of processing very large files, while keeping the memory usage really low (less than 3MB).

## Documentation

Full documentation can be found at [https://opensource.box.com/spout/](https://opensource.box.com/spout/).

## Requirements

* PHP version 7.2 or higher
* PHP extension `php_zip` enabled
* PHP extension `php_xmlreader` enabled

## Upgrade guide

Version 3 introduced new functionality but also some breaking changes. If you want to upgrade your Spout codebase from version 2 please consult the [Upgrade guide](UPGRADE-3.0.md). 

## Running tests

The `main` branch includes unit, functional and performance tests.
If you just want to check that everything is working as expected, executing the unit and functional tests is enough.

* `phpunit` - runs unit and functional tests
* `phpunit --group perf-tests` - only runs the performance tests

For information, the performance tests take about 10 minutes to run (processing 1 million rows files is not a quick thing).

> Performance tests status: [![Build Status](https://travis-ci.org/box/spout.svg?branch=perf-tests)](https://travis-ci.org/box/spout)

## Copyright and License

This is a fork of Box's Spout library: https://github.com/box/spout

Code until and directly descending from commit [`cc42c1d`](https://github.com/openspout/openspout/commit/cc42c1d29fc5d29f07caeace99bd29dbb6d7c2f8)
is copyright of _Box, Inc._ and licensed under the Apache License, Version 2.0:

    https://github.com/openspout/openspout/blob/cc42c1d29fc5d29f07caeace99bd29dbb6d7c2f8/LICENSE

Code created, edited and released after the commit mentioned above
is copyright of _openspout_ Github organization and licensed under MIT License.

    https://github.com/openspout/openspout/blob/main/LICENSE
