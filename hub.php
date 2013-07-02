<?php
header('Content-Type:text/plain');
define('USE_WEBREQUEST', true);
require_once("libs/prepend.php");
require_once("libs/simple_html_dom.php");
error_reporting(E_ALL);

$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime; 

if(isset($_REQUEST['startLocation']))
{
	$startLocation_f = substr($_REQUEST['startLocation'],0,3);
}
else $startLocation_f = 'SFO';

if(isset($_REQUEST['startDate']) && isset($_REQUEST['budget']))
{
	$startDate = $_REQUEST['startDate'];
	$startDate = date('Y-m-d', strtotime($startDate));	
	$budget = $_REQUEST['budget'];
	$numberNights = $_REQUEST['numberNights'];
	$numberPeople = $_REQUEST['numberPeople'];
	
	//$endDate = date('Y-m-d', strtotime('last day next month', strtotime($startDate)));	
}
else
{
	$today = date('Y-m-d');
	$budget = "1000";
	$numberNights = 2;
	$numberPeople = 1;
	$startDate = $today;
}
$endDate = date ( 'Y-m-d' , strtotime ( '+'.$numberNights.' day' , strtotime ( $startDate )) );

$ticketBudgetPerPerson = round(($budget-$numberNights*200)/$numberPeople);


$web = new WebRequest(array("type" => "other", "use_ips" => 1, "verbose" => 0));
curl_setopt($web->ch, CURLOPT_HEADER, 0);
//http://www.kayak.com/h/explore/api?airport=LAX&hotel=n&v=1&depart=20100801&return=20100831
$urlstartDate = date('Ymd', strtotime($startDate));	
//$urlendDate = date('Ymd', strtotime($endDate));
// we use full 30 day window for kayak api, so we get more results
$urlendDate = date('Ymd', (strtotime($startDate)+30*86400));
$url = 'http://www.kayak.com/h/explore/api?airport=' . $startLocation_f .'&hotel=n&v=1&depart=' . $urlstartDate . '&return=' . $urlendDate;
$result = $web->DoRequest($url);

//echo "url: $url result: $result";
//print_r($result['res']);
$json_res = json_decode($result['res'], 1);
//echo "json_res $json_res";
$trips = array();
foreach($json_res['destinations'] as $destination)
{
	if($destination['flight'] < $ticketBudgetPerPerson)
	{
		
		// because this page is returning huge amount of results, we will filter some cities in US:
		if ($destination['country']['shortName'] == 'US')
		{
			// allow just some cities
			$allowed_cities = array("Chicago", "Minneapolis", "Kansas City", "Omaha", "Saint Louis", "Albany", "Atlantic City", "Baltimore", "Boston", "Buffalo", "Cape Cod",
			"Cleveland", "Columbus", "Detroit", "Hartford", "Indianapolis", "Long Island", "Manchester", "Martha's Vineyard", "Nantucket", "New Haven", "New York",
			"Newport", "Philadelphia", "Pittsburgh", "Providence", "Springfield", "Birmingham", "Chattanooga", "Fort Lauderdale", "Fort Myers", "Jackson", "Jacksonville",
			"Key West", "Louisville", "Marco Island", "Memphis", "Miami", "Naples", "Nashville", "Norfolk", "Orlando", "Panama City Beach", "Raleigh",
			"Tampa Bay", "Washington DC", "Washington, DC", "West Palm Beach", "Amarillo", "Austin", "Corpus Christi", "Dallas", "Fort Worth", "Harlingen", "Houston", "Little Rock",
			"Lubbock", "Midland", "Odessa", "New Orleans", "Oklahoma City", "San Antonio", "Tulsa", "Albuquerque", "Aspen", "Berkeley", "Boulder",
			"Breckenridge", "Burbank", "Carmel", "Colorado Springs", "Denver", "El Paso", "Flagstaff", "La Jolla", "Las Vegas", "Los Angeles", "Monterey",
			"Napa Valley", "Oakland", "Ontario", "Orange County", "Phoenix", "Reno", "Tahoe", "Sacramento", "Salt Lake City", "San Diego", "San Francisco",
			"San Jose", "Santa Fe", "Scottsdale", "Sedona", "Tucson", "Vail", "Yosemite", "Portland", "Seattle", "Spokane", "Boise"	
			);
			if (in_array($destination['city']['name'], $allowed_cities))
			{
				$trips['destinations'][] = $destination;
			}
			
		}
		else $trips['destinations'][] = $destination;	
		
	}
}

echo json_encode($trips);
exit();

function runTime() 
{
	Global $starttime;
	$mtime = microtime();
	$mtime = explode(" ",$mtime);
	$mtime = $mtime[1] + $mtime[0];
	$endtime = $mtime;
	$totaltime = ($endtime - $starttime);
	$totaltime = round($totaltime, 2);
	echo 'Script ran for '. $totaltime . ' seconds';
}

function cExit()
{
	runTime();
	exit();
}