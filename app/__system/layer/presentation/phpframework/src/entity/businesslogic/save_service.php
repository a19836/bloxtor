<?php
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once $EVC->getUtilPath("WorkFlowBusinessLogicHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$_GET["rename_file_with_class"] = true;

if ($_POST["object"]) {
	//Getting default extend
	$bean_name = $_GET["bean_name"];
	$bean_file_name = $_GET["bean_file_name"];
	$path = $_GET["path"];
	$class = $_GET["class"];

	$path = str_replace("../", "", $path);//for security reasons
	
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	
	$common_namespace = "";
	$common_service_file_path = $obj->settings["business_logic_modules_service_common_file_path"];
	if ($common_service_file_path && file_exists($common_service_file_path)) {
		$common_namespace = PHPCodePrintingHandler::getNamespacesFromFile($common_service_file_path);
		$common_namespace = $common_namespace[0];
		$common_namespace = substr($common_namespace, 0, 1) == "\\" ? substr($common_namespace, 1) : $common_namespace;
		$common_namespace = substr($common_namespace, -1) == "\\" ? substr($common_namespace, 0, -1) : $common_namespace;
	}
	
	$default_extend = ($common_namespace ? "\\$common_namespace\\" : "") . "CommonService";
	
	//preparing object with right includes and extends
	WorkFlowBusinessLogicHandler::prepareServiceObjectForsaving($_POST["object"], array(
		"default_include" => '$vars["business_logic_modules_service_common_file_path"]', 
		"default_extend" => $default_extend,
	));
	
	if (!WorkFlowBusinessLogicHandler::renameServiceObjectFile($obj->getLayerPathSetting() . $path, $class))
		$_GET["rename_file_with_class"] = false;
}

$do_not_die_on_save = true;

include $EVC->getEntityPath("admin/save_file_class");

//delete caches
if ($obj && is_a($obj, "BusinessLogicLayer") && $_POST && $status) 
	CacheHandlerUtil::deleteFolder($obj->getCacheLayer()->getCachedDirPath(), false);

die($status);
?>
