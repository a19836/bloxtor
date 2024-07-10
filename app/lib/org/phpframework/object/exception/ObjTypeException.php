<?php
class ObjTypeException extends Exception {
	public $problem;

	public function __construct($class_name, $value) {
		$value = is_object($value) ? get_class($value) : $value;
		$this->problem = "Wrong {$class_name} value: '{$value}' ";
	}
}
?>
