<?php
class TableDiagramException extends Exception {
	public $problem;

	public function __construct($error_num, $value = array()) {
		$value_0 = $value_1 = null;
		
		if (is_array($value)) {
			$value_0 = isset($value[0]) ? $value[0] : null;
			$value_1 = isset($value[1]) ? $value[1] : null;
		}
		
		switch($error_num) {
			case 1: $this->problem = "Invalid table name!"; break;
			case 2: $this->problem = "Invalid attribute name for table '" . $value_0 . "'. Attribute: " . print_r($value_1, true); break;
			case 3: $this->problem = "Invalid attribute type for table '" . $value_0 . "'. Attribute: " . print_r($value_1, true); break;
			case 4: $this->problem = "Invalid unique key name for table '" . $value_0 . "'. Attribute: " . print_r($value_1, true); break;
			case 5: $this->problem = "Invalid uniquekey type for table '" . $value_0 . "'. Attribute: " . print_r($value_1, true); break;
			case 6: $this->problem = "Invalid foreign key attribute for table '" . $value_0 . "'. Attribute: " . print_r($value_1, true); break;
			case 7: $this->problem = "Invalid foreign key reference table for table '" . $value_0 . "'. Attribute: " . print_r($value_1, true); break;
			case 8: $this->problem = "Invalid foreign key reference attribute for table '" . $value_0 . "'. Attribute: " . print_r($value_1, true); break;
			case 9: $this->problem = "Invalid index key name for table '" . $value_0 . "'. Attribute: " . print_r($value_1, true); break;
			case 10: $this->problem = "Invalid index key type for table '" . $value_0 . "'. Attribute: " . print_r($value_1, true); break;
			case 11: $this->problem = "Invalid TableDiagram. " . print_r($value, true); break;
		}
	}
}
?>
