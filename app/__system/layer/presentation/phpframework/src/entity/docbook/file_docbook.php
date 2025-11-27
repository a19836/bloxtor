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

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$path = isset($_GET["path"]) ? $_GET["path"] : null;

$path = str_replace("../", "", $path);//for security reasons

$file_path = $path ? APP_PATH . $path : null;
$file_exists = $file_path ? file_exists($file_path) : null;

if ($file_exists) {
	$is_docbook_allowed = strpos($file_path, LIB_PATH . "org/") === 0;
	$is_contents_allowed = strpos($file_path, LIB_PATH) === 0;
	
	if ($is_docbook_allowed) {
		//get $classes_properties from cache
		$relative_file_path = substr($file_path, strlen(APP_PATH));
		$cached_path = $EVC->getEntitiesPath() . "docbook/files/$relative_file_path.ser";
		
		$classes_properties = file_exists($cached_path) ? unserialize(file_get_contents($cached_path)) : null;
		//echo "<pre>";print_r($classes_properties);die();
	}
	else if ($is_contents_allowed)
		$contents = file_get_contents($file_path);
}
?>
