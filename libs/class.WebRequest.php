<?php

class WebRequest
{
	var $ch;	// Curl Handler
	var $mc;	// Memcache Object
	var $db;	// Database Object
	var $wl;	// WebLog Object
	var $proxy;	// Proxy Object

	var $opt;	// Class Options
	var $res;	// cURL Result

	var $id;	// Instance ID of this class
	var $req_id;// # of current request (starts at 1, incremented for each req)

	function __construct($set_opt = "")
	{
		$this->id = rand(1,9999999);
		$this->opt = array(
		"type"			=>	"other",	// "type" is the kind of script is running. ie yahoo_creator/myspace_creator
		"use_ips"		=>	FALSE,		// Use random ips from server.
		"use_proxy"		=>	FALSE,		// Use AOL proxy? Set to ip:port to use specific proxy regardless of status
		"use_slot"		=>	FALSE,
		"save_results"	=>	FALSE,		// Save Page Results for debugging
		"use_useragent"	=>	0,			// Use random user agent string
		"verbose"		=>	TRUE,		// Set cURL to be Verbose?
		"use_dom"		=>	TRUE,		// Should we run result through a dom parser?
		"cookie_dir"	=>	"/tmp/", // Cookies will be stored in this directory
		"timeout"		=> 60
		);

		if ($set_opt)
		{
			foreach($set_opt as $key => $val)
			$this->opt[$key] = $val;
		}
		
		$this->user_agent = $this->get_useragent();
		
		if(isset($this->opt["proxy"]))
		{
			$proxy = $this->opt["proxy"];
			//e("Using defined proxy: $proxy" , 3);
		}
		$this->init_curl();
	}

	/*
	*	cURL Initilization Methods
	*/

	function init_curl()
	{
		$this->ch = curl_init();
		$user_agent = $this->user_agent;
		$header[] = "Accept: application/x-ms-application, image/jpeg, application/xaml+xml, image/gif, image/jpeg, application/x-ms-xbap, application/x-shockwave-flash, */*";
		$header[] = "Accept-Language: en-US";
		$header[] = "User-Agent: " . $user_agent;
		$header[] = "Connection: Keep-Alive";
		$header[] = "Pragma:";
		$header[] = "Expect:";
		$header[] = "Content-Type:";

		$this->header = $header;

		$header2[] = "Accept: application/x-ms-application, image/jpeg, application/xaml+xml, image/gif, image/jpeg, application/x-ms-xbap, application/x-shockwave-flash, */*";
		$header2[] = "Accept-Language: en-US";
		$header2[] = "User-Agent: " . $user_agent;
		$header2[] = "Connection: Keep-Alive";
		$header2[] = "Content-Type: application/x-www-form-urlencoded";
		$header2[] = "Pragma:";
		$header2[] = "Expect:";

		$this->header_post = $header2;

		$header3[] = "Accept: application/json, text/javascript, */*";
		$header3[] = "Accept-Language: en-US";
		$header3[] = "User-Agent: " . $user_agent;
		$header3[] = "Content-Type: application/x-www-form-urlencoded";
		$header3[] = "x-requested-with: XMLHttpRequest";
		$header3[] = "Connection: Keep-Alive";
		$header3[] = "Pragma:";
		$header3[] = "Expect:";

		$this->header_json = $header3;

		$header4[] = "Accept: application/x-ms-application, image/jpeg, application/xaml+xml, image/gif, image/jpeg, application/x-ms-xbap, application/x-shockwave-flash, */*";
		$header4[] = "Accept-Language: en-US";
		$header4[] = "User-Agent: " . $user_agent;
		$header4[] = "Connection: Keep-Alive";
		$header4[] = "Content-Type: multipart/form-data";
		$header4[] = "Pragma:";
		$header4[] = "Expect:";

		$this->header_multipart = $header4;

		curl_setopt($this->ch, CURLOPT_USERAGENT, $user_agent);
		curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->opt["cookie_dir"]."/".$this->id);
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, $this->opt["cookie_dir"]."/".$this->id);
		//curl_setopt($this->ch, CURLOPT_ENCODING, 'gzip, deflate');
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_HEADER, 1);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->ch, CURLOPT_VERBOSE, $this->opt["verbose"]);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->opt["timeout"]);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		if ($this->opt["use_proxy"] && isset($proxy))
		curl_setopt($this->ch, CURLOPT_PROXY, $proxy);
		//print "Set Curl Proxy $proxy\n";
		//curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, "ocbeta:echo11ra");

		//if ($this->opt["use_ips"])
		//curl_setopt($this->ch, CURLOPT_INTERFACE, $this->get_ip());
	}

	function get_ip (){
		$ips = array();
		$temp = preg_split("/[\s]+/",shell_exec("/sbin/ifconfig | /bin/grep \"inet\""), -1, PREG_SPLIT_NO_EMPTY);
		foreach ($temp as $key => $val){
			if (preg_match('/addr:(.*)/', $val, $matches)){
				if ($matches[1] != "127.0.0.1" && $matches[1] != "" && $matches[1] != "10.4.232.66" && $matches[1] !="75.126.9.50" && $matches[1] !="10.17.232.130" && $matches[1] !="174.36.20.18")
				$ips[] = $matches[1];
			}
		}
		$ip = $ips[array_rand($ips)];
		//echo "\nUsing $ip\n";
		return $ip;
	}
	
	function get_new_ip($proxy)
	{
		if(isset($proxy)){
			$result = mysql_query("SELECT proxy_ip FROM main.proxy WHERE proxy_ip = '$proxy'") or die(mysql_error());
			if(mysql_numrows($result) < 1){
				e("Proxy: $proxy expired, getting new proxy", white);
				unset($proxy);
				while(!isset($proxy)){
					$result = mysql_query("SELECT proxy_ip FROM main.proxy ORDER BY RAND()") or die(mysql_error());
					if(mysql_numrows($result) < 1){
						e("Waiting...", "yellow");
						sleep(rand(1,10));
					}else{
						$row = mysql_result($result, 0);
						$proxy = $row;
						//e("New Proxy is $proxy", "white");
						return($proxy);
					}
				}
			}else{
				//e('Proxy: ' . $proxy . ' is still active', white);
				return($proxy);
			}
		}else{
			$result = mysql_query("SELECT proxy_ip FROM main.proxy ORDER BY RAND()");
			while(!isset($proxy)){
				$result = mysql_query("SELECT proxy_ip FROM main.proxy ORDER BY RAND()") or die(mysql_error());
				if(mysql_numrows($result) < 1){
					e("Waiting...", "yellow");
					sleep(rand(1,10));
				}else{
					$row = mysql_result($result, 0);
					$proxy = $row;
					e("New Proxy is $proxy", "white");
					return($proxy);
				}
			}
		}
	}

	function get_new_slot($slot)
	{
		if(isset($slot)){
			$res = mysql_query("SELECT proxy_ip FROM main.proxy_slots WHERE proxy_ip = '$slot'") or die(mysql_error());
			if(mysql_numrows($res) < 1){
				e("Slot: $slot expired, getting new slot", white);
				unset($slot);
			}else{
				return($slot);
			}
		}

		if(!isset($slot)){
			$res = mysql_query("SELECT proxy_ip FROM main.proxy_slots WHERE proxy_ip is not NULL and ".SITE." < ".MAX_USE." ORDER by RAND()") or die(mysql_error());
			if(mysql_numrows($res) < 1){
				e("No slots available", "white");
				while(!isset($slot)){
					$res = mysql_query("SELECT proxy_ip FROM main.proxy_slots WHERE proxy_ip is NULL ORDER by RAND()") or die(mysql_error());
					if(mysql_numrows($res) < MAX_USE *.5){
						e("No Slots Left, Requesting...", white);
						$res = mysql_query("INSERT into main.proxy_slots (".SITE.") VALUES (NULL)") or die(mysql_error());
						//$res = mysql_query("INSERT into main.proxy_slots (".SITE.") VALUES (NULL)") or die(mysql_error());
					}
					//$res = mysql_query("SELECT proxy_ip FROM main.proxy_slots WHERE proxy_ip is not NULL and ".SITE." < ".MAX_USE." ORDER by RAND()") or die(mysql_error());
					$res = mysql_query("SELECT proxy_ip FROM main.proxy_slots WHERE ".SITE." < ".MAX_USE." ORDER by RAND()") or die(mysql_error());
					if(mysql_numrows($res)  > 0){
						$row = mysql_result($res, 0);
						$slot = $row;
						if($slot!==''){
							e("Got a new slot: $slot","white");
						}else{
							unset($slot);
						}
					}else{
						e("Waiting...", white);
						sleep(rand(10,30));
						//$res = mysql_query("SELECT proxy_ip FROM main.proxy_slots WHERE proxy_ip is not NULL and ".SITE." < ".MAX_USE." ORDER by RAND()") or die(mysql_error());
						$res = mysql_query("SELECT proxy_ip FROM main.proxy_slots WHERE proxy_ip is NULL") or die(mysql_error());
					}
				}
				mysql_query("UPDATE main.proxy_slots set ".SITE."=".SITE."+1 where proxy_ip='$slot'") or die(mysql_error());
			}else{
				$row = mysql_result($res, 0);
				$slot = $row;
				e("Got a new slot: $slot","white");
				mysql_query("UPDATE main.proxy_slots set ".SITE."=".SITE."+1 where proxy_ip='$slot'") or die(mysql_error());
				return($slot);
			}
		}
	}

	function get_useragent()
	{
		$agents = array(
		"Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.5) Gecko/20091102 Firefox/3.5.5",
		"Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.1.8) Gecko/20100202 Firefox/3.5.8 (.NET CLR 3.5.30729)",
		"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2) Gecko/20100115 Firefox/3.6",
		"Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.7) Gecko/20091221 Firefox/3.5.7",
		"Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; GTB6.4; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)",
		"Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; WOW64; Trident/4.0; SLCC1; .NET CLR 2.0.50727; Media Center PC 5.0; Media Center PC 5.1; VER#69#80837674486745484887".rand(484856,984856)."; .NET CLR 3.5.30729; .NET CLR 3.0.30618)",
		"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; FBSMTWB; GTB6.4; InfoPath.1; .NET CLR 2.0.50727; OfficeLiveConnector.1.". rand(1,9)."; OfficeLivePatch.0.0; InfoPath.2)",
		"Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; WOW64; Trident/4.0)",
		"Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Win64; x64; Trident/4.0)",
		"Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50". rand(10,9999)."; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.3)",
		"Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; EmbeddedWB 14.52 from: http://www.bsalsa.com/ EmbeddedWB 14.52; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.3)"
		);

		if ($this->opt["use_useragent"]) // User-Specific UserAgent
		return($agents[$this->opt["use_useragent"]]);
		else
		return ($agents[array_rand($agents)]);
	}

	function get_proxy()
	{
		if ($this->opt["use_proxy"] == 2)
		{
			// Test Proxy List
			$proxies = array('66.78.23.12','66.78.1.201','66.78.1.202','66.78.1.203','66.78.1.204','66.78.1.205','66.78.1.206','66.78.4.129','66.78.4.130','66.78.4.131','66.78.4.132','66.78.4.133','66.78.4.134','66.78.5.129','66.78.5.130','66.78.5.131','66.78.5.132','66.78.5.133','66.78.5.134','66.78.6.129','66.78.6.130','66.78.6.131','66.78.6.132','66.78.6.133','66.78.6.134','66.78.7.129','66.78.7.130','66.78.7.131','66.78.7.132','66.78.7.133','66.78.7.134','66.78.8.129','66.78.8.130','66.78.8.131','66.78.8.132','66.78.8.133','66.78.8.134','66.78.9.81','66.78.9.82','66.78.9.83','66.78.9.84','66.78.9.85','66.78.9.86','66.78.10.73','66.78.10.74','66.78.10.75','66.78.10.76','66.78.10.77','66.78.10.78','66.78.11.105','66.78.11.106','66.78.11.107','66.78.11.108','66.78.11.109','66.78.11.110','66.78.12.17','66.78.12.18','66.78.12.19','66.78.12.20','66.78.12.21','66.78.12.22','66.78.13.9','66.78.13.10','66.78.13.11','66.78.13.12','66.78.13.13','66.78.13.14','66.78.14.1','66.78.14.2','66.78.14.3','66.78.14.4','66.78.14.5','66.78.14.6','66.78.15.9','66.78.15.10','66.78.15.11','66.78.15.12','66.78.15.13','66.78.15.14','66.78.16.73','66.78.16.74','66.78.16.75','66.78.16.76','66.78.16.77','66.78.16.78','66.78.17.73','66.78.17.74','66.78.17.75','66.78.17.76','66.78.17.77','66.78.17.78','66.78.18.81','66.78.18.82','66.78.18.83','66.78.18.84','66.78.18.85','66.78.18.86','66.78.19.81','66.78.19.82','66.78.19.83','66.78.19.84','66.78.19.85','66.78.19.86','66.78.20.9','66.78.20.10','66.78.20.11','66.78.20.12','66.78.20.13','66.78.20.14','66.78.21.17','66.78.21.18','66.78.21.19','66.78.21.20','66.78.21.21','66.78.21.22','66.78.22.9','66.78.22.10','66.78.22.11','66.78.22.12','66.78.22.13','66.78.22.14');
			$proxy = $proxies[array_rand($proxies)] . ':8282';
			return ($proxy);
		}
		else if (strlen($this->opt["use_proxy"]) > 1) // User-Specified Proxy
		return($this->opt["use_proxy"]);
		else
		{
			/* insert code here to grab list of active proxies from database/memcache */
			//$proxies = array("10.1.10.40:808");
			if (!is_object($this->proxy))
			{
				$this->proxy = new Proxy;
			}

			if (!is_array($this->selected_proxy))
			{
				if ($proxy_to_use = $this->proxy->get_proxy($this->opt["type"]))
				{
					$this->selected_proxy = $proxy_to_use;
					return($this->selected_proxy["proxy_ip"]);
				}
				else
				{
					echo "* Couldn't get a proxy.. waiting until there is one..\n";
					sleep(rand(1,10));
					return($this->get_proxy());
				}
			}
			else
			{
				return($this->selected_proxy["proxy_ip"]);
			}
		}
	}

	/*
	*	Set custom header method
	*/
	function setHeader($header)	{
		if(isset($header))	{
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);
		}
		else {
			echo "** Expecting header.\n";
		}
	}

	/*
	*	Web Request Methods
	*/

	function DoRequest($url, $post_options = "", $referer = "", $style="")
	{
		$this->req_id++;
		if ($this->opt["use_proxy"] ==1)
		{
			$this->proxy = $this->get_new_ip($this->proxy);
			curl_setopt($this->ch, CURLOPT_PROXY, $this->proxy);
			//e("doing request with: " .$this->proxy, 3);
		}
		elseif($this->opt["use_proxy"]==2)
		{
			$this->proxy=$this->get_proxy();
			curl_setopt($this->ch, CURLOPT_PROXY, $this->proxy);
		}
		if ($this->opt["use_slot"])
		{
			$this->slot = $this->get_new_slot($this->slot);
			//e("doing request with: " .$this->slot, 3);
			curl_setopt($this->ch, CURLOPT_PROXY, $this->slot);
		}

		curl_setopt($this->ch, CURLOPT_URL, $url);
		if (!$style)
		$style = "array";

		if ($style == "array" && is_array($post_options))
		{
			$post_string = "";
			foreach ($post_options as $key => $val)
			$post_string .= "&".urlencode($key)."=".urlencode($val);
			curl_setopt($this->ch, CURLOPT_POST, 1);
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->header_post);
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, substr($post_string,1));
		}
		else if ($style == "multipart" && is_array($post_options))
		{	// HTTP POST
			//echo "Doing POST\n";
			curl_setopt($this->ch, CURLOPT_POST, 1);
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_options);
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->header_multipart);
		}
		elseif($style == "string" && !is_array($post_options))
		{
			//echo "Doing POST\n";
			curl_setopt($this->ch, CURLOPT_POST, 1);
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_options);
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->header_post);
		}
		elseif($style == "json" && !is_array($post_options))
		{
			curl_setopt($this->ch, CURLOPT_POST, 1);
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->header_json);
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_options);
		}
		else{
			curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->header);
		}


		if ($referer)		curl_setopt($this->ch, CURLOPT_REFERER, $referer);
		else 				curl_setopt($this->ch, CURLOPT_REFERER, "");
		
		// added by vlatko in order to test
		//curl_setopt($this->ch, CURLOPT_HTTP_VERSION, 1.0);
		//curl_setopt($this->ch, CURLOPT_HEADER, TRUE);
		//curl_setopt($this->ch, CURLOPT_BINARYTRANSFER, TRUE);
		//curl_setopt($this->ch, CURLOPT_FAILONERROR, true);
		
		
		// added by vlatko
		//if ( $error = curl_error($this->ch) ) echo 'ERROR0: ',$error;
		
		$this->res = curl_exec($this->ch);
		
		// added by vlatko
		//print_r(curl_getinfo($this->ch));
		//if ( $error = curl_error($this->ch) ) echo 'ERROR1: ',$error;

		//TRAP ERROR RESET PROXY
		//**********************
		/*if(curl_error($this->ch)){
			if(stripos(curl_error($this->ch), "timed out") || stripos(curl_error($this->ch), "connect to host")) {
				while(1){
					if ($this->opt["use_proxy"]==1){
						e(curl_error($this->ch) . ' ' . $this->proxy . ' proxy is down, getting new one',4);
						unset($this->proxy);
						$this->proxy = $this->get_new_ip($this->proxy);
						curl_setopt($this->ch, CURLOPT_PROXY, $this->proxy);
					}
					if ($this->opt["use_slot"]||$this->opt["use_slots"]){
						e(curl_error($this->ch) . ' ' . $this->slot . ' slot is down, getting new one',4);
						mysql_query("DELETE FROM main.proxy_slots where proxy_ip='$this->slot'");
						unset($this->slot);
						$this->slot = $this->get_new_slot($this->slot);
						curl_setopt($this->ch, CURLOPT_PROXY, $this->slot);
					}
					$this->res = curl_exec($this->ch);
					if(!stripos(curl_error($this->ch), 'timed out')){
						break;
					}else{
						$try++;
						if($try > 5) break;
						sleep(1);
					}
				}
			}else{
				e(curl_error($this->ch), 4);
			}
		}
		*/

		if (!$this->check_result($this->res))
		{
			$this->res = curl_exec($this->ch);
			if (!$this->check_result($this->res))
			$this->res = curl_exec($this->ch);
			
			//echo "check_result not OK ";
			//print_r(curl_getinfo($this->ch));
		}

		if ($this->check_result($this->res))
		{
			$result = array(
			"req_id"		=>	$this->req_id,
			"url"			=>	$url,
			"post_options"	=>	$post_options,
			"referer"		=>	$referer,
			"res"			=>	$this->res,
			"ok"			=>	TRUE
			);
			//echo "check_result OK";
		}
		else
		$result = array("req_id" => $this->req_id,
		"ok" => FALSE);

		if ($this->opt["save_results"])
		$this->save_result($result);

		if ($this->opt["verbose"])
		echo "- Issued Request to: ".$url."\n";

		//if ($style == "json")
		//$this->setHeader(array("")); // remove the content type..
		
		// added by vlatko in order to test
		//print_r(curl_getinfo($this->ch));
		//if ( $error = curl_error($this->ch) ) echo 'ERROR2: ',$error;
		
		return ($result);
	}
	function DoRequestFromDom($dom, $style="")
	{
		if ($this->opt["verbose"])
		echo "Doing request from dom.\n";
		/*
		if ($urlencode_keys)
		{
		$f = array();
		foreach ($dom->form["f"] as $key => $value)
		$f[urlencode($key)] = urlencode($value);

		$result = $this->DoRequest($dom->form["action"], $f, $dom->form["referer"]);
		print_r($result);
		}
		else
		{*/


		$result = $this->DoRequest($dom->form["action"], $dom->form["f"], $dom->form["referer"], $style);
		//}
		return ($result);
	}
	function DoRequestImage($url, $referer = "")
	{
		e("Saving image: $url", 3);
		// When getting an image we want to make sure not to have anything extra like headers in the result..
		curl_setopt($this->ch, CURLOPT_HEADER, 0);
		curl_setopt($this->ch, CURLOPT_ENCODING, 'gzip, deflate');
		$result = $this->DoRequest($url,"",$referer);
		curl_setopt($this->ch, CURLOPT_HEADER, 1);
		return($result);
	}
	function check_result(&$res)
	{
		/* Check for Bad Gateway, MySpace Error.aspx, Yahoo 999, etc */
		/* Maybe add in a custom callback function for Myspace, Yahoo and Gmail in addition? */
		if (strpos($res, "errors.aol.com"))		return false;	// AOL returns this when connections time out
		if (strpos($res, "Gateway Timeout"))	return false;
		if (strlen($res) > 100)		return true;
	}

	function save_result(&$result)
	{
		if (!is_object($this->wl))
		{
			require_once("class.WebLog.php");
			$this->wl = new WebLog();
		}

		$this->wl->save_result($result);
	}
	function get_result($instance_id,$request_id)
	{
		// This is for when we want to test by pulling out saved pages
		if (!is_object($this->wl))
		{
			require_once("class.WebLog.php");
			$this->wl = new WebLog();
		}

		$data = $this->wl->get_result($instance_id,$request_id);
		return($data);
	}

	/*
	* Cookie Handling Methods
	*/


	function save_cookie($id, $type)
	{
		$cookiedata = $this->get_cookie_data();
		//mysql_query("INSERT INTO main.cookies (id, type, cookie_data) VALUES ('$id','$type','".mysql_real_escape_string($this->get_cookie_data())."') ON DUPLICATE KEY UPDATE cookie_data='".mysql_real_escape_string($this->get_cookie_data())."'");
		if($type =='aol')
		{
			mysql_query("INSERT INTO main.cookies (id, type, cookie_data) VALUES ('$id','$type','".mysql_real_escape_string($cookiedata)."') ON DUPLICATE KEY UPDATE cookie_data='".mysql_real_escape_string($cookiedata)."'") or die(mysql_error());
		}
		else
		{
			mysql_query("INSERT INTO $type.cookies (id, type, cookie_data) VALUES ('$id','$type','".mysql_real_escape_string($cookiedata)."') ON DUPLICATE KEY UPDATE cookie_data='".mysql_real_escape_string($cookiedata)."'") or die(mysql_error());
		}
	}

	function save_cookie_from_browser($domain = "")
	{
		// Pull cookies sent to us from browser and save in database in cURL format
		//  .meonyou.com    TRUE    /       FALSE   0       PHPSESSID       8p47p0c8g65kjtfnl7gjs4u5k2
		//   www.meonyou.com FALSE   /       FALSE   1267546450      track   a%3A4%3A%7Bs%3A12%3A%22affiliate_id%22%3Bs%3A1%3A%221%22%3Bs%3A10%3A

	}

	function load_cookie($id, $type)
	{
		if($id==''){
			e("You must pass the ID for cookie to load", 5);
			exit();
		}
		
		if($type=='aol')
			$result = mysql_query("SELECT cookie_data FROM main.cookies WHERE id='$id' AND type='$type'") or die("Web Request " . mysql_error());
		else
			$result = mysql_query("SELECT cookie_data FROM ".SITE.".cookies WHERE id='$id' AND type='$type'") or die("Web Request " . mysql_error());
			
		if (mysql_num_rows($result))
		{
			$cookie_data = mysql_result($result,0,0);
			curl_close($this->ch);
			if ($fp = fopen($this->opt["cookie_dir"]."/".$this->id, "w+"))
			{
				fwrite($fp, $cookie_data);
				fclose($fp);
			}
			$this->init_curl();
			echo "** Cookie loaded for $id.\n";
			return(1);
		}
		else
		{
			echo "** No cookie found for $id!\n";
			return(0);
		}
	}
	function load_cookie_from_old_format($id, $type)
	{
		if ($type == "myspace")
		{
			$result = mysql_query("SELECT cookie_data FROM main.cookie WHERE profile_id='$id'");
			if (mysql_num_rows($result))
			{
				$cookie_data = unserialize(mysql_result($result,0,0));
				curl_close($this->ch);
				if ($fp = fopen($this->opt["cookie_dir"]."/".$this->id, "w+"))
				{
					foreach ($cookie_data as $k => $v)
					fwrite($fp,".myspace.com\tTRUE\t/\tFALSE\t0\t".$k."\t".$v."\n");

					fclose($fp);
				}
				$this->init_curl();
			}
		}
	}

	function get_cookie_data()
	{
		curl_close($this->ch);
		$cookie = file_get_contents($this->opt["cookie_dir"]."/".$this->id);
		$this->init_curl();
		return($cookie);
	}
}

?>