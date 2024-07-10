<?php
class BeanException extends Exception {
	public $problem;

	public function __construct($error_num, $value) {
		switch($error_num) {
			case 1: $this->problem = "Bean name cannot be undefined: '{$value}'!"; break;
		}
	}
}
?>
