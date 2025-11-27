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

include_once $EVC->getUtilPath("WorkFlowQueryHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$hbn_obj_id = isset($_GET["obj"]) ? $_GET["obj"] : null;
$query_id = isset($_GET["query_id"]) ? $_GET["query_id"] : null;
$map_id = isset($_GET["map"]) ? $_GET["map"] : null;
$query_type = isset($_GET["query_type"]) ? $_GET["query_type"] : null;
$relationship_type = isset($_GET["relationship_type"]) ? $_GET["relationship_type"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null;
$selected_db_driver = isset($_GET["selected_db_driver"]) ? $_GET["selected_db_driver"] : null;
$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

$is_import_file = $relationship_type == "import";

$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
$PHPVariablesFileHandler->startUserGlobalVariables();

$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);

$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);

if ($obj && is_a($obj, "DataAccessLayer")) {
	$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
	$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
	$WorkFlowTaskHandler->setAllowedTaskTypes(array("query"));
		
	$layer_path = $obj->getLayerPathSetting();
	$file_path = $layer_path . $path;
	
	if ($path && file_exists($file_path)) {
		$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($file_path, "layer", "access");
		
		$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
		
		$selected_table = $obj_data = null;
		
		//PREPARING OBJ DATA
		if ($obj->getType() == "hibernate") {
			switch ($file_type) {
				case "edit_obj": 
					$obj_data = WorkFlowDataAccessHandler::getXmlHibernateObjData($file_path, $hbn_obj_id); 
					$selected_table = WorkFlowDataAccessHandler::getNodeValue($obj_data, "table");
					
					$hbn_class_objs = WorkFlowDataAccessHandler::getDAOObjectsLibPath("HibernateModel");
					break;
				case "edit_query": 
				case "edit_relationship": 
				case "edit_map": 
					$id = $file_type == "edit_map" ? $map_id : $query_id;
					
					if ($is_import_file) 
						$obj_data = WorkFlowDataAccessHandler::getXmlQueryOrMapData($file_path, $id, array($query_type));
					else 
						$obj_data = WorkFlowDataAccessHandler::getXmlHibernateObjQueryOrMapData($file_path, $hbn_obj_id, $id, array($query_type), $relationship_type);
					
					break;
				case "edit_includes": 
					$obj_data = WorkFlowDataAccessHandler::getXmlHibernateImportsData($file_path);
					break;
			}
			
			//echo "<pre>";print_r($obj_data);die();
			$is_hbn_obj_equal_to_file_name = $hbn_obj_id && strtolower(pathinfo($path, PATHINFO_FILENAME)) == strtolower($hbn_obj_id);
		}
		else {
			switch ($file_type) {
				case "edit_query": 
					$obj_data = WorkFlowDataAccessHandler::getXmlQueryOrMapData($file_path, $query_id, array($query_type));
					break;
				case "edit_map": 
					$obj_data = WorkFlowDataAccessHandler::getXmlQueryOrMapData($file_path, $map_id, array($query_type));
					break;
				case "edit_includes": 
					$obj_data = WorkFlowDataAccessHandler::getXmlHibernateImportsData($file_path);
					break;
			}
			
			$is_hbn_obj_equal_to_file_name = true; //this is to only show the folder path in the breadcrumbs
		}
		
		if ($file_type == "edit_query") {
			$sql = XMLFileParser::getValue($obj_data);
			
			$data = $sql ? $obj->getFunction("convertSQLToObject", $sql) : array();
			$selected_table = !empty($data["table"]) ? $data["table"] : (isset($data["attributes"][0]["table"]) ? $data["attributes"][0]["table"] : null);
			//echo "<pre>";print_r($data);die("123");
			
			$rel_type = isset($data["type"]) ? $data["type"] : (
				!empty($obj_data["name"]) ? $obj_data["name"] : (
					$query_type ? $query_type : null
				)
			);
			$name = isset($obj_data["@"]["id"]) ? $obj_data["@"]["id"] : null;
			$parameter_class = isset($obj_data["@"]["parameter_class"]) ? $obj_data["@"]["parameter_class"] : null;
			$parameter_map = isset($obj_data["@"]["parameter_map"]) ? $obj_data["@"]["parameter_map"] : null;
			$result_class = isset($obj_data["@"]["result_class"]) ? $obj_data["@"]["result_class"] : null;
			$result_map = isset($obj_data["@"]["result_map"]) ? $obj_data["@"]["result_map"] : null;
			
			if ($sql) {
				if (!$rel_type && !$data) {
					if (SQLQueryHandler::isSetSQL($sql))
						$rel_type = SQLQueryHandler::getSQLType($sql);
					else if (SQLQueryHandler::isGetSQL($sql))
						$rel_type = "select";
				}
				//echo "$rel_type = $query_type";echo "<pre>";print_r($obj_data);die("123");
				
				$converted_sql = $obj->getFunction("convertObjectToSQL", array($data));
				
				//remove "as" keyword bc is optional. Remove spaces, quotes (double and single), apostrophes, paranthesis...
				$converted_sql = strtolower(preg_replace("/(\s|'|\"|`|\)|\()+/", "", preg_replace("/\s(as)\s/i", "", $converted_sql)));
				$sql_aux = strtolower(preg_replace("/(\s|'|\"|`|\)|\()+/", "", preg_replace("/\s(as)\s/i", "", $sql)));
				
				$is_covertable_sql = $converted_sql == $sql_aux || str_replace("$selected_table.", "", $converted_sql) == $sql_aux;
				
				/*echo "<pre>is_covertable_sql:$is_covertable_sql\n";
				echo "sql1:".$sql_aux."\n";
				echo "sql2:".$converted_sql."\n";
				echo "sql3:".str_replace("$selected_table.", "", $converted_sql)."\n";
				print_r($data);die();*/
			}
			else
				$is_covertable_sql = true;
		}
		
		//PREPARING DB BROKERS, DRIVERS, TABLES, ATTRIBUTES...
		$selected_data = WorkFlowQueryHandler::getSelectedDBBrokersDriversTablesAndAttributes($obj, $workflow_paths_id, $selected_table, $selected_db_driver, $filter_by_layout, $LayoutTypeProjectHandler);
		$brokers = $selected_data["brokers"];
		$db_drivers = $selected_data["db_drivers"];
		$selected_db_broker = $selected_data["selected_db_broker"];
		$selected_db_driver = $selected_data["selected_db_driver"];
		$selected_type = $selected_data["selected_type"];
		$selected_table = $selected_data["selected_table"];
		$selected_tables_name = $selected_data["selected_tables_name"];
		$selected_table_attrs = $selected_data["selected_table_attrs"];
		//echo "<pre>";print_r($selected_data);die();
		//echo "$selected_db_broker|$selected_db_driver|$selected_table";die();
		
		//PREPARING TYPES...
		$obj_type_objs = WorkFlowDataAccessHandler::getDAOObjectsLibPath("objtype");
		$map_php_types = WorkFlowDataAccessHandler::getMapPHPTypes();
		$map_db_types = WorkFlowDataAccessHandler::getMapDBTypes();
	}
	else {
		launch_exception(new Exception("File Not Found: " . $path));
		die();
	}
}

//print_r($obj_data);die();

$PHPVariablesFileHandler->endUserGlobalVariables();
?>
