<?php
include_once get_lib("org.phpframework.object.ObjType");
include_once get_lib("org.phpframework.object.exception.ObjTypeException");

class Float extends ObjType  {
	
	public function __construct($int_value = false) {
		if ($int_value !== false)
			$this->setData($int_value);
	}
	
	public function getData() {return (int)$this->data;}
	public function setData($data) {
		if (preg_match("/^(([\-]?)([0-9]*)([\.]?)([0-9]{0,7}))$/i", $data)) {
			$this->data = $data;
			return true;
		}
		
		launch_exception(new ObjTypeException(get_class($this), $data));
		return false;
	}
}
?>
