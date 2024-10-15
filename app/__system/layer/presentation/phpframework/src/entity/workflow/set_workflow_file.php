<?php
include_once $EVC->getUtilPath("WorkFlowTasksFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$path = isset($_GET["path"]) ? $_GET["path"] : null;
$path_extra = isset($_GET["path_extra"]) ? $_GET["path_extra"] : null;

$path = str_replace("../", "", $path);//for security reasons
$path_extra = str_replace("../", "", $path_extra);//for security reasons

UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);

$path = WorkFlowTasksFileHandler::getTaskFilePathByPath($workflow_paths_id, $path, $path_extra);

if (isset($_POST["save"])) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
	UserAuthenticationHandler::checkActionsMaxNum($UserAuthenticationHandler);
	
	$data = isset($_POST["data"]) ? $_POST["data"] : null;
	$file_read_date = isset($_POST["file_read_date"]) ? $_POST["file_read_date"] : null;
	
	$status = WorkFlowTasksFileHandler::createTasksFile($path, $data, $file_read_date);
	
	if ($status)
		$UserAuthenticationHandler->incrementUsedActionsTotal();
}
else 
	$status = false;
?>
