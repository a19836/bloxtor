<?php
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$url = getProjectUrl($user_beans_folder_path, $user_global_variables_file_path);

function getProjectUrl($user_beans_folder_path, $user_global_variables_file_path) {
	$bean_name = $_GET["bean_name"];
	$bean_file_name = $_GET["bean_file_name"];
	$path = $_GET["path"];

	$query_string = $_GET["query_string"];
	$get_vars = $_GET["get_vars"];
	
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
