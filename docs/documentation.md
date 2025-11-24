# Documentation

## Configuration for CSV

It is possible to configure both the CSV reader and writer to adapt them to your requirements:

```php
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

$reader = new Reader(new Options(
    FIELD_DELIMITER: '|',
    FIELD_ENCLOSURE: '@',
));
```

Additionally, if you need to read non UTF-8 files, you can specify the encoding of your file this way:

```php
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

$reader = new Reader(new Options(
    ENCODING: 'UTF-16LE',
));
```

By default, the writer generates CSV files encoded in UTF-8, with a BOM.
It is however possible to not include the BOM:

```php
use OpenSpout\Writer\CSV\Writer;
use OpenSpout\Writer\CSV\Options;

$writer = new Writer(new Options(
    SHOULD_ADD_BOM: false,
));
```

## Configuration for XLSX and ODS

### New sheet creation

It is possible to change the behavior of the writers when the maximum number of rows (*1,048,576*) has been written in
the current sheet. By default, a new sheet is automatically created so that writing can keep going but that may not
always be preferable.

```php
use OpenSpout\Writer\ODS\Writer;
use OpenSpout\Writer\ODS\Options;

$writer = new Writer(new Options(
    SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY: false, // default:true, with false will stop writing new data when limit is reached
));
```

### Setting custom document creator (ODS writer)

It is possible to change default document creator.
The default creator is OpenSpout

```php
use OpenSpout\Writer\ODS\Options;
use OpenSpout\Writer\ODS\Writer;

$writer = new Writer();
$writer->setCreator('Custom creator');
```

### Setting custom document properties (XLSX writer)

It is possible to change default document properties.
The default values are as follows.

```php
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Properties;
use OpenSpout\Writer\XLSX\Writer;

$writer = new Writer(new Options(
    properties: new Properties(
        title: 'Untitled Spreadsheet',
        subject: null,
        application: 'OpenSpout',
        creator: 'OpenSpout',
        lastModifiedBy: 'OpenSpout',
        keywords: null,
        description: null,
        category: null,
        language: null,
        customProperties: [
            'test' => 'Test'
        ]
    ),
));
```

### Sheet view (XLSX writer)

Sheet view settings must be configured before any rows are added to the sheet.

```php
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

$sheetView = new SheetView(
    freezeRow: 2, // First row will be fixed
    freezeColumn: 'D', // Columns A to C will be fixed
    zoomScale: 150,
    showFormulas: true,
    showGridLines: false,
    showRowColHeaders: false,
    showZeros: false,
    rightToLeft: true,
    tabSelected: false,
    showOutlineSymbols: false,
    defaultGridColor: false,
    view: 'normal',
    topLeftCell: 'A2',
    colorId: 1,
    zoomScale: 50,
    zoomScaleNormal: 70,
    zoomScalePageLayoutView: 80,
    workbookViewId: 90,
    freezeColumn: 'B',
    freezeRow: 2,
);
$writer = new Writer();
$writer->getCurrentSheet()->setSheetView($sheetView);
```

### AutoFilter (XLSX Writer)

AutoFilter can be configured using the `AutoFilter` class:

```
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Writer;

$autoFilter = new AutoFilter(0, 10, 5, 20);
$writer->getCurrentSheet()->setAutoFilter($autoFilter);
```

Note that columns are 0-indexed, while rows are 1-indexed.

### Using a custom temporary folder

Processing XLSX and ODS files requires temporary files to be created. By default, OpenSpout will use the system default
temporary folder (as returned by `sys_get_temp_dir()`). It is possible to override this by explicitly setting it on the
reader or writer:

```php
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;

$writer = new Writer(new Options(
    tempFolder: $customTempFolderPath,
));
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

$writer = new Writer(new Options(
    SHOULD_USE_INLINE_STRINGS: false, // default:true, with false will use shared strings
));
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

$reader = new Reader(new Options(
    SHOULD_FORMAT_DATES: true, // default:true, with false will return formatted dates
));
```
 
## Empty rows

By default, when OpenSpout reads a spreadsheet it skips empty rows and only return rows containing data.
This behavior can be changed so that OpenSpout returns all rows:

```php
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;

$reader = new Reader(new Options(
    SHOULD_PRESERVE_EMPTY_ROWS: true,
));
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

## Page setup

```php
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Options\PageMargin;
use OpenSpout\Writer\XLSX\Options\PageOrientation;
use OpenSpout\Writer\XLSX\Options\PageSetup;
use OpenSpout\Writer\XLSX\Options\PaperSize;

$writer = new Writer(new Options(
    pageSetup: new PageSetup(
        PageOrientation::LANDSCAPE,
        PaperSize::A4,
        0, // ?int fitToHeight
        1  // ?int fitToWidth
    ),
    // set margin in inches: top, right, bottom, left, header, footer
    pageMargin: new PageMargin(0.75, 0.7, 0.75, 0.7, 0.3, 0.3),
));
```

## HeaderFooter

```php
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Options\HeaderFooter;

$writer = new Writer(new Options(
    headerFooter: new HeaderFooter(
        'oddHeader',
        'oddFooter',
        'evenHeader',
        'evenFooter',
        true,  // differentOddEven, default value is false
    ),
));
```

## Styling

### Available styles

OpenSpout supports styling at cell level. It is possible to customize the fonts, backgrounds, alignment as
well as borders.

For fonts and alignments, OpenSpout does not support all the possible formatting options yet. But you can find the most
important ones:

| Category             | Property                | API
|:---------------------|:------------------------|:--------------------------------------
| Font                 | Bold                    | `Style::withFontBold()`
|                      | Italic                  | `Style::withFontItalic()`
|                      | Underline               | `Style::withFontUnderline()`
|                      | Strikethrough           | `Style::withFontStrikethrough()`
|                      | Font name               | `Style::withFontName('Arial')`
|                      | Font size               | `Style::withFontSize(14)`
|                      | Font color              | `Style::withFontColor(Color::BLUE)`
|                      |                         | `Style::withFontColor(Color::rgb(0, 128, 255))`
| Alignment            | Cell alignment          | `Style::withCellAlignment(CellAlignment::CENTER)`
|                      | Cell vertical alignment | `Style::withCellVerticalAlignment(CellVerticalAlignment::CENTER)`
|                      | Wrap text               | `Style::withShouldWrapText(true)`
| Format _(XLSX only)_ | Number format           | `Style::withFormat('0.000')`
|                      | Date format             | `Style::withFormat('m/d/yy h:mm')`


Adding borders to a row requires a ```Border``` object.

```php
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\BorderStyle;
use OpenSpout\Common\Entity\Style\BorderWidth;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Writer\XLSX\Writer;

$border = new Border(
    new BorderPart(BorderName::BOTTOM, Color::GREEN, BorderWidth::THIN, BorderStyle::DASHED)
);

$style = new Style(
    border: $border,
);
```

### Fallback style

OpenSpout will use a fallback style for all created rows. This style can be overridden this way:

```php
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;

$fallbackStyle = new Style(
    fontName: 'Arial',
    fontSize: 11,
);

$writer = new Writer(new Options(FALLBACK_STYLE: $fallbackStyle));
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

$comment = new Comment(
    height: '200px',
    width: '400px',
    textRuns: [
        new TextRun(
            text: "WARNING\n\n",
            bold: true,
        ),
        new TextRun(
            text: 'There is something wrong with this cell',
            italic: true,
        ),
    ],
);

$cell = Cell::fromValue('Test', null, $comment);
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

## Protection

There are a number of ways to protect the editing of a spreadsheet.

> #### Note on security
>
> These protections are trivial to remove/bypass. They are only enforced if the application reading the spreadsheet
> chooses to respect them. They should not be relied upon.

### Workbook Protection

> #### Note on LibreOffice support
>
> LibreOffice does not respect workbook protection.

```php
use OpenSpout\Writer\XLSX\Writer;
use \OpenSpout\Writer\XLSX\Options
use OpenSpout\Writer\XLSX\Options\WorkbookProtection;

$protection = new WorkbookProtection(
    password: 'password',
    lockStructure: true, // Prevents adding, deleting, renaming, or rearranging worksheets
    lockRevisions: true, // Restricts revision history
    lockWindows: true, // Prevents resizing or moving the Excel window
);

$writer = new Writer(new Options(workbookProtection: $protection));
```


### Single Worksheet Protection

> #### Note on LibreOffice support
>
> LibreOffice only respects the following protections, all others will be ignored:
> - Select (un)protected cells
> - Insert rows/columns
> - Delete rows/columns

```php
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options\SheetProtection;

$writer = new Writer();

$protection = new SheetProtection(
    password: 'password',
    lockSheet: true,
    lockColumnInsert: true,
    lockColumnDelete: true,
    lockColumnFormatting: true,
    lockRowInsert: true,
    lockRowDelete: true,
    lockRowFormatting: true,
    lockAutoFilter: true,
    lockSort: true,
    lockCellFormatting: true,
    lockLockedCellSelection: true,
    lockUnlockedCellsSelection: true,
    lockObjects: true,
    lockHyperlinkInsert: true,
    lockPivotTables: true,
    lockScenarios: true,
);

$writer
    ->getCurrentSheet()
    ->setSheetProtection($protection);
```
