<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once $EVC->getUtilPath("PHPVariablesFileHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$project = isset($_GET["project"]) ? $_GET["project"] : null;
$module_id = isset($_GET["module"]) ? $_GET["module"] : null;
$block = isset($_GET["block"]) ? $_GET["block"] : null;

if ($project && ($module_id || $block)) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $project);
	
	if ($PEVC) {
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		if (!$module_id) {
			$block_path = $PEVC->getBlockPath($block);
			if ($block_path) {
				$block_params = CMSFileHandler::getFileCreateBlockParams($block_path);
				$module_id = isset($block_params[0]["module_type"]) && $block_params[0]["module_type"] == "string" ? $block_params[0]["module"] : "";
			}
			
		}
		
		if ($module_id) {
			$P = $PEVC->getPresentationLayer();
			$selected_project_id = $P->getSelectedPresentationId();
			
			$PCMSModuleLayer = $PEVC->getCMSLayer()->getCMSModuleLayer();
			$PCMSModuleLayer->loadModules(getProjectCommonUrlPrefix($PEVC, $selected_project_id) . "module/");
			$module = $PCMSModuleLayer->getLoadedModule($module_id);
			$module_handler_impl_file_path = isset($module["module_handler_impl_file_path"]) ? $module["module_handler_impl_file_path"] : null;
		
			if ($module_handler_impl_file_path) 
				$contents = file_get_contents($module_handler_impl_file_path);
		}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
}

echo isset($contents) ? $contents : null;
die();

function getProjectCommonUrlPrefix($EVC, $selected_project_id) {
	include $EVC->getConfigPath("config", $selected_project_id);
	return $project_common_url_prefix;
}
?>
