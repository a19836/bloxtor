<?php
class BeanFactoryException extends Exception {
	public $problem;

	public function __construct($error_num, $value) {
		switch($error_num) {
			case 1: $this->problem = "Bean number '{$value}' is invalid. The beans only can be IMPORT, BEAN or VAR type!"; break;
			case 2: $this->problem = "Bean '{$value}' does not exist!"; break;
			case 3: $this->problem = "Infinitive cicle creating bean '{$value}'!"; break;
		}
	}
}
?>
