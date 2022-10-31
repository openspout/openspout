# Getting started

This guide will help you install OpenSpout and teach you how to use it.

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

use OpenSpout\Reader\CSV\Reader;

$reader = new Reader();
$reader->open('/path/to/file.ext');

foreach ($reader->getSheetIterator() as $sheet) {
    foreach ($sheet->getRowIterator() as $row) {
        // do stuff with the row
        $cells = $row->getCells();
    }
}

$reader->close();
```

If there are multiple sheets in the file, the reader will read all of them sequentially.

Note that OpenSpout guesses the reader type based on the file extension. If the extension is not standard (`.csv`,
`.ods`, `.xlsx` _- lower/uppercase_), a specific reader can be created directly:

```php
$reader = new \OpenSpout\Reader\XLSX\Reader();
// $reader = new \OpenSpout\Reader\ODS\Reader();
// $reader = new \OpenSpout\Reader\CSV\Reader();
```

### Writer

As with the reader, there is one common interface to write data to a file:

```php
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

$writer = new \OpenSpout\Writer\XLSX\Writer();
// $writer = new \OpenSpout\Writer\ODS\Writer();
// $writer = new \OpenSpout\Writer\CSV\Writer();

$writer->openToFile($filePath); // write data to a file or to a PHP stream
//$writer->openToBrowser($fileName); // stream data directly to the browser

$cells = [
    Cell::fromValue('Carl'),
    Cell::fromValue('is'),
    Cell::fromValue('great!'),
];

/** add a row at a time */
$singleRow = new Row($cells);
$writer->addRow($singleRow);

/** add multiple rows at a time */
$multipleRows = [
    new Row($cells),
    new Row($cells),
];
$writer->addRows($multipleRows); 

/** Shortcut: add a row from an array of values */
$values = ['Carl', 'is', 'great!'];
$rowFromValues = Row::fromValues($values);
$writer->addRow($rowFromValues);

$writer->close();
/**
 * in case of streaming data directly to the browser with $writer->openToBrowser() ensure
 * to not send any further data after the $writer->close() call as that would be appended
 * to the generated file and that makes Excel complain about it being corrupted.
 * For example, you could place an `exit;` here or terminate the output in any other way.
 */
```

For XLSX and ODS files, the number of rows per sheet is limited to *1,048,576*. By default, once this limit is reached,
the writer will automatically create a new sheet and continue writing data into it.


## Advanced usage

You can do a lot more with OpenSpout! Check out the [full documentation](./documentation.md) to learn about all the
features.
