<?php
include get_lib("org.phpframework.bean.exception.BeanPropertyException");

class BeanProperty {
	const APP_KEY = "2wIDAQAB"; //DO NOT CHANGE THIS. THIS IS THEPHPMYFRAMEWORK PUBLIC KEY TO DECODE THE LICENCE
	
	public $name;
	public $value = false;
	public $reference = false;
	
	public function __construct($name, $value = false, $reference = false) {
		$this->name = trim($name);
		$this->value = $value;
		$this->reference = $reference;
		
		$this->isValid();
	}
	
	private function isValid() {
		if(empty($this->name)) {
			launch_exception(new BeanPropertyException(1, $this->name));
			return false;
		}
		elseif($this->value && $this->reference) {
			launch_exception(new BeanPropertyException(2, array($this->value, $this->reference)));
			return false;
		}
		return true;
	}
}
?>
