<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once $EVC->getUtilPath("WorkFlowBusinessLogicHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$_GET["rename_file_with_class"] = true;

if (!empty($_POST["object"])) {
	//Getting default extend
	$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
	$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
	$path = isset($_GET["path"]) ? $_GET["path"] : null;
	$class = isset($_GET["class"]) ? $_GET["class"] : null;

	$path = str_replace("../", "", $path);//for security reasons
	
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	
	$common_namespace = "";
	$common_service_file_path = isset($obj->settings["business_logic_modules_service_common_file_path"]) ? $obj->settings["business_logic_modules_service_common_file_path"] : null;
	if ($common_service_file_path && file_exists($common_service_file_path)) {
		$common_namespace = PHPCodePrintingHandler::getNamespacesFromFile($common_service_file_path);
		$common_namespace = isset($common_namespace[0]) ? $common_namespace[0] : null;
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
if (!empty($obj) && is_a($obj, "BusinessLogicLayer") && !empty($_POST) && !empty($status)) 
	CacheHandlerUtil::deleteFolder($obj->getCacheLayer()->getCachedDirPath(), false);

echo isset($status) ? $status : null;
die();
?>
