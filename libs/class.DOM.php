<?php

class DOM
{
	var $doc;
	var $xpath;
	var $form;
	
	var $form_settings;
	
	function __construct($result)
	{
		$this->doc = new DOMDocument();
		$this->init_doc($result);
	}
	function init_doc(&$result)
	{
		@$this->doc->loadHTML($result["res"]);
		$this->xpath = new DOMXpath($this->doc);
	}
	function parse_dom($xpath_string)
	{
		$elements = $this->xpath->query($xpath_string);
		if ($elements->length > 0)
		{
			foreach ($elements as $e)
			{
				$this->form = array(
					"id"		=>	$e->getAttribute('id'),
					"src"	=>	$e->getAttribute('src'),
					"href"	=>	$e->getAttribute('href')
				);
				$this->form_settings["xpath"] = $xpath_string;
				
				
			}
		}
		else 
		{
			echo "* Error: Could not locate form ($xpath_string)\n";
		}
	}
	function get_images()
	{
		$elements = $this->xpath->query("//img");
		if ($elements->length > 0)
		{
			foreach ($elements as $e)
			{
				$images[] = array("src" => $e->getAttribute('src'), 
								  "height" => $e->getAttribute('height'),
								  "width" => $e->getAttribute('width'));
			}
		}
		return($images);
	}
	function parse_links($xpath_string)
	{
		$elements = $this->xpath->query($xpath_string);
		if ($elements->length > 0)
		{
			foreach ($elements as $e)
			{
				$this->links[] = array(
					"id"	=>	$e->getAttribute('id'),
					"href"	=>	$e->getAttribute('href'));
				$this->link_settings["xpath"] = $xpath_string;
			}
		}
	}
	
	function parse_form($xpath_string, $verbose=0)
	{	
		$this->form["vars"] = array();
		$elements = $this->xpath->query($xpath_string);
		if ($elements->length > 0)
		{
			foreach ($elements as $e)
			{
				$this->form = array(
					"name"		=>	$e->getAttribute('name'),
					"action"	=>	$e->getAttribute('action'),
					"method"	=>	strtolower($e->getAttribute('method'))
				);
				
				$this->form_settings["xpath"] = $xpath_string;
				$this->form["vars"] = array();
				$inputs = $e->getElementsByTagName('input');
				foreach ($inputs as $input)
				{
					
					if (!$input->getAttribute('name'))
						$this->form["f"][$input->getAttribute('id')] = $input->getAttribute('value'); // Try to fall back on ID if Name doesnt seem to be defined
					else
						$this->form["f"][$input->getAttribute('name')] = $input->getAttribute('value');
						
					if ($input->getAttribute('size')){
						//e("Field: ".$input->getAttribute('name')." size=".$input->getAttribute('size'),3);
						$size = $input->getAttribute('size');
						if(array_key_exists($size, $this->form["vars"])){
							$this->form["vars"][$input->getAttribute('size')][1] = $input->getAttribute('name');
						}else{
								$this->form["vars"][$input->getAttribute('size')][0] = $input->getAttribute('name');
						}
					}
				}

				$inputs = $e->getElementsByTagName('select');
				foreach ($inputs as $input)
				{
					$value = "";
					$options = $input->getElementsByTagName('option');
					foreach ($options as $option)
					{
						//echo "Option: ".$option->getAttribute('value');
						if ($option->hasAttribute('selected'))
							$value = $option->getAttribute('value');
					}
					$this->form["f"][$input->getAttribute('name')] = $value; //$input->getAttribute('value');
				}
					
				$inputs = $e->getElementsByTagName('textarea');
				foreach ($inputs as $input)
				{
					$this->form["f"][$input->getAttribute('name')] = $input->nodeValue;
					if ($input->getAttribute('rows')){
						//e("Field: ".$input->getAttribute('name')." size=".$input->getAttribute('rows'),3);
						$size = $input->getAttribute('rows');
						if(array_key_exists($size, $this->form["vars"])){
							$this->form["vars"][$input->getAttribute('rows')][1] = $input->getAttribute('name');
						}else{
								$this->form["vars"][$input->getAttribute('rows')][0] = $input->getAttribute('name');
						}
					}
				}
			}
			return(1);
		}
		else 
		{
			if($verbose==1)
			echo "* Error: Could not locate form ($xpath_string)\n";
		}
	}

	function set ($values, $force=0)
	{
		/*
		 force values..
		  0 = throw an error if variable doesnt exist in dom and we are trying to set it
		  1 = force setting the variable even if it wasnt picked up by dom
		  -2 = delete variable
		*/
		
		foreach ($values as $key => $val)
			$this->form_settings['f'][$key] = $val;
		
		if (is_array($values) && is_array($this->form))
		{
			foreach ($values as $key => $val)
			{
				// Try and find the key in the form fields by the DOM
				$_set=0; // We will throw an error if the variable is never set..
				if (isset($this->form["f"][$key]) || $force == 1)	// Exact Match or Forced to set exact
				{
					$this->form["f"][$key] = $val;
					$_set=1;
				}
				else
				{
					foreach($this->form["f"] as $_k => $_v)
					{
						if (strpos($_k, $key) !== FALSE)
						{
							if ($force == -2)
								unset($this->form["f"][$_k]); // delete mode
							else
								$this->form["f"][$_k] = $val;
								
							$_set=1;
						}
					}
				}
				if ($force == 2 && $_set == 0)
					$this->form["f"][$key] = $val;
				//else if ($_set==0)
					//echo "* ERROR: Did not set $key $val\n";
			}
		}
	}
	function reset_values ()
	{
		// re-sets values, not reset!
		$this->parse_form($this->form_settings['xpath']);
		$this->set($this->form_settings["f"], 2);
	}
}

?>