<?php
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$item_type = isset($_GET["item_type"]) ? $_GET["item_type"] : null;
$on_success_js_func = isset($_GET["on_success_js_func"]) ? $_GET["on_success_js_func"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null;
$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons
$root_path = !empty($root_path) ? $root_path : null;
$obj = null;

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
