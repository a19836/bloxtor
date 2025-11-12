<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

class DataAccessLayerException extends Exception {
	public $problem;

	public function __construct($error_num, $value) {
		switch($error_num) {
			case 1: $this->problem = "Data access service folder doesn't exists: '{$value}'!"; break;
			case 2: $this->problem = "Data access service doesn't exists: '{$value}'!"; break;
			case 3: $this->problem = "Data access services file doesn't exists: '{$value}'!"; break;
			case 4: $this->problem = "'$value' variable cannot be empty!"; break;
		}
	}
}
?>
