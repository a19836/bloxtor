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

include_once get_lib("org.phpframework.compression.IFileCompressionHandler");

class NoneFileCompressionHandler implements IFileCompressionHandler {
	protected $file_pointer = null;

	public function __construct() {
		
	}

	public function open($file_path) {
		$this->file_pointer = fopen($file_path, "wb");
		
		if ($this->file_pointer === false)
			throw new Exception("Could not open file! Please check if the '" . basename($file_path) . "' file is writeable...");

		return true;
	}

	public function write($str) {
		$bytes = fwrite($this->file_pointer, $str);
		
		if ($bytes === false)
			throw new Exception("Could not write to file! Please check if you have enough free space...");
		
		return $bytes;
	}

	public function close() {
		return fclose($this->file_pointer);
	}
}
?>
