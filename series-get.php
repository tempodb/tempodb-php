<?php

require('./tempodb.php');
date_default_timezone_set("America/Chicago");

$tdb = new TempoDB("api-key", "api-secret");

$result = $tdb->get_series();
var_dump($result);

?>
