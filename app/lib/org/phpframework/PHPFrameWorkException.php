<?php
class PHPFrameWorkException extends Exception {
	public $problem;
	
	public function __construct($error_num, $value) {
		switch($error_num) {
			case 1: $this->problem = "PHPFrameWork obj '{$value}' does not exist!"; break;
			case 2: $this->problem = "Bean obj '{$value}' does not exist!"; break;
		}
	}
}
?>
