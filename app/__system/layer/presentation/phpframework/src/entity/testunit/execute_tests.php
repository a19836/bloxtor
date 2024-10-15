<?php
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
