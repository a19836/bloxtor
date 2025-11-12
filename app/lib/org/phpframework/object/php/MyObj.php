<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.object.ObjType");

class MyObj extends ObjType {
	
	public function __construct() {
		$this->field = false;
	}
	
	public function setData($data) {
		$status = parent::setData($data);
		
		if (is_array($this->data)) {
			foreach($this->data as $key => $value) {
				$func_name = "set" . str_replace(" ", "", ucwords(strtolower( str_replace(array("_", "-"), " ", $key) )));
				
				if (method_exists($this, $func_name) && $func_name != "setData" && $func_name != "setField")
					eval("\$this->{$func_name}(\$value);");
				else
					$status = false;
			}
		}
		
		return $status;
	}
	
	public function getData() {
		$chars = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "Y", "X", "W", "Z");
		
		$class_methods = get_class_methods($this);
		foreach($class_methods as $method_name) {
			if (substr($method_name, 0, 3) == "get" && $method_name != "getData" && $method_name != "getField") {
				$func_name = substr($method_name, 3);
				
				$first_char = substr($func_name, 0, 1);
				if (in_array($first_char, $chars)) {
					$attr_name = strtolower($first_char);
					for($i = 1; $i < strlen($func_name); $i++) {
						$char = $func_name[$i];
						$attr_name .= (in_array($char, $chars) ? "_" : "").strtolower($char);
					}
					
					eval("\$this->data[\"{$attr_name}\"] = \$this->{$method_name}();");
				}
			}
		}
		
		return parent::getData();
	}
}
?>
