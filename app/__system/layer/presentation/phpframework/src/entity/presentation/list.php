<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$item_type = isset($_GET["item_type"]) ? $_GET["item_type"] : null;
$element_type = isset($_GET["element_type"]) ? $_GET["element_type"] : null; //only apply if is presentation layer
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null;
$filter_by_layout_permission = isset($_GET["filter_by_layout_permission"]) ? $_GET["filter_by_layout_permission"] : null;
$selected_db_driver = isset($_GET["selected_db_driver"]) ? $_GET["selected_db_driver"] : null;

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

$filter_by_layout_permission = $filter_by_layout_permission ? $filter_by_layout_permission : UserAuthenticationHandler::$PERMISSION_BELONG_NAME;
$exists_db_drivers = false;

if ($item_type == "dao") {
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("vendor/dao/$path", "layer", "access");
	
	$layers = array(
		$item_type => AdminMenuHandler::getDaoObjs(false, 1)
	);
}
else if ($item_type == "lib") {
	$layers = array(
		$item_type => AdminMenuHandler::getLibObjs(false, 1)
	);
}
else if ($item_type == "vendor") {
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("vendor/$path", "layer", "access");
	
	$layers = array(
		$item_type => AdminMenuHandler::getVendorObjs(false, 1)
	);
}
else if ($item_type == "other") {
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("other/$path", "layer", "access");
	
	$layers = array(
		$item_type => AdminMenuHandler::getOtherObjs(false, 1)
	);
}
else if ($item_type == "test_unit") {
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("vendor/testunit/$path", "layer", "access");
	
	$layers = array(
		$item_type => AdminMenuHandler::getTestUnitObjs(false, 1)
	);
}
else {
	//Note that if a project doesn't have any layout_type_permission created, the presentation and DB layers should not be removed from the $layers variable. This is the "Citizen UI" and is for a specific project, so even if there isn't a layout_type_permissions defined, it should always show the correspondent presentation layer and default DB layer.
	$filter_layout_by_layers_type = array("presentation_layers", "business_logic_layers", "data_access_layers"/*, "db_layers"*/);
	$do_not_filter_by_layout = array(
		"bean_name" => $bean_name,
		"bean_file_name" => $bean_file_name,
	);
	include $EVC->getUtilPath("admin_uis_layers_and_permissions");
	//echo "<pre>";print_r($layers);die();
	
	if ($item_type && !empty($layers[$item_type . "_layers"]))
		$layers = $layers[$item_type . "_layers"];
	else if ($bean_name && $bean_file_name) {
		$new_layers = array();
		
		foreach ($layers as $layer_type_name => $layer_type)
			foreach ($layer_type as $layer_name => $layer) {
				$properties = isset($layer["properties"]) ? $layer["properties"] : null;
				$layer_bean_name = isset($properties["bean_name"]) ? $properties["bean_name"] : null;
				$layer_bean_file_name = isset($properties["bean_file_name"]) ? $properties["bean_file_name"] : null;
				
				if ($layer_bean_name == $bean_name && $layer_bean_file_name == $bean_file_name) {
					$new_layers[$layer_name] = $layer;
					
					if (!$item_type)
						$item_type = substr($layer_type_name, 0, - strlen("_layers"));
					
					break;
				}
			}
		
		$layers = $new_layers;
	}
	else
		$layers = null;
}
//echo "<pre>";print_r($layers);die();
?>
