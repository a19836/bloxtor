<?php
include_once $EVC->getUtilPath("AdminMenuHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$item_type = isset($_GET["item_type"]) ? $_GET["item_type"] : null;
$folder_type = isset($_GET["folder_type"]) ? $_GET["folder_type"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null;
$filter_by_layout_permission = isset($_GET["filter_by_layout_permission"]) ? $_GET["filter_by_layout_permission"] : null;

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons
	
/*$UserCacheHandler = $PHPFrameWork->getObject("UserCacheHandler");
$UserCacheHandler->config(false, true);

$cached_file_name = "admin_menu_layers_" . md5($bean_file_name . "_" . $bean_name . "_" . $path);

if ($UserCacheHandler->isValid($cached_file_name)) {
	$sub_files = $UserCacheHandler->read($cached_file_name);
}

if (empty($layers)) {*/
	$AdminMenuHandler = new AdminMenuHandler();
	
	if ($item_type == "dao") {
		$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("vendor/dao/$path", "layer", "access");
		
		$sub_files = AdminMenuHandler::getDaoObjs($path, 1);
		
		$sub_files["properties"]["bean_name"] = "dao";
		$sub_files["properties"]["bean_file_name"] = "";
	}
	else if ($item_type == "lib") {
		$sub_files = AdminMenuHandler::getLibObjs($path, 1);
		
		$sub_files["properties"]["bean_name"] = "lib";
		$sub_files["properties"]["bean_file_name"] = "";
	}
	else if ($item_type == "vendor") {
		$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("vendor/$path", "layer", "access");
		
		$sub_files = AdminMenuHandler::getVendorObjs($path, 1);
		
		$sub_files["properties"]["bean_name"] = "vendor";
		$sub_files["properties"]["bean_file_name"] = "";
	}
	else if ($item_type == "other") {
		$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("other/$path", "layer", "access");
		
		$sub_files = AdminMenuHandler::getOtherObjs($path, 1);
		
		$sub_files["properties"]["bean_name"] = "other";
		$sub_files["properties"]["bean_file_name"] = "";
	}
	else if ($item_type == "test_unit") {
		$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("vendor/testunit/$path", "layer", "access");
		
		$sub_files = AdminMenuHandler::getTestUnitObjs($path, 1);
		
		$sub_files["properties"]["bean_name"] = "test_unit";
		$sub_files["properties"]["bean_file_name"] = "";
	}
	else {
		$layer_object_id = LAYER_PATH . WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bean_file_name, $bean_name, $user_global_variables_file_path) . "/";
		$layer_path_object_id = $layer_object_id . $path . "/";
		$options = array(
			"all" => true, //in case of business logic or presentation util files, load hidden methods too
		);
		
		$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_path_object_id, "layer", "access");
		
		if ($item_type == "presentation" && $path && $folder_type != "project_folder") {
			if ($folder_type == "project") //refresh specific project
				$sub_files = AdminMenuHandler::getBeanObjs($bean_file_name, $bean_name, $user_global_variables_file_path, $path, 1, $options);
			else //get sub files from a project folder, like: entity, view, template, etc... or others sub-folders
				$sub_files = AdminMenuHandler::getPresentationFolderFiles($bean_file_name, $bean_name, $user_global_variables_file_path, $path, 1, $folder_type, $options);
		}
		else //get available db_drivers, projects, business logic sub-files, etc...
			$sub_files = AdminMenuHandler::getBeanObjs($bean_file_name, $bean_name, $user_global_variables_file_path, $path, 1, $options);
		
		//check the returned files for permission, but only check the first level. This is used to get the available db_drivers, projects, etc...
		if ($sub_files) {
			if ($filter_by_layout) {
				$is_layer_root_path = empty($path);
				
				//if is layer root folder and filter_by_layout_permission is belong and reference, add_referenced_folder to true and set filter_by_layout_permission to belong
				if ($is_layer_root_path && is_array($filter_by_layout_permission) && in_array(UserAuthenticationHandler::$PERMISSION_BELONG_NAME, $filter_by_layout_permission) && in_array(UserAuthenticationHandler::$PERMISSION_REFERENCED_NAME, $filter_by_layout_permission)) {
					$filter_by_layout_permission = UserAuthenticationHandler::$PERMISSION_BELONG_NAME;
					$add_referenced_folder = true;
				}
				else if (!$filter_by_layout_permission) //only if not exists, set filter_by_layout_permission to belong. Note that the $filter_by_layout_permission could be only REFERENCED
					$filter_by_layout_permission = UserAuthenticationHandler::$PERMISSION_BELONG_NAME;
				
				//only allow filter if really exists
				if (!$UserAuthenticationHandler->searchLayoutTypes(array("name" => $filter_by_layout, "type_id" => UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID)))
					$filter_by_layout = $filter_by_layout_permission = null;
				else
					$UserAuthenticationHandler->loadLayoutPermissions($filter_by_layout, UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID);
			}
			
			//filter files according with permissions
			prepareSubFiles($sub_files, $UserAuthenticationHandler, $layer_object_id, $layer_path_object_id, $filter_by_layout, $filter_by_layout_permission);
			
			if ($filter_by_layout) {
				//if should only show referenced files
				if ($filter_by_layout_permission == UserAuthenticationHandler::$PERMISSION_REFERENCED_NAME || (is_array($filter_by_layout_permission) && count($filter_by_layout_permission) == 1 && $filter_by_layout_permission[0] == UserAuthenticationHandler::$PERMISSION_REFERENCED_NAME)) {
					//loop for all $sub_files and in all properties add the property: parse_get_sub_files_url_handler => prepareGetSubFilesUrlToFilterOnlyByReferencedFiles
					$js_url_handler = LayoutTypeProjectUIHandler::getJavascriptHandlerToParseGetSubFilesUrlWithOnlyReferencedFiles();
					addParseGetSubFilesURLHandlerPropertyToSubFiles($sub_files, $js_url_handler);
				}
				
				//if layer root path with belonging files, add the referenced folder
				if (!empty($add_referenced_folder)) {
					$js_url_handler = LayoutTypeProjectUIHandler::getJavascriptHandlerToParseGetSubFilesUrlWithOnlyBelongingFiles();
					addParseGetSubFilesURLHandlerPropertyToSubFiles($sub_files, $js_url_handler);
					
					AdminMenuHandler::addReferencedFolderToFilesList($sub_files, $bean_file_name, $bean_name, $path, $item_type);
				}
			}
		}
	}
	
	/*$UserCacheHandler->write($cached_file_name, $sub_files);
}*/

//print_r($sub_files);die();

//filter files according with permissions
function prepareSubFiles(&$sub_files, $UserAuthenticationHandler, $layer_object_id, $layer_path_object_id, $filter_by_layout, $filter_by_layout_permission) {
	//echo "$layer_object_id, $layer_path_object_id, $filter_by_layout, ".print_r($filter_by_layout_permission, 1)."\n";
	
	if (is_array($sub_files))
		foreach ($sub_files as $sub_file_name => $sub_file) 
			if ($sub_file_name != "aliases" && $sub_file_name != "properties") {
				if (isset($sub_file["properties"]["item_type"]) && $sub_file["properties"]["item_type"] == "properties") { //inside of each project we have a folder called others which has the item_type==properties
					prepareSubFiles($sub_files[$sub_file_name], $UserAuthenticationHandler, $layer_object_id, $layer_path_object_id, $filter_by_layout, $filter_by_layout_permission);
					
					//if others folder, is empty, then remove the others folder.
					$new_sub_file = $sub_files[$sub_file_name];
					unset($new_sub_file["aliases"]);
					unset($new_sub_file["properties"]);
					
					if (empty($new_sub_file))
						unset($sub_files[$sub_file_name]);
				}
				else {
					if (!empty($sub_file["properties"]["path"]))
						$object_id = $layer_object_id . $sub_file["properties"]["path"];
					else
						$object_id = $layer_path_object_id . $sub_file_name;
					
					//echo "object_id:$object_id\n";
					//echo "$filter_by_layout:$filter_by_layout_permission\n";die();
					
					if (!$UserAuthenticationHandler->isInnerFilePermissionAllowed($object_id, "layer", "access"))
						unset($sub_files[$sub_file_name]);
					else if ($filter_by_layout && !$UserAuthenticationHandler->isLayoutInnerFilePermissionAllowed($object_id, $filter_by_layout, "layer", $filter_by_layout_permission)) //if filter_by_layout: check if sub_files belong or is referenced to that project
						unset($sub_files[$sub_file_name]);
					else //check sub_files
						prepareSubFiles($sub_files[$sub_file_name], $UserAuthenticationHandler, $layer_object_id, $layer_path_object_id, $filter_by_layout, $filter_by_layout_permission);
				}
			}
}

//loop for all $sub_files and in all properties add the property: parse_get_sub_files_url_handler => prepareGetSubFilesUrlToFilterOnlyByReferencedFiles
function addParseGetSubFilesURLHandlerPropertyToSubFiles(&$sub_files, $js_url_handler) {
	if (is_array($sub_files))
		foreach ($sub_files as $sub_file_name => $sub_file) 
			if ($sub_file_name != "aliases" && $sub_file_name != "properties") {
				if (!empty($sub_file["properties"]))
					$sub_files[$sub_file_name]["properties"]["parse_get_sub_files_url_handler"] = $js_url_handler;
				
				addParseGetSubFilesURLHandlerPropertyToSubFiles($sub_files[$sub_file_name], $js_url_handler);
			}
}
?>
