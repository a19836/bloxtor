<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$filter_by_layout = $_GET["filter_by_layout"];
$filter_by_layout_permission = $_GET["filter_by_layout_permission"];

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

if ($path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$P = $PEVC->getPresentationLayer();
		$selected_project_id = $P->getSelectedPresentationId();
		
		//PREPARING AVAILABLE BLOCKS
		$available_blocks_list = CMSPresentationLayerHandler::getAvailableBlocksList($PEVC, $selected_project_id);
		//echo "<pre>";print_r($available_blocks_list);die();
		
		if ($available_blocks_list && $filter_by_layout) {
			$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
			$LayoutTypeProjectHandler->filterPresentationLayerProjectsByUserAndLayoutPermissions($available_blocks_list, $filter_by_layout, $filter_by_layout_permission, array(
				"do_not_filter_by_layout" => array(
					"bean_name" => $bean_name,
					"bean_file_name" => $bean_file_name,
					"project" => $selected_project_id
				)
			));
		}
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
