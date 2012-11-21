<?php

require('../src/tempodb.php');
date_default_timezone_set("America/Chicago");

$tdb = new TempoDB("your-api-key", "your-api-secret");

$series1 = $tdb->create_series("my-custom-key");
$series2 = $tdb->create_series();

var_dump($series1);
var_dump($series2);

?>
