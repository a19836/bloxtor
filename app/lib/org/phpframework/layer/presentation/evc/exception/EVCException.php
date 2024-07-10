<?php
class EVCException extends Exception {
	public $problem;
	public $file_not_found = true;
	
	public function __construct($error_num, $value) {
		switch($error_num) {
			case 1: $this->problem = "EVC Controller '".$value."' does not exist."; break;
			case 2: $this->problem = "EVC Entity '".$value."' does not exist."; break;
			case 3: $this->problem = "EVC View '".$value."' does not exist."; break;
			case 4: $this->problem = "EVC Template '".$value."' does not exist."; break;
			case 5: $this->problem = "'$value' variable cannot be empty!"; break;
		}
	}
}
?>
