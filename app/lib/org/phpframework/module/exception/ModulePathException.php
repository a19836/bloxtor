<?php
class ModulePathException extends Exception {
	public $problem;

	public function __construct($error_num, $value) {
		$value_0 = $value_1 = null;
		
		if (is_array($value)) {
			$value_0 = isset($value[0]) ? $value[0] : null;
			$value_1 = isset($value[1]) ? $value[1] : null;
		}
		
		switch($error_num) {
			case 1: $this->problem = "Module in the $value_0 is not register or is empty: '$value_1'!"; break;
			case 2: $this->problem = "$value_0 doesn't exists: '$value_1'!"; break;
			case 3: $this->problem = "Module id cannot be undefined in class: $value!"; break;
		}
	}
}
?>
