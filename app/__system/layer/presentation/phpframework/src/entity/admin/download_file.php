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

include_once get_lib("org.phpframework.compression.ZipHandler");
include_once get_lib("org.phpframework.util.MimeTypeHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$item_type = isset($_GET["item_type"]) ? $_GET["item_type"] : null;
$folder_type = isset($_GET["folder_type"]) ? $_GET["folder_type"] : null;

$path = str_replace("../", "", $path);//for security reasons

$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);

if ($item_type == "dao") 
	$layer_path = DAO_PATH;
else if ($item_type == "vendor")
	$layer_path = VENDOR_PATH;
else if ($item_type == "test_unit")
	$layer_path = TEST_UNIT_PATH;
else if ($item_type == "other")
	$layer_path = OTHER_PATH;
else {
	if ($item_type != "presentation")
		$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	else {
		$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
		$obj = $PEVC ? $PEVC->getPresentationLayer() : null;
	}
	
	$layer_path = null;
	if ($obj)
		$layer_path = $obj->getLayerPathSetting();
}

$file_path = $layer_path . $path;

$file_exists = file_exists($file_path);

if ($path && $file_exists) {
	$layer_object_id = $item_type == "dao" ? "vendor/dao/$path" : ($item_type == "vendor" || $item_type == "other" ? "$item_type/$path" : ($item_type == "test_unit" ? "vendor/testunit/$path" : $file_path));
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	
	//echo "file_path:$file_path";die();
	
	if (is_dir($file_path)) {
		$tmp_file = tmpfile();
		$tmp_file_path = stream_get_meta_data($tmp_file);
		$tmp_file_path = isset($tmp_file_path['uri']) ? $tmp_file_path['uri'] : null; //eg: /tmp/phpFx0513a
		
		if (ZipHandler::zip($file_path, $tmp_file_path)) {
			if ($folder_type == "template_folder" && !empty($PEVC)) {
				$webroot_template_path = $PEVC->getWebrootPath() . "template/" . substr($file_path, strlen($PEVC->getTemplatesPath()));
				
				if (file_exists($webroot_template_path) && is_dir($webroot_template_path))
					ZipHandler::addFileToZip($tmp_file_path, $webroot_template_path, basename($file_path) . "/webroot/");
			}
			
			header('Content-Type: application/zip');
			header('Content-Length: ' . filesize($tmp_file_path));
			header('Content-Disposition: attachment; filename="' . basename($file_path) . '.zip"');
			
			readfile($tmp_file_path);
		}
		
		unlink($tmp_file_path); 
	}
	else {
		$mime_type = MimeTypeHandler::getFileMimeType($file_path);
		$mime_type = $mime_type ? $mime_type : "application/octet-stream";
		
		header('Content-Type: ' . $mime_type);
		header('Content-Length: ' . filesize($file_path));
		header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
		
		readfile($file_path);
	}
}

die();
?>
