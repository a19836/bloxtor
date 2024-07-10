<?php
class BeanArgumentException extends Exception {
	public $problem;

	public function __construct($error_num, $value) {
		switch($error_num) {
			case 1: $this->problem = "Bean argument should have a numeric index: '{$value}'!"; break;
			case 2: $this->problem = "Bean argument should have a numeric index equal or bigger than 1: '{$value}'!"; break;
			case 3: $this->problem = "Bean argument cannot have value and reference at the same time: value: '".$value[0]."', reference: '".$value[1]."'!"; break;
		}
	}
}
?>
