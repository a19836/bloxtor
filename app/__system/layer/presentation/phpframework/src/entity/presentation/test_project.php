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

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;

$path = str_replace("../", "", $path);//for security reasons

$view_project_url = $project_url_prefix . "phpframework/presentation/view_project?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path&";

if (!empty($_POST)) {
	$post_vars = isset($_POST["post_vars"]) ? $_POST["post_vars"] : null;
	$get_vars = isset($_POST["get_vars"]) ? $_POST["get_vars"] : null;
	$vars = array("post_vars" => array(), "get_vars" => array());
	
	if ($post_vars)
		foreach ($post_vars as $var) {
			$var_name = isset($var["name"]) ? $var["name"] : null;
			$vars["post_vars"][$var_name] = isset($var["value"]) ? $var["value"] : null;
		}
	
	if ($get_vars)
		foreach ($get_vars as $var) {
			$var_name = isset($var["name"]) ? $var["name"] : null;
			$vars["get_vars"][$var_name] = isset($var["value"]) ? $var["value"] : null;
		}
	
	$view_project_url .= http_build_query($vars);
}
?>
