<?php
class Message {
	private $id;
	private $module_id;
	private $message;
	private $attributes = array();
	
	public function __construct() {
	
	}
	
	public function setId($id) {$this->id = $id;}
	public function getId() {return $this->id;}
	
	public function setModule($module_id) {$this->module_id = $module_id;}
	public function getModule() {return $this->module_id;}
	
	public function setMessage($message) {$this->message = $message;}
	public function getMessage() {return $this->message;}
	
	public function setAttributes($attributes) {$this->attributes = $attributes;}
	public function getAttributes() {return $this->attributes;}
	public function getAttribute($name) {return isset($this->attributes[$name]) ? $this->attributes[$name] : null;}
	
	public function checkAttributes($attributes) {
		if(is_array($attributes)) {
			foreach($attributes as $name => $value) {
				$v = isset($this->attributes[$name]) ? $this->attributes[$name] : null;
				
				if( (!isset($v) && strlen($value) > 0) || $v != $value)
					return false;
			}
		}
		return true;
	}
}
?>
