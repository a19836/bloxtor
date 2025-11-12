<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

class ErrorHandler {
	private $error;
	
	public function __construct() {
		$this->start();
	}
	
	public function stop() {
		$this->error = true;
	}
	
	public function start() {
		$this->error = false;
	}
	
	public function ok() {
		return !$this->error;
	}
}
?>
