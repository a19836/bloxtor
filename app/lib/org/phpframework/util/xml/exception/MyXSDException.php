<?php
class MyXSDException extends Exception {
	public $problem;

	public function __construct($error_num, $value) {
		$value_0 = $value_1 = $value_2 = null;
		
		if (is_array($value)) {
			$value_0 = isset($value[0]) ? $value[0] : null;
			$value_1 = isset($value[1]) ? $value[1] : null;
			$value_2 = isset($value[2]) ? $value[2] : null;
		}
		
		switch($error_num) {
			case 1: $this->problem = "DOMDocument::schemaValidate('".$value_1."') generated errors, trying to validate  file '".$value_2."'!<br/>Errors:<ul>"; 
				$errors = $value_0;
				if ($errors)
					foreach ($errors as $error) 
						$this->problem .= "<li>".$error."</li>";
				
				$this->problem .= "</ul>";
				break;
			case 2: $this->problem = "ERROR: The xml schema file '{$value}' does not exist."; break;
		}
	}
}
?>
