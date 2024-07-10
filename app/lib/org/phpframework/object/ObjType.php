<?php
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
