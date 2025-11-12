<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

class BusinessLogicLayerException extends Exception {
	public $problem;

	public function __construct($error_num, $value) {
		$value_0 = $value_1 = $value_2 = null;
		
		if (is_array($value)) {
			$value_0 = isset($value[0]) ? $value[0] : null;
			$value_1 = isset($value[1]) ? $value[1] : null;
			$value_2 = isset($value[2]) ? $value[2] : null;
		}
		
		switch($error_num) {
			case 1: $this->problem = "Business Logic service function is not register: '{$value}'!"; break;
			case 2: $this->problem = "Business Logic service function doesn't exists: '{$value}'!"; break;
			case 3: $this->problem = "Business Logic service constructor doesn't exists: '{$value}'!"; break;
			case 4: $this->problem = "Business Logic services file doesn't exists: '{$value}'!"; break;
			case 5: $this->problem = "Business Logic service input annotation error in module '$value_0' for '$value_1' function: " . self::prepareInputAnnotationsErrors($value_2); break;
			case 6: $this->problem = "Business Logic service output annotation error in module '$value_0' for '$value_1' function: " . self::prepareOutputAnnotationsErrors($value_2); break;
			case 7: $this->problem = "'$value_0' class cannot be repeated in Business Logic Layer. You cannot have 2 classes with the same name. Please add a namespace to one of them. Error in file '" . $value_1 . "'"; break;
			case 8: $this->problem = "'$value' variable must be an array"; break;
			case 9: $this->problem = "'$value' variable cannot be empty"; break;
		}
	}
	
	private static function prepareInputAnnotationsErrors($errors) {
		$err = "";
		
		if (is_array($errors)) {
			foreach ($errors as $param_name => $param_errors) {
				$param_name = is_numeric($param_name) ? "Param $param_name" : ucfirst($param_name) . " Param"; 
				$err .= "<br>- $param_name:<br>&nbsp;&nbsp;&nbsp;+ " . implode("<br>&nbsp;&nbsp;&nbsp;+ ", $param_errors);
			}
		}
		
		return $err;
	}
	
	private static function prepareOutputAnnotationsErrors($errors) {
		return "<br>- Return value:<br>&nbsp;&nbsp;&nbsp;+ " . implode("<br>&nbsp;&nbsp;&nbsp;+ ", $errors);
	}
}
?>
