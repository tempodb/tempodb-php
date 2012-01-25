<?php
require('./tempodb.php');

$tdb = new TempoDB("myagley", "opensesame");
$series_name = 1;

date_default_timezone_set("America/Chicago");
$start = new DateTime("2014-01-01");
$end = new DateTime("2014-01-03");
$result = $tdb->range($start, $end, NULL, $series_name);
var_dump($result);

/*
$date = new DateTime("2014-01-01");
for ($day = 0; $day < 1; $day++)
{
	$data = array();

	for ($min=0; $min < 1440; $min++)
	{ 
		$data[] = array('t' => date("c"), 'v' => rand()/17);
		$date->modify("+1 minute");
	}

	echo json_encode($data);
	$result = $tdb->add($data, NULL, $series_name);
	var_dump($result);
}
*/

?>
