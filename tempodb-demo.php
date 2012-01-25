<?php
require('./tempodb.php');
date_default_timezone_set("UTC");

$tdb = new TempoDB("api-key", "api-secret");
$series_name = 1;

/*
// read
$start = new DateTime("2012-01-01");
$end = new DateTime("2012-01-03");
$result = $tdb->range($start, $end, NULL, $series_name);
var_dump($result);
*/


// write
/*
    // data to add is an array of associative arrays, serialized to json as a list of dictionaries
    // data array can have as many, or few, timestamp/value pairs as you'd like
    
    $data = array( \
        array("t" => $datetime1->format("c"), "v' => 37.146), \
        array("t" => $datetime2>format("c"), "v' => 45.542), \
        array("t" => $datetime3->format("c"), "v' => 42.339), \
    )
    $result = $tdb->add($data, NULL, $series_name);
 */

$date = new DateTime("2014-02-09");

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
	$result = $tdb->add($data, NULL, $series_name);
	var_dump($result);
}

?>
