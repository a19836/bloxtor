<?php
class HibernateException extends Exception {
	public $problem;

	public function __construct($error_num, $value = array()) {
		$value_0 = $value_1 = null;
		
		if (is_array($value)) {
			$value_0 = isset($value[0]) ? $value[0] : null;
			$value_1 = isset($value[1]) ? $value[1] : null;
		}
		
		switch($error_num) {
			case 1: $this->problem = "ERROR trying to include '$value_0' class path: $value_1"; break;
			case 2: $this->problem = "Hibernate obj '" . $value . "' does not exist. Please check your hibernate xml files."; break;
			case 3: $this->problem = "Undefined id generator '$value_0'. You must select one of the following generators: [".strtolower(implode(", ", $value_1))."]"; break;
			case 4: $this->problem = "Object '".$value."' can only have one parameter map or parameter class. You cannot have multiple parameter types."; break;
			case 5: $this->problem = "Object '".$value."' can only have one result map or result class. You cannot have multiple result types."; break;
			case 6: $this->problem = "There is an object with out name in the hibernate xml file: '".$value."'."; break;
			case 7: $this->problem = "Duplicate class with id '".$value."'."; break;
			case 8: $this->problem = "There is a Relationship with out a name."; break;
			case 9: $this->problem = "Relationship '".$value."' can only have a result_class or a result_map."; break;
			case 10: $this->problem = "Relationship '".$value."' can only have a parameter_class or a parameter_map."; break;
		}
	}
}
?>
