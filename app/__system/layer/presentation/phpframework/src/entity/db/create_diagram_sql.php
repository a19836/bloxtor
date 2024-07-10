<?php
include_once $EVC->getUtilPath("WorkFlowDBHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = $_GET["layer_bean_folder_name"];
$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$popup = $_GET["popup"];

if ($bean_name) {
	$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
	
	$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	
	if ($_POST) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		
		$sql = $_POST["sql"];
		
		if ($sql) {
			$DBDriver = $WorkFlowDBHandler->getBeanObject($bean_file_name, $bean_name);
			$status = $DBDriver->setData($sql);
		}
	}
	else {
		$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $bean_name);
		
		$sql = $WorkFlowDBHandler->getTaskDBDiagramSql($bean_file_name, $bean_name, $tasks_file_path);
	}
}
?>
