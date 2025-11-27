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

include_once get_lib("org.phpframework.util.MyArray");
include_once $EVC->getUtilPath("WorkFlowTestUnitHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);
UserAuthenticationHandler::checkActionsMaxNum($UserAuthenticationHandler);

$path = isset($_GET["path"]) ? $_GET["path"] : null;
$file_modified_time = isset($_GET["file_modified_time"]) ? $_GET["file_modified_time"] : null;

$path = str_replace("../", "", $path);//for security reasons

$file_path = TEST_UNIT_PATH . $path;

if ($path && file_exists($file_path) && !empty($_POST)) {
	$folder_path = substr($file_path, strlen($file_path) - 1) == "/" ? $file_path : dirname($file_path);
	if (!is_dir($folder_path))
		mkdir($folder_path, 0755, true);
	
	$object = isset($_POST["object"]) ? $_POST["object"] : null;
	MyArray::arrKeysToLowerCase($object, true);
	
	$file_was_changed = file_exists($file_path) && $file_modified_time && $file_modified_time < filemtime($file_path);
	$class_name = pathinfo($path, PATHINFO_FILENAME);
	
	if ($file_was_changed) {
		$old_code = file_exists($file_path) ? file_get_contents($file_path) : "";
		$tmp_file_path = tempnam(TMP_PATH, "test_unit_");
		file_put_contents($tmp_file_path, $old_code);
		
		$status = WorkFlowTestUnitHandler::saveTestFile($tmp_file_path, $object, $class_name);
		
		$ret = array(
			"status" => "CHANGED",
			"old_code" => $old_code,
			"new_code" => file_exists($tmp_file_path) ? file_get_contents($tmp_file_path) : "",
		);
		
		unlink($tmp_file_path);
	}
	else {
		$status = WorkFlowTestUnitHandler::saveTestFile($file_path, $object, $class_name);
		
		clearstatcache(true, $file_path); //very important otherwise the filemtime will contain the old modified time.
		
		if ($status)
			$UserAuthenticationHandler->incrementUsedActionsTotal();
		
		$ret = array(
			"status" => $status,
			"modified_time" => filemtime($file_path),
		);
	}
	
	$status = json_encode($ret);
}

echo isset($status) ? $status : null;
die();
?>
