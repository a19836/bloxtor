<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

class LayerException extends Exception {
	public $problem;

	public function __construct($error_num, $value = "") {
		switch($error_num) {
			case 1: $this->problem = "Broker Layer '{$value}' does not exist!"; break;
			case 2: $this->problem = "'{$value}' variable cannot be empty!"; break;
		}
	}
}
?>
