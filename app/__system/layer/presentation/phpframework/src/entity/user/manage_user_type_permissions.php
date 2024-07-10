<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

if ($_POST) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

	$user_type_id = $_POST["user_type_id"];
	$permissions_by_objects = $_POST["permissions_by_objects"];
	
	if ($user_type_id && $UserAuthenticationHandler->updateUserTypesByObjectsPermissions($user_type_id, $permissions_by_objects))
		$status_message = "User Type Permissions were saved correctly";
	else
		$error_message = "There was an error trying to save the user type permissions. Please try again...";
}

$user_types = $UserAuthenticationHandler->getAvailableUserTypes();
$permissions = $UserAuthenticationHandler->getAvailablePermissions();
$object_types = $UserAuthenticationHandler->getAvailableObjectTypes();
$page_object_type_id = $object_types["page"];
$layer_object_type_id = $object_types["layer"];
$admin_ui_object_type_id = $object_types["admin_ui"];

//preparing permissions - removing permissions correspondent to project_types
unset($permissions[UserAuthenticationHandler::$PERMISSION_BELONG_NAME]);
unset($permissions[UserAuthenticationHandler::$PERMISSION_REFERENCED_NAME]);

//Preparing pages
$projects = $EVC->getProjectsId();

$reserved_files = array("__system/presentation/phpframework/src/entity/auth/logout.php", "__system/presentation/phpframework/src/entity/auth/login.php", "__system/presentation/phpframework/src/entity/auth/non_authorized.php");

$files = array();
foreach ($projects as $project) {
	$folder_path = $EVC->getEntitiesPath($project);
	$items = CMSPresentationLayerHandler::getFolderFilesList($folder_path, $folder_path);
	
	foreach ($items as $file_path => $file)
		if ($file["type"] != "folder") {
			$extension = pathinfo($file_path, PATHINFO_EXTENSION);
			if (strtolower($extension) == "php") {
				$fp = substr($file_path, 0, -4);
				$fp = str_replace(APP_PATH, "", $EVC->getEntityPath($fp, $project));
				
				if (!in_array($fp, $reserved_files))
					$files[] = $fp;
			}
		}
	
	$folder_path = $EVC->getModulesPath($project);
	$items = CMSPresentationLayerHandler::getFolderFilesList($folder_path, $folder_path);
	
	foreach ($items as $file_path => $file)
		if ($file["type"] != "folder") {
			$extension = pathinfo($file_path, PATHINFO_EXTENSION);
			if (strtolower($extension) == "php") {
				$fp = substr($file_path, 0, -4);
				$fp = str_replace(APP_PATH, "", $EVC->getModulePath($fp, $project));
				
				if (!in_array($fp, $reserved_files))
					$files[] = $fp;
			}
		}
}

$pages = array();
foreach ($files as $file) {
	$contents = file_get_contents(APP_PATH . $file);
	preg_match_all('/\$UserAuthenticationHandler->checkPresentationFileAuthentication\(\$(\w+), ([^)]+)\)/u', $contents, $matches, PREG_PATTERN_ORDER); //'/u' means with accents and ç too.
	
	if ($matches[2])
		foreach ($matches[2] as $match) {
			preg_match_all('/array([ ]*)\((.+)/u', $match, $sub_matches, PREG_PATTERN_ORDER); //'/u' means with accents and ç too.
			$sub_matches = explode(",", str_replace(array("'", '"'), "", $sub_matches[2][0] ? $sub_matches[2][0] : $match));
			
			foreach ($sub_matches as $m)
				$pages[$file][ strtolower(trim($m)) ] = true;
		}
}
ksort($pages);
//echo "<pre>";print_r($pages);die();

//Preparing layers
$raw_layers = AdminMenuHandler::getLayersFiles($user_global_variables_file_path);
$raw_layers["others"]["other"] = AdminMenuHandler::getOtherObjs(false, 1);

$raw_layers["vendors"]["vendor"]["properties"]["item_label"] = "Vendors";
$raw_layers["others"]["other"]["properties"]["item_label"] = "Other Files";

$presentation_layers_projects = CMSPresentationLayerHandler::getPresentationLayersProjectsFiles($user_global_variables_file_path, $user_beans_folder_path);

$layer_object_id_prefix = str_replace(APP_PATH, "", LAYER_PATH);
$layer_object_id_prefix = substr($layer_object_id_prefix, -1) == "/" ? substr($layer_object_id_prefix, 0, -1) : $layer_object_id_prefix;

$layers = array();
$layers_label = array();
$layers_object_id = array();
$layers_props = array();

foreach ($raw_layers as $layer_type_name => $layer_type)
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
		else if ($layer_type_name == "presentation_layers" && is_array($presentation_layers_projects[$layer_name]["projects"]))
			foreach ($presentation_layers_projects[$layer_name]["projects"] as $project_name => $p) {
				$parts = explode("/", $project_name);
				$parts_prefix = "";
				
				for ($i = 0; $i < count($parts); $i++) {
					$pn = $parts_prefix . $parts[$i];
					$layers[$layer_type_name][$lln][$pn] = array();
					$parts_prefix = $pn . "/";
				}
			}
	}

//preparing admin uis
$admin_uis = array(
	"simple" => "Simple Workspace",
	"citizen" => "Citizen-Development Workspace",
	"low_code" => "Low-Code Workspace",
	"advanced" => "Advanced Workspace",
	"expert" => "Expert Workspace",
);

//echo "<pre>";print_r($projects);die();
//echo "<pre>";print_r($raw_layers);die();
//echo "<pre>";print_r($layers);die();
//echo "<pre>";print_r($layers_label);die();
//echo "<pre>";print_r($layers_object_id);die();

?>
