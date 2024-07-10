<?php
class BeanPropertyException extends Exception {
	public $problem;

	public function __construct($error_num, $value) {
		switch($error_num) {
			case 1: $this->problem = "Bean property name cannot be undefined: '{$value}'!"; break;
			case 2: $this->problem = "Bean property cannot have value and reference at the same time: value: '".(isset($value[0]) ? $value[0] : "")."', reference: '".$value[1]."'!"; break;
		}
	}
}
?>
