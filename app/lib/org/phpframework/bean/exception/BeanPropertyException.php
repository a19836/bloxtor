<?php
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
