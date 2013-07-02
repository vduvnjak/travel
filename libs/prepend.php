<?php
error_reporting (E_ALL ^ E_NOTICE);
define("black","\033[0;30m");
define("gray","\033[1;30m");
define("blue","\033[0;34m");
define("l_blue","\033[1;34m");
define("green","\033[0;32m");
define("l_green","\033[1;32m");
define("cyan","\033[0;36m");
define("l_cyan","\033[1;36m");
define("red","\033[0;31m");
define("l_red","1;31m");
define("purple","\033[0;35m");
define("l_purple","\033[1;35m");
define("brown","\033[0;33m");
define("yellow","\033[1;33m");
define("l_gray","\033[0;37m");
define("white","\033[1;37m");
define("original","\033[0m");

function e($msg,$level)
{
	if ($level == 5)		$out = red."*********************************\n*!* ".$msg."\n*********************************\n".original;
	elseif ($level > 3)	$out = red."-- ".$msg."\n".original;
	elseif ($level == 3)	$out = cyan."-- ".$msg."\n".original;
	elseif ($level == 2)	$out = white."-- ".$msg."\n".original;
	elseif ($level == 1) $out = green." ** ".$msg. "\n".original;
	elseif (defined($level)) $out =  constant($level) ."-  ".$msg."\n".original;
	else $out = " - ".$msg."\n".original;

	echo $out;
}
if(strpos($argv[1], "@")){
	//e("Checking if $argv[1] is running", "yellow");
	$command = "ps auxw | grep ". $argv[1] . " | grep -v grep";
	exec($command, $pids);
	if(count($pids) > 1){
		print_r($pids);
		e("We have " . count($pids) . " running for " . $argv[1] . " quiting " . $argv[0] . "...", 4);
		exit();
	}
}

if (defined('USE_DATABASE'))
{
	
}
if (defined('USE_DATABASE_LOCAL'))
{
	
}

if (defined('USE_WEBREQUEST'))
{
	require_once(dirname(__FILE__)."/class.WebRequest.php");
}
if (defined('USE_DOM'))
{
	require_once(dirname(__FILE__)."/class.DOM.php");
}
if (defined('USE_JSON'))
{
	require_once(dirname(__FILE__)."/class.JSON.php");
}


	
function get_text($text, $info)
{
	$out = "";

	$text_parts = split(" ", $text);
	foreach ($text_parts as $word)
	{
		if (strpos($word,"|"))
		{
			$choices = explode("|", $word);
			$out .= str_replace("_"," ",$choices[array_rand($choices)]." ");
		}
		else
		$out .= $word." ";
	}

	$out = str_replace("{first_name}", $info["first_name"], $out);
	$out = str_replace("{city}", $info["city"], $out);
	$out = str_replace("{college}", $info["college"], $out);

	$out = str_replace("{match}", $info["match"], $out);
	$out = str_replace("{remain_match}", $info["remain_match"], $out);
	$out = str_replace("{fortythree_things}", $info["fortythree_things"], $out);
	$out = str_replace("{about_me}", $info["about_me"], $out);
	$out = str_replace("{movie}", $info["movie"], $out);
	$out = str_replace("{music}", $info["music"], $out);
	return($out);
}

function xmlentities_decode($string) {
	return str_replace ( array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;', '&apos;' ), array ( '&', '"', "'", '<', '>', ' ' ),  $string );
}

function xmlentities($string) {
	return str_replace ( array ( '&', '"', "'", '<', '>', ' ' ), array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;', '&apos;' ), $string );
}


function writefile($file_name, $content)
{
	$fp = fopen('/var/www/html/'.$file_name, 'w');
	fwrite($fp, $content);
	fclose($fp);
}

function genRandomText($length=1)
{
	$i = 0;
	$x = '';
	while($i < $length){
		$x .= chr(rand(97,122));
		$i++;
	}
	return($x);
}
function echo_memory_usage()
{
        $mem_usage = memory_get_usage(true);
       
        if ($mem_usage < 1024)
            $mem_amount =  $mem_usage." bytes";
        elseif ($mem_usage < 1048576)
            $mem_amount = round($mem_usage/1024,2)." kilobytes";
        else
            $mem_amount = round($mem_usage/1048576,2)." megabytes";
            
            e($mem_amount, "yellow");
        

}
function GetBetween($content,$start,$end)
{
    $r = explode($start, $content);
    if (isset($r[1])){
        $r = explode($end, $r[1]);
        return $r[0];
    }
    return '';
}
?>