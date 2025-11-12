<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.object.ObjType");
include_once get_lib("org.phpframework.object.exception.ObjTypeException");

class HashMap extends ObjType {
	
	public function __construct($arr_value = false) {
		if($arr_value !== false)
			$this->setData($arr_value);
	}
	
	public function getData() {return (array)$this->data;}
	public function setData($data) {
		if(is_array($data)) {
			$this->data = (array)$data;
			return true;
		}
		
		launch_exception(new ObjTypeException(get_class($this), $data));
		return false;
	}

	public function getValue($key = 0) {
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}
	public function setValue($key, $value) {
		$this->data[$key] = $value;
	}
	
	public function getAllValues() {
		return $this->getData();
	}
}
?>
