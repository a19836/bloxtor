<?php
include_once $EVC->getUtilPath("WorkFlowTasksFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$path = $_GET["path"];
$path_extra = $_GET["path_extra"];

$path = str_replace("../", "", $path);//for security reasons
$path_extra = str_replace("../", "", $path_extra);//for security reasons

UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);

$path = WorkFlowTasksFileHandler::getTaskFilePathByPath($workflow_paths_id, $path, $path_extra);

if (isset($_POST["save"])) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
	UserAuthenticationHandler::checkActionsMaxNum($UserAuthenticationHandler);
	
	$status = WorkFlowTasksFileHandler::createTasksFile($path, $_POST["data"], $_POST["file_read_date"]);
	
	if ($status)
		$UserAuthenticationHandler->incrementUsedActionsTotal();
}
?>
