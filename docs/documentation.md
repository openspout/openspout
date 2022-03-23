# Documentation

## Configuration for CSV

It is possible to configure both the CSV reader and writer to adapt them to your requirements:

```php
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

$options = new Options();
$options->FIELD_DELIMITER = '|';
$options->FIELD_ENCLOSURE = '@';
$reader = new Reader($options);
```

Additionally, if you need to read non UTF-8 files, you can specify the encoding of your file this way:

```php
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

$options = new Options();
$options->ENCODING = 'UTF-16LE';
$reader = new Reader($options);
```

By default, the writer generates CSV files encoded in UTF-8, with a BOM.
It is however possible to not include the BOM:

```php
use OpenSpout\Writer\CSV\Writer;
use OpenSpout\Writer\CSV\Options;

$options = new Options();
$options->SHOULD_ADD_BOM = false;
$writer = new Writer($options);
```


## Configuration for XLSX and ODS

### New sheet creation

It is possible to change the behavior of the writers when the maximum number of rows (*1,048,576*) has been written in
the current sheet. By default, a new sheet is automatically created so that writing can keep going but that may not
always be preferable.

```php
use OpenSpout\Writer\ODS\Writer;
use OpenSpout\Writer\ODS\Options;

$options = new Options();
$options->SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY = true; // default value
$options->SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY = false; // will stop writing new data when limit is reached
$writer = new Writer($options);
```

### Sheet view (XLSX writer)

Sheet view settings must be configured before any rows are added to the sheet.

```php
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

$sheetView = new SheetView();
$sheetView->setFreezeRow(2); // First row will be fixed
$sheetView->setFreezeColumn('D'); // Columns A to C will be fixed
$sheetView->setZoomScale(150); // And other options

$writer = new Writer();
$writer->getCurrentSheet()->setSheetView($sheetView);
```

### Using a custom temporary folder

Processing XLSX and ODS files requires temporary files to be created. By default, OpenSpout will use the system default
temporary folder (as returned by `sys_get_temp_dir()`). It is possible to override this by explicitly setting it on the
reader or writer:

```php
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;

$options = new Options();
$options->TEMP_FOLDER = $customTempFolderPath;
$writer = new Writer($options);
```

### Strings storage (XLSX writer)

XLSX files support different ways to store the string values:

* Shared strings are meant to optimize file size by separating strings from the sheet representation and ignoring
  strings duplicates (if a string is used three times, only one string will be stored)
* Inline strings are less optimized (as duplicate strings are all stored) but is faster to process

In order to keep the memory usage really low, OpenSpout does not de-duplicate strings when using shared strings. It is
nevertheless possible to use this mode.

```php
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;

$options = new Options();
$options->SHOULD_USE_INLINE_STRINGS = true; // default (and recommended) value
$options->SHOULD_USE_INLINE_STRINGS = false; // will use shared strings
$writer = new Writer($options);
```

> #### Note on Apple Numbers and iOS support
>
> Apple's products (Numbers and the iOS previewer) don't support inline strings and display empty cells instead.
> Therefore, if these platforms need to be supported, make sure to use shared strings!

### Date/Time formatting

When reading a spreadsheet containing dates or times, OpenSpout returns the values by default as `DateTime` objects.
It is possible to change this behavior and have a formatted date returned instead (e.g. "2016-11-29 1:22 AM"). The
format of the date corresponds to what is specified in the spreadsheet.

```php
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Reader\XLSX\Options;

$options = new Options();
$options->SHOULD_FORMAT_DATES = false; // default value
$options->SHOULD_FORMAT_DATES = true; // will return formatted dates
$reader = new Reader($options);
```
 
## Empty rows

By default, when OpenSpout reads a spreadsheet it skips empty rows and only return rows containing data.
This behavior can be changed so that OpenSpout returns all rows:

```php
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

$options = new Options();
$options->SHOULD_PRESERVE_EMPTY_ROWS = true;
$reader = new Reader($options);
```

## Styling

### Available styles

OpenSpout supports styling at a row and cell level. It is possible to customize the fonts, backgrounds, alignment as
well as borders.

For fonts and alignments, OpenSpout does not support all the possible formatting options yet. But you can find the most
important ones:

| Category             | Property       | API |
|:---------------------|:---------------|:-------------------------------------- |
| Font                 | Bold           | `StyleBuilder::setFontBold()` |
|                      | Italic         | `StyleBuilder::setFontItalic()` |
|                      | Underline      | `StyleBuilder::setFontUnderline()` |
|                      | Strikethrough  | `StyleBuilder::setFontStrikethrough()` |
|                      | Font name      | `StyleBuilder::setFontName('Arial')` |
|                      | Font size      | `StyleBuilder::setFontSize(14)` |
|                      | Font color     | `StyleBuilder::setFontColor(Color::BLUE)`<br>`StyleBuilder::setFontColor(Color::rgb(0, 128, 255))` |
| Alignment            | Cell alignment | `StyleBuilder::setCellAlignment(CellAlignment::CENTER)` |
|                      | Wrap text      | `StyleBuilder::setShouldWrapText(true)` |
| Format _(XLSX only)_ | Number format  | `StyleBuilder::setFormat('0.000')` |
|                      | Date format    | `StyleBuilder::setFormat('m/d/yy h:mm')` |

### Styling rows

It is possible to apply some formatting options to a row. In this case, all cells of the row will have the same style:

```php
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Writer\Common\Creator\Style\StyleBuilder;
use OpenSpout\Writer\XLSX\Writer;

$writer = new Writer();
$writer->openToFile($filePath);

// Create a style with the StyleBuilder
$style = new Style();
$style->setFontBold();
$style->setFontSize(15);
$style->setFontColor(Color::BLUE);
$style->setShouldWrapText();
$style->setCellAlignment(CellAlignment::RIGHT);
$style->setBackgroundColor(Color::YELLOW);

// Create a row with cells and apply the style to all cells
$row = Row::fromValues(['Carl', 'is', 'great'], $style);

// Add the row to the writer
$writer->addRow($row);
$writer->close();
```

Adding borders to a row requires a ```Border``` object.

```php
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Writer\Common\Creator\Style\StyleBuilder;
use OpenSpout\Writer\XLSX\Writer;

$border = new Border(
    new BorderPart(Border::BOTTOM, Color::GREEN, Border::WIDTH_THIN, Border::STYLE_DASHED)
);

$style = new Style();
$style->setBorder($border);

$writer = new Writer();
$writer->openToFile($filePath);

$cells = Cell::fromValue('Border Bottom Green Thin Dashed');
$row = new Row([$cells]);
$row->setStyle($style);
$writer->addRow($row);

$writer->close();
```

### Styling cells

The same styling techniques as described in [Styling rows](#styling-rows) can be applied to individual cells of a row
as well.

Cell styles are inherited from the parent row and the default row style respectively.

The styles applied to a specific cell will override any parent styles if present.

Example:

```php
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;

$defaultStyle = new Style();
$defaultStyle->setFontSize(8);

$options = new Options();
$options->DEFAULT_ROW_STYLE = $defaultStyle;
$writer = new Writer($options);
$writer->openToFile($filePath);

$zebraBlackStyle = new Style();
$zebraBlackStyle->setBackgroundColor(Color::BLACK);
$zebraBlackStyle->setFontColor(Color::WHITE);
$zebraBlackStyle->setFontSize(10);

$zebraWhiteStyle = new Style();
$zebraWhiteStyle->setBackgroundColor(Color::WHITE);
$zebraWhiteStyle->setFontColor(Color::BLACK);
$zebraWhiteStyle->setFontItalic();

$cells = [
    Cell::fromValue('Ze', $zebraBlackStyle),
    Cell::fromValue('bra', $zebraWhiteStyle)
];

$rowStyle = new Style();
$rowStyle->setFontBold();

$row = new Row($cells, $rowStyle);

$writer->addRow($row);
$writer->close();
```

### Default style

OpenSpout will use a default style for all created rows. This style can be overridden this way:

```php
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;

$defaultStyle = new Style();
$defaultStyle->setFontName('Arial');
$defaultStyle->setFontSize(11);

$options = new Options();
$options->DEFAULT_ROW_STYLE = $defaultStyle;
$writer = new Writer($options);
$writer->openToFile($filePath);
```

## Playing with sheets

When creating a XLSX or ODS file, it is possible to control which sheet the data will be written into. At any time, you
can retrieve or set the current sheet:

```php
$firstSheet = $writer->getCurrentSheet();
$writer->addRow($rowForSheet1); // writes the row to the first sheet

$newSheet = $writer->addNewSheetAndMakeItCurrent();
$writer->addRow($rowForSheet2); // writes the row to the new sheet

$writer->setCurrentSheet($firstSheet);
$writer->addRow($anotherRowForSheet1); // append the row to the first sheet
```

It is also possible to retrieve all the sheets currently created:
```php
$sheets = $writer->getSheets();
```

It is possible to retrieve some sheet's attributes when reading:
```php
foreach ($reader->getSheetIterator() as $sheet) {
    $sheetName = $sheet->getName();
    $isSheetVisible = $sheet->isVisible();
    $isSheetActive = $sheet->isActive(); // active sheet when spreadsheet last saved
}
```

If you rely on the sheet's name in your application, you can customize it this way:

```php
// Accessing the sheet name when writing
$sheet = $writer->getCurrentSheet();
$sheetName = $sheet->getName();

// Customizing the sheet name when writing
$sheet = $writer->getCurrentSheet();
$sheet->setName('My custom name');
```

> Please note that Excel has some restrictions on the sheet's name:
> * it must not be blank
> * it must not exceed 31 characters
> * it must not contain these characters: \ / ? * : [ or ]
> * it must not start or end with a single quote
> * it must be unique
>
> Handling these restrictions is the developer's responsibility. OpenSpout does not try to automatically change the
> sheet's name, as one may rely on this name to be exactly what was passed in.
