<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

class SQLException extends Exception {
	public $problem;

	public function __construct($error_num, $e, $value = array()) {
		$value_0 = $value_1 = null;
		
		if (is_array($value)) {
			$value_0 = isset($value[0]) ? $value[0] : null;
			$value_1 = isset($value[1]) ? $value[1] : null;
		}
		
		switch($error_num) {
			case 1: 
				$options = $value;
				$opt_str = "";
				if (is_array($options))
					foreach ($options as $k => $v) {
						$opt_str .= ($opt_str ? "; " : "") . "$k=";
						
						if (strtolower($k) == "password")
							$opt_str .= strlen($v) ? "***" : "";
						else
							$opt_str .= is_array($v) ? "[" . implode(", ", $v) . "]" : (is_object($v) ? "Object(" . get_class($v) . ")" : $v);
					}
				
				$this->problem = "DB connection fail with options: $opt_str"; 
				break;
			case 2: $this->problem = "ERROR selecting DB: " . $value_0; break;
			case 3: $this->problem = "ERROR cosing DB connection."; break;
			case 4: $this->problem = "ERROR returning DB errno." . $value_0; break;
			case 5: $this->problem = "ERROR returning DB error." . $value_0; break;
			case 6: $this->problem = "ERROR executing query: " . $value_0; break;
			case 7: $this->problem = "ERROR to free result: " . $value_0; break;
			case 8: $this->problem = "ERROR fetching result to array. Result:" . $value_0 . ". Array type:" . $value_1; break;
			case 9: $this->problem = "ERROR fetching result to row. Result:" . $value_0; break;
			case 10: $this->problem = "ERROR fetching result to assoc array. Result:" . $value_0; break;
			case 11: $this->problem = "ERROR fetching result to object. Result:" . $value_0; break;
			case 12: $this->problem = "ERROR fetching field. Result:" . $value_0 . ". Offset:" . $value_1; break;
			case 13: $this->problem = "ERROR getting num rows. Result:" . $value_0; break;
			case 14: $this->problem = "ERROR getting num fields. Result:" . $value_0; break;
			case 15: $this->problem = "ERROR in DB->getData(). SQL:" . $value_0; break;
			case 16: $this->problem = "ERROR in DB->setData(sql). SQL:" . $value_0; break;
			case 17: $this->problem = "ERROR: Query result null. SQL:" . $value_0; break;
			case 18: $this->problem = "ERROR: DB Driver incorrect options. Host, username and db_name are mandatory! Your options were:[" . implode("', '", $value) . "]"; break;
			case 19: $this->problem = "ERROR: DB name is undefined in query: " . $value; break;
			case 20: $this->problem = "ERROR in DB->setConnectionEncoding(" . $value . ")"; break;
			case 21: $this->problem = "ERROR checking getData resourcing for SQL:" . $value_0; break;
		}
		
		if (!empty($e)) {
			if (is_string($e)) {
				$this->problem .= "\n\n\n\n*** NATIVE ERROR ***\n\n$e";
				parent::__construct($e, $error_num, null);
			}
			else {
				if (!empty($e->problem))
					$this->problem = $e->problem . PHP_EOL . $this->problem;
				
				parent::__construct($e->getMessage(), $error_num, $e);
			}
			//$this->problem .= "<br><br>EXCEPTION: " . $e;
		}
	}
}
?>
