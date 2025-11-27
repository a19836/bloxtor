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

class VendorFrameworkHandler {
	
	public static function getVendorFrameworkFolder($path) {
		if (self::isLaravelProjectFolder($path))
			return "laravel";
		
		return null;
	}
	
	public static function isLaravelProjectFolder($path) {
		return file_exists("$path/artisan") && is_dir("$path/app") && is_dir("$path/bootstrap") && is_dir("$path/config") && file_exists("$path/composer.json") && strpos(file_get_contents("$path/composer.json"), '"laravel/framework"');
	}
}
?>
