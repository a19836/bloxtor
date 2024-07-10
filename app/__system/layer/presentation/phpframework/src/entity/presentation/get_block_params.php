<?php
include_once get_lib("org.phpframework.layer.presentation.cms.CMSFileHandler");
include_once get_lib("org.phpframework.phpscript.PHPUICodeExpressionHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

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
		$available_block_params = CMSFileHandler::getFileBlockParams($block_path);
	
		$available_block_params_list = array();
		$t = count($available_block_params);
		for ($i = 0; $i < $t; $i++) {
			$p = $available_block_params[$i];
		
			$available_block_params_list[] = PHPUICodeExpressionHandler::getArgumentCode($p["param"], $p["param_type"]);
		}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
}
?>
