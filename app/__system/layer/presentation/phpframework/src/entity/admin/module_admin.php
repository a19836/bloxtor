<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$group_module_id = $_GET["group_module_id"];
$filter_by_layout = $_GET["filter_by_layout"]; //optional
$popup = $_GET["popup"];

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
	
	if ($_POST) {
		$project = $_POST["project"];
		
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
