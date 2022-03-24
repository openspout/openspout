# Upgrade guide

## Upgrading from 3.x to 4.0

Beginning with v4, only actively supported [PHP version](https://www.php.net/supported-versions.php) will be supported.
Removing support for EOLed PHP versions as well adding support for new PHP versions will be included in MINOR releases.

### Most notable changes

1. OpenSpout is now fully typed
2. Classes and interfaces not consumed by the user are now marked as `@internal`
3. Classes used by the user are all `final`

### Reader & Writer objects

Both readers and writers have to be naturally instantiated with `new` keyword, passing the eventual needed `Options`
class as the first argument:

```php
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

$options = new Options();
$options->FIELD_DELIMITER = '|';
$options->FIELD_ENCLOSURE = '@';
$reader = new Reader($options);
```

### Cell types on writes

Cell types are now handled with separate classes:

```php
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

$row = new Row([
    new Cell\BooleanCell(true),
    new Cell\DateIntervalCell(new DateInterval('P1D')),
    new Cell\DateTimeCell(new DateTimeImmutable('now')),
    new Cell\EmptyCell(null),
    new Cell\FormulaCell('=SUM(A1:A2)'),
    new Cell\NumericCell(3),
    new Cell\StringCell('foo'),
]);
```

Auto-typing is still available though:

```php
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

$cell = Cell::fromValue(true); // Instance of Cell\BooleanCell

$row = Row::fromValues([
    true,
    new DateInterval('P1D'),
    new DateTimeImmutable('now'),
    null,
    '=SUM(A1:A2)',
    3,
    'foo',
]);
```

## Upgrading from 2.x to 3.0

OpenSpout 3.0 introduced several backwards-incompatible changes. The upgrade from OpenSpout 2.x to 3.0 must therefore
be done with caution.
This guide is meant to ease this process.

### Most notable changes

In 2.x, styles were applied per row; it was therefore impossible to apply different styles to cells in the same row.
With the 3.0 version, this is now possible: each cell can have its own style.

OpenSpout 3.0 tries to enforce better typing. For instance, instead of using/returning generic arrays, OpenSpout now
makes use of specific `Row` and `Cell` objects that can encapsulate more data such as type, style, value.

Finally, **_OpenSpout 3.2 only supports PHP 7.2 and above_**, as other PHP versions are no longer supported by the
community.

### Reader changes

Creating a reader should now be done through the Reader `ReaderEntityFactory`, instead of using the `ReaderFactory`.
Also, the `ReaderFactory::create($type)` method was removed and replaced by methods for each reader:

```php
use OpenSpout\Reader\Common\Creator\ReaderEntityFactory; // namespace is no longer "OpenSpout\Reader"

$reader = ReaderEntityFactory::createXLSXReader(); // replaces ReaderFactory::create(Type::XLSX)
$reader = ReaderEntityFactory::createCSVReader();  // replaces ReaderFactory::create(Type::CSV)
$reader = ReaderEntityFactory::createODSReader();  // replaces ReaderFactory::create(Type::ODS)
```

When iterating over the spreadsheet rows, OpenSpout now returns `Row` objects, instead of an array containing row
values. Accessing the row values should now be done this way:

```php
foreach ($reader->getSheetIterator() as $sheet) {
    foreach ($sheet->getRowIterator() as $row) { // $row is a "Row" object, not an array
        $rowAsArray = $row->toArray();  // this is the 2.x equivalent
        // OR
        $cellsArray = $row->getCells(); // this can be used to get access to cells' details
        ... 
    }
}
```

### Writer changes

Writer creation follows the same change as the reader. It should now be done through the Writer `WriterEntityFactory`,
instead of using the `WriterFactory`.
Also, the `WriterFactory::create($type)` method was removed and replaced by methods for each writer:

```php
use OpenSpout\Writer\Common\Creator\WriterEntityFactory; // namespace is no longer "OpenSpout\Writer"

$writer = WriterEntityFactory::createXLSXWriter(); // replaces WriterFactory::create(Type::XLSX)
$writer = WriterEntityFactory::createCSVWriter();  // replaces WriterFactory::create(Type::CSV)
$writer = WriterEntityFactory::createODSWriter();  // replaces WriterFactory::create(Type::ODS)
```

Adding rows is also done differently: instead of passing an array, the writer now takes in a `Row` object (or an
array of `Row`). Creating such objects can easily be done this way:
```php
// Adding a row from an array of values (2.x equivalent)
$cellValues = ['foo', 12345];
$row1 = WriterEntityFactory::createRowFromArray($cellValues, $rowStyle);

// Adding a row from an array of Cell
$cell1 = WriterEntityFactory::createCell('foo', $cellStyle1); // this cell has its own style
$cell2 = WriterEntityFactory::createCell(12345, $cellStyle2); // this cell has its own style
$row2 = WriterEntityFactory::createRow([$cell1, $cell2]);

$writer->addRows([$row1, $row2]);
```

### Namespace changes for styles

The namespaces for styles have changed. Styles are still created by using a `builder` class.

For the builder, please update your import statements to use the following namespaces:

    OpenSpout\Writer\Common\Creator\Style\StyleBuilder
    OpenSpout\Writer\Common\Creator\Style\BorderBuilder

The `Style` base class and style definitions like `Border`, `BorderPart` and `Color` also have a new namespace.

If your are using these classes directly via an import statement in your code, please use the following namespaces:

    OpenSpout\Common\Entity\Style\Border
    OpenSpout\Common\Entity\Style\BorderPart
    OpenSpout\Common\Entity\Style\Color
    OpenSpout\Common\Entity\Style\Style

### Handling of empty rows

In 2.x, empty rows were not added to the spreadsheet.
In 3.0, `addRow` now always writes a row to the spreadsheet: when the row does not contain any cells, an empty row
is created in the sheet.
