<?php
include_once get_lib("org.phpframework.object.ObjType");

class MyString extends ObjType {
	
	public function __construct($str_value = false) {
		if($str_value !== false)
			$this->setData($str_value);
	}
	
	public function getData() {return (string)$this->data;}
	public function setData($data) {
		$this->data = (string)$data;
		return true;
	}
}
?>
