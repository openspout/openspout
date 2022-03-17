# Read data from a specific sheet only

Even though a spreadsheet contains multiple sheets, you may be interested in reading only one of them and skip the other ones. Here is how you can do it with OpenSpout:

* If you know the name of the sheet

```php
$reader = \OpenSpout\Reader\XLSX\Reader::factory();
$reader->open($filePath);

foreach ($reader->getSheetIterator() as $sheet) {
    // only read data from "summary" sheet
    if ($sheet->getName() === 'summary') {
        foreach ($sheet->getRowIterator() as $row) {
            // do something with the row
        }
        break; // no need to read more sheets
    }
}

$reader->close();
```

* If you know the position of the sheet

```php
$reader = \OpenSpout\Reader\XLSX\Reader::factory();
$reader->open($filePath);

foreach ($reader->getSheetIterator() as $sheet) {
    // only read data from 3rd sheet
    if ($sheet->getIndex() === 2) { // index is 0-based
        foreach ($sheet->getRowIterator() as $row) {
            // do something with the row
        }
        break; // no need to read more sheets
    }
}

$reader->close();
```
