<?php
include_once $EVC->getUtilPath("WorkFlowQueryHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$hbn_obj_id = $_GET["obj"];
$query_id = $_GET["query_id"];
$map_id = $_GET["map"];
$query_type = $_GET["query_type"];
$relationship_type = $_GET["relationship_type"];
$filter_by_layout = $_GET["filter_by_layout"];
$selected_db_driver = $_GET["selected_db_driver"];
$popup = $_GET["popup"];

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
			$selected_table = $data["table"] ? $data["table"] : $data["attributes"][0]["table"];
			//echo "<pre>";print_r($data);die("123");
			
			$rel_type = $data["type"];
			$name = $obj_data["@"]["id"];
			$parameter_class = $obj_data["@"]["parameter_class"];
			$parameter_map = $obj_data["@"]["parameter_map"];
			$result_class = $obj_data["@"]["result_class"];
			$result_map = $obj_data["@"]["result_map"];
			
			if ($sql) {
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
		$selected_data = WorkFlowQueryHandler::getSelectedDBBrokersDriversTablesAndAttributes($obj, $tasks_file_path, $workflow_paths_id, $selected_table, $selected_db_driver, $filter_by_layout, $LayoutTypeProjectHandler);
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
