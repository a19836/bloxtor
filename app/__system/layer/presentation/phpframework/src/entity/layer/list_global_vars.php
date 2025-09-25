<?php
include_once $EVC->getUtilPath("PHPVariablesFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;

if (isset($_POST["save"])) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
	
	$vars_name = isset($_POST["vars_name"]) ? $_POST["vars_name"] : null;
	$vars_value = isset($_POST["vars_value"]) ? $_POST["vars_value"] : null;
	
	$global_variables = array();
	
	if ($vars_name) {
		$t = count($vars_name);
		for($i = 0; $i < $t; $i++) {
			$var_name = $vars_name[$i];
			$var_value = $vars_value[$i];
			$var_value_lower = strtolower($var_value);
			
			if ($var_value_lower == "true")
				$var_value = true;
			else if ($var_value_lower == "false")
				$var_value = false;
			else if ($var_value_lower == "null")
				$var_value = null;
			
			$global_variables[$var_name] = $var_value;
		}
	}
	
	//check if the old global_variables are being used in the diagram and in the bean xml files. And if yes, doesn't let delete that variables.
	$old_vars = PHPVariablesFileHandler::getVarsFromFileContent($user_global_variables_file_path);
	$removed_vars = array_diff_key($old_vars, $global_variables);
	
	$workflow_path_id = "layer";
	$used_vars = getUsedVarsInSettings($removed_vars, $workflow_paths_id[ $workflow_path_id ], $user_beans_folder_path);
	
	if ($used_vars)
		$global_variables = array_merge($global_variables, $used_vars);
	
	if (PHPVariablesFileHandler::saveVarsToFile($user_global_variables_file_path, $global_variables, true)) {
		$status_message = "Variables saved successfully.";
		
		if ($used_vars)
			$status_message .= "<br/>However the following vars were not removed,<br/>because they are still being used:<ul><li>- " . implode("</li><li>- ", array_keys($used_vars)) . "</li></ul>";
	}
	else
		$error_message = "There was an error trying to save variables. Please try again...";
}

$vars = PHPVariablesFileHandler::getVarsFromFileContent($user_global_variables_file_path);
//echo "<pre>";print_r($vars);die();

foreach ($vars as $var_name => $var_value) {
	if ($var_value === true)
		$vars[$var_name] = "true";
	else if ($var_value === false)
		$vars[$var_name] = "false";
	else if ($var_value === null)
		$vars[$var_name] = "null";
}

function getUsedVarsInSettings($vars, $tasks_file_path, $user_beans_folder_path) {
	$used_vars = array();
	
	if ($vars) {
		$layers_diagram_content = $tasks_file_path && file_exists($tasks_file_path) ? file_get_contents($tasks_file_path) : null;
		$beans = is_dir($user_beans_folder_path) ? array_diff(scandir($user_beans_folder_path), array('..', '.')) : null;
		
		if ($beans)
			foreach ($beans as $file)
				if (substr($file, -4) == ".xml")
					$layers_diagram_content .= file_get_contents($user_beans_folder_path . $file);
		
		foreach ($vars as $var_name => $var_value) {
			//if ($layers_diagram_content && strpos($layers_diagram_content, '$' . $var_name))
			if ($layers_diagram_content && (
				preg_match("/[$]" . $var_name . "([<\]])/", $layers_diagram_content)
				 || 
				preg_match("/[$]GLOBALS\[('|\")" . $var_name . "('|\")\]/", $layers_diagram_content)
			))
				$used_vars[$var_name] = $var_value;
		}
	}
	
	return $used_vars;
}
?>
