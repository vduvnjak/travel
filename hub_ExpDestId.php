<?php
require "include/connect_db.php";
$airport_code=$_REQUEST['airport_code'];
$data = array("data");

	$d_query = db_query ("SELECT destinationId FROM expedia_destinations WHERE airportCode='$airport_code' ORDER BY mainAirport DESC, destinationId ASC LIMIT 1");
	$data['data']['destId'] = db_result($d_query,0,"destinationId");
	db_free_result($d_query);
	

	echo json_encode($data); 
?>
