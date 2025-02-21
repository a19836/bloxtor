<?php
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$item_type = isset($_GET["item_type"]) ? $_GET["item_type"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null;

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons
$obj = $layer_path = null;

if ($item_type == "dao") {
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("vendor/dao/$path", "layer", "access");
	
	$layer_path = DAO_PATH;
}
else if ($item_type == "lib") {
	$layer_path = LIB_PATH;
}
else if ($item_type == "vendor") {
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("vendor/$path", "layer", "access");
	
	$layer_path = VENDOR_PATH;
}
else if ($item_type == "other") {
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("other/$path", "layer", "access");
	
	$layer_path = OTHER_PATH;
}
else if ($item_type == "test_unit") {
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("vendor/testunit/$path", "layer", "access");
	
	$layer_path = TEST_UNIT_PATH;
}
else {
	$layer_object_id = LAYER_PATH . WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bean_file_name, $bean_name, $user_global_variables_file_path) . "/";
	$layer_path_object_id = $layer_object_id . $path . "/";
	
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_path_object_id, "layer", "access");
	
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	
	if ($item_type != "presentation")
		$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	else {
		$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
		
		if ($PEVC)
			$obj = $PEVC->getPresentationLayer();
	}
	
	if ($obj)
		$layer_path = $obj->getLayerPathSetting();
}

if ($layer_path) {
	$folder_path = $layer_path . $path;
	
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($folder_path, "layer", "access");
	
	include $EVC->getEntityPath("admin/terminal_console");
}
?>
