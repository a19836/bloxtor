<?php
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$item_type = $_GET["item_type"];
$on_success_js_func = $_GET["on_success_js_func"];
$filter_by_layout = $_GET["filter_by_layout"];
$popup = $_GET["popup"];

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

if ($item_type == "dao")
	$root_path = DAO_PATH;
else if ($item_type == "vendor")
	$root_path = VENDOR_PATH;
else if ($item_type == "test_unit")
	$root_path = TEST_UNIT_PATH;
else if ($item_type == "other")
	$root_path = OTHER_PATH;
else if ($bean_name) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	
	if ($item_type != "presentation") 
		$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	else {
		$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
		$obj = $PEVC ? $PEVC->getPresentationLayer() : null;
	}
	
	$root_path = $obj->getLayerPathSetting();
}

$file_path = $root_path . $path;
?>
