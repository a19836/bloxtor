<?php
include_once $EVC->getUtilPath("PHPVariablesFileHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$project = $_GET["project"];
$module_id = $_GET["module"];
$block = $_GET["block"];

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
				$module_id = $block_params[0]["module_type"] == "string" ? $block_params[0]["module"] : "";
			}
			
		}
		
		if ($module_id) {
			$P = $PEVC->getPresentationLayer();
			$selected_project_id = $P->getSelectedPresentationId();
			
			$PCMSModuleLayer = $PEVC->getCMSLayer()->getCMSModuleLayer();
			$PCMSModuleLayer->loadModules(getProjectCommonUrlPrefix($PEVC, $selected_project_id) . "module/");
			$module = $PCMSModuleLayer->getLoadedModule($module_id);
			$module_handler_impl_file_path = $module["module_handler_impl_file_path"];
		
			if ($module_handler_impl_file_path) 
				$contents = file_get_contents($module_handler_impl_file_path);
		}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
}

echo $contents;
die();

function getProjectCommonUrlPrefix($EVC, $selected_project_id) {
	include $EVC->getConfigPath("config", $selected_project_id);
	return $project_common_url_prefix;
}
?>
