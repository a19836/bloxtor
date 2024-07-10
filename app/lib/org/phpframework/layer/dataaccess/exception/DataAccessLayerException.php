<?php
class DataAccessLayerException extends Exception {
	public $problem;

	public function __construct($error_num, $value) {
		switch($error_num) {
			case 1: $this->problem = "Data access service folder doesn't exists: '{$value}'!"; break;
			case 2: $this->problem = "Data access service doesn't exists: '{$value}'!"; break;
			case 3: $this->problem = "Data access services file doesn't exists: '{$value}'!"; break;
			case 4: $this->problem = "'$value' variable cannot be empty!"; break;
		}
	}
}
?>
