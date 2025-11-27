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

include_once get_lib("org.phpframework.object.IObjType");
include_once get_lib("org.phpframework.object.ObjectHandler");

abstract class ObjType implements IObjType {
	protected $field;
	protected $data;
	
	public function getField() {return $this->field;}
	public function setField($field) {$this->field = $field;}
	
	public function getData() {return $this->data;} //simply returns the data.
	public function setData($data) {
		//receives the data and parses it, converting it to what the user wishes.
		$this->data = $data;
		return true;
	}
	
	public function setInstance($obj) {
		if(is_object($obj) && get_class($obj) && ObjectHandler::checkIfObjType($obj))
			return $this->setData($obj->getData());
		else 
			return $this->setData($obj);
	}
}
?>
