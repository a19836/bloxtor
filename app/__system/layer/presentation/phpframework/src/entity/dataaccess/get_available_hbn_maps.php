<?php
include_once get_lib("org.phpframework.util.xml.MyXML");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$hbn_obj_id = $_GET["obj"];
$map_type = $_GET["map_type"];
$query_type = $_GET["query_type"];

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
			$maps = $nodes["class"][$hbn_obj_id]["childs"][$query_type][$map_type . "_map"];
		}
		else {
			$maps = $nodes[$map_type . "_map"];
		}
		
		$items = is_array($maps) ? array_keys($maps) : array();
	}
}

$PHPVariablesFileHandler->endUserGlobalVariables();
?>
