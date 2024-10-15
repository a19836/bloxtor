<?php
class ObjTypeException extends Exception {
	public $problem;

	public function __construct($class_name, $value) {
		$value = is_object($value) ? get_class($value) : json_encode($value); //json_encode is very important bc if the value is an array we must convert it to a string
		$this->problem = "Wrong {$class_name} value: {$value} ";
	}
}
?>
