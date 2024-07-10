<?php
include_once $EVC->getUtilPath("VideoTutorialHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$filter_by_layout = $_GET["filter_by_layout"]; //optional
$selected_layout_project = $_GET["selected_layout_project"]; //optional
$popup = $_GET["popup"];
$folder_to_filter = $_GET["folder_to_filter"];
$redirect_path = urldecode($_GET["redirect_path"]);
$redirect_path = $redirect_path ? $redirect_path : "admin";
//echo "redirect_path:$redirect_path";die();

$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons
$selected_layout_project = str_replace("../", "", $selected_layout_project);//for security reasons

//prepare some video tutorials
$admin_type = !empty($_COOKIE["admin_type"]) ? $_COOKIE["admin_type"] : "simple";
$tutorials = VideoTutorialHandler::getSimpleTutorials($project_url_prefix, $online_tutorials_url_prefix);
$filtered_tutorials = VideoTutorialHandler::filterTutorials($tutorials, $entity, $admin_type);

//get projects with logos
$layers_projects = CMSPresentationLayerHandler::getPresentationLayersProjectsFiles($user_global_variables_file_path, $user_beans_folder_path, false, false, -1, true, null, true);
$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path);
$LayoutTypeProjectHandler->filterPresentationLayersProjectsByUserAndLayoutPermissions($layers_projects, $filter_by_layout);
//echo "<pre>";print_r($layers_projects);die();

$projects_exists = false;
$selected_project_id = null;

if ($layers_projects)
	foreach ($layers_projects as $bean_name => $layer_props) {
		$layers_projects[$bean_name]["layer_bean_folder_name"] = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $layer_props["bean_file_name"], $bean_name, $user_global_variables_file_path);
		
		if (count($layer_props["projects"])) {
			$projects_exists = true;
			
			foreach ($layer_props["projects"] as $proj_id => $proj_props)
				if ($proj_props["path"] == LAYER_PATH . $selected_layout_project) {
					$selected_project_id = $proj_id;
					break;
				}
		}
	}
?>
