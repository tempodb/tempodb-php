<?php
/* http://tempo-db.com/api/write-series/#bulk-write-multiple-series */

require('./tempodb.php');
date_default_timezone_set("America/Chicago");

$tdb = new TempoDB("myagley", "opensesame");

$data = array(
    't' => date("c"),
    'data' => array(
        array('key' => "custom-series-key1", 'v' => 1.11),
        array('key' => "custom-series-key2", 'v' => 2.22),
        array('key' => "custom-series-key3", 'v' => 3.33),
        array('key' => "custom-series-key4", 'v' => 4.44),
    )
);
// write bulk data
$result = $tdb->write_bulk($data);
var_dump($result);

?>
