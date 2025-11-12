<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

class IBatisException extends Exception {
	public $problem;

	public function __construct($error_num, $value = array()) {
		$value_0 = $value_1 = null;
		
		if (is_array($value)) {
			$value_0 = isset($value[0]) ? $value[0] : null;
			$value_1 = isset($value[1]) ? $value[1] : null;
		}
		
		switch($error_num) {
			case 1: $this->problem = "Invalid query type '$value_0'. You must select one of the following types: [".strtolower(implode(", ", $value_1))."]"; break;
			case 2: $this->problem = ucfirst(strtolower($value_0))." query '$value_1' does not exist."; break;
			case 3: $this->problem = "Query '".$value."' can only have one parameter map or parameter class. You cannot have multiple parameter types."; break;
			case 4: $this->problem = "Query '".$value."' can only have one result map or result class. You cannot have multiple result types."; break;
		}
	}
}
?>
