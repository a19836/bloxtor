<?php
//getting all layers
/*$UserCacheHandler = $PHPFrameWork->getObject("UserCacheHandler");
$UserCacheHandler->config(false, true);

$cached_file_name = "admin_menu_layers";

if ($UserCacheHandler->isValid($cached_file_name)) {
	$layers = $UserCacheHandler->read($cached_file_name);
}

if (empty($layers)) {*/
	$layers = AdminMenuHandler::getLayersFiles($user_global_variables_file_path);
	$layers["vendors"]["vendor"]["properties"]["item_label"] = "External Library";
	$layers["vendors"]["vendor"]["properties"]["item_title"] = "Folder name: 'vendor'";
	
	$layers["libs"]["lib"] = AdminMenuHandler::getLibObjs(false, 1);
	$layers["libs"]["lib"]["properties"]["item_label"] = "Internal Library";
	$layers["libs"]["lib"]["properties"]["item_title"] = "Folder name: 'lib'";
	
	$layers["others"]["other"] = AdminMenuHandler::getOtherObjs(false, 1);
	$layers["others"]["other"]["properties"]["item_label"] = "Other Files";
	$layers["others"]["other"]["properties"]["item_title"] = "Folder name: 'other'";
	//echo "<pre>";print_r($layers);die();
	
/*	$UserCacheHandler->write($cached_file_name, $layers);
}*/

//echo "<pre>";print_r($layers);die();

$exists_db_drivers = false;

/*Only for testing:
$layers["db_layers"] = array("Dbdata" => array(
	"properties" => array(
		"bean_file_name" => "dbdata_dbl.xml",
		"bean_name" => "Dbdata",
		"item_type" => "db",
		"item_id" => "mgewnguxywia",
	)
));*/

//preparing db layers with right structure
//echo "<pre>";print_r($layers["db_layers"]);
if (!empty($layers["db_layers"])) {
	$aux = array();
	
	foreach ($layers["db_layers"] as $layer_name => $layer) {
		if ($layer_name != "properties") {
			foreach ($layer as $driver_name => $driver) {
				if ($driver_name != "properties") {
					//$db_driver_item_type = $driver["properties"]["item_type"];
					
					//$db_name = "DB: " . $driver["properties"]["item_menu"]["db_name"];
					$db_name = "Tables";
					
					$new_driver = array(
						$db_name => $driver,
						//"DB Diagram" => array("properties" => $driver["properties"]),
						"properties" => isset($driver["properties"]) ? $driver["properties"] : null
					);
					
					$new_driver[$db_name]["properties"]["item_type"] = "db_management";
					//$new_driver["DB Diagram"]["properties"]["item_type"] = "db_diagram";
					
					$layers["db_layers"][$layer_name][$driver_name] = $new_driver;
					
					$exists_db_drivers = true;
				}
			}
		}
	}
}
//echo "<pre>";print_r($layers["db_layers"]);die();
//echo "<pre>";print_r($layers);die();

//preparing filter_by_layout if valid
if ($filter_by_layout) {
	//only allow filter if really exists
	if (!$UserAuthenticationHandler->searchLayoutTypes(array("name" => $filter_by_layout, "type_id" => UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID)))
		$filter_by_layout = $filter_by_layout_permission = null;
	else
		$UserAuthenticationHandler->loadLayoutPermissions($filter_by_layout, UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID);
}
//echo "filter_by_layout:$filter_by_layout:$filter_by_layout_permission";die();

//filter layers by user permissions and filter_by_layout permissions
$layout_types = $UserAuthenticationHandler->getAvailableLayoutTypes(UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID);
ksort($layout_types);
$non_projects_layout_types = $layout_types;
//echo "<pre>";print_r($layout_types);die();

$filter_layout_by_layers_type = $filter_layout_by_layers_type ? $filter_layout_by_layers_type : array("presentation_layers", "business_logic_layers", "data_access_layers", "db_layers");
//echo "<pre>";print_r($filter_layout_by_layers_type);die();

$presentation_projects = array();
$presentation_projects_by_layer_label = array();
$presentation_projects_by_layer_label_organized_by_folders = array();

foreach ($layers as $layer_type_name => $layer_type)
	foreach ($layer_type as $layer_name => $layer) {
		$layer_bean_name = isset($layer["properties"]["bean_name"]) ? $layer["properties"]["bean_name"] : null;
		$layer_bean_file_name = isset($layer["properties"]["bean_file_name"]) ? $layer["properties"]["bean_file_name"] : null;
		
		//prepare layer_bean_folder_name and layer_object_id
		if ($layer_type_name == "vendors" || $layer_type_name == "others" || $layer_type_name == "libs") {
			$layer_bean_folder_name = $layer_name;
			$layer_object_id = $layer_name;
		}
		else {
			$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $layer_bean_file_name, $layer_bean_name, $user_global_variables_file_path);
			$layer_object_id = LAYER_PATH . $layer_bean_folder_name;
			//echo "<pre>layer_object_id:$layer_object_id<br>";
		}
		
		$layers[$layer_type_name][$layer_name]["properties"]["layer_bean_folder_name"] = $layer_bean_folder_name;
		
		$do_not_filter_layer_by_layout = $do_not_filter_by_layout && $do_not_filter_by_layout["bean_name"] == $layer_bean_name && $do_not_filter_by_layout["bean_file_name"] == $layer_bean_file_name;
		
		//check layer permissions
		if (!$UserAuthenticationHandler->isInnerFilePermissionAllowed($layer_object_id, "layer", "access")) {
			unset($layers[$layer_type_name][$layer_name]);
			//echo "isInnerFilePermissionAllowed layer_name:$layer_name";
		}
		else if ($layer_type_name == "db_layers" || $layer_type_name == "presentation_layers") {
			foreach ($layer as $fn => $f)
				if ($fn != "properties" && $fn != "aliases") { 
					$fn_layer_object_id = "$layer_object_id/$fn";
					
					//echo "fn_layer_object_id:$fn_layer_object_id<br>";
					//echo"$fn_layer_object_id:".$UserAuthenticationHandler->isInnerFilePermissionAllowed($fn_layer_object_id, "layer", "access")."<br>";
					//echo "<pre>";print_r($layers[$layer_type_name][$layer_name][$fn]);
					
					if (!$UserAuthenticationHandler->isInnerFilePermissionAllowed($fn_layer_object_id, "layer", "access"))
						unset($layers[$layer_type_name][$layer_name][$fn]);
					else if ($layer_type_name == "db_layers" && $filter_by_layout) { //if filter_by_layout: check if sub_files belong to selected project
						if (!$UserAuthenticationHandler->isLayoutInnerFilePermissionAllowed($fn_layer_object_id, $filter_by_layout, "layer", $filter_by_layout_permission))
							unset($layers[$layer_type_name][$layer_name][$fn]);
					}
				}
			
			//prepare presentation layers projects
			if ($layer_type_name == "presentation_layers" && $layout_types) {
				$projects = CMSPresentationLayerHandler::getPresentationLayerProjectsFiles($user_global_variables_file_path, $user_beans_folder_path, $layer_bean_file_name, $layer_bean_name);
				$projs = array();
				$projs_by_folders = array();
				
				//echo "<pre>";print_r($projects);echo "</pre>";
				
				if ($projects) {
					$do_not_filter_by_layout_project = isset($do_not_filter_by_layout["project"]) ? $do_not_filter_by_layout["project"] : null;
					
					foreach ($projects as $project_name => $project_props) {
						$fn_layer_object_id = "$layer_object_id/$project_name";
						
						if ($UserAuthenticationHandler->isInnerFilePermissionAllowed($fn_layer_object_id, "layer", "access")) {
							$proj_id = "$layer_bean_folder_name/$project_name";
							//echo "$proj_id\n<br/>";
							
							if (!empty($layout_types[$proj_id])) {
								$projs[$proj_id] = $project_name;
							
								unset($non_projects_layout_types[$proj_id]);
								
								//organize projects into sub_folders
								$dirs = explode("/", $project_name);
								$file_name = array_pop($dirs);
								$obj = &$projs_by_folders;
								
								foreach ($dirs as $dir) {
									if (!isset($obj[$dir]))
										$obj[$dir] = array();
									
									$obj = &$obj[$dir];
								}
								
								$obj[$proj_id] = $file_name;
							}
						}
						else if (!$do_not_filter_by_layout || $do_not_filter_by_layout_project != $project_name)
							unset($projects[$project_name]);
					}
				}
				
				$layer_label = isset($layer["properties"]["item_label"]) ? $layer["properties"]["item_label"] : strtolower($layer_name);
				$presentation_projects[$layer_name] = $projects;
				$presentation_projects_by_layer_label[$layer_label] = $projs;
				$presentation_projects_by_layer_label_and_folders[$layer_label] = $projs_by_folders;
				$presentation_bean_folder_name_by_layer_label[$layer_label] = $layer_bean_folder_name;
			}
		}
		
		//must be at the end, bc if we have multiple presentation layers, we need to have the projects for each layer, even if the filter_by_layout only shows a specific presentation layer.
		if ($filter_by_layout && !$do_not_filter_layer_by_layout && in_array($layer_type_name, $filter_layout_by_layers_type) && !$UserAuthenticationHandler->isLayoutInnerFilePermissionAllowed($layer_object_id, $filter_by_layout, "layer", $filter_by_layout_permission)) {
			unset($layers[$layer_type_name][$layer_name]);
			//echo "isLayoutInnerFilePermissionAllowed layer_name:$layer_name";
		}
	}
//echo "<pre>";print_r($layers);die();

//Preparing tools permissions
$is_flush_cache_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("admin/flush_cache"), "delete");
$is_manage_modules_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("admin/manage_modules"), "access");
$is_manage_projects_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("presentation/manage_projects"), "access");
$is_manage_users_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("user/manage_users"), "access");
$is_manage_layers_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("setup/layers"), "access");
$is_deployment_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("deployment/index"), "access");
$is_testunits_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("testunit/index"), "access");
$is_program_installation_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("admin/install_program"), "access");
$is_diff_files_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("diff/index"), "access");

$is_module_user_installed = file_exists($EVC->getModulesPath($EVC->getCommonProjectName()) . "user/");

//prepare admin uis permissions
include $EVC->getUtilPath("admin_uis_permissions");
?>
