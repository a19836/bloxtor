<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layout_type_id = isset($_GET["layout_type_id"]) ? $_GET["layout_type_id"] : null;

if ($layout_type_id)
	$layout_type_data = $UserAuthenticationHandler->getLayoutType($layout_type_id);

if (!empty($_POST["layout_type_data"])) {
	$new_layout_type_data = isset($_POST["layout_type_data"]) ? $_POST["layout_type_data"] : null;
	
	if (!empty($_POST["delete"])) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");

		if ($layout_type_id && $UserAuthenticationHandler->deleteLayoutType($layout_type_id)) {
			echo "<script>alert('Layout Type deleted successfully'); document.location = '$project_url_prefix/user/manage_layout_types';</script>";
			die();
		}
		else {
			$layout_type_data = $new_layout_type_data;
			$error_message = "There was an error trying to delete this layout type. Please try again...";
		}
	}
	else if (empty($new_layout_type_data["name"])) {
		$layout_type_data = $new_layout_type_data;
		$error_message = "Error: Name cannot be undefined";
	}
	else {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		
		$layout_type_name = isset($layout_type_data["name"]) ? $layout_type_data["name"] : null;
		
		//$new_layout_type_data["name"] = strtolower($new_layout_type_data["name"]); //Do not strtolower bc if project is inside of folder which is upper case, then we cannot make the layout lower case.
		
		if ($layout_type_name != $new_layout_type_data["name"]) {
			$results = $UserAuthenticationHandler->searchLayoutTypes(array("name" => $new_layout_type_data["name"]));
			
			if (!empty($results[0])) {
				$layout_type_data = $new_layout_type_data;
				$error_message = "Error: Repeated Name";
			}
		}
		
		if (empty($error_message)) {
			if (!empty($layout_type_data)) {
				$layout_type_data = array_merge($layout_type_data, $new_layout_type_data);
				
				if ($UserAuthenticationHandler->updateLayoutType($layout_type_data)) {
					$status_message = "Layout Type updated successfully...";
				}
				else {
					$error_message = "There was an error trying to update this layout type. Please try again...";
				}
			}
			else {
				$layout_type_data = $new_layout_type_data;
				
				$status = $UserAuthenticationHandler->insertLayoutType($layout_type_data);
				
				if ($status) {
					echo "<script>alert('Layout Type inserted successfully'); document.location = '?layout_type_id=" . $status . "';</script>";
					die();
				}
				else {
					$error_message = "There was an error trying to insert this layout type. Please try again...";
				}
			}
		}
	}
}

//prepare empty data
if (empty($layout_type_data)) {
	$layout_type_data = array(
		"layout_type_id" => $layout_type_id,
		"type_id" => UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID,
		"name" => "",
	);
}

$available_types = UserAuthenticationHandler::$AVAILABLE_LAYOUTS_TYPES;

//prepare presentation layers
$presentation_layers_projects = CMSPresentationLayerHandler::getPresentationLayersProjectsFiles($user_global_variables_file_path, $user_beans_folder_path);
$presentation_projects = array();

if ($presentation_layers_projects)
	foreach ($presentation_layers_projects as $bean_name => $props) {
		$projs = array();
		$projs_by_folders = array();
		
		if (!empty($props["projects"])) {
			$folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $props["bean_file_name"], $bean_name, $user_global_variables_file_path);
			
			foreach ($props["projects"] as $project_name => $project_props) {
				$proj_id = "$folder_name/$project_name";
				$projs[$proj_id] = $project_name;
				
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
		
		$layer_label = isset($props["item_label"]) ? $props["item_label"] : null;
		$presentation_projects[$layer_label] = $projs;
		$presentation_projects_by_folders[$layer_label] = $projs_by_folders;
		//echo "<pre>";print_r($presentation_projects);print_r($presentation_projects_by_folders);die();
	}
?>
