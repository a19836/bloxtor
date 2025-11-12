<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("lib.vendor.phpdiff.Differ");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$src_bean_name = isset($_GET["src_bean_name"]) ? $_GET["src_bean_name"] : null;
$src_bean_file_name = isset($_GET["src_bean_file_name"]) ? $_GET["src_bean_file_name"] : null;
$src_item_type = isset($_GET["src_item_type"]) ? $_GET["src_item_type"] : null;
$src_path = isset($_GET["src_path"]) ? $_GET["src_path"] : null;
$dst_bean_name = isset($_GET["dst_bean_name"]) ? $_GET["dst_bean_name"] : null;
$dst_bean_file_name = isset($_GET["dst_bean_file_name"]) ? $_GET["dst_bean_file_name"] : null;
$dst_item_type = isset($_GET["dst_item_type"]) ? $_GET["dst_item_type"] : null;
$dst_path = isset($_GET["dst_path"]) ? $_GET["dst_path"] : null;
//echo "<pre>";print_r($_GET);echo "</pre>";

$src_path = str_replace("../", "", $src_path);//for security reasons
$dst_path = str_replace("../", "", $dst_path);//for security reasons

$src_root_path = getRootPath($src_bean_name, $src_bean_file_name, $src_item_type, $src_path, $user_beans_folder_path, $user_global_variables_file_path, $src_obj);
$dst_root_path = getRootPath($dst_bean_name, $dst_bean_file_name, $dst_item_type, $dst_path, $user_beans_folder_path, $user_global_variables_file_path, $dst_obj);

if ($src_root_path && $dst_root_path) {
	$orig_src_path = $src_path;
	$orig_dst_path = $dst_path;
	$src_path = $src_root_path . $src_path;
	$dst_path = $dst_root_path . $dst_path;
	//echo "$src_path<br>$dst_path<br>";
	
	if ($orig_src_path && $orig_dst_path && file_exists($src_path) && file_exists($dst_path)) {
		$src_layer_object_id = $src_item_type == "dao" ? "vendor/dao/$orig_src_path" : ($src_item_type == "vendor" || $src_item_type == "other" ? "$src_item_type/$orig_src_path" : ($src_item_type == "test_unit" ? "vendor/testunit/$orig_src_path" : $src_path));
		$dst_layer_object_id = $dst_item_type == "dao" ? "vendor/dao/$orig_dst_path" : ($dst_item_type == "vendor" || $dst_item_type == "other" ? "$dst_item_type/$orig_dst_path" : ($dst_item_type == "test_unit" ? "vendor/testunit/$orig_dst_path" : $dst_path));
		
		$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($src_layer_object_id, "layer", "access");
		$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($dst_layer_object_id, "layer", "access");
		
		$diffs = \Diff\Differ::compareFiles($src_path, $dst_path);
		$html = \Diff\Differ::toTable($diffs);
	}
}

function getRootPath($bean_name, $bean_file_name, $item_type, $path, $user_beans_folder_path, $user_global_variables_file_path, &$obj = false) {
	$root_path = null;
	
	if ($item_type == "lib")
		$root_path = LIB_PATH;
	else if ($item_type == "dao")
		$root_path = DAO_PATH;
	else if ($item_type == "vendor")
		$root_path = VENDOR_PATH;
	else if ($item_type == "other")
		$root_path = OTHER_PATH;
	else if ($item_type == "test_unit")
		$root_path = TEST_UNIT_PATH;
	else {
		$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	
		if ($item_type != "presentation") 
			$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
		else {
			$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
			$obj = $PEVC ? $PEVC->getPresentationLayer() : null;
		}
	
		if ($obj)
			$root_path = $obj->getLayerPathSetting();
	}
	
	return $root_path;
}
?>
