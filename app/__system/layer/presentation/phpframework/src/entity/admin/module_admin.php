<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$group_module_id = isset($_GET["group_module_id"]) ? $_GET["group_module_id"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null; //optional
$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;

$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

if ($bean_name && $bean_file_name && $group_module_id) {
	$projects = CMSPresentationLayerHandler::getPresentationLayerProjectsFiles($user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name, "config", false, 0);
	
	$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
$LayoutTypeProjectHandler->filterPresentationLayerProjectsByUserAndLayoutPermissions($projects, $filter_by_layout, UserAuthenticationHandler::$PERMISSION_BELONG_NAME, array(
		"do_not_filter_by_layout" => array(
			"bean_name" => $bean_name,
			"bean_file_name" => $bean_file_name
		)
	));
	$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bean_file_name, $bean_name, $user_global_variables_file_path);
	$selected_project = strpos($filter_by_layout, "$layer_bean_folder_name/") === 0 ? substr($filter_by_layout, strlen("$layer_bean_folder_name/")) : null;
	
	if (!empty($_POST)) {
		$project = isset($_POST["project"]) ? $_POST["project"] : null;
		
		if ($project) {
			$url = $project_url_prefix . "phpframework/module/$group_module_id/admin/index?bean_name=$bean_name&bean_file_name=$bean_file_name&filter_by_layout=$filter_by_layout&path=$project" . ($popup ? "&popup=$popup" : "");
			
			header("Location: $url");
			echo "<script>document.location='$url';</script>";
		}
		else {
			$error_message = "You must select a project. Please try again...";
		}
	}
}
?>
