<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

class PHPTemplateLanguageException extends Exception {
	public $problem;

	public function __construct($error_num, $value = array(), $e = null) {
		$value_0 = $value_1 = $value_1_0 = $value_2 = null;
		
		if (is_array($value)) {
			$value_0 = isset($value[0]) ? $value[0] : null;
			$value_1 = isset($value[1]) ? $value[1] : null;
			$value_1_0 = isset($value[1][0]) ? $value[1][0] : null;
			$value_2 = isset($value[2]) ? $value[2] : null;
		}
		
		switch($error_num) {
			case 1: $this->problem = "ERROR: Invalid name for tag: " . $value; break;
			case 2: $this->problem = "ERROR: $value_2 Argument must be string instead of: $value_1. Error in in the following tag: $value_0"; break;
			case 3: $this->problem = "ERROR: $value_2 Argument must be a variable, instead of: '$value_1_0'. Error in in the following tag: $value_0"; break;
			case 4: $this->problem = "ERROR: $value_2 Argument must be string or variable instead of: $value_1. Error in in the following tag: $value_0"; break;
			case 5: $this->problem = "ERROR: $value_2 Argument must be a variable or function, instead of: '$value_1_0'. Error in in the following tag: $value_0"; break;
			case 6: $this->problem = "ERROR: Incorrect number of arguments. Minimum number is 2 and maximum is 3. Error in the following tag: " . $value; break;
			case 7: $this->problem = "ERROR: The following php code couldn't be executed with eval: \n<pre>$value</pre>"; break;
		}
		
		if (!empty($e)) {
			if (is_string($e)) {
				$this->problem .= "\n\nNATIVE ERROR\n$e";
				parent::__construct($e, $error_num, null);
			}
			else
				parent::__construct(!empty($e->problem) ? $e->problem : $e->getMessage(), $error_num, $e);
			
			//$this->problem .= "<br><br>EXCEPTION: " . $e;
		}
	}
}
?>
