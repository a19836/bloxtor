<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");
$choose_available_project_url = "{$project_url_prefix}admin/choose_available_project?redirect_path=admin";

if (!empty($_GET["bean_name"])) {
	$bean_name = $_GET["bean_name"];
	CookieHandler::setCurrentDomainEternalRootSafeCookie("selected_bean_name", $bean_name);
}
else if (!empty($_COOKIE["selected_bean_name"]))
	$bean_name = $_COOKIE["selected_bean_name"];

if (!empty($_GET["bean_file_name"])) {
	$bean_file_name = $_GET["bean_file_name"];
	CookieHandler::setCurrentDomainEternalRootSafeCookie("selected_bean_file_name", $bean_file_name);
}
else if (!empty($_COOKIE["selected_bean_file_name"]))
	$bean_file_name = $_COOKIE["selected_bean_file_name"];

if (!empty($_GET["project"])) {
	$project = $_GET["project"];
	$project = preg_replace("/[\/]+/", "/", $project); //remove duplicated "/"
	$project = preg_replace("/^\//", "", $project); //remove first "/"
	$project = preg_replace("/\/$/", "", $project); //remove last "/"
	
	CookieHandler::setCurrentDomainEternalRootSafeCookie("selected_project", $project);
}
else if (!empty($_COOKIE["selected_project"]))
	$project = $_COOKIE["selected_project"];

if (empty($bean_name) || empty($bean_file_name) || empty($project)) {
	header("Location: $choose_available_project_url");
	echo "<script>document.location = '$choose_available_project_url';</script>";
	die();
}

//preparing filter_by_layout
$layers_beans = AdminMenuHandler::getLayers($user_global_variables_file_path);
//echo "<pre>";print_r($layers_beans);die();

if ($bean_name && $bean_file_name && $project && $layers_beans && !empty($layers_beans["presentation_layers"][$bean_name]) && $layers_beans["presentation_layers"][$bean_name] == $bean_file_name) {
	$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bean_file_name, $bean_name, $user_global_variables_file_path);
	
	$filter_by_layout = "$layer_bean_folder_name/" . preg_replace("/\/+$/", "", $project); //remove last slash from $path
	$filter_by_layout_permission = UserAuthenticationHandler::$PERMISSION_BELONG_NAME;
}

//preparing available layers
//Note that if a project doesn't have any layout_type_permission created, the presentation and DB layers should not be removed from the $layers variable. This is the "Citizen UI" and is for a specific project, so even if there isn't a layout_type_permissions defined, it should always show the correspondent presentation layer and default DB layer.
$filter_layout_by_layers_type = array("presentation_layers", "business_logic_layers", "data_access_layers"/*, "db_layers"*/);
$do_not_filter_by_layout = array(
	"bean_name" => $bean_name,
	"bean_file_name" => $bean_file_name,
	"project" => $project
);
include $EVC->getUtilPath("admin_uis_layers_and_permissions");
//echo "<pre>";print_r($layers);die();

//getting project default db driver and available projects
$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $project);
$P = $PEVC->getPresentationLayer();
$projects = null;

if (!empty($layers["presentation_layers"]))
	foreach ($layers["presentation_layers"] as $layer_name => $layer)
		if (isset($layer["properties"]["bean_name"]) && $layer["properties"]["bean_name"] == $bean_name && isset($layer["properties"]["bean_file_name"]) && $layer["properties"]["bean_file_name"] == $bean_file_name) {
			$projects = $presentation_projects[$layer_name];
			//echo "<pre>";print_r($projects);die();
			break;
		}
	
//if project doesn't exist, forward it to choose project.
if (!$projects || empty($projects[$project])) {
	header("Location: $choose_available_project_url");
	echo "<script>document.location='$choose_available_project_url';</script>";
	die();
}
else { //checks if util path exists so it can show the Actions menu
	$util_path = $PEVC->getUtilsPath($project);
	$util_exists = $util_path && is_dir($util_path);
	//echo "util_exists:$util_exists:$util_path";die();
}

//organize projects into sub_folders
if ($projects) {
	$new_projects = array();
	
	foreach ($projects as $fp => $project_props) {
		$fp = preg_replace("/[\/]+/", "/", $fp); //remove duplicated "/"
		$fp = preg_replace("/^\//", "", $fp); //remove first "/"
		$fp = preg_replace("/\/$/", "", $fp); //remove last "/"
		
		$dirs = explode("/", $fp);
		$file_name = array_pop($dirs);
		$obj = &$new_projects;
		
		foreach ($dirs as $dir) {
			if (!isset($obj[$dir]))
				$obj[$dir] = array();
			
			$obj = &$obj[$dir];
		}
		
		if ($project == $fp)
			$project_props["is_selected"] = true;
		
		$project_props["is_project"] = true;
		$obj[$file_name] = $project_props;
	}
	
	$projects = $new_projects;
	//echo "<pre>";print_r($projects);die();
}

//get the selected DB for the selected project and check if is allowed
if (!empty($layers["db_layers"])) {
	//echo "<pre>";print_r($layers["db_layers"]);die();
	
	//get project default db driver
	$pres_db_drivers = WorkFlowBeansFileHandler::getLayerDBDrivers($user_global_variables_file_path, $user_beans_folder_path, $P, true);
	$pres_db_drivers_bn = array_keys($pres_db_drivers);
	
	$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
	$PHPVariablesFileHandler->startUserGlobalVariables();
	
	$db_driver_broker_name = !empty($GLOBALS["default_db_driver"]) ? $GLOBALS["default_db_driver"] : (isset($pres_db_drivers_bn[0]) ? $pres_db_drivers_bn[0] : null);
	
	$PHPVariablesFileHandler->endUserGlobalVariables();
	
	$db_driver_props = isset($pres_db_drivers[$db_driver_broker_name]) ? $pres_db_drivers[$db_driver_broker_name] : null;
	//echo "<pre>";print_r($db_driver_props);die();
	
	//check if project default db driver is allowed
	if ($db_driver_broker_name && $db_driver_props) {
		//get broker name for correspondent db data layer. Althought the $db_data_layer be the name of the broker
		$db_data_layer = WorkFlowBeansFileHandler::getLayerLocalDBBrokerNameForChildBrokerDBDriver($user_global_variables_file_path, $user_beans_folder_path, $P, $db_driver_broker_name, $found_broker_obj, $found_broker_props);
		//echo "<pre>db_data_layer:$db_data_layer<br>";print_r($found_broker_props);die();
		
		//if layer exists
		if ($db_data_layer) {
			$is_db_layer_allowed = false;
			
			//find 
			foreach ($layers["db_layers"] as $layer_name => $layer) {
				$db_data_bean_name = isset($layer["properties"]["bean_name"]) ? $layer["properties"]["bean_name"] : null;
				$db_data_bean_file_name = isset($layer["properties"]["bean_file_name"]) ? $layer["properties"]["bean_file_name"] : null;
				
				$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $db_data_bean_file_name, $user_global_variables_file_path);
				$db_data_obj = $WorkFlowBeansFileHandler->getBeanObject($db_data_bean_name);
				$db_data_layer_name = WorkFlowBeansFileHandler::getLayerNameFromBeanObject($db_data_bean_name, $db_data_obj);
				$db_data_broker_name = WorkFlowBeansConverter::getBrokerNameFromRawLabel($db_data_layer_name);
				
				if ($db_data_broker_name == $db_data_layer) {
					if (!empty($layer[$db_driver_props[2]]))
						$is_db_layer_allowed = true;
					else {
						$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerObjFolderName($db_data_obj);
						$layer_object_id = LAYER_PATH . $layer_bean_folder_name;
						
						$fn_layer_object_id = "$layer_object_id/" . (isset($db_driver_props[2]) ? $db_driver_props[2] : null);
						//echo "layer_object_id:$layer_object_id";die();
						//echo "fn_layer_object_id:$fn_layer_object_id";die();
						$is_db_layer_allowed = $UserAuthenticationHandler->isInnerFilePermissionAllowed($fn_layer_object_id, "layer", "access");
					}
					break;
				}
			}
			
			if ($is_db_layer_allowed) {
				$db_driver_bean_name = isset($db_driver_props[2]) ? $db_driver_props[2] : null;
				$db_driver_bean_file_name = isset($db_driver_props[1]) ? $db_driver_props[1] : null;
				$db_driver_layer_bean_name = isset($db_data_bean_name) ? $db_data_bean_name : null;
				$db_driver_layer_bean_file_name = isset($db_data_bean_file_name) ? $db_data_bean_file_name : null;
				$db_driver_layer_folder_name = isset($layer_bean_folder_name) ? $layer_bean_folder_name : null;
				
				$BeanFactory = new BeanFactory();
				$BeanFactory->init(array("file" => $user_beans_folder_path . $db_driver_layer_bean_file_name));
				$bean = $BeanFactory->getBean($db_driver_layer_bean_name);
				$bean_objs = AdminMenuHandler::getBeanDBObjs($bean, $BeanFactory->getBeans(), $BeanFactory->getObjects()); 
				//echo "<pre>";print_r($bean_objs[$db_driver_bean_name]);die();
				
				$menu_item_properties = array(
					$db_driver_bean_name => isset($bean_objs[$db_driver_bean_name]["properties"]["item_menu"]) ? $bean_objs[$db_driver_bean_name]["properties"]["item_menu"] : null
				);
			}
		}
	}
}
?>
