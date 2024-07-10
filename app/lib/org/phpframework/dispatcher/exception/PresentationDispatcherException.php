<?php
class PresentationDispatcherException extends Exception {
	public $problem;

	public function __construct($error_num, $value) {
		switch($error_num) {
			case 1: $this->problem = "File '{$value}' does not exist!"; break;
		}
	}
}
?>
