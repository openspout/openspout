# Read data from a specific sheet only

Even though a spreadsheet contains multiple sheets, you may be interested in reading only one of them and skip the
other ones. Here is how you can do it with OpenSpout:

* If you know the name of the sheet

```php
$reader = new \OpenSpout\Reader\XLSX\Reader();
$reader->open($filePath);

foreach ($reader->getSheetIterator() as $sheet) {
    // only read data from "summary" sheet
    if ($sheet->getName() === 'summary') {
        foreach ($sheet->getRowIterator() as $row) {
            // do something with the row
            foreach ($row->getCells() as $cell) {
                // do something with a cell for example print it.
                echo $cell->getValue() . "\n";
            }
        }
        break; // no need to read more sheets
    }
}

$reader->close();
```

* If you know the position of the sheet

```php
$reader = new \OpenSpout\Reader\XLSX\Reader();
$reader->open($filePath);

foreach ($reader->getSheetIterator() as $sheet) {
    // only read data from 3rd sheet
    if ($sheet->getIndex() === 2) { // index is 0-based
        foreach ($sheet->getRowIterator() as $row) {
            // do something with the row example grab cell 2
            $cells = $row->getCells(); //Load all the cells
            $cell_value = $cells[2]->getValue();
            echo "$cell_value \n";
        }
        break; // no need to read more sheets
    }
}

$reader->close();
```
