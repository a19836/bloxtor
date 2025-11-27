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

include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$project = isset($_GET["project"]) ? $_GET["project"] : null;
$block = isset($_GET["block"]) ? $_GET["block"] : null;

if ($project && $block) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $project);

	if ($PEVC) {
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		//PREPARING BLOCK PARAMS
		$block_path = $PEVC->getBlockPath($block);
		
		if ($block_path) {
			$block_params = CMSFileHandler::getFileCreateBlockParams($block_path);
			$module_id = isset($block_params[0]["module_type"]) && $block_params[0]["module_type"] == "string" ? $block_params[0]["module"] : "";
			
			if ($module_id) {
				$P = $PEVC->getPresentationLayer();
				$selected_project_id = $P->getSelectedPresentationId();
				
				$PCMSModuleLayer = $PEVC->getCMSLayer()->getCMSModuleLayer();
				$PCMSModuleLayer->loadModules(getProjectCommonUrlPrefix($PEVC, $selected_project_id) . "module/");
				$module = $PCMSModuleLayer->getLoadedModule($module_id);
				$join_points = isset($module["join_points"]) ? $module["join_points"] : null;
				//echo "<pre>";print_r($join_points);die();
				
				$raw_block_id = isset($block_params[0]["block"]) ? $block_params[0]["block"] : null;
				$raw_block_id = PHPUICodeExpressionHandler::getArgumentCode($raw_block_id, isset($block_params[0]["block_type"]) ? $block_params[0]["block_type"] : null);
				$block_local_join_points = CMSPresentationLayerHandler::getFileBlockLocalJoinPointsListByBlock($block_path);
				$block_local_join_points = isset($block_local_join_points[$raw_block_id]) ? $block_local_join_points[$raw_block_id] : null;
				//echo "<pre>";print_r($block_local_join_points);die();
				
				$jps = array();
				if ($join_points) {
					$t = count($join_points);
					for ($i = 0; $i < $t; $i++) {
						$jp = $join_points[$i];
						$join_point_name = isset($jp["join_point_name"]) ? $jp["join_point_name"] : null;
						$join_point_name = PHPUICodeExpressionHandler::getArgumentCode($join_point_name, isset($jp["join_point_name_type"]) ? $jp["join_point_name_type"] : null);
						$jps[$join_point_name] = $jp;
					}
				}
				//echo "<pre>";print_r($jps);die();
				
				$module_join_points = array();
				if ($block_local_join_points) {
					foreach ($block_local_join_points as $block_local_join_point) {
						$join_point_name = isset($block_local_join_point["join_point_name"]) ? $block_local_join_point["join_point_name"] : null;
						$join_point_name = PHPUICodeExpressionHandler::getArgumentCode($join_point_name, isset($block_local_join_point["join_point_name_type"]) ? $block_local_join_point["join_point_name_type"] : null);
						$jp = isset($jps[$join_point_name]) ? $jps[$join_point_name] : null;
					
						if ($jp) {
							$module_join_points[] = $jp;
						}
					}
				}
				//echo "<pre>";print_r($module_join_points);die();
			}
		}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
}

function getProjectCommonUrlPrefix($EVC, $selected_project_id) {
	include $EVC->getConfigPath("config", $selected_project_id);
	return $project_common_url_prefix;
}
?>
