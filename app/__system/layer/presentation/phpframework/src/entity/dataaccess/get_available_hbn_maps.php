<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.util.xml.MyXML");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$hbn_obj_id = isset($_GET["obj"]) ? $_GET["obj"] : null;
$map_type = isset($_GET["map_type"]) ? $_GET["map_type"] : null;
$query_type = isset($_GET["query_type"]) ? $_GET["query_type"] : null;

$path = str_replace("../", "", $path);//for security reasons

$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
$PHPVariablesFileHandler->startUserGlobalVariables();

$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);

if ($obj && is_a($obj, "DataAccessLayer")) {
	$layer_path = $obj->getLayerPathSetting();
	//$layer_path = SYSTEM_LAYER_PATH . "dataaccess/hibernate/";//only for testing
	$file_path = $layer_path . $path;
	
	if ($path && file_exists($file_path)) {
		$obj->getSQLClient()->loadXML($file_path);
		$nodes = $obj->getSQLClient()->getNodesData();
		
		if ($obj->getType() == "hibernate") {
			$maps = isset($nodes["class"][$hbn_obj_id]["childs"][$query_type][$map_type . "_map"]) ? $nodes["class"][$hbn_obj_id]["childs"][$query_type][$map_type . "_map"] : null;
		}
		else {
			$maps = isset($nodes[$map_type . "_map"]) ? $nodes[$map_type . "_map"] : null;
		}
		
		$items = is_array($maps) ? array_keys($maps) : array();
	}
}

$PHPVariablesFileHandler->endUserGlobalVariables();
?>
