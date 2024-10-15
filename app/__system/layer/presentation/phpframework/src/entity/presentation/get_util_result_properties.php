<?php
//This file tries to find what are the possible attributes for the result object in a Resource Util class method. This is, based in a method name (like get, get, getAll, etc...), tries to read the inner code and get sql or business logic services or data-access rules or DB methods callls and get the correspondent table. From the table get the correspondent attributes which is probably the attributes of the returned object.

include_once $EVC->getUtilPath("CodeResultGuesser");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$class_path = isset($_GET["class_path"]) ? $_GET["class_path"] : null;
$class_name = isset($_GET["class_name"]) ? $_GET["class_name"] : null;
$method = isset($_GET["method"]) ? $_GET["method"] : null;
$db_driver = isset($_GET["db_driver"]) ? $_GET["db_driver"] : null;

$class_path = str_replace("../", "", $class_path);//for security reasons
$path = str_replace("../", "", $path);//for security reasons

if ($class_name && $method) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$P = $PEVC->getPresentationLayer();
		$layer_path = $P->getLayerPathSetting();
		$selected_project_id = $P->getSelectedPresentationId();
		$default_extension = "." . $P->getPresentationFileExtension();
		$folder_path = $layer_path . $selected_project_id . "/";
		
		//prepare class path
		if (!$class_path && preg_match("/ResourceUtil$/", $class_name))
			$class_path = $PEVC->getUtilPath("resource/$class_name");
		else
			$class_path = $folder_path . $class_path;
		
		//if class path doesn't exists, tries to find it
		if (!file_exists($class_path))
			$class_path = findsClassPath($folder_path, $class_name);
		
		if (file_exists($class_path)) {
			$code = PHPCodePrintingHandler::getFunctionCodeFromFile($class_path, $method, $class_name);
			//echo "$class_path:\n$code";die();
			
			if ($code) {
				$db_driver = $db_driver ? $db_driver : (isset($GLOBALS["default_db_driver"]) ? $GLOBALS["default_db_driver"] : null);
				
				$CodeResultGuesser = new CodeResultGuesser($P, $UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $project_url_prefix, $db_driver);
				$props = $CodeResultGuesser->getCodeResultAttributes($code);
			}
		}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
}

function findsClassPath($folder_path, $class_name) {
	if ($folder_path && is_dir($folder_path)) {
		$files = array_diff(scandir($folder_path), array('..', '.'));
		
		//check first the files
		foreach ($files as $file) {
			$file_path = $folder_path . $file;
			
			if (!is_dir($file_path) && pathinfo($file, PATHINFO_FILENAME) == $class_name)
				return $file_path;
		}
		
		//check then the folders
		foreach ($files as $file) {
			$file_path = $folder_path . $file;
			
			if (is_dir($file_path)) {
				$ret = findsClassPath($file_path . "/", $class_name);
				
				if ($ret)
					return $ret;
			}
		}
	}
	
	return null;
}
?>
