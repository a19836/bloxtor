<?php
class LayerException extends Exception {
	public $problem;

	public function __construct($error_num, $value = "") {
		switch($error_num) {
			case 1: $this->problem = "Broker Layer '{$value}' does not exist!"; break;
			case 2: $this->problem = "'{$value}' variable cannot be empty!"; break;
		}
	}
}
?>
