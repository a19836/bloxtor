<?php
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
