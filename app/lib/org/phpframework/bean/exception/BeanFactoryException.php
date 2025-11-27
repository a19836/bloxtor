<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

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
