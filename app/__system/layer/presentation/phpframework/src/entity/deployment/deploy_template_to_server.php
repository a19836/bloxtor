<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once $EVC->getUtilPath("CMSDeploymentHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");
UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);
UserAuthenticationHandler::checkActionsMaxNum($UserAuthenticationHandler);

$server_name = isset($_GET["server"]) ? $_GET["server"] : null;
$template_id = isset($_GET["template_id"]) ? $_GET["template_id"] : null;
$deployment_id = isset($_GET["deployment_id"]) ? $_GET["deployment_id"] : null;
$action = isset($_GET["action"]) ? $_GET["action"] : null;

$li = $EVC->getPresentationLayer()->getPHPFrameWork()->getLicenceInfo();
$CMSDeploymentHandler = new CMSDeploymentHandler($workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $deployments_temp_folder_path, $user_beans_folder_path, $user_global_variables_file_path, $user_global_settings_file_path, $li);
$res = $CMSDeploymentHandler->executeServerAction($server_name, $template_id, $deployment_id, $action);

if ($res && !empty($res["status"]))
	$UserAuthenticationHandler->incrementUsedActionsTotal();
?>
