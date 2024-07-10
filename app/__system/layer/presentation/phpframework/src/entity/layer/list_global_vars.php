<?php
include_once $EVC->getUtilPath("PHPVariablesFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$popup = $_GET["popup"];

if (isset($_POST["save"])) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
	
	$vars_name = $_POST["vars_name"];
	$vars_value = $_POST["vars_value"];
	
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
	
	if (PHPVariablesFileHandler::saveVarsToFile($user_global_variables_file_path, $global_variables, true)) 
		$status_message = "Variables saved successfully";
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
?>
