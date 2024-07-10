<?php
class ObjException extends Exception {
	public $problem;

	public function __construct($error_num, $value = array()) {
		$value_0 = $value_1 = null;
		
		if (is_array($value)) {
			$value_0 = isset($value[0]) ? $value[0] : null;
			$value_1 = isset($value[1]) ? $value[1] : null;
		}
		
		switch($error_num) {
			case 1: $this->problem = "ERROR trying to include object path: $value_0"; break;
			case 2: $this->problem = "ERROR trying to create object: $value_0"; break;
			case 3: $this->problem = "ERROR: '$value_0' doesn't implements the '$value_1' class!"; break;
		}
	}
}
?>
