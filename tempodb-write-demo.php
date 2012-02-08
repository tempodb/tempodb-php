<?php
require('./tempodb.php');
date_default_timezone_set("America/Chicago");

$tdb = new TempoDB("your-api-key", "your-api-secret");

$series_key = "custom-series-key";

/* http://tempo-db.com/api/write-series/#write-series-by-key */


$date = new DateTime("2012-01-01");

// insert random data for testing
// write in ten days worth of data, starting on midnight of $date
for ($day = 0; $day < 10; $day++)
{
    $data = array();

    // build up array of timestamp/value pairs for one day
    for ($min=0; $min < 1440; $min++)
    { 
        $data[] = array('t' => $date->format("c"), 'v' => rand()/17);
        // increment by 1 min
        $date->modify("+1 minute");
    }

    // send the days worth of data
    $result = $tdb->write_key($series_key, $data);
    var_dump($result);
}

?>
