<?php
include_once $EVC->getUtilPath("WorkFlowTasksFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$path = $_GET["path"];
$path_extra = $_GET["path_extra"];

$path = str_replace("../", "", $path);//for security reasons
$path_extra = str_replace("../", "", $path_extra);//for security reasons

$path = WorkFlowTasksFileHandler::getTaskFilePathByPath($workflow_paths_id, $path, $path_extra);
//echo "path:$path";die();

$WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($path);
$WorkFlowTasksFileHandler->init();
$tasks = $WorkFlowTasksFileHandler->getWorkflowData();
//print_r($tasks);
?>
