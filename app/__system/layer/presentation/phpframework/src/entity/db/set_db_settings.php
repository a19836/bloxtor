<?php
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once $EVC->getUtilPath("PHPVariablesFileHandler");
include_once $EVC->getUtilPath("WorkFlowTasksFileHandler");
include_once $EVC->getUtilPath("WorkFlowDBHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = $_GET["layer_bean_folder_name"];
$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];

$bean_path = BEAN_PATH . $bean_file_name;
$workflow_path_id = "layer";

if (!empty($bean_file_name) && file_exists($bean_path)) {
	$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	
	$db_settings_variables = array();
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($bean_path, $user_global_variables_file_path);
	$WorkFlowBeansFileHandler->init();
	$DBDriver = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	$db_settings = $WorkFlowBeansFileHandler->getDBSettings($bean_name, $db_settings_variables, $_POST["data"]);
	//echo "<pre>";print_r($db_settings);print_r($db_settings_variables);print_r($_POST["data"]);die();
	
	//PREPARE POST EVENT
	if (isset($_POST["data"])) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		
		include_once $EVC->getUtilPath("WorkFlowDBHandler");
		$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
		
		//check if DB credentials are valid here, before delete or create beans
		if ($WorkFlowDBHandler->isDBDriverSettingsValid($_POST["data"], false)) {
			//SAVES NEW CHANGES TO DBDRIVER BEANS FILE
			if ($WorkFlowBeansFileHandler->saveNodesBeans()) {
				if (WorkFlowTasksFileHandler::updateTaskProperties($workflow_paths_id[ $workflow_path_id ], $bean_name, $_POST["data"])) {
					if ($WorkFlowDBHandler->areTasksDBDriverBeanValid($workflow_paths_id[ $workflow_path_id ], false)) 
						$status_message = "Settings saved successfully";
					else
						$error_message = "DataBase connection issue. " . str_replace($_POST["data"]["password"], "***", $WorkFlowDBHandler->getError());
				}
				else
					$error_message = "There was an error trying to save the DB task settings. Please try again...";
			}
			else
				$error_message = "There was an error trying to save db settings. Please try again...";
		}
		else
			$error_message = "DataBase settings are wrong. " . str_replace($_POST["data"]["password"], "***", $WorkFlowDBHandler->getError());
	}
	
	//preparing db types
	$drivers_labels = DB::getAllDriverLabelsByType();
	$available_types_options = array();
	foreach ($drivers_labels as $type => $label)
		$available_types_options[] = array("value" => $type, "label" => $label);
	
	//preparing db extensions
	$drivers_extensions = DB::getAllExtensionsByType();
	$available_extensions_options = array();
	
	if ($db_settings["type"] && is_array($drivers_extensions[ $db_settings["type"] ]))
		foreach ($drivers_extensions[ $db_settings["type"] ] as $idx => $enc)
			$available_extensions_options[] = array("value" => $enc, "label" => $enc . ($idx == 0 ? " - Default" : ""));
	
	if ($db_settings["extension"] && (!$drivers_extensions[ $db_settings["type"] ] || !in_array($db_settings["extension"], $drivers_extensions[ $db_settings["type"] ])))
		$available_extensions_options[] = array("value" => $db_settings["extension"], "label" => $db_settings["extension"] . " - DEPRECATED");
	
	//preparing db encodings
	$drivers_encodings = DB::getAllDBCharsetsByType();
	$available_encodings_options = array(array("value" => "", "label" => "-- Default --"));
	
	if ($db_settings["type"] && is_array($drivers_encodings[ $db_settings["type"] ]))
		foreach ($drivers_encodings[ $db_settings["type"] ] as $enc => $label)
			$available_encodings_options[] = array("value" => $enc, "label" => $label);
	
	if ($db_settings["encoding"] && (!$drivers_encodings[ $db_settings["type"] ] || !array_key_exists($db_settings["encoding"], $drivers_encodings[ $db_settings["type"] ])))
		$available_encodings_options[] = array("value" => $db_settings["encoding"], "label" => $db_settings["encoding"] . " - DEPRECATED");
	
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
