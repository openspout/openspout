# OpenSpout

[![Latest Stable Version](https://poser.pugx.org/openspout/openspout/v/stable)](https://packagist.org/packages/openspout/openspout)
[![Total Downloads](https://poser.pugx.org/openspout/openspout/downloads)](https://packagist.org/packages/openspout/openspout)
[![Build Status](https://github.com/openspout/openspout/actions/workflows/ci.yml/badge.svg)](https://github.com/openspout/openspout/actions/workflows/ci.yml)
[![Infection MSI](https://badge.stryker-mutator.io/github.com/openspout/openspout/4.x)](https://dashboard.stryker-mutator.io/reports/github.com/openspout/openspout/4.x)

OpenSpout is a community driven fork of `box/spout`, a PHP library to read and write spreadsheet files
(CSV, XLSX and ODS), in a fast and scalable way. Unlike other file readers or writers, it is capable of processing
very large files, while keeping the memory usage really low (less than 3MB).

## Documentation

Documentation can be found at [`docs/`](docs).

## Upgrade from `box/spout:v3` to `openspout/openspout:v3`

1. Replace `box/spout` with `openspout/openspout` in your `composer.json`
2. Replace `Box\Spout` with `OpenSpout` in your code

## Upgrade guide

Version 4 introduced new functionality but also some breaking changes. If you want to upgrade your OpenSpout codebase
please consult the [Upgrade guide](UPGRADE.md).

## Copyright and License

This is a fork of Box's Spout library: https://github.com/box/spout

Code until and directly descending from commit [`cc42c1d`](https://github.com/openspout/openspout/commit/cc42c1d29fc5d29f07caeace99bd29dbb6d7c2f8)
is copyright of _Box, Inc._ and licensed under the Apache License, Version 2.0:

https://github.com/openspout/openspout/blob/cc42c1d29fc5d29f07caeace99bd29dbb6d7c2f8/LICENSE

Code created, edited and released after the commit mentioned above
is copyright of _openspout_ Github organization and licensed under MIT License.

https://github.com/openspout/openspout/blob/main/LICENSE
