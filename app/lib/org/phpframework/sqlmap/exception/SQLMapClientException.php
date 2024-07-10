<?php
class SQLMapClientException extends Exception {
	public $problem;

	public function __construct($error_num, $value = array()) {
		$value_0 = $value_1 = null;
		
		if (is_array($value)) {
			$value_0 = isset($value[0]) ? $value[0] : null;
			$value_1 = isset($value[1]) ? $value[1] : null;
		}
		
		switch($error_num) {
			case 1: $this->problem = "Undefined sql mapping node '".$value."' in the queries xml file."; break;
			case 2: $this->problem = "There is a query with out id in the '".$value."' queries."; break;
			case 3: $this->problem = "Duplicate node entries '$value_1' in the '$value_0' nodes."; break;
		}
	}
}
?>
