<?php

require('../src/tempodb.php');
date_default_timezone_set("America/Chicago");

$tdb = new TempoDB("your-api-key", "your-api-secret");
$keys = array("custom-series-key");
$series_list = $tdb->get_series(array("keys" => $keys));

if ($series_list) {
    $series = $series_list[0];
    $series->tags = array("tag3");
    $result = $tdb->update_series($series);
    var_dump($result);
}

?>
