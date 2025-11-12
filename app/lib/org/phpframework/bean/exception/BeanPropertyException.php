<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

class BeanPropertyException extends Exception {
	public $problem;

	public function __construct($error_num, $value) {
		$value_0 = $value_1 = null;
		
		if (is_array($value)) {
			$value_0 = isset($value[0]) ? $value[0] : null;
			$value_1 = isset($value[1]) ? $value[1] : null;
		}
		
		switch($error_num) {
			case 1: $this->problem = "Bean property name cannot be undefined: '{$value}'!"; break;
			case 2: $this->problem = "Bean property cannot have value and reference at the same time: value: '". $value_0 ."', reference: '". $value_1 ."'!"; break;
		}
	}
}
?>
