<?php
include_once get_lib("org.phpframework.compression.ZipHandler");
include_once get_lib("org.phpframework.util.MimeTypeHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$item_type = $_GET["item_type"];
$folder_type = $_GET["folder_type"];

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
		$tmp_file_path = stream_get_meta_data($tmp_file)['uri']; // eg: /tmp/phpFx0513a
		
		if (ZipHandler::zip($file_path, $tmp_file_path)) {
			if ($folder_type == "template_folder" && $PEVC) {
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
