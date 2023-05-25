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

### Setting custom document creator

It is possible to change default document creator.
The default creator is OpenSpout

```php
use OpenSpout\Writer\ODS\Options;

$writer = new Writer();
$writer->setCreator('Custom creator');
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
$options->setTempFolder($customTempFolderPath);
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
 
## Column widths

Column widths can be set on options for both ODS and XLSX writers:

```php
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;

$options = new Options();
$writer = new Writer($options);
$writer->openToFile('/tmp/file.xlsx');
$writer->addRow(Row::fromValues(['foo', 'bar', 'baz']));

$options->setColumnWidth(10, 1);
$options->setColumnWidthForRange(12, 2, 3);
$writer->close();
```

Column widths can also be set at the sheet level for XLSX writers. Sheets that have column widths defined will not inherit any column widths from options.

```php
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;

$writer = new Writer;
$writer->openToFile('/tmp/file.xlsx');
$writer->addRow(Row::fromValues(['foo', 'bar', 'baz']));

$sheet = $writer->getCurrentSheet();
$sheet->setColumnWidth(10, 1);
$sheet->setColumnWidthForRange(12, 2, 3);

$writer->close();
```

For XLSX readers, you can also retrieve the column widths:

```php
$reader = new \OpenSpout\Reader\XLSX\Reader();
$reader->open('input.xlsx');

foreach ($reader->getSheetIterator() as $sheet) {
  $colWidths = $sheet->getColumnWidths();
  foreach ($colWidths as $cw) {
    print "Columns $cw->start - $cw->end have width $cw->width\n";
  }
}
```


## Cell merging

Cell can be merged with the XLSX writers:

```php
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;

$options = new Options();
$writer = new Writer($options);
$writer->openToFile('/tmp/file.xlsx');
$writer->addRow(Row::fromValues(['foo', 'bar', 'baz']));

$options->mergeCells(0, 1, 0, 2, $writer->getCurrentSheet()->getIndex());
$writer->close();
```

## Styling

### Available styles

OpenSpout supports styling at a row and cell level. It is possible to customize the fonts, backgrounds, alignment as
well as borders.

For fonts and alignments, OpenSpout does not support all the possible formatting options yet. But you can find the most
important ones:

| Category             | Property                | API
|:---------------------|:------------------------|:--------------------------------------
| Font                 | Bold                    | `Style::setFontBold()`
|                      | Italic                  | `Style::setFontItalic()`
|                      | Underline               | `Style::setFontUnderline()`
|                      | Strikethrough           | `Style::setFontStrikethrough()`
|                      | Font name               | `Style::setFontName('Arial')`
|                      | Font size               | `Style::setFontSize(14)`
|                      | Font color              | `Style::setFontColor(Color::BLUE)`
|                      |                         | `Style::setFontColor(Color::rgb(0, 128, 255))`
| Alignment            | Cell alignment          | `Style::setCellAlignment(CellAlignment::CENTER)`
|                      | Cell vertical alignment | `Style::setCellVerticalAlignment(CellVerticalAlignment::CENTER)`
|                      | Wrap text               | `Style::setShouldWrapText(true)`
| Format _(XLSX only)_ | Number format           | `Style::setFormat('0.000')`
|                      | Date format             | `Style::setFormat('m/d/yy h:mm')`

### Styling rows

It is possible to apply some formatting options to a row. In this case, all cells of the row will have the same style:

```php
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Writer\XLSX\Writer;

$writer = new Writer();
$writer->openToFile($filePath);

// Create a style with the Style
$style = new Style();
$style->setFontBold();
$style->setFontSize(15);
$style->setFontColor(Color::BLUE);
$style->setShouldWrapText();
$style->setCellAlignment(CellAlignment::RIGHT);
$style->setCellVerticalAlignment(CellVerticalAlignment::BOTTOM);
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

## Cell comments
The XLSX writer has support for adding comments (notes) to cells. To create a 400x200 panel, with in **bold** the message
'WARNING' and 2 newlines, then in *italic* a warning message.

```php
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Comment\Comment;
use OpenSpout\Common\Entity\Comment\TextRun;

$writer = new \OpenSpout\Writer\XLSX\Writer();
$writer->openToFile('output.xlsx');

$cell = Cell::fromValue('Test');
$comment = new Comment();
$comment->height = '200px';
$comment->width = '400px';

$warningTextRun = new TextRun("WARNING\n\n");
$warningTextRun->bold = true;

$somethingWrongTextRun = new TextRun('There is something wrong with this cell');
$somethingWrongTextRun->italic = true;

$comment->addTextRun($warningTextRun);
$comment->addTextRun($somethingWrongTextRun);

$cell->comment = $comment;
$row = new Row([$cell]);
$writer->addRow($row);
$writer->close();
```

A comment renders as a panel that has a height and width, of which the following can be set:

- `height`: height of the panel, in CSS format (can be with 'px' or 'pt')
- `width`: width of the panel, in CSS format (can be with 'px' or 'pt')
- `marginLeft`: left margin of the panel, in CSS format (can be with 'px' or 'pt')
- `marginTop`: top margin of the panel, in CSS format (can be with 'px' or 'pt')
- `visible`: defines whether the panel is open or hidden, the default is **false**.
- `fillColor`: sets the background of the panel, defaults to **#FFFFE1** (light yellow)

Within the panel, you can have multiple lines that have their own styling.
Each is called a `TextRun` and after instantiation is must be added to the comment with `\OpenSpout\Common\Entity\Comment\Comment::addTextRun` API.

A TextRun can be styled using the following methods:

- `bold`: Defaults to **false**
- `italic`: Defaults to **false**
- `fontName`: Name of the font, defaults to **Tahoma**
- `fontColor`: Color of the font, defaults to **000000** (note it is a 8 character
- `fontSize`: Size of the font in points

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

## Reader factory

In case of you have to manage multiple file format entries, you can use the reader factory for build your reader.  
The reader factory support two guessing method.

**Guess type by extension**

```php
$file = 'path/to/my_file.xlsx'

$reader = ReaderFactory::createFromFile($file); // return XLSX/Reader
$reader->open($file));

// Do stuff

$reader->close();
```

**Guess type by mime type**

```php
// "my_file" is an ods file.
$file = 'path/to/my_file.any'

$reader = ReaderFactory::createFromFileByMimeType($file); // return ODS/Reader
$reader->open($file));

// Do stuff

$reader->close();
```
