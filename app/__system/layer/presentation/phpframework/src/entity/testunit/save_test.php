<?php
include_once get_lib("org.phpframework.util.MyArray");
include_once $EVC->getUtilPath("WorkFlowTestUnitHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);
UserAuthenticationHandler::checkActionsMaxNum($UserAuthenticationHandler);

$path = $_GET["path"];
$file_modified_time = $_GET["file_modified_time"];

$path = str_replace("../", "", $path);//for security reasons

$file_path = TEST_UNIT_PATH . $path;

if ($path && file_exists($file_path) && $_POST) {	
	$folder_path = substr($file_path, strlen($file_path) - 1) == "/" ? $file_path : dirname($file_path);
	if (!is_dir($folder_path))
		mkdir($folder_path, 0755, true);
	
	$object = $_POST["object"];
	MyArray::arrKeysToLowerCase($object, true);
	
	$file_was_changed = file_exists($file_path) && $file_modified_time && $file_modified_time < filemtime($file_path);
	$class_name = pathinfo($path, PATHINFO_FILENAME);
	
	if ($file_was_changed) {
		$old_code = file_exists($file_path) ? file_get_contents($file_path) : "";
		$tmp_file_path = tempnam(TMP_PATH, $file_type . "_");
		file_put_contents($tmp_file_path, $old_code);
		
		$status = WorkFlowTestUnitHandler::saveTestFile($tmp_file_path, $object, $class_name, $object["name"]);
		
		$ret = array(
			"status" => "CHANGED",
			"old_code" => $old_code,
			"new_code" => file_exists($tmp_file_path) ? file_get_contents($tmp_file_path) : "",
		);
		
		unlink($tmp_file_path);
	}
	else {
		$status = WorkFlowTestUnitHandler::saveTestFile($file_path, $object, $class_name, $object["name"]);
		
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

die($status);
?>
