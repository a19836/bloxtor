<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.layer.presentation.cms.CMSFileHandler");
include_once get_lib("org.phpframework.phpscript.PHPUICodeExpressionHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

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
		$available_block_params = CMSFileHandler::getFileBlockParams($block_path);
	
		$available_block_params_list = array();
		$t = count($available_block_params);
		for ($i = 0; $i < $t; $i++) {
			$p = $available_block_params[$i];
			$param_value = isset($p["param"]) ? $p["param"] : null;
			$param_type = isset($p["param_type"]) ? $p["param_type"] : null;
			
			$available_block_params_list[] = PHPUICodeExpressionHandler::getArgumentCode($param_value, $param_type);
		}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
}
?>
