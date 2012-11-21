<?php
/* http://tempo-db.com/api/read-series/#read-series-by-key */

require('../src/tempodb.php');
date_default_timezone_set("America/Chicago");

$tdb = new TempoDB("your-api-key", "your-api-secret");

$series_key = "custom-series-key";
$start = new DateTime("2011-01-03");
$end = new DateTime("2011-01-06");


/* read query with no rollup interval or function specified */
/* rollup interval will be auto-calculated based on start-end date read */
/* rollup function defaults to avg */
$result = $tdb->read_key($series_key, $start, $end);

/* read query with no rollup interval specified */
/* rollup function defaults to avg */
//$result = $tdb->read_key($series_key, $start, $end, $interval="1month");

/* read query with a rollup interval specified */
//$result = $tdb->read_key($series_key, $start, $end, $interval="1month", $function="min");

var_dump($result);

?>
