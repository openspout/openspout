# Edit an existing spreadsheet

Editing an existing spreadsheet is a pretty common task that OpenSpout is totally capable of doing.

With OpenSpout, it is not possible to do things like `deleteRow(3)` or `insertRowAfter(5, $newRow)`. This is because
OpenSpout does not keep an in-memory representation of the entire spreadsheet, to avoid consuming all the memory
available with large spreadsheets. This means, OpenSpout does not know how to jump to the 3rd row directly and has
especially no way of moving backwards (changing row 3 after having changed row 5). So let's see how this can be done,
in a scalable way.

For this example, let's assume we have an existing ODS spreadsheet called "my-music.ods" that looks like this:

| Song title       | Artist          | Album           | Year |
| ---------------- | --------------- | --------------- | ---- |
| Yesterday        | The Beatles     | The White Album | 1968 |
| Yellow Submarine | The Beatles     | Unknown         | 1968 |
| Space Oddity     | David Bowie     | David Bowie     | 1969 |
| Thriller         | Michael Jackson | Thriller        | 1982 |
| No Woman No Cry  | Bob Marley      | Legend          | 1984 |
| Buffalo Soldier  | Bob Marley      | Legend          | 1984 |

> Note that the album for "Yellow Submarine" is "Unknown" and that the songs are ordered by year (most recent last).

We'd like to update the missing album for "Yellow Submarine", remove the Bob Marley's songs and add a new song: "Hotel
California" from "The Eagles", released in 1976. Here is how this can be done:

```php
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\ODS\Options;
use OpenSpout\Reader\ODS\Reader;
use OpenSpout\Writer\ODS\Writer;

$existingFilePath = '/path/to/my-music.ods';
$newFilePath = '/path/to/my-new-music.ods';

// we need a reader to read the existing file...
// (SHOULD_FORMAT_DATES is needed to be able to copy dates)
$reader = new Reader(new Options(SHOULD_FORMAT_DATES: true));
$reader->open($existingFilePath);

// ... and a writer to create the new file
$writer = new Writer();
$writer->openToFile($newFilePath);

// let's read the entire spreadsheet
foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
    // Add sheets in the new file, as you read new sheets in the existing one
    if ($sheetIndex !== 1) {
        $writer->addNewSheetAndMakeItCurrent();
    }

    foreach ($sheet->getRowIterator() as $rowIndex => $row) {
        // Each row exposes its cells as an array indexed from 0 (column index),
        // where each value is a `Cell` object whose value can be read via `getValue()`
        $songTitle = $row->cells[0]->getValue();
        $artist = $row->cells[1]->getValue();

        // Change the album name for "Yellow Submarine"
        if ($songTitle === 'Yellow Submarine') {
            // Rows are immutable, so build a modified copy of the cells
            // and hand it back through `withCells()`
            $cells = $row->cells;
            $cells[2] = Cell::fromValue('The White Album');
            // keep the cell indexes in ascending order, as required by the Row constructor
            ksort($cells);
            $row = $row->withCells($cells);
        }

        // skip Bob Marley's songs
        if ($artist === 'Bob Marley') {
            continue;
        }

        // write the edited row to the new file
        $writer->addRow($row);

        // insert new song at the right position, between the 3rd and 4th rows
        if ($rowIndex === 3) {
            $writer->addRow(
                Row::fromValues(['Hotel California', 'The Eagles', 'Hotel California', 1976])
            );
        }
    }
}

$reader->close();
$writer->close();
```

Optionally, if you rely on the file name or want to keep only one file, simple remove the old file and rename the new one:

```php
unlink($existingFilePath);
rename($newFilePath, $existingFilePath);
```

That's it! The created file now contains the updated data and is ready to be used.
