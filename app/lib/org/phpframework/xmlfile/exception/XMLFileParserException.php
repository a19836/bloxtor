<?php
class XMLFileParserException extends Exception {
	public $problem;

	public function __construct($error_num, $value = array()) {
		switch($error_num) {
			case 1: $this->problem = "ERROR trying to include the '".$value."' file."; break;
		}
	}
}
?>
