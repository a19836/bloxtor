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

class MyIOManagerException extends Exception {
    public $problem;
	
    function __construct($error_num, $value) {
		switch($error_num) {
			case 1: $this->problem = "FILEMANAGER_DATA cannot be undefined."; break;
			case 2: $this->problem = "FileManager cannot be undefined. Current filemanager type is:'$value'!"; break;
		}
    }
}
?>
