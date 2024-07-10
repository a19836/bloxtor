<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("WorkFlowTestUnitHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$popup = $_GET["popup"];
$on_success_js_func = $_GET["on_success_js_func"];

$path = str_replace("../", "", $path);//for security reasons

if ($bean_name && $bean_file_name && $path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$P = $PEVC->getPresentationLayer();
		$layer_path = $P->getLayerPathSetting();
		$selected_project_id = $P->getSelectedPresentationId();
		
		$file_path = $layer_path . $path;
		
		if (file_exists($file_path)) {
			$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($file_path, "layer", "access");
			
			$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
			
			//checks if path is a project
			if (!$LayoutTypeProjectHandler->isPathAPresentationProjectPath($file_path))
				$error_message = "Error: This path is not a presentation project! Only presentation project paths are allowed!";
			else if ($LayoutTypeProjectHandler->existsLayoutFromProjectPath($file_path) || $LayoutTypeProjectHandler->createNewLayoutFromProjectPath($file_path, false)) { //creates new layout type if not exists
				$layout_type_data = $LayoutTypeProjectHandler->getLayoutFromProjectPath($file_path);
					
				if ($layout_type_data && $layout_type_data["layout_type_id"]) {
					//echo "<pre>";print_r($layout_type_data);die();
					$layout_type_id = $layout_type_data["layout_type_id"];
					
					//save new references
					if ($_POST) {
						$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
						
						$permissions_by_objects = $_POST["permissions_by_objects"];
						//echo "<pre>";print_r($permissions_by_objects);die();
						
						if ($layout_type_id && $UserAuthenticationHandler->updateLayoutTypePermissionsByObjectsPermissions($layout_type_id, $permissions_by_objects))
							$status_message = "Layout Type Permissions were saved correctly";
						else
							$error_message = "There was an error trying to save the layout type permissions. Please try again...";
					}
					
					//preparing UI
					$permissions = $UserAuthenticationHandler->getAvailablePermissions();
					$object_types = $UserAuthenticationHandler->getAvailableObjectTypes();
					$layer_object_type_id = $object_types["layer"];
					
					//preparing UI - preparing layers
					$raw_layers = getLayers($EVC, $UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path);
					unset($raw_layers["others"]);
					unset($raw_layers["vendors"]);
					unset($raw_layers["db_layers"]); //references don't contain the db layers
					//echo "<pre>";print_r($raw_layers);die();
					
					$layer_object_id_prefix = str_replace(APP_PATH, "", LAYER_PATH);
					$layer_object_id_prefix = substr($layer_object_id_prefix, -1) == "/" ? substr($layer_object_id_prefix, 0, -1) : $layer_object_id_prefix;

					$layers = array();
					$layers_label = array();
					$layers_object_id = array();
					$layers_props = array();
					$layers_to_show = array("presentation_layers", "business_logic_layers", "data_access_layers", "db_layers");
					$presentation_projects = array();

					foreach ($layers_to_show as $layer_type_name) {
						$layer_type = $raw_layers[$layer_type_name];
						
						if ($layer_type) //filter by layers
							foreach ($layer_type as $layer_name => $layer) {
								$lln = strtolower($layer_name);
								$layers[$layer_type_name][$lln] = array();
								$layers_label[$layer_type_name][$lln] = isset($layer["properties"]["item_label"]) ? $layer["properties"]["item_label"] : $lln;
								$layers_object_id[$layer_type_name][$lln] = $layer["properties"]["layer_bean_folder_name"];
								$layers_props[$layer_type_name][$lln] = $layer["properties"];
							}
					}
					//echo "<pre>";print_r($layers);die();

					$layers_to_be_referenced = $layers;
					
					//preparing UI - preparing brokers
					$layer_brokers_settings = WorkFlowTestUnitHandler::getAllLayersBrokersSettings($user_global_variables_file_path, $user_beans_folder_path);
					$presentation_brokers = $layer_brokers_settings["presentation_brokers"];
					$business_logic_brokers = $layer_brokers_settings["business_logic_brokers"];
					$data_access_brokers = $layer_brokers_settings["data_access_brokers"];
				}
				else
					$error_message = "Error trying to get layout type for this project. Please try again...";
			}
			else
				$error_message = "Error trying to create new layout type for this project. Please try again...";
		}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
}

function getLayers($EVC, $UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path) {
	include $EVC->getUtilPath("admin_uis_layers_and_permissions");
	
	return $layers;
}
?>
