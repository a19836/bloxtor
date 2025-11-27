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

include_once $EVC->getUtilPath("WorkFlowTestUnitHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);
UserAuthenticationHandler::checkActionsMaxNum($UserAuthenticationHandler);

if (!empty($_POST)) {
	$selected_paths = isset($_POST["selected_paths"]) ? $_POST["selected_paths"] : null;
	
	if ($selected_paths) {
		$WorkFlowTestUnitHandler = new WorkFlowTestUnitHandler($user_global_variables_file_path, $user_beans_folder_path);
		$WorkFlowTestUnitHandler->initBeanObjects();
		$responses = array();
		
		foreach ($selected_paths as $test_path)
			$WorkFlowTestUnitHandler->executeTest($test_path, $responses);
		
		$UserAuthenticationHandler->incrementUsedActionsTotal();
	}
}
?>
