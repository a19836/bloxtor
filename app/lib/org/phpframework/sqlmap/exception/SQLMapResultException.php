<?php
class SQLMapResultException extends Exception {
	public $problem;

	public function __construct($error_num, $value = array()) {
		$value_0 = $value_1 = null;
		
		if (is_array($value)) {
			$value_0 = isset($value[0]) ? $value[0] : null;
			$value_1 = isset($value[1]) ? $value[1] : null;
		}
		
		switch($error_num) {
			case 1: $this->problem = "ERROR: ResultMap item doesn't have column name defined!"; break;
			case 2: $this->problem = "ERROR: ResultMap item doesn't have property name defined!"; break;
			case 3: $this->problem = "ERROR: ResultMap doesn't have any items!"; break;
			case 4: $this->problem = "ERROR: ResultMap doesn't exists!"; break;
			case 5: $this->problem = "ERROR: ResultMap column name doesn't exist! Column '$value_0' doesn't exist in [".implode(", ",$value_1)."]"; break;
			case 6: $this->problem = "ERROR: ResultMap column '".$value."' doesn't exist in the DB result! Please check your result map xml."; break;
		}
	}
}
?>
