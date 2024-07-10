<?php
class JoinPointHandlerException extends Exception {
	public $problem;

	public function __construct($error_num, $e, $value = array()) {
		switch($error_num) {
			case 1: $this->problem = "Error trying to execute code: $value!"; break;
			case 2: $this->problem = "Error trying to include join point method file: '$value'!"; break;
		}
		
		if (!empty($e)) {
			if (is_string($e)) {
				parent::__construct($e, $error_num, null);
			}
			else {
				parent::__construct($e->problem ? $e->problem : $e->getMessage(), $error_num, $e);
			}
			//$this->problem .= "<br><br>EXCEPTION: " . $e;
		}
	}
}
?>
