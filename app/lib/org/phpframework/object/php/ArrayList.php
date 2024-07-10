<?php
include_once get_lib("org.phpframework.object.ObjType");
include_once get_lib("org.phpframework.object.exception.ObjTypeException");

class ArrayList extends ObjType {
	
	public function __construct($arr_value = false) {
		if ($arr_value !== false)
			$this->setData($arr_value);
	}
	
	public function getData() {return (array)$this->data;}
	public function setData($data) {
		if (is_array($data)) {
			$this->data = (array)$data;
			$this->reset();
			return true;
		}
		
		launch_exception(new ObjTypeException(get_class($this), $data));
		return false;
	}
	
	public function getValue($index = 0) {
		return isset($this->data[$index]) ? $this->data[$index] : null;
	}
	public function setValue($value) {
		$this->data[] = $value;
	}
	
	public function each() {
		list($key, $value) = each($this->data);
		return $value;
	}
	
	public function reset() {
		reset($this->data);
	}
	
	public function getAllValues() {
		return $this->getData();
	}
	
}
?>
