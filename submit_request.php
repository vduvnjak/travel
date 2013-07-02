<?php
require_once('include/ean_api.php');
error_reporting(E_ALL);

$cid = 55505;
$minorRev = 7;
$customerUserAgent = 'Mozilla/4.0';
$customerIpAddress = '172.16.82.13';
$locale='en_US';
$currencyCode='USD';
$apikey_air = '8jebxgv7tnuecvpenbx5kjmn';  
$secret_air = 'Eks3qA8Q';
$apikey_hotels = 'z2488y7dj622jx5yjd52w2dh';  
$secret_hotels = 'DEqbBBRZ';    
$timestamp = gmdate('U'); 

$nr_flights_requested=4;
$nr_hotels_requested=50;

$hotel_td_width = 320;
$flight_td_width = 120;

if(isset($_REQUEST['frairport']) )
{
	$frairport = $_REQUEST['frairport'];
	$fromdate = $_REQUEST['fromdate'];
	$todate = $_REQUEST['todate'];
	$numadult = $_REQUEST['numadult'];
	$numnights = $_REQUEST['numnights'];
	$numroom = $_REQUEST['numroom'];
	$dest_city = $_REQUEST['dest_city'];
	$dest_city_code = $_REQUEST['dest_city_code'];
	$dest_country = $_REQUEST['dest_country'];
	$dest_state = $_REQUEST['dest_state'];
	$xml = $_REQUEST['xmlRequest'];
	
}

if (($numadult%10)==1) $string_traveler = "traveler";
else $string_traveler = "travelers";

if (($numnights%10)==1) $string_night = "night";
else $string_night = "nights";

//$numroom = ceil($numadult/2);

if (($numroom%10)==1) $string_room = "room";
else $string_room = "rooms";

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Terms</title>
<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/3.3.0/build/cssreset/reset-context-min.css">
<link type="text/css" href="css/results.css" rel="Stylesheet" />		
<script type="text/javascript" src="js/jquery-1.4.4.min.js"></script>
</head>
<body>

<div >
<?php 
//echo"<pre>";print_r($_REQUEST);echo"</pre>";
//$destinationId = '47CC53D2-98A6-486B-8106-8783749CA665';


$destinationId = getRESTLocation ($cid,$minorRev,$customerUserAgent,$customerIpAddress,$locale,$currencyCode,$apikey_hotels,$secret_hotels,$timestamp,$dest_city,$dest_state,$dest_country);
sleep(1);
$hotelListArray = curl_HotelList($cid,$minorRev,$customerUserAgent,$customerIpAddress,$locale,$currencyCode,$apikey_hotels,$secret_hotels,$timestamp,$destinationId);
sleep(1);
$flightListArray = curl_FlightList($cid,$apikey_air,$frairport,$fromdate,$todate,$dest_city_code,$numadult,$nr_flights_requested);

//echo "<pre>"; print_r($flightListArray); echo "</pre>";


	$nr_hotels=$hotelListArray['HotelListResponse']['HotelList']['@activePropertyCount'];
 	//echo "<br>Nr of hotels returned: $nr_hotels<br>";
 	if ($nr_hotels > $nr_hotels_requested) $nr_hotels = $nr_hotels_requested;
 	
 	$nr_flights=$flightListArray['@attributes']['size'];
 	if ($nr_flights > $nr_flights_requested) $nr_flights = $nr_flights_requested;
 	$flight_segment_array = $flightListArray['SegmentList']['Segment'];
 	
 	//$flight_segment_array_sorted = sortFilghtSegmentArray($flight_segment_array);

	$table_width = $hotel_td_width + $nr_flights*$flight_td_width;
 	
 	echo "<center><table border=0 width=$table_width cellspacing=1 cellpadding=1>";
 		
 	echo "<tr><td width=$hotel_td_width><b>$frairport</b> to <b>$dest_city, $dest_country</b><br> <br> $fromdate - $todate <br> <br><b>$numadult</b> adult $string_traveler  <b>$numnights</b> $string_night  <b>$numroom </b>$string_room</td>";
 	for ($m=0;$m<$nr_flights;$m++)
 	{
 		
 		$flight_rate_array = $flightListArray['AirAvailabilityReply'][$m]['RateInfo'];
 		//echo "<pre>"; print_r($flight_rate_array); echo "</pre>";
 		$flight_price[$m] = $flight_rate_array['displayTotalPrice'];
 		$flight_price_curency[$m] = $flight_rate_array['displayCurrencyCode'];
 		
 		$flight_price_f[$m] = format_amount($flight_price[$m]);
 		
 		$current_flight_segments[$m] = $flightListArray['AirAvailabilityReply'][$m]['FlightSegment'];
 		
 		$nr_segments = count($current_flight_segments[$m]);
 		$string_flights[$m] = "";
 		for ($n=0;$n<$nr_segments;$n++)
 		{
 			
 			$segment_key = $current_flight_segments[$m][$n][segmentKey];
 			$segment_position = find_segment_position($segment_key,$flight_segment_array);
 			
 			$airlineCode = $flight_segment_array[$segment_position]['airlineCode'];
 			$airline = $flight_segment_array[$segment_position]['airline'];
 			$flightNumber = $flight_segment_array[$segment_position]['flightNumber'];
 			$originCityCode = $flight_segment_array[$segment_position]['originCityCode'];
 			$destinationCityCode = $flight_segment_array[$segment_position]['destinationCityCode'];
 			$departureDateTime = $flight_segment_array[$segment_position]['departureDateTime'];
 			$arrivalDateTime = $flight_segment_array[$segment_position]['arrivalDateTime'];
 			$string_flights[$m] .= "<br>$airline $flightNumber<br> $originCityCode - $destinationCityCode<br> $departureDateTime <br> $arrivalDateTime<br>";
 			if ($destinationCityCode == $dest_city_code) $string_flights[$m] .= "<br>----------<br>";

 		}
 		echo "<td width=$flight_td_width><a href='' title='".$string_flights[$m]."'>flight info</a><br><b> $flight_price_f[$m]</b></td>";
 	}
 	echo "</tr>";
 	for ($i=0;$i<$nr_hotels;$i++)
 	{
 		$hotel_data_array = $hotelListArray['HotelListResponse']['HotelList']['HotelSummary'][$i];
 		$hotelId = $hotel_data_array['hotelId'];
 		$hotelName = $hotel_data_array['name'];
 		$hotelAddress = $hotel_data_array['address1'];
 		$hotelCity = $hotel_data_array['city'];
 		$hotelState = $hotel_data_array['stateProvinceCode'];
 		$hotelZip = $hotel_data_array['postalCode'];
 		$hotelCountry = $hotel_data_array['countryCode'];
 		$hotelAirportCode = $hotel_data_array['airportCode'];
 		$hotelCategory = $hotel_data_array['propertyCategory'];
 		$hotelRating = $hotel_data_array['hotelRating'];
 		$hotelTARating = $hotel_data_array['tripAdvisorRating'];
 		$hotelLocationDesc = $hotel_data_array['locationDescription'];
 		$hotelShortDes = $hotel_data_array['shortDescription'];
 		$hotelHighRate = $hotel_data_array['highRate'];
 		$hotelLowRate = $hotel_data_array['lowRate'];
 		$hotelRateCurrency = $hotel_data_array['rateCurrencyCode'];
 		$hotelLat = $hotel_data_array['latitude'];
 		$hotelLon = $hotel_data_array['longitude'];
 		$hotelProxDistance = $hotel_data_array['proximityDistance'];
 		$hotelProxUnit = $hotel_data_array['proximityUnit'];
 		$hotelThumb = $hotel_data_array['thumbNailUrl'];
 		$hotelFullThumb = 'http://images.travelnow.com/'.$hotelThumb;
 		$hotelDeepLink = $hotel_data_array['deepLink'];
 		
 		$star_rating = get_stars_image($hotelRating);
 		
 		$hotelHighRate_f = format_amount($hotelHighRate);
 		$hotelLowRate_f = format_amount($hotelLowRate);
 		
 		$tmp_hotel_price = number_format((($hotelHighRate+$hotelLowRate)/2),2);
 		$tmp_hotel_price_f = format_amount($tmp_hotel_price);
 		
 		echo "<tr><td width=$hotel_td_width>";
 			echo "<table border=0 cellspacing=0 cellpadding=0 width = 320>";
 			echo "<tr><td rowspan=2 width = 80> <a href='$hotelDeepLink'><img border=0 src='$hotelFullThumb'></a></td><td><b>$hotelName</b><br><img border=0 src='$star_rating'></td></tr>";
 			echo "<tr><td>$hotelAddress <br>$hotelCity, $hotelState $hotelZip $hotelCountry<br>price range: $hotelLowRate_f - $hotelHighRate_f avg:<b>$tmp_hotel_price_f</b><br> <a href='$hotelDeepLink'>book</a></td></tr>";
 			echo "</table>";
 		echo "</td>";
 		
 		for ($j=0;$j<$nr_flights;$j++)
 		{
 			
 			$total_flight_price[$j] = $numadult*$flight_price[$j];
 			$total_flight_price_f[$j] = format_amount($total_flight_price[$j]);
 			$total_hotel_price = $numroom*$numnights*$tmp_hotel_price;
 			$total_hotel_price_f = format_amount($total_hotel_price);
 			$total_price = $total_hotel_price + $total_flight_price[$j];
 			$total_price_f = format_amount($total_price);
 			echo "<td width=$flight_td_width>flight: $total_flight_price_f[$j]<br> hotel: $total_hotel_price_f<br><span class='total_price'> $total_price_f</span></td>";
 		}
 		
 		echo "</tr>";
 	}
 	echo "</table></center><br><br>";

function get_stars_image($rating)
{
	switch ($rating) 
	{
    	case 1: $image='stars_1.png';break;
    	case 1.5: $image='stars_15.png';break;
    	case 2: $image='stars_2.png';break;
    	case 2.5: $image='stars_25.png';break;
    	case 3: $image='stars_3.png';break;
    	case 3.5: $image='stars_35.png';break;
    	case 4: $image='stars_4.png';break;
    	case 4.5: $image='stars_45.png';break;
    	case 5: $image='stars_5.png';break;
	}
	return "images/$image";
}

function sortFilghtSegmentArray($flight_segment_array)
{
	// we will loop through array. add element strtotime, sort it by that element and return the array back
	
	foreach ($flight_segment_array as $key => $row)
	{
		$orderByDate[$key]=strtotime($row['departureDateTime']);
	}
	array_multisort($orderByDate, SORT_ASC, $flight_segment_array);
	return $flight_segment_array;
}

function find_segment_position($segment_key,$flight_segment_array)
{
   // we have to find which element of segment array holds data for the flight
	foreach ($flight_segment_array as $key => $row)
	{
		if ($flight_segment_array[$key]['@attributes']['key']==$segment_key)
		{
			$result = $key;
		}
		
	}
	return $result;
}

function format_amount($amount)
{
	setlocale(LC_MONETARY, 'en_US');
	$amount_f = money_format('%(#1.2n', $amount); 
	return $amount_f;
}

?>
</div>

<br/>

<?php include("footer.php"); ?>
<script src="//static.getclicky.com/js" type="text/javascript"></script>
<script type="text/javascript">try{ clicky.init(66454091); }catch(e){}</script>
<noscript><p><img alt="Clicky" width="1" height="1" src="//in.getclicky.com/66454091ns.gif" /></p></noscript>
</body>
</html>

