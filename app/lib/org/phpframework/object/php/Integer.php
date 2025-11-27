<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

include_once get_lib("org.phpframework.object.ObjType");
include_once get_lib("org.phpframework.object.exception.ObjTypeException");

class Integer extends ObjType {
	
	public function __construct($int_value = false) {
		if($int_value !== false)
			$this->setData($int_value);
	}
	
	public function getData() {return (int)$this->data;}
	public function setData($data) {
		if(is_numeric($data)) {
			$this->data = (int)$data;
			return true;
		}
		
		launch_exception(new ObjTypeException(get_class($this), $data));
		return false;
	}
}
?>
