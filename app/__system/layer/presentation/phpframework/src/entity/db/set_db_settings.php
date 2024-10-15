<?php
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once $EVC->getUtilPath("PHPVariablesFileHandler");
include_once $EVC->getUtilPath("WorkFlowTasksFileHandler");
include_once $EVC->getUtilPath("WorkFlowDBHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = isset($_GET["layer_bean_folder_name"]) ? $_GET["layer_bean_folder_name"] : null;
$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;

$bean_path = BEAN_PATH . $bean_file_name;
$workflow_path_id = "layer";

if (!empty($bean_file_name) && file_exists($bean_path)) {
	$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	
	$db_settings_variables = array();
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($bean_path, $user_global_variables_file_path);
	$WorkFlowBeansFileHandler->init();
	$DBDriver = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	$db_settings = $WorkFlowBeansFileHandler->getDBSettings($bean_name, $db_settings_variables, isset($_POST["data"]) ? $_POST["data"] : null);
	$db_driver_type = isset($db_settings["type"]) ? $db_settings["type"] : null;
	//echo "<pre>";print_r($db_settings);print_r($db_settings_variables);print_r($_POST["data"]);die();
	
	//PREPARE POST EVENT
	if (isset($_POST["data"])) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		
		include_once $EVC->getUtilPath("WorkFlowDBHandler");
		$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
		
		$data_password = isset($_POST["data"]["password"]) ? $_POST["data"]["password"] : null;
		
		//check if DB credentials are valid here, before delete or create beans
		if ($WorkFlowDBHandler->isDBDriverSettingsValid($_POST["data"], false)) {
			//SAVES NEW CHANGES TO DBDRIVER BEANS FILE
			if ($WorkFlowBeansFileHandler->saveNodesBeans()) {
				if (WorkFlowTasksFileHandler::updateTaskProperties($workflow_paths_id[ $workflow_path_id ], $bean_name, $_POST["data"])) {
					if ($WorkFlowDBHandler->areTasksDBDriverBeanValid($workflow_paths_id[ $workflow_path_id ], false)) 
						$status_message = "Settings saved successfully";
					else
						$error_message = "DataBase connection issue. " . str_replace($data_password, "***", $WorkFlowDBHandler->getError());
				}
				else
					$error_message = "There was an error trying to save the DB task settings. Please try again...";
			}
			else
				$error_message = "There was an error trying to save db settings. Please try again...";
		}
		else
			$error_message = "DataBase settings are wrong. " . str_replace($data_password, "***", $WorkFlowDBHandler->getError());
	}
	
	//preparing db types
	$drivers_labels = DB::getAllDriverLabelsByType();
	$available_types_options = array();
	foreach ($drivers_labels as $type => $label)
		$available_types_options[] = array("value" => $type, "label" => $label);
	
	//preparing db extensions
	$drivers_extensions = DB::getAllExtensionsByType();
	$available_extensions_options = array();
	
	$db_driver_extension = isset($db_settings["extension"]) ? $db_settings["extension"] : null;
	$db_driver_type_extensions = isset($drivers_extensions[$db_driver_type]) ? $drivers_extensions[$db_driver_type] : null;
	
	if ($db_driver_type && is_array($db_driver_type_extensions))
		foreach ($db_driver_type_extensions as $idx => $enc)
			$available_extensions_options[] = array("value" => $enc, "label" => $enc . ($idx == 0 ? " - Default" : ""));
	
	if (!empty($db_driver_extension) && (!$db_driver_type_extensions || !in_array($db_driver_extension, $db_driver_type_extensions)))
		$available_extensions_options[] = array("value" => $db_driver_extension, "label" => $db_driver_extension . " - DEPRECATED");
	
	//preparing db encodings
	$drivers_encodings = DB::getAllDBCharsetsByType();
	$available_encodings_options = array(array("value" => "", "label" => "-- Default --"));
	
	$db_driver_encoding = isset($db_settings["encoding"]) ? $db_settings["encoding"] : null;
	$db_driver_type_encodings = isset($drivers_encodings[$db_driver_type]) ? $drivers_encodings[$db_driver_type] : null;
	
	if ($db_driver_type && is_array($db_driver_type_encodings))
		foreach ($db_driver_type_encodings as $enc => $label)
			$available_encodings_options[] = array("value" => $enc, "label" => $label);
	
	if ($db_driver_encoding && (!$db_driver_type_encodings || !array_key_exists($db_driver_encoding, $db_driver_type_encodings)))
		$available_encodings_options[] = array("value" => $db_driver_encoding, "label" => $db_driver_encoding . " - DEPRECATED");
	
	//preparing ignore db options
	$drivers_ignore_connection_options = DB::getAllIgnoreConnectionOptionsByType();
	$drivers_ignore_connection_options_by_extension = DB::getAllIgnoreConnectionOptionsByExtensionAndType();
	
	//print_r($db_settings_variables);
	//print_r($db_settings);
	//print_r($encodings);
	//print_r($nodes);
}
else
	$error = "There was an error trying to get the file '$bean_path'. Please try again...";
?>
