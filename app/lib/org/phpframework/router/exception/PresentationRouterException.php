<?php
class PresentationRouterException extends Exception {
	public $problem;

	public function __construct($error_num, $value) {
		switch($error_num) {
			case 1: $this->problem = "'$value' variable cannot be empty"; break;
		}
	}
}
?>
