<?php

function curl_HotelList($cid,$minorRev,$customerUserAgent,$customerIpAddress,$locale,$currencyCode,$apikey_hotels,$secret_hotels,$timestamp,$destinationId)
{
	$ch = curl_init();
	$ver = 'v3/';
	$method = 'list/';
	$otherElementsStr = "cid=$cid&minorRev=$minorRev&customerUserAgent=$customerUserAgent&customerIpAddress=$customerIpAddress&locale=$locale&currencyCode=$currencyCode";
	$sig = md5($apikey_hotels . $secret_hotels . $timestamp);  
	$service = 'http://api.ean.com/ean-services/rs/hotel/';
	$request_string_hotels = $service . $ver. $method . '?apikey=' .$apikey_hotels .'&sig='.$sig. '&' . $otherElementsStr . '&destinationId=' . $destinationId;
	//echo $request_string_hotels;
	curl_setopt($ch, CURLOPT_URL, $request_string_hotels);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:application/json'));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$data_hotels = curl_exec($ch);
	$headers = curl_getinfo($ch);
	
	// close curl
	curl_close($ch);
	
	if ($headers['http_code'] != '200') 
	{
	  echo "<br>An error in retrieving hotel list has occurred. ". $headers['http_code'];
	  //echo "<pre>";print_r ($headers);echo"</pre>";
	  return false;
	} 
	else 
	{
	 	$json_result_hotels=json_decode($data_hotels,true);
	 	return $json_result_hotels;
	}

}

function curl_FlightList($cid,$apikey_air,$frairport,$fromdate,$todate,$dest_city_code,$numadult,$nr_results_requested)
{
	// we build xml request here
	
	$xmlRequest = "<AirSessionRequest method='getAirAvailability'>";
	$xmlRequest .= "<AirAvailabilityQuery>";
	$xmlRequest .= "<originCityCode>$frairport</originCityCode>";
	$xmlRequest .= "<destinationCityCode>$dest_city_code</destinationCityCode>";
	$xmlRequest .= "<tripType>R</tripType>";
	$xmlRequest .= "<xmlResultFormat>2</xmlResultFormat>";
	$xmlRequest .= "<searchType>2</searchType>";
	$xmlRequest .= "<numResultsRequested>$nr_results_requested</numResultsRequested>";
	$xmlRequest .= "<departureDateTime>$fromdate 00:00 AM</departureDateTime>";
	$xmlRequest .= "<returnDateTime>$todate 00:00 AM</returnDateTime>";
	$xmlRequest .= "<Passengers><adultPassengers>$numadult</adultPassengers></Passengers>";
	$xmlRequest .= "</AirAvailabilityQuery>";
	$xmlRequest .= "</AirSessionRequest>";
	

	
	// airline code DL-delta NW-Northwest/KLM AC-AirCanada AS-AlaskaAirlines BA-BritishAirways LH-LuftHansa NK-Spirit US-UsAirways UA-United VS-VirginAtlantic
	
	
	$xmlRequest=urlencode($xmlRequest); 

	$ch = curl_init();
	$otherElementsStr = "cid=$cid&resType=air&intfc=ws";
	$service = 'http://api.ean.com/ean-services/rs/air/200919/xmlinterface.jsp?';
	$request_string_flights = $service. $otherElementsStr.'&apiKey=' .$apikey_air .'&xml='.$xmlRequest;
	//echo $request_string_flights;
	curl_setopt($ch, CURLOPT_URL, $request_string_flights);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:application/xml'));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$data_flights = curl_exec($ch);
	$headers = curl_getinfo($ch);
	
	// close curl
	curl_close($ch);
	
	if ($headers['http_code'] != '200') 
	{
	  echo "<br>An error in retrieving flight list has occurred. ". $headers['http_code'];
	  //echo "<pre>";print_r ($headers);echo"</pre>";
	  return false;
	} 
	else 
	{
	 	$xml_encoded_object = new SimpleXMLElement($data_flights);
	 	$json = json_encode($xml_encoded_object);
		$json_result_flights = json_decode($json,TRUE);
	 	return $json_result_flights;
	}

}


function getRESTLocation ($cid,$minorRev,$customerUserAgent,$customerIpAddress,$locale,$currencyCode,$apikey_hotels,$secret_hotels,$timestamp,$city,$state,$country)
{
	$ch = curl_init();
	$ver = 'v3/';
	$method = 'geoSearch';
	$otherElementsStr = "cid=$cid&minorRev=$minorRev&apiKey=$apikey_hotels&customerUserAgent=$customerUserAgent&customerIpAddress=$customerIpAddress&locale=$locale&currencyCode=$currencyCode&city=$city&stateProvinceCode=$state&countryCode=$country";
	$sig = md5($apikey_hotels . $secret_hotels . $timestamp);  
	$service = 'http://api.ean.com/ean-services/rs/hotel/';
	$request_string = $service . $ver. $method . '?' . $otherElementsStr;
	//echo $request_string;
	curl_setopt($ch, CURLOPT_URL, $request_string);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:application/json'));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$data_city = curl_exec($ch);
	$headers = curl_getinfo($ch);
	
	// close curl
	curl_close($ch);
	
	if ($headers['http_code'] != '200') 
	{
	  echo "<br>An error in retrieving destination has occurred. ". $headers['http_code'];
	  //echo "<pre>";print_r ($headers);echo"</pre>";
	  return false;
	} 
	else 
	{
	 	$json_result=json_decode($data_city,true);
	 	//echo "<pre>";print_r ($json_result);echo"</pre>";
	 	$destinationId = $json_result['LocationInfoResponse']['LocationInfos']['LocationInfo']['destinationId'];
	 	//echo "$destinationId";
	 	return $destinationId;
	}
}

?>