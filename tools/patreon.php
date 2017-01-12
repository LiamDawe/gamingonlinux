 <?php
$csv = array_map('str_getcsv', file('patreon.csv'));

//$csvFile = file('patreon.csv');
$data = [];
array_splice($csv, 0, 2);
//echo '<pre>';
//print_r($csvFile);
//echo '</pre>';
foreach ($csv as $line) {
    echo '<pre>';
    print_r($line);
    echo '</pre>';
}

?>
