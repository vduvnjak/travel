<?php
define('USE_DATABASE', true);
define('USE_WEBREQUEST', true);
define('USE_DOM', true);
define('SITE', "main");
define('MAX_USE', 50);
require_once('libs/prepend.php');
require_once("libs/simple_html_dom.php");
$ip = $_SERVER["REMOTE_ADDR"];
$uri = $_SERVER['REQUEST_URI'];
$uri = preg_replace('%/%', '', $uri);

include("libs/geoip.inc");
include("libs/geoipcity.inc");

$gi = geoip_open("libs/GeoIPCity.dat",GEOIP_STANDARD);   
$record = geoip_record_by_addr($gi, $ip);    

$lat = $record->latitude;
$long = $record->longitude;
$country = $record->country_code;
$city = $record->city;

geoip_close($gi);

error_reporting(E_ALL);
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime; 

if(isset($_REQUEST['startDate']) && isset($_REQUEST['budget']))
{
	$startDate = $_REQUEST['startDate'];
	$startDate = date('Y-m-d', strtotime($startDate));	
	$budget = $_REQUEST['budget'];
	$startLocation = $_REQUEST['startLocation'];
	$numberNights = $_REQUEST['numberNights'];
	$numberPeople = $_REQUEST['numberPeople'];
	
	//$endDate = date('Y-m-d', strtotime('last day next month', strtotime($startDate)));
}
else
{
	$today = date('Y-m-d');
	//$endDate = date('Y-m-d', strtotime('last day next month'));
	//$startDate = date('Y-m-d', strtotime('-4 weeks', strtotime($endDate)));
	$budget = "1000";
	// we wil try to find start location based on IP
	$startLocation = 'SFO';
	$numberNights = 2;
	$numberPeople = 1;
	$startDate = $today;
}
//$endDate = date ( 'Y-m-d' , strtotime ( '+'.$numberNights.' day' , strtotime ( $startDate )) );

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Fruitfly</title>
<link type="text/css" href="css/jquery-ui-1.8.1.custom.css" rel="Stylesheet" />
<link type="text/css" href="css/fruitfly.css" rel="Stylesheet" />		
<script type="text/javascript" src="js/jquery-1.4.4.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.1.custom.min.js"></script>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?v=3.6&sensor=false"></script>
<script type="text/javascript" src="js/fruitfly.js"></script>
<script type="text/javascript" src="js/infobox.js" ></script>
</head>

<body onload="initialize();" style="padding:0px; left: 0px">

<div class="trip_main" id="trip_main">
<form name="travelForm" id="travelForm" method="post" action="">
<table width=950 cellspacing=0 cellpadding=0 border=0>
<tr><td width=200><b>Leaving From: </b></td>
<td width=105><b>Departure Date: </b></td>
<td width=55><b>Nights: </b></td>
<td width=55><b>People: </b></td>
<td width=235><b>Total Budget ($):</b></td>
<td width=150></td>
<td width=150 rowspan=2 valign=bottom>
<span id='fb-root'></span><script src='http://connect.facebook.net/en_US/all.js#appId=227796143922773&amp;xfbml=1'></script>
<fb:like href='www.fruitfly.me' send='true' layout='button_count' width='150' show_faces='false' font=''></fb:like> </td>
<!--<td width=164 rowspan=2><img border=0 src='css/images/legend.png'></td>-->
</tr>
<tr><td><span class="ui-widget"><input type="text" id="startLocation" name="startLocation" value="<? echo $startLocation; ?>" size=24></span></td>
<td><input type="text" id="startDate" name="startDate" value="<? echo $startDate; ?>" size=10></td>
<td><input type="text" id="numberNights" name="numberNights" value="<? echo $numberNights; ?>" size=2></td>
<td><input type="text" id="numberPeople" name="numberPeople" value="<? echo $numberPeople; ?>" size=2></td>
<td><span id="slider"></span>&nbsp;&nbsp;<input type="text" id="budget" name="budget" value="<? echo $budget; ?>" size=4></td>
<td><input type="submit" name="Submit" id="Submit" value="Submit">&nbsp;<input type="submit" name="Clear" id="Clear" value="Clear"></td></tr>
</table>

</form>

</div>

<br/>
<div id="map_canvas"></div>
<?php include("footer.php"); ?>
<script src="//static.getclicky.com/js" type="text/javascript"></script>
<script type="text/javascript">try{ clicky.init(66454091); }catch(e){}</script>
<noscript><p><img alt="Clicky" width="1" height="1" src="//in.getclicky.com/66454091ns.gif" /></p></noscript>
</body>
</html>

