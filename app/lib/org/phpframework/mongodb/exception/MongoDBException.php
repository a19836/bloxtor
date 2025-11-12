<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

class MongoDBException extends Exception {
	public $problem;

	public function __construct($error_num, $e, $value = array()) {
		switch($error_num) {
			case 1: $this->problem = "Mongo DB connection fail: connect(".implode(", ", $value).")"; break;
			case 2: $this->problem = "ERROR selecting Mongo DB: " . $value; break;
			case 3: $this->problem = "ERROR executing command on Mongo DB: " . $value; break;
			case 4: $this->problem = "ERROR inserting in Mongo DB: " . var_export($value, 1); break;
			case 5: $this->problem = "ERROR updating in Mongo DB: " . var_export($value, 1); break;
			case 6: $this->problem = "ERROR deleting in Mongo DB: " . var_export($value, 1); break;
			case 7: $this->problem = "ERROR executing query on Mongo DB: " . var_export($value, 1); break;
		}
		
		if (!empty($e)) {
			if (is_string($e))
				parent::__construct($e, $error_num, null);
			else
				parent::__construct(!empty($e->problem) ? $e->problem : $e->getMessage(), $error_num, $e);
			
			//$this->problem .= "<br><br>EXCEPTION: " . $e;
		}
	}
}
?>
