<?php
include_once $EVC->getUtilPath("WorkFlowDBHandler");
include_once $EVC->getUtilPath("FlushCacheHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");			
			
$workflow_path_id = "layer";
$tasks_file_path = $workflow_paths_id[$workflow_path_id];

$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);

if (isset($_POST["data"])) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
	
	if ($_POST["data"]["db_name"])
		$_POST["data"]["db_name"] = str_replace(" ", "_", strtolower($_POST["data"]["db_name"]));
	
	//PREPARE TASKS WORKFLOW
	$content = file_get_contents($EVC->getPresentationLayer()->getSelectedPresentationSetting("presentation_webroot_path") . "/assets/default_layers_workflow_" . (!empty($_POST["data"]["db_type"]) ? "with" : "without") . "_db.xml");
	
	$content = str_replace("\$db_type", $_POST["data"]["db_type"], $content);
	$content = str_replace("\$driver_label", $_POST["data"]["db_type"], $content);
	$content = str_replace("\$db_extension", $_POST["data"]["db_extension"], $content);
	$content = str_replace("\$db_host", $_POST["data"]["db_host"], $content);
	$content = str_replace("\$db_port", $_POST["data"]["db_port"], $content);
	$content = str_replace("\$db_name", $_POST["data"]["db_name"], $content);
	$content = str_replace("\$db_username", $_POST["data"]["db_username"], $content);
	$content = str_replace("\$db_password", $_POST["data"]["db_password"], $content);
	$content = str_replace("\$db_persistent", $_POST["data"]["db_persistent"] ? $_POST["data"]["db_persistent"] : 0, $content);
	$content = str_replace("\$db_new_link", $_POST["data"]["db_new_link"] ? $_POST["data"]["db_new_link"] : 0, $content);
	$content = str_replace("\$db_encoding", $_POST["data"]["db_encoding"], $content);
	$content = str_replace("\$db_schema", $_POST["data"]["db_schema"], $content);
	$content = str_replace("\$db_odbc_data_source", $_POST["data"]["db_odbc_data_source"], $content);
	$content = str_replace("\$db_odbc_driver", $_POST["data"]["db_odbc_driver"], $content);
	$content = str_replace("\$db_extra_dsn", $_POST["data"]["db_extra_dsn"], $content);
	
	$folder = dirname($tasks_file_path);
	
	if (is_dir($folder) || mkdir($folder, 0775, true)) {
		if (file_put_contents($tasks_file_path, $content)) {
			//PREPARE GLOBAL VARIABLES
			$post_global_vars = array("default_db_driver" => "");
				
			//Only creates these global variables if is a new file, otherwise these variables and others already exist and we MUST NOT overwrite them! If new file, check first if DB credentials are valid here.
			if ((file_exists($user_global_variables_file_path) && filesize($user_global_variables_file_path) > 0) ||	PHPVariablesFileHandler::saveVarsToFile($user_global_variables_file_path, $post_global_vars, true)) {
				//check if DB credentials are valid here, before delete or create beans
				if ($WorkFlowDBHandler->areTasksDBDriverSettingsValid($tasks_file_path, true)) {
					//PREPARE FOLDERS AND BEANS
					$WorkFlowBeansConverter = new WorkFlowBeansConverter($tasks_file_path, $user_beans_folder_path, $user_global_variables_file_path, $user_global_settings_file_path);
					$WorkFlowBeansConverter->init();
					
					if ($WorkFlowBeansConverter->createBeans()) {
						//add layout type if it comes from setup
						$continue = true;
						
						if (!$WorkFlowBeansConverter->createSetupDefaultProjectLayouts($UserAuthenticationHandler)) {
							$continue = false;
							$error_message = "Error trying to create the default project layout types. Please try again...";
						}
						
						//flush cache - must be after the code: '$WorkFlowBeansConverter->createSetupDefaultProjectLayouts()'
						FlushCacheHandler::flushCache($EVC, $webroot_cache_folder_path, $webroot_cache_folder_url, $workflow_paths_id, $user_global_variables_file_path, $user_beans_folder_path, $css_and_js_optimizer_webroot_cache_folder_path, $deployments_temp_folder_path);
						
						//check if db drivers are valid
						if ($WorkFlowDBHandler->areTasksDBDriverBeanValid($tasks_file_path, true)) {
							if ($continue) {
								if ($is_inside_of_iframe)
									echo '<script>
										var url = window.top.location;
										window.top.location = url;
										//window.parent.location = url;
									</script>';
								else {
									header("location: ?step=4");
									echo '<script>window.location = "?step=4"</script>';
								}
							
								die();
							}
						}
						else
							$error_message = "DataBase connection issue. " . str_replace($_POST["data"]["db_password"], "***",$WorkFlowDBHandler->getError());
					}
					else
						$error_message = "Error trying to create some folders. Please try again or talk with the system administrator.";
				}
				else
					$error_message = "DataBase settings are wrong. " . str_replace($_POST["data"]["db_password"], "***",$WorkFlowDBHandler->getError());
			}
			else
				$error_message = "There was an error saving the DB settings. Please try again...";
		}
		else
			$error_message = "There was an error saving the DB settings in the layers diagram. Please try again...";
	}
	else
		$error_message = "There was an error creating the layers diagram folder. Please try again...";
}

$diagram_already_exists = file_exists($tasks_file_path);

//set default data
$data = $diagram_already_exists ? $WorkFlowDBHandler->getFirstTaskDBDriverCredentials($tasks_file_path, "db_") : array();
if (isset($data["db_db_name"])) {
	$data["db_name"] = $data["db_db_name"];
	unset($data["db_db_name"]);
}

if (!isset($data["db_type"])) {
	$data["db_type"] = isset($_POST["data"]["db_type"]) ? $_POST["data"]["db_type"] : "";
}

//print_r($data);die();

if (!isset($data["db_encoding"])) {
	$data["db_encoding"] = isset($_POST["data"]["db_encoding"]) ? $_POST["data"]["db_encoding"] : "utf8";
}

//preparing db types
$all_driver_labels = DB::getAllDriverLabelsByType();
$available_db_types = array(array("value" => "", "label" => "I do NOT need a DataBase"));

foreach ($all_driver_labels as $type => $label)
	$available_db_types[] = array("value" => $type, "label" => "$label DataBase");

//preparing db extensions
$drivers_extensions = DB::getAllExtensionsByType();
$available_extensions_options = array();

if ($data["db_type"] && is_array($drivers_extensions[ $data["db_type"] ]))
	foreach ($drivers_extensions[ $data["db_type"] ] as $idx => $enc)
		$available_extensions_options[] = array("value" => $enc, "label" => $enc . ($idx == 0 ? " - Default" : ""));

if ($data["db_extension"] && (!$drivers_extensions[ $data["db_type"] ] || !in_array($data["db_extension"], $drivers_extensions[ $data["db_type"] ])))
	$available_extensions_options[] = array("value" => $data["db_extension"], "label" => $data["db_extension"] . " - DEPRECATED");

//preparing db encodings
$drivers_encodings = DB::getAllDBCharsetsByType();
$available_encodings_options = array(array("value" => "", "label" => "-- Default --"));

if ($data["db_type"] && is_array($drivers_encodings[ $data["db_type"] ]))
	foreach ($drivers_encodings[ $data["db_type"] ] as $enc => $label)
		$available_encodings_options[] = array("value" => $enc, "label" => $label);

if ($data["db_encoding"] && (!$drivers_encodings[ $data["db_type"] ] || !array_key_exists($data["db_encoding"], $drivers_encodings[ $data["db_type"] ])))
	$available_encodings_options[] = array("value" => $data["db_encoding"], "label" => $data["db_encoding"] . " - DEPRECATED");

//preparing ignore db options
$drivers_ignore_connection_options = DB::getAllIgnoreConnectionOptionsByType();
$drivers_ignore_connection_options_by_extension = DB::getAllIgnoreConnectionOptionsByExtensionAndType();

//check if already passed through the setup. If it did, it means that app/config/bean/app.xml exists
$already_did_setup = file_exists(CONFIG_PATH . "bean/app.xml");

?>
