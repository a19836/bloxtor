<?php
class SQLMapQueryException extends Exception {
	public $problem;

	public function __construct($error_num, $value = array()) {
		$value_0 = $value_1 = null;
		
		if (is_array($value)) {
			$value_0 = isset($value[0]) ? $value[0] : null;
			$value_1 = isset($value[1]) ? $value[1] : null;
		}
		
		switch($error_num) {
			case 1: $this->problem = "ERROR: ParameterMap item doesn't have column name defined!"; break;
			case 2: $this->problem = "ERROR: ParameterMap item doesn't have property name defined!"; break;
			case 3: $this->problem = "ERROR: ParameterMap doesn't have any items!"; break;
			case 4: $this->problem = "ERROR: ParameterMap doesn't exists!"; break;
			case 6: $this->problem = "ERROR: ParameterMap class obj '".get_class($value_0)."' doesn't contain the '$value_1' method!"; break;
			case 7: $this->problem = "ERROR: Query can only have ParameterMap if the input value is an array!"; break;
			case 8: $this->problem = "ERROR: ParameterMap column '".$value."' doesn't exist in the input data! Please check your parameter map xml."; break;
		}
	}
}
?>
