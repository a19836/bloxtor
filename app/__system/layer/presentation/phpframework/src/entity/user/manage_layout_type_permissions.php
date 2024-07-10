<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("WorkFlowTestUnitHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$type_id = $_GET["type_id"];
$layout_type_id = $_GET["layout_type_id"];

if ($_POST) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

	$layout_type_id = $_POST["layout_type_id"];
	$permissions_by_objects = $_POST["permissions_by_objects"];
	
	if ($layout_type_id && $UserAuthenticationHandler->updateLayoutTypePermissionsByObjectsPermissions($layout_type_id, $permissions_by_objects))
		$status_message = "Layout Type Permissions were saved correctly";
	else
		$error_message = "There was an error trying to save the layout type permissions. Please try again...";
}

$available_types = UserAuthenticationHandler::$AVAILABLE_LAYOUTS_TYPES;
$type_id = is_numeric($type_id) ? $type_id : key($available_types);
//echo "<pre>";print_r($available_types);die();

$layout_types = $UserAuthenticationHandler->getAvailableLayoutTypes($type_id);
ksort($layout_types);

$permissions = $UserAuthenticationHandler->getAvailablePermissions();
$object_types = $UserAuthenticationHandler->getAvailableObjectTypes();
$layer_object_type_id = $object_types["layer"];

//Preparing layers
$raw_layers = AdminMenuHandler::getLayersFiles($user_global_variables_file_path);
unset($raw_layers["others"]);
unset($raw_layers["vendors"]);

$layer_object_id_prefix = str_replace(APP_PATH, "", LAYER_PATH);
$layer_object_id_prefix = substr($layer_object_id_prefix, -1) == "/" ? substr($layer_object_id_prefix, 0, -1) : $layer_object_id_prefix;

$layers = array();
$layers_label = array();
$layers_object_id = array();
$layers_props = array();
$layers_to_show = array("presentation_layers", "business_logic_layers", "data_access_layers", "db_layers");
$presentation_projects = array();
$presentation_projects_by_folders = array();

foreach ($layers_to_show as $layer_type_name) {
	$layer_type = $raw_layers[$layer_type_name];
	
	if ($layer_type) //filter by layers
		foreach ($layer_type as $layer_name => $layer) {
			$lln = strtolower($layer_name);
			$layers[$layer_type_name][$lln] = array();
			$layers_label[$layer_type_name][$lln] = isset($layer["properties"]["item_label"]) ? $layer["properties"]["item_label"] : $lln;
			$layers_object_id[$layer_type_name][$lln] = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $layer["properties"]["bean_file_name"], $layer["properties"]["bean_name"], $user_global_variables_file_path);
			$layers_props[$layer_type_name][$lln] = $layer["properties"];
			
			if ($layer_type_name == "db_layers") {
				foreach ($layer as $driver_name => $driver) 
					if ($driver_name != "properties" && $driver_name != "aliases")
						$layers[$layer_type_name][$lln][$driver_name] = array();
			}
			else if ($layer_type_name == "presentation_layers" && $type_id == 0 && $layout_types) {
				//prepare presentation layers projects
				$projects = CMSPresentationLayerHandler::getPresentationLayerProjectsFiles($user_global_variables_file_path, $user_beans_folder_path, $layer["properties"]["bean_file_name"], $layer["properties"]["bean_name"]);
				$projs = array();
				$projs_by_folders = array();
				
				if ($projects)
					foreach ($projects as $project_name => $project_props) {
						$proj_id = $layers_object_id[$layer_type_name][$lln] . "/$project_name";
						
						if ($layout_types[$proj_id]) {
							$lt_id = $layout_types[$proj_id];
							$projs[$lt_id] = $project_name;
							
							unset($layout_types[$proj_id]);
							
							//organize projects into sub_folders
							$dirs = explode("/", $project_name);
							$file_name = array_pop($dirs);
							$obj = &$projs_by_folders;
							
							foreach ($dirs as $dir) {
								if (!isset($obj[$dir]))
									$obj[$dir] = array();
								
								$obj = &$obj[$dir];
							}
							
							$obj[$file_name] = $lt_id;
						}
					}
				
				$layer_label = $layers_label[$layer_type_name][$lln];
				$presentation_projects[$layer_label] = $projs;
				$presentation_projects_by_folders[$layer_label] = $projs_by_folders;
			}
		}
}
//echo "<pre>";print_r($presentation_projects);print_r($presentation_projects_by_folders);die();
//echo "<pre>";print_r($layers);die();

$layers_to_be_referenced = $layers;
unset($layers_to_be_referenced["db_layers"]);

$layer_brokers_settings = WorkFlowTestUnitHandler::getAllLayersBrokersSettings($user_global_variables_file_path, $user_beans_folder_path);
$presentation_brokers = $layer_brokers_settings["presentation_brokers"];
$business_logic_brokers = $layer_brokers_settings["business_logic_brokers"];
$data_access_brokers = $layer_brokers_settings["data_access_brokers"];

//echo "<pre>";print_r($layer_brokers_settings);die();
//echo "<pre>";print_r($presentation_brokers);die();
//echo "<pre>";print_r($layout_types);die();
//echo "<pre>";print_r($raw_layers);die();
//echo "<pre>";print_r($layers);die();
//echo "<pre>";print_r($layers_label);die();
//echo "<pre>";print_r($layers_object_id);die();
//echo "<pre>";print_r($layers_props);die();
?>
