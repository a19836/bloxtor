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

include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleEnableHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleInstallationHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$module_id = isset($_GET["module_id"]) ? $_GET["module_id"] : null;
$action = isset($_GET["action"]) ? $_GET["action"] : null;

if ($module_id && $action) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name);

	if ($PEVC) {
		$P = $PEVC->getPresentationLayer();
		
		$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$PresentationLayer = $EVC->getPresentationLayer();
		
		if (empty($PresentationLayer->settings["presentation_modules_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_modules_path]' cannot be undefined!"));
		
		if (empty($PresentationLayer->settings["presentation_webroot_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_webroot_path]' cannot be undefined!"));
		
		$system_presentation_settings_module_path = $PresentationLayer->getLayerPathSetting() . $PresentationLayer->getCommonProjectName() . "/" . $PresentationLayer->settings["presentation_modules_path"] . $module_id;
		$system_presentation_settings_webroot_module_path = $PresentationLayer->getLayerPathSetting() . $PresentationLayer->getCommonProjectName() . "/" . $PresentationLayer->settings["presentation_webroot_path"] . "module/$module_id";
		
		if ($action == "enable" || $action == "disable") {
			$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
			
			$CMSModuleEnableHandler = CMSModuleEnableHandler::createCMSModuleEnableHandlerObject($P, $module_id, $system_presentation_settings_module_path);
		
			$status = $action == "enable" ? $CMSModuleEnableHandler->enable() : $CMSModuleEnableHandler->disable();
			
			if ($status)
				$CMSModuleEnableHandler->freeModuleCache();
		}
		else if ($action == "uninstall") {
			$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");
			
			//only get the layers that the $bean_name has access to
			$layers = WorkFlowBeansFileHandler::getLocalBeanLayersFromBrokers($user_global_variables_file_path, $user_beans_folder_path, $P->getBrokers(), true);
			$layers[$bean_name] = $P;
			
			$delete_system_module = deleteSystemModule($user_global_variables_file_path, $user_beans_folder_path, $module_id, $system_presentation_settings_module_path, $system_presentation_settings_webroot_module_path, $layers);
			//echo "delete_system_module:$delete_system_module";die();
			
			$CMSModuleInstallationHandler = CMSModuleInstallationHandler::createCMSModuleInstallationHandlerObject($layers, $module_id, $system_presentation_settings_module_path, $system_presentation_settings_webroot_module_path);
			
			$status = $CMSModuleInstallationHandler->uninstall($delete_system_module);
			
			if ($status)
				$CMSModuleInstallationHandler->freeModuleCache();
		}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
}

function deleteSystemModule($user_global_variables_file_path, $user_beans_folder_path, $module_id, $system_presentation_settings_module_path, $system_presentation_settings_webroot_module_path, $layers_to_check) {
	$all_layers = WorkFlowBeansFileHandler::getAllLayersBeanObjs($user_global_variables_file_path, $user_beans_folder_path);
	$excluded_layers = array();
	
	//check if all $layers_to_check are the total number of all layers
	if ($all_layers)
		foreach ($all_layers as $bean_name => $obj) {
			if (empty($layers_to_check[$bean_name]))
				$excluded_layers[$bean_name] = $obj;
		}
	
	//for the layers not included in $layers_to_check, check if module exists in this layer
	if (!$excluded_layers) 
		return true;
	
	$CMSModuleInstallationHandler = CMSModuleInstallationHandler::createCMSModuleInstallationHandlerObject($excluded_layers, $module_id, $system_presentation_settings_module_path, $system_presentation_settings_webroot_module_path);
	
	return !$CMSModuleInstallationHandler->isModuleInstalled();
}

echo isset($status) ? $status : null;
die();
?>
