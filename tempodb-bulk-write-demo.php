<?php
/* http://tempo-db.com/api/write-series/#bulk-write-multiple-series */

require('./tempodb.php');
date_default_timezone_set("America/Chicago");

$tdb = new TempoDB("myagley", "opensesame");

$series_key1 = "custom-series-key1";
$series_key2 = "custom-series-key2";
$series_key3 = "custom-series-key3";
$series_key4 = "custom-series-key4";


$data = array(
    't' => date("c"),
    'data' => array(
        array('key' => $series_key1, 'v' => 1.11),
        array('key' => $series_key2, 'v' => 2.22),
        array('key' => $series_key3, 'v' => 3.33),
        array('key' => $series_key4, 'v' => 4.44),
    )
);
// write bulk data
$result = $tdb->write_bulk($data);
var_dump($result);

?>
