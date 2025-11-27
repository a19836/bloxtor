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

class FileCompressionFactory {
	
	public static function create($class_prefix) {
		$is_valid = self::isValid($class_prefix);
		
		if (!$is_valid)
			throw new Exception("Compression method ($class_prefix) is not allowed!");
		
		$class_name = "{$class_prefix}FileCompressionHandler";
		return new $class_name();
	}
	
	public static function isValid($class_prefix) {
		$class_name = "{$class_prefix}FileCompressionHandler";
		$file_path = get_lib("org.phpframework.compression.{$class_name}");
		
		if (file_exists($file_path)) {
			include_once $file_path;
			
			return is_a($class_name, "IFileCompressionHandler", true);
		}
		
		return false;
	}
}
?>
