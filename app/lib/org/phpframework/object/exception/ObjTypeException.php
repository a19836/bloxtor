<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

class ObjTypeException extends Exception {
	public $problem;

	public function __construct($class_name, $value) {
		$value = is_object($value) ? get_class($value) : json_encode($value); //json_encode is very important bc if the value is an array we must convert it to a string
		$this->problem = "Wrong {$class_name} value: {$value} ";
	}
}
?>
