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

include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("VideoTutorialHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$active_tab = isset($_GET["active_tab"]) ? $_GET["active_tab"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null;
$filter_by_layout_permission = UserAuthenticationHandler::$PERMISSION_BELONG_NAME;

$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

//prepare some video tutorials
$admin_type = !empty($_COOKIE["admin_type"]) ? $_COOKIE["admin_type"] : "simple";
$tutorials = VideoTutorialHandler::getSimpleTutorials($project_url_prefix, $online_tutorials_url_prefix);
$filtered_tutorials = VideoTutorialHandler::filterTutorials($tutorials, $entity, $admin_type);

//prepare project_details and presentation brokers
$presentation_brokers = array();
$project_details = null;
$project_default_template = null;
$layer_bean_folder_name = null;

$layers = AdminMenuHandler::getLayers($user_global_variables_file_path);

$presentation_layers = isset($layers["presentation_layers"]) ? $layers["presentation_layers"] : null;
if (is_array($presentation_layers))
	foreach ($presentation_layers as $bn => $bfn) {
		$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bfn, $user_global_variables_file_path);
		$P = $WorkFlowBeansFileHandler->getBeanObject($bn);
		$presentation_broker_name = WorkFlowBeansFileHandler::getLayerObjFolderName($P);
		$layer_bean_folder_name = $presentation_broker_name . "/";
		
		if (substr($filter_by_layout, 0, strlen($layer_bean_folder_name)) == $layer_bean_folder_name) {
			$presentation_brokers[] = array($presentation_broker_name, $bfn, $bn);
			$proj_name = substr($filter_by_layout, strlen($layer_bean_folder_name));
			
			if (empty($P->settings["presentation_entities_path"]))
				launch_exception(new Exception("'PresentationLayer->settings[presentation_entities_path]' cannot be undefined!"));
			
			if (empty($P->settings["presentation_webroot_path"]))
				launch_exception(new Exception("'PresentationLayer->settings[presentation_webroot_path]' cannot be undefined!"));
			
			$layer_path = $P->getLayerPathSetting();
			$is_project = $proj_name ? is_dir($layer_path . $proj_name . "/" . $P->settings["presentation_webroot_path"]) : false;
			
			if ($is_project) {
				$bean_name = $bn;
				$bean_file_name = $bfn;
				
				$project_details = CMSPresentationLayerHandler::getPresentationLayerProjectFiles($user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name, $layer_path, $proj_name, "", false, 0, true);
				
				$project_id = isset($project_details["element_type_path"]) ? $project_details["element_type_path"] : null;
				$project_id = preg_replace("/^[\/]+/", "", $project_id); //remove start /
				$project_id = preg_replace("/[\/]+$/", "", $project_id); //remove end /
				$project_details["project_id"] = $project_id;
				
				//available templates
				$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $project_id);
				
				$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
				$PHPVariablesFileHandler->startUserGlobalVariables();
				
				$default_extension = "." . $P->getPresentationFileExtension();
				
				$available_templates = CMSPresentationLayerHandler::getAvailableTemplatesList($PEVC, $default_extension);
				$available_templates = array_keys($available_templates);
				$available_templates_props = CMSPresentationLayerHandler::getAvailableTemplatesProps($PEVC, $project_id, $available_templates);
				
				//default template
				$project_default_template = isset($GLOBALS["project_default_template"]) ? $GLOBALS["project_default_template"] : null;
				
				//num_of_created_pages
				$files = CMSPresentationLayerHandler::getFolderFilesTree($layer_path, $layer_path . $proj_name . "/" . $P->settings["presentation_entities_path"], false, -1);
				$num_of_created_pages = countProjectPages($files);
				$is_fresh_project = $num_of_created_pages <= 1; //by default all projects have the index.php page
				//echo "num_of_created_pages:$num_of_created_pages\n<br>is_fresh_project:$is_fresh_project";die();
				
				$PHPVariablesFileHandler->endUserGlobalVariables();
			}
		}
	}
//echo "project_id:$project_id<br>\n project_default_template:$project_default_template";die();
//echo "<pre>";print_r($presentation_brokers);die();
//echo "<pre>";print_r($project_details);die();
//echo "<pre>";print_r($available_templates_props);die();

function countProjectPages($files) {
	$num_of_created_pages = 0;
	
	if ($files)
		foreach ($files as $file_name => $file_props) {
			$file_type = isset($file_props["type"]) ? $file_props["type"] : null;
			
			if ($file_type == "php_file")
				$num_of_created_pages++;
			else if ($file_type == "folder" && isset($file_props["sub_files"]))
				$num_of_created_pages += countProjectPages($file_props["sub_files"]);
		}
	
	return $num_of_created_pages;
}
?>
