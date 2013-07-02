<?php  
require "include/connect_db.php";
//echo"<pre>";print_r($_REQUEST);	echo"</pre>";
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

	$data = array("form_data");
	
	$serialized_form_array = $_REQUEST['form_data'];
	$params = array();
	parse_str($serialized_form_array, $params);
	
	$event = 			$data['form_data']['event'] =			$_REQUEST['event'];
	$destination = 		$data['form_data']['destination'] =		$_REQUEST['destination'];
	
	$Ip = 				$data['form_data']['Ip'] =				$_SERVER['REMOTE_ADDR'];			
	$userAgent = 		$data['form_data']['userAgent'] =		$_SERVER["HTTP_USER_AGENT"];		
	$httpReferer = 		$data['form_data']['httpReferer'] = 	$_SERVER['HTTP_REFERER'];	
	
	$startLocation = 	$data['form_data']['startLocation'] =	$params['startLocation'];
	$departureDate = 	$data['form_data']['departureDate'] =	$params['startDate'];
	$nights = 			$data['form_data']['nights'] =			$params['numberNights'];
	$people = 			$data['form_data']['people'] =			$params['numberPeople'];
	$budget = 			$data['form_data']['budget'] =			$params['budget'];
	
	$insert_string = "INSERT INTO _tracking
	(Ip, userAgent, httpReferer, event, startLocation, destination, departureDate, nights, people, budget)
	VALUES 
	('$Ip', '$userAgent', '$httpReferer', '$event', '$startLocation', '$destination', '$departureDate', $nights, $people, $budget)
	";
	//echo "$insert_string";
	$insertquery = db_query ($insert_string);
	db_free_result($insertquery) OR die(mysql_error());
	
	$json_res = json_encode($data);

	
?>