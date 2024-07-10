<?php
class EVCDispatcherException extends Exception {
	public $problem;

	public function __construct($error_num, $value) {
		$value_0 = $value_1 = null;
		
		if (is_array($value)) {
			$value_0 = isset($value[0]) ? $value[0] : null;
			$value_1 = isset($value[1]) ? $value[1] : null;
		}
		
		switch($error_num) {
			case 1: $this->problem = "Controller '".$value_0."' and default controller '".$value_1."' do not exist!"; break;
		}
	}
}
?>
