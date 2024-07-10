<?php
include_once $EVC->getUtilPath("WorkFlowDBHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = $_GET["layer_bean_folder_name"];
$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];

$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");

if ($bean_name && $bean_file_name && isset($_POST["sync"])) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
	
	$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
	
	if ($_POST["simulate"]) {
		$statements = $WorkFlowDBHandler->getSyncTaskDBDiagramWithDBServerSQLStatements($bean_file_name, $bean_name, $_POST["data"], $parsed_data);
		
		$status = array(
			"status" => $status,
			"statements" => $statements,
			"data" => $parsed_data
		);
	}
	else if ($_POST["statements"]) {
		$status = $WorkFlowDBHandler->executeSyncTaskDBDiagramWithDBServerSQLStatements($bean_file_name, $bean_name, $_POST["statements"], $errors);
		
		if (!$status && $errors)
			$status = array(
				"status" => $status,
				"errors" => $errors,
			);
	}
	else if ($_POST["data"]) {
		$status = $WorkFlowDBHandler->syncTaskDBDiagramWithDBServer($bean_file_name, $bean_name, $_POST["data"], $parsed_data, $errors);
		
		if (!$status && $errors)
			$status = array(
				"status" => $status,
				"data" => $parsed_data,
				"errors" => $errors,
			);
	}
}
?>
