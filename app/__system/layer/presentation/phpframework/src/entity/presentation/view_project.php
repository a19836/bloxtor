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

include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$url = getProjectUrl($user_beans_folder_path, $user_global_variables_file_path);

function getProjectUrl($user_beans_folder_path, $user_global_variables_file_path) {
	$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
	$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
	$path = isset($_GET["path"]) ? $_GET["path"] : null;

	$query_string = isset($_GET["query_string"]) ? $_GET["query_string"] : null;
	$get_vars = isset($_GET["get_vars"]) ? $_GET["get_vars"] : null;
	
	$path = str_replace("../", "", $path);//for security reasons

	if ($path) {
		$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
		$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);

		if ($PEVC) {
			$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
			$PHPVariablesFileHandler->startUserGlobalVariables();
			
			$P = $PEVC->getPresentationLayer();
			$layer_path = $P->getLayerPathSetting();
			$selected_project_id = $P->getSelectedPresentationId();
			$extension = $P->getPresentationFileExtension();
			
			$project_url_prefix = getProjectUrlPrefix($PEVC, $selected_project_id);
			$project_url_prefix .= substr($project_url_prefix, -1) != "/" ? "/" : "";
			
			if (empty($P->settings["presentation_entities_path"]))
				launch_exception(new Exception("'PresentationLayer->settings[presentation_entities_path]' cannot be undefined!"));
			
			$project_url_suffix = substr($path, strlen($selected_project_id . $P->settings["presentation_entities_path"]));
			$project_url_suffix = file_exists($layer_path . $path) && !is_dir($layer_path . $path) ? substr($project_url_suffix, 0, strlen($project_url_suffix) - strlen($extension) - 1) : $project_url_suffix; //remove extension
			$project_url_suffix = substr($project_url_suffix, 0, 1) == "/" ? substr($project_url_suffix, 1) : $project_url_suffix;
			
			$PHPVariablesFileHandler->endUserGlobalVariables();
			
			$url = $project_url_prefix . $project_url_suffix;
			$query_string = $query_string ? $query_string : "";
			
			if ($get_vars) 
				foreach ($get_vars as $k => $v)
					 $query_string .= "&$k=$v";
			
			if ($query_string)
				$url .= (strpos($url, "?") !== false ? "&" : "?") . $query_string;
			
			return $url;
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
}

function getProjectUrlPrefix($EVC, $selected_project_id) {
	@include $EVC->getConfigPath("config", $selected_project_id); //config file may not exist
	
	return $project_url_prefix;
}
?>
