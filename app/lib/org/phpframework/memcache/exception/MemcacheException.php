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

class MemcacheException extends Exception {
	public $problem;

	public function __construct($error_num, $e, $value = array()) {
		switch($error_num) {
			case 1: $this->problem = "Memcache connection fail: MemcacheHandler->connect(".implode(", ", $value).")"; break;
		}
		
		if (!empty($e)) {
			if (is_string($e))
				parent::__construct($e, $error_num, null);
			else
				parent::__construct(!empty($e->problem) ? $e->problem : $e->getMessage(), $error_num, $e);
			
			//$this->problem .= "<br><br>EXCEPTION: " . $e;
		}
	}
}
?>
