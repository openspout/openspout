# Getting started

This guide will help you install OpenSpout and teach you how to use it.

## Requirements

* PHP version 7.2 or higher
* PHP extension `ext-zip` enabled
* PHP extension `ext-xmlreader` enabled


## Installation

OpenSpout can be installed directly from [Composer](https://getcomposer.org/).

Run the following command:
```shell
composer require openspout/openspout
```

## Basic usage

### Reader

Regardless of the file type, the interface to read a file is always the same:

```php

use OpenSpout\Reader\Common\Creator\ReaderEntityFactory;

$reader = ReaderEntityFactory::createReaderFromFile('/path/to/file.ext');

$reader->open($filePath);

foreach ($reader->getSheetIterator() as $sheet) {
    foreach ($sheet->getRowIterator() as $row) {
        // do stuff with the row
        $cells = $row->getCells();
        ...
    }
}

$reader->close();
```

If there are multiple sheets in the file, the reader will read all of them sequentially.


Note that OpenSpout guesses the reader type based on the file extension. If the extension is not standard (`.csv`, `.ods`, `.xlsx` _- lower/uppercase_), a specific reader can be created directly:

```php
use OpenSpout\Reader\Common\Creator\ReaderEntityFactory;

$reader = ReaderEntityFactory::createXLSXReader();
// $reader = ReaderEntityFactory::createODSReader();
// $reader = ReaderEntityFactory::createCSVReader();
```

### Writer

As with the reader, there is one common interface to write data to a file:

```php
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Common\Entity\Row;

$writer = WriterEntityFactory::createXLSXWriter();
// $writer = WriterEntityFactory::createODSWriter();
// $writer = WriterEntityFactory::createCSVWriter();

$writer->openToFile($filePath); // write data to a file or to a PHP stream
//$writer->openToBrowser($fileName); // stream data directly to the browser

$cells = [
    WriterEntityFactory::createCell('Carl'),
    WriterEntityFactory::createCell('is'),
    WriterEntityFactory::createCell('great!'),
];

/** add a row at a time */
$singleRow = WriterEntityFactory::createRow($cells);
$writer->addRow($singleRow);

/** add multiple rows at a time */
$multipleRows = [
    WriterEntityFactory::createRow($cells),
    WriterEntityFactory::createRow($cells),
];
$writer->addRows($multipleRows); 

/** Shortcut: add a row from an array of values */
$values = ['Carl', 'is', 'great!'];
$rowFromValues = WriterEntityFactory::createRowFromArray($values);
$writer->addRow($rowFromValues);

$writer->close();
```

Similar to the reader, if the file extension of the file to be written is not standard, specific writers can be created this way:

```php
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Common\Entity\Row;

$writer = WriterEntityFactory::createXLSXWriter();
// $writer = WriterEntityFactory::createODSWriter();
// $writer = WriterEntityFactory::createCSVWriter();
```

For XLSX and ODS files, the number of rows per sheet is limited to *1,048,576*. By default, once this limit is reached, the writer will automatically create a new sheet and continue writing data into it.


## Advanced usage

You can do a lot more with OpenSpout! Check out the [full documentation](./documentation.md) to learn about all the features.
