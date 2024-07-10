<?php
include get_lib("org.phpframework.bean.exception.BeanArgumentException");

class BeanArgument {
	const APP_KEY = "x1qMj9Bx3BxeefcvT4/FtHadhrNOM/J31akctINwSmsLWcn6YJ7g7fJrPjkwZtRO"; //DO NOT CHANGE THIS. THIS IS THEPHPMYFRAMEWORK PUBLIC KEY TO DECODE THE LICENCE
	
	public $index;
	public $value = false;
	public $reference = false;
	
	public function __construct($index, $value = false, $reference = false) {
		$this->index = $index;
		$this->value = $value;
		$this->reference = $reference;
		
		$this->isValid();
	}
	
	private function isValid() {
		if(!is_numeric($this->index)) {
			launch_exception(new BeanArgumentException(1, $this->index));
			return false;
		}
		elseif($this->index <= 0) {
			launch_exception(new BeanArgumentException(2, $this->index));
			return false;
		}
		elseif($this->value && $this->reference) {
			launch_exception(new BeanArgumentException(3, array($this->value, $this->reference)));
			return false;
		}
		return true;
	}
}
?>
