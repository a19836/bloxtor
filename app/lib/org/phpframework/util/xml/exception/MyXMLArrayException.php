<?php
class MyXMLArrayException extends Exception {
	public $problem;

	public function __construct($error_num, $value) {
		switch($error_num) {
			case 1: $this->problem = "ERROR: Node conditions can only contain attribute or numeric indexes. Sub-nodes conditions are not supported! Please remove the '{$value}' sub-node condition please."; break;
			case 2: $this->problem = "ERROR: Node conditions contains a unknown character. Please check the '{$value}' character, please."; break;
		}
	}
}
?>
