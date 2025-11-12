<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

function get_lib($str, $type = "php") {
	$settings = get_lib_settings($str);
	
	return $settings[0] . str_replace(".", "/", $settings[1] ) . "." . $type;
}

function get_lib_settings($str) {
	$index = strpos($str, ".");
	$sub_str = substr($str, 0, $index);
	
	$prefix_path = "";
	
	switch(strtolower($sub_str)) {
		case "lib": $prefix_path = LIB_PATH; break;
		case "app": $prefix_path = APP_PATH; break;
		case "vendor": $prefix_path = VENDOR_PATH; break;
		case "root": $prefix_path = CMS_PATH; break;
		default: 
			$prefix_path = LIB_PATH;
			$index = -1;
	}
	
	return array($prefix_path, substr($str, $index + 1));
}
?>
