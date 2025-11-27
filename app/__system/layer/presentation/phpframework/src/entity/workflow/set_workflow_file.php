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
