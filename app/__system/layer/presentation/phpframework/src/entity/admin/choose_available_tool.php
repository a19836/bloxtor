<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$selected_db_driver = isset($_GET["selected_db_driver"]) ? $_GET["selected_db_driver"] : null;
$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null;

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

//catch this bc if the bean xml files are invalid, this will break the system.
$error_reporting = error_reporting();
error_reporting(0);

try {
	//preparing filter_by_layout
	$layers_beans = AdminMenuHandler::getLayers($user_global_variables_file_path);
	//echo "<pre>";print_r($layers_beans);die();

	if ($layers_beans && !empty($layers_beans["presentation_layers"])) {
		if ($bean_name && $bean_file_name && $path && !empty($layers_beans["presentation_layers"][$bean_name]) && $layers_beans["presentation_layers"][$bean_name] == $bean_file_name) {
			$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bean_file_name, $bean_name, $user_global_variables_file_path);
			
			$filter_by_layout = "$layer_bean_folder_name/" . preg_replace("/\/+$/", "", $path); //remove last slash from $path
			$filter_by_layout_permission = UserAuthenticationHandler::$PERMISSION_BELONG_NAME;
		}
		else if ($filter_by_layout) {
			foreach ($layers_beans["presentation_layers"] as $bn => $bfn) {
				$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bfn, $bn, $user_global_variables_file_path);
				$layer_bean_folder_name .= "/";
				
				if (substr($filter_by_layout, 0, strlen($layer_bean_folder_name)) == $layer_bean_folder_name) {
					$bean_name = $bn;
					$bean_file_name = $bfn;
					$path = substr($filter_by_layout, strlen($layer_bean_folder_name));
					$filter_by_layout_permission = UserAuthenticationHandler::$PERMISSION_BELONG_NAME;
					break;
				}
			}
			
		}
	}

	//echo "bean_name:$bean_name, bean_file_name:$bean_file_name, path:$path, filter_by_layout:$filter_by_layout, filter_by_layout_permission:$filter_by_layout_permission";die();

	//Preparing permissions
	$do_not_filter_by_layout = array(
		"bean_name" => $bean_name,
		"bean_file_name" => $bean_file_name,
	);
	include $EVC->getUtilPath("admin_uis_layers_and_permissions");
	//echo "<pre>";print_r($layers);die();
}
catch (Throwable $e) {
	$error_message = $e->getMessage();
	
	include $EVC->getUtilPath("admin_uis_permissions");
}

error_reporting($error_reporting);
?>
