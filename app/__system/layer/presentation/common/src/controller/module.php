<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

/******* PREPARE EVC *******/
$EVC->setController( basename(__FILE__, ".php") );

$page_prefix = "";
$page = "";
$parameters_count = $parameters ? count($parameters) : 0;
$modules_path = $EVC->getModulesPath($EVC->getCommonProjectName());
for($i = 0; $i < $parameters_count; $i++) {
	$page = $parameters[$i];
	
	if ($page) {
		if(is_dir($modules_path . $page_prefix . $page)) {
			if ($i + 1 == $parameters_count && is_file($EVC->getModulePath($page_prefix . $page))) //if last parameter and is a php file (besides a directory), gives priority to the file.
				break;
			
			$page_prefix .= $page . "/";
			$page = "";
		}
		else
			break;
	}
}

/******* SHOW MODULE *******/
$page = $page ? $page : "index";
$module_path = $EVC->getModulePath($page_prefix . $page, $EVC->getCommonProjectName());

if (file_exists($module_path)) {
	include $module_path;
}
else {
	header("HTTP/1.0 404 Not Found");
	launch_exception(new CMSModuleLayerException(4, $module_path));
}
?>
