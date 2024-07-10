<?php
class BeanSettingsFileFactoryException extends Exception {
	public $problem;

	public function __construct($error_num, $value) {
		switch($error_num) {
			case 1: $this->problem = "Bean file doesn't exist: '$value'!"; break;
		}
	}
}
?>
