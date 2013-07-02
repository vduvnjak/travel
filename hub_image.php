<?php
require "include/connect_db.php";
$airport_code=$_REQUEST['airport_code'];
$data = array("image_data");

	$d_query = db_query ("SELECT city_image FROM airport_codes WHERE airport_code='$airport_code'");
	$city_image = $data['image_data']['city_image'] = db_result($d_query,0,"city_image");
	db_free_result($d_query);
	

	echo json_encode($data); 
?>