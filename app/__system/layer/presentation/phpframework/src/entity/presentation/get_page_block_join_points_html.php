<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$project = $_GET["project"];
$block = $_GET["block"];

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
			$module_id = $block_params[0]["module_type"] == "string" ? $block_params[0]["module"] : "";
			
			if ($module_id) {
				$P = $PEVC->getPresentationLayer();
				$selected_project_id = $P->getSelectedPresentationId();
				
				$PCMSModuleLayer = $PEVC->getCMSLayer()->getCMSModuleLayer();
				$PCMSModuleLayer->loadModules(getProjectCommonUrlPrefix($PEVC, $selected_project_id) . "module/");
				$module = $PCMSModuleLayer->getLoadedModule($module_id);
				$join_points = $module["join_points"];
				//echo "<pre>";print_r($join_points);die();
				
				$raw_block_id = PHPUICodeExpressionHandler::getArgumentCode($block_params[0]["block"], $block_params[0]["block_type"]);
				$block_local_join_points = CMSPresentationLayerHandler::getFileBlockLocalJoinPointsListByBlock($block_path);
				$block_local_join_points = $block_local_join_points[$raw_block_id];
				//echo "<pre>";print_r($block_local_join_points);die();
				
				$jps = array();
				if ($join_points) {
					$t = count($join_points);
					for ($i = 0; $i < $t; $i++) {
						$jp = $join_points[$i];
						$join_point_name = PHPUICodeExpressionHandler::getArgumentCode($jp["join_point_name"], $jp["join_point_name_type"]);
						$jps[$join_point_name] = $jp;
					}
				}
				//echo "<pre>";print_r($jps);die();
				
				$module_join_points = array();
				if ($block_local_join_points) {
					foreach ($block_local_join_points as $block_local_join_point) {
						$join_point_name = PHPUICodeExpressionHandler::getArgumentCode($block_local_join_point["join_point_name"], $block_local_join_point["join_point_name_type"]);
						$jp = $jps[$join_point_name];
					
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
