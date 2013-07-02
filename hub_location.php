<?php
require "include/connect_db.php";
$term=$_REQUEST['name_startsWith'];
$data = array("location_data");
$row_array = array();

	$d_query = db_query ("
	SELECT city_name, country_name, airport_code 
	FROM airport_codes 
	WHERE (airport_code LIKE '$term%' OR city_name LIKE '$term%') AND country_name = 'United States'
	ORDER BY country_name DESC 
	LIMIT 12");
	
	$nr_results = db_numrows($d_query);
	for ($i=0; $i<$nr_results; $i++)
	{
		$city_name = db_result($d_query,$i,"city_name");
		$country_name  = db_result($d_query,$i,"country_name");
		$airport_code  = db_result($d_query,$i,"airport_code");
		
		$row_array['label'][$i] = "$airport_code";
		$row_array['value'][$i] = "$airport_code - $city_name, $country_name";
	}
		
	db_free_result($d_query);
	echo json_encode($row_array);
?>

<?php/*
require "include/connect_db.php";
$term=$_REQUEST['name_startsWith'];
$data = array("location_data");

	$d_query = db_query ("SELECT city_name, country_name, airport_code FROM airport_codes WHERE airport_code LIKE '$term%' OR city_name LIKE '$term%' LIMIT 12");
	
	$nr_results = db_numrows($d_query);
	for ($i=0; $i<$nr_results; $i++)
	{
		$city_name = $data['location_data']['city_name'][$i] = db_result($d_query,$i,"city_name");
		$country_name = $data['location_data']['country_name'][$i] = db_result($d_query,$i,"country_name");
		$airport_code = $data['location_data']['airport_code'][$i] = db_result($d_query,$i,"airport_code");
	}
	
	db_free_result($d_query);
	echo json_encode($data); */
?>