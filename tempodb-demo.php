<?php
require('./tempodb.php');
date_default_timezone_set("UTC");

$tdb = new TempoDB("myagley", "opensesame");
$series_name = 1;

/*
// read
$start = new DateTime("2014-01-01");
$end = new DateTime("2014-01-03");
$result = $tdb->range($start, $end, NULL, $series_name);
var_dump($result);
*/


// write
$date = new DateTime("2014-02-09");

// write in ten days
for ($day = 0; $day < 1; $day++)
{
	$data = array();
	for ($min=0; $min < 1440; $min++)
	{ 
		$data[] = array('t' => $date->format("c"), 'v' => rand()/17);
		$date->modify("+1 minute");
	}

	$result = $tdb->add($data, NULL, $series_name);
	var_dump($result);
}

?>
