<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$filter_by_layout = $_GET["filter_by_layout"];
$include_empty_project_folders = $_GET["include_empty_project_folders"]; //This is used in the choose_available_project

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

if ($path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$P = $PEVC->getPresentationLayer();
		$selected_project_id = $P->getSelectedPresentationId();
		
		//PREPARING AVAILABLE PROJECTS TO SHOW IN TEMPLATES CHOOSE POPUP
		$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
		
		$pres_layers_projects_props = CMSPresentationLayerHandler::getPresentationLayersProjectsFiles($user_global_variables_file_path, $user_beans_folder_path, false, false, -1, $include_empty_project_folders, null, true);
		$LayoutTypeProjectHandler->filterPresentationLayersProjectsByUserAndLayoutPermissions($pres_layers_projects_props, $filter_by_layout, null, array(
			"do_not_filter_by_layout" => array(
				"bean_name" => $bean_name,
				"bean_file_name" => $bean_file_name,
				"project" => $selected_project_id
			)
		));
		$available_projects_props = $pres_layers_projects_props[$bean_name]["projects"];
		//echo "<pre>";print_r($available_projects_props);die();
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
	else {
		launch_exception(new Exception("PEVC doesn't exists!"));
		die();
	}
}
else {
	launch_exception(new Exception("Undefined path!"));
	die();
}
