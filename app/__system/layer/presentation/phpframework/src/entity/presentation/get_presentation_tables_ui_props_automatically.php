<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.util.MyArray");
include_once $EVC->getUtilPath("AdminMenuHandler");
include_once $EVC->getUtilPath("CMSPresentationFormSettingsUIHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null;
$db_layer = isset($_GET["db_layer"]) ? $_GET["db_layer"] : null;
$db_layer_file = isset($_GET["db_layer_file"]) ? $_GET["db_layer_file"] : null;
$db_driver = isset($_GET["db_driver"]) ? $_GET["db_driver"] : null;
$include_db_driver = isset($_GET["include_db_driver"]) ? $_GET["include_db_driver"] : null;
$type = isset($_GET["type"]) ? $_GET["type"] : null;
$selected_tables = !empty($_POST["st"]) ? $_POST["st"] : (isset($_GET["st"]) ? $_GET["st"] : null);
$selected_tables_alias = !empty($_POST["sta"]) ? $_POST["sta"] : (isset($_GET["sta"]) ? $_GET["sta"] : null);
$active_brokers = !empty($_POST["ab"]) ? $_POST["ab"] : (isset($_GET["ab"]) ? $_GET["ab"] : null);
$active_brokers_folder = !empty($_POST["abf"]) ? $_POST["abf"] : (isset($_GET["abf"]) ? $_GET["abf"] : null);
//echo "<pre>";print_r($selected_tables);print_r($selected_tables_alias);die();
//echo "<pre>";print_r($_POST);die();

//TODO: add filter_by_layout to url to filter the folders where to search

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

$selected_tables_alias = is_array($selected_tables_alias) ? $selected_tables_alias : (!is_array($selected_tables) && $selected_tables && $selected_tables_alias ? array($selected_tables => $selected_tables_alias) : array());
$selected_tables = is_array($selected_tables) ? $selected_tables : ($selected_tables ? array($selected_tables) : array());

if ($path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$P = $PEVC->getPresentationLayer();
		
		$user_global_variables_files_path = array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config"));
		$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_files_path);
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		//PREPARING TASKS-TABLES
		$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
		
		if ($type == "diagram") {//TRYING TO GET THE DB TABLES FROM THE TASK FLOW
			$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $db_driver);
			$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
			$tasks = $WorkFlowDataAccessHandler->getTasks();
		}
		else {//TRYING TO GET THE DB TABLES DIRECTLY FROM DB
			if (!$db_layer_file) {
				$db_driver_props = WorkFlowBeansFileHandler::getLayerDBDriverProps($user_global_variables_files_path, $user_beans_folder_path, $P, $db_driver);
				$db_layer_file = $db_driver_props && isset($db_driver_props[1]) ? $db_driver_props[1] : null;
			}
			
			$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_files_path);
			$tasks = $db_layer_file ? $WorkFlowDBHandler->getUpdateTaskDBDiagram($db_layer_file, $db_driver) : array();
			$WorkFlowDataAccessHandler->setTasks($tasks);
			$tasks = $WorkFlowDataAccessHandler->getTasks();
		}
		//echo "<pre>";print_r($tasks);die();
		
		$tasks = isset($tasks["tasks"]) ? $tasks["tasks"] : null;
		$foreign_keys = $WorkFlowDataAccessHandler->getForeignKeys();
		MyArray::arrKeysToLowerCase($foreign_keys, true);
		//echo "<pre>";print_r($foreign_keys);
		
		$tables = $WorkFlowDataAccessHandler->getTasksAsTables();
		MyArray::arrKeysToLowerCase($tables, true);
		//echo "<pre>";print_r($tables);die();
		
		//PREPARING BROKERS
		$brokers = $P->getBrokers();
		foreach ($brokers as $broker_name => $broker)
			if (empty($active_brokers[$broker_name]))
				unset($brokers[$broker_name]);
		
		//PREPARING DB DRIVERS
		$db_drivers = WorkFlowBeansFileHandler::getBrokersDBDrivers($user_global_variables_files_path, $user_beans_folder_path, $brokers, true);
		$db_drivers = array_keys($db_drivers);
		//echo "<pre>";print_r($db_drivers);die();
		
		//PREPARING PROPS
		$props = array(
			"tables" => array(),
			"brokers" => getPresentationBrokersProps($brokers),
		);
		//echo "<pre>";print_r($props);die();
		
		$layer_brokers_settings = WorkFlowBeansFileHandler::getLayerBrokersSettings($user_global_variables_files_path, $user_beans_folder_path, $brokers);
		
		$db_broker = $db_driver && $brokers && count($db_drivers) > 1 ? WorkFlowBeansFileHandler::getBrokersLocalDBBrokerNameForChildBrokerDBDriver($user_global_variables_files_path, $user_beans_folder_path, $brokers, $db_driver) : null;
		//Do not add here $GLOBALS["default_db_broker"] bc it will be already passed automatically through the RESTClientBroker
		$include_db_broker = $include_db_driver || (isset($layer_brokers_settings["db_brokers"]) && count($layer_brokers_settings["db_brokers"]) > 1); //check if exists other db broker and if not, do not include db_broker, bc is the only one
		
		$t = count($selected_tables);
		for ($i = 0; $i < $t; $i++) {
			$table_name = strtolower($selected_tables[$i]);
			$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
			
			if ($attrs) {
				$props["tables"][$table_name] = getPresentationTableUIProps($user_global_variables_files_path, $user_beans_folder_path, $table_name, $tables, $foreign_keys, $brokers, $db_broker, count($db_drivers) > 1 ? $db_driver : null, $include_db_broker, $include_db_driver, $selected_tables_alias, $active_brokers_folder, $WorkFlowDataAccessHandler, $UserAuthenticationHandler, $filter_by_layout);
			}
		}
		
		MyArray::arrKeysToLowerCase($props, true);
		//echo "<pre>";print_r($props);die();
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
}

function getPresentationBrokersProps($brokers) {
	$props = array();
	
	foreach ($brokers as $broker_name => $broker) {
		if (is_a($broker, "IBusinessLogicBrokerClient")) 
			$props["business_logic_broker_name"] = $broker_name;
		else if (is_a($broker, "IHibernateDataAccessBrokerClient")) 
			$props["hibernate_broker_name"] = $broker_name;
		else if (is_a($broker, "IIbatisDataAccessBrokerClient"))
			$props["ibatis_broker_name"] = $broker_name;
		else if (is_a($broker, "IDBBrokerClient"))
			$props["db_broker_name"] = $broker_name;
	}
	
	return $props;
}

function getPresentationTableUIProps($user_global_variables_file_path, $user_beans_folder_path, $table_name, $tables, $foreign_keys, $brokers, $db_broker, $db_driver, $include_db_broker, $include_db_driver, $selected_tables_alias, $active_brokers_folder, $WorkFlowDataAccessHandler, $UserAuthenticationHandler, $filter_by_layout) {
	$props = array();
	
	$table = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
	$fks = WorkFlowDBHandler::getTableFromTables($foreign_keys, $table_name);
	
	if ($filter_by_layout) {
		//only allow filter if really exists
		if (!$UserAuthenticationHandler->searchLayoutTypes(array("name" => $filter_by_layout, "type_id" => UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID)))
			$filter_by_layout = null;
		else
			$UserAuthenticationHandler->loadLayoutPermissions($filter_by_layout, UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID);
	}
	
	foreach ($brokers as $broker_name => $broker) {
		$layer_props = WorkFlowBeansFileHandler::getLocalBeanLayerFromBroker($user_global_variables_file_path, $user_beans_folder_path, $broker);
		$Layer = isset($layer_props[2]) ? $layer_props[2] : null;
		
		if ($Layer && (is_a($Layer, "DataAccessLayer") || is_a($Layer, "BusinessLogicLayer") || is_a($Layer, "DBLayer"))) {
			$layer_object_id = $Layer->getLayerPathSetting();
			$path = isset($active_brokers_folder[$broker_name]) && trim($active_brokers_folder[$broker_name]) ? trim($active_brokers_folder[$broker_name]) : "";
			
			if ($path && substr($path, -1) != "/")
				$path .= "/";
			//echo "path:$path";die();
			
			$bean_objs = is_a($Layer, "DBLayer") ? array() : AdminMenuHandler::getBeanLayerObjs($Layer, $path, -1); 
			unset($bean_objs["aliases"]);
			unset($bean_objs["properties"]);
			//echo "<pre>";print_r(array_keys($bean_objs));print_r($bean_objs);die();
			
			if (is_a($broker, "IBusinessLogicBrokerClient")) {
				$objs = parseBeanObjects($bean_objs, array("method"), array("cms_module", "cms_program", "cms_resource", "cms_common", "folder", "file", "service"), $UserAuthenticationHandler, $layer_object_id, $filter_by_layout);
				//echo "<pre>";print_r($objs);die();
				$props[$broker_name] = getBusinessLogicProps($objs, $table_name, $table, $fks, $db_broker, $db_driver, $include_db_broker, $include_db_driver, $selected_tables_alias);
				//echo "<pre>";print_r($props[$broker_name]);die();
			}
			else if (is_a($broker, "IHibernateDataAccessBrokerClient")) {
				$objs = parseBeanObjects($bean_objs, array("obj", "query", "relationship"), array("cms_module", "cms_program", "cms_resource", "cms_common", "folder", "file", "obj"), $UserAuthenticationHandler, $layer_object_id, $filter_by_layout);
				//echo "<pre>";print_r($objs);die();
				$props[$broker_name] = getHibernateProps($objs, $table_name, $table, $fks, $db_broker, $db_driver, $include_db_broker, $include_db_driver, $selected_tables_alias);
				//echo "<pre>";print_r($props[$broker_name]);die();
			}
			else if (is_a($broker, "IIbatisDataAccessBrokerClient")) {
				$objs = parseBeanObjects($bean_objs, array("query"), array("cms_module", "cms_program", "cms_resource", "cms_common", "folder", "file"), $UserAuthenticationHandler, $layer_object_id, $filter_by_layout);
				//echo "<pre>";print_r($objs);die();
				$props[$broker_name] = getIbatisProps($objs, $table_name, $table, $fks, $db_broker, $db_driver, $include_db_broker, $include_db_driver, $selected_tables_alias);
				//echo "<pre>";print_r($props[$broker_name]);die();
				
				foreach ($props[$broker_name] as $type => $prop) {
					$SQLClient = $Layer->getSQLClient();
					
					if ($type == "relationships" || $type == "relationships_count") {
						foreach ($prop as $tn => $rp) {
							$rp_path = isset($rp["path"]) ? $rp["path"] : null;
							$rp_service_type = isset($rp["service_type"]) ? $rp["service_type"] : null;
							$rp_service_id = isset($rp["service_id"]) ? $rp["service_id"] : null;
							
							$SQLClient->loadXML($Layer->getLayerPathSetting() . "/" . $rp_path);
							$query = $SQLClient->getQuery($rp_service_type, $rp_service_id);
					
							if ($query && !empty($query["value"])) {
								$props[$broker_name][$type][$tn]["sql"] = str_replace(array("\t", "#searching_condition#"), "", $query["value"]);
								$props[$broker_name][$type][$tn]["sql_type"] = "string";
								
								if ($type == "relationships_count")
									$props[$broker_name][$type][$tn]["sql"] = preg_replace("/\s*WHERE\s*1=1\s*$/", "", $props[$broker_name][$type][$tn]["sql"]);
							}
						}
					}
					else {
						$prop_path = isset($prop["path"]) ? $prop["path"] : null;
						$prop_service_type = isset($prop["service_type"]) ? $prop["service_type"] : null;
						$prop_service_id = isset($prop["service_id"]) ? $prop["service_id"] : null;
						
						$SQLClient->loadXML($Layer->getLayerPathSetting() . "/" . $prop_path);
						$query = $SQLClient->getQuery($prop_service_type, $prop_service_id);
						
						if ($query && !empty($query["value"])) {
							$props[$broker_name][$type]["sql"] = str_replace(array("\t", "#searching_condition#"), "", $query["value"]);
							$props[$broker_name][$type]["sql_type"] = "string";
							
							if ($type == "count" || $type == "get_all")
								$props[$broker_name][$type]["sql"] = preg_replace("/\s*WHERE\s*1=1\s*$/", "", $props[$broker_name][$type]["sql"]);
						}
					}
				}
				//echo "<pre>";print_r($props[$broker_name]);die();
			}
			else if (is_a($broker, "IDBBrokerClient")) {
				$props[$broker_name] = getDBTableProps($table_name, $table, $fks, $db_broker, $db_driver, $include_db_broker, $include_db_driver, $selected_tables_alias, $WorkFlowDataAccessHandler);
				
				foreach ($props[$broker_name] as $type => $prop) {
					if ($type == "relationships" || $type == "relationships_count") {
						foreach ($prop as $tn => $rp) {
							$rp_sql = isset($rp["sql"]) ? $rp["sql"] : null;
							$props[$broker_name][$type][$tn]["sql"] = str_replace(array("\t", "#searching_condition#"), "", $rp_sql);
							
							if ($type == "relationships_count")
								$props[$broker_name][$type][$tn]["sql"] = preg_replace("/\s*WHERE\s*1=1\s*$/", "", $props[$broker_name][$type][$tn]["sql"]);
						}
					}
					else {
						$prop_sql = isset($prop["sql"]) ? $prop["sql"] : null;
						$props[$broker_name][$type]["sql"] = str_replace(array("\t", "#searching_condition#"), "", $prop_sql);
						
						if ($type == "count" || $type == "get_all")
							$props[$broker_name][$type]["sql"] = preg_replace("/\s*WHERE\s*1=1\s*$/", "", $props[$broker_name][$type]["sql"]);
					}
				}
				
				//echo "<pre>";print_r($props[$broker_name]);die();
			}
		}		
	}
	
	//print_r($props);die();
	return $props;
}

function getBusinessLogicProps($objs, $table_name, $table, $fks, $db_broker, $db_driver, $include_db_broker, $include_db_driver, $selected_tables_alias) {
	$props = array();
	
	//PREPARING TABLE SETTINGS:
	$table_alias = getTableAlias($table_name, $selected_tables_alias);
	
	$class_name = WorkFlowDataAccessHandler::getClassName($table_name);
	$class_alias = $table_alias ? WorkFlowDataAccessHandler::getClassName($table_alias) : null;
	
	$service_name = $class_name . "Service";
	$service_alias = $class_alias ? $class_alias . "Service" : null;
	
	//PREPARING FOREIGN METHODS SETTINGS:
	$foreign_queries_name = getForeignQueriesName($fks, $table_name, $selected_tables_alias);
	
	$foreign_methods = array();
	$foreign_count_methods = array();
	$repeated_foreign_table_names = array();
	foreach ($foreign_queries_name as $foreign_query_name => $foreign_table_name) {
		$foreign_table_alias = getTableAlias($foreign_table_name, $selected_tables_alias);
		
		$rn = WorkFlowDataAccessHandler::getClassName($foreign_query_name);
		
		if (!in_array($foreign_table_name, $repeated_foreign_table_names)) {
			$repeated_foreign_table_names[] = $foreign_table_name;
			
			$tn = WorkFlowDataAccessHandler::getClassName($foreign_table_name);
			$ta = $foreign_table_alias ? WorkFlowDataAccessHandler::getClassName($foreign_table_alias) : null;
		
			$tnp = CMSPresentationFormSettingsUIHandler::getPlural($tn);
			$tap = $ta ? CMSPresentationFormSettingsUIHandler::getPlural($ta) : null;
		
			$method = "findRelationship" . $tn;
			$foreign_methods[$method] = $foreign_table_name;
			$foreign_count_methods[str_replace("findRelationship", "countRelationship", $method)] = $foreign_table_name;
		
			if ($ta) {
				$method = "findRelationship" . $ta;
				$foreign_methods[$method] = $foreign_table_name;
				$foreign_count_methods[str_replace("findRelationship", "countRelationship", $method)] = $foreign_table_name;
			}
		
			$method = "findRelationship" . $class_name . $tn;
			$foreign_methods[$method] = $foreign_table_name;
			$foreign_count_methods[str_replace("findRelationship", "countRelationship", $method)] = $foreign_table_name;
		
			if ($ta) {
				$method = "findRelationship" . $class_name . $ta;
				$foreign_methods[$method] = $foreign_table_name;
				$foreign_count_methods[str_replace("findRelationship", "countRelationship", $method)] = $foreign_table_name;
			}
		
			if ($class_alias) {
				$method = "findRelationship" . $class_alias . $tn;
				$foreign_methods[$method] = $foreign_table_name;
				$foreign_count_methods[str_replace("findRelationship", "countRelationship", $method)] = $foreign_table_name;
			
				if ($ta) {
					$method = "findRelationship" . $class_alias . $ta;
					$foreign_methods[$method] = $foreign_table_name;
					$foreign_count_methods[str_replace("findRelationship", "countRelationship", $method)] = $foreign_table_name;
				}
			}
		
			$method = "findRelationship" . $tnp;
			$foreign_methods[$method] = $foreign_table_name;
			$foreign_count_methods[str_replace("findRelationship", "countRelationship", $method)] = $foreign_table_name;
		
			if ($tap) {
				$method = "findRelationship" . $tap;
				$foreign_methods[$method] = $foreign_table_name;
				$foreign_count_methods[str_replace("findRelationship", "countRelationship", $method)] = $foreign_table_name;
			}
			
			$method = "findRelationship" . $class_name . $tnp;
			$foreign_methods[$method] = $foreign_table_name;
			$foreign_count_methods[str_replace("findRelationship", "countRelationship", $method)] = $foreign_table_name;
		
			if ($class_alias) {
				$method = "findRelationship" . $class_alias . $tnp;
				$foreign_methods[$method] = $foreign_table_name;
				$foreign_count_methods[str_replace("findRelationship", "countRelationship", $method)] = $foreign_table_name;
			
				if ($tap) {
					$method = "findRelationship" . $class_alias . $tap;
					$foreign_methods[$method] = $foreign_table_name;
					$foreign_count_methods[str_replace("findRelationship", "countRelationship", $method)] = $foreign_table_name;
				}
			}
		}
		
		$method = stripos($rn, "findrelationship") !== false ? lcfirst($rn) : "findRelationship" . $rn;
		$foreign_methods[$method] = $foreign_table_name;
		$foreign_count_methods[str_replace("findRelationship", "countRelationship", $method)] = $foreign_table_name;
		
		$method = stripos($rn, "get") !== false || stripos($rn, "select") !== false ? lcfirst($rn) : "get" . $rn;
		$foreign_methods[$method] = $foreign_table_name;
		$foreign_count_methods[str_replace(array("get", "select"), "count", $method)] = $foreign_table_name;
		
		$method = "count" . ucfirst(str_ireplace(array("numberof", "number", "total", "get", "select"), "", $method));
		$foreign_count_methods[$method] = $foreign_table_name;
	}
	//echo "<pre>";print_r($foreign_methods);die();
	//echo "<pre>";print_r($foreign_count_methods);die();
	//echo "<pre>";print_r($objs);die();
	
	//PREPARING ALL METHODS:
	if ($objs) {
		$snl = strtolower($service_name);
		$sasl = strtolower($service_alias);
		$t = count($objs);
		
		for ($i = 0; $i < $t; $i++) {
			$obj = $objs[$i];
			$osl = isset($obj["service"]) ? strtolower($obj["service"]) : "";
			$opl = isset($obj["path"]) ? strtolower($obj["path"]) : "";
			
			//remove namespace from service if exists
			if (strpos($osl, "\\") !== false)
				$osl = substr($osl, strrpos($osl, "\\") + 1);
			
			$cond = isset($obj["item_type"]) && $obj["item_type"] == "method" && 
				($osl == $snl || ($sasl && $osl == $sasl)) && 
				(stripos($opl, "/{$snl}.php") !== false || ($sasl && stripos($opl, "/{$sasl}.php") !== false));
			
			if ($cond) {
				$name = isset($obj["name"]) ? $obj["name"] : null;
				$lower_name = strtolower($name);
				$lower_class_name = strtolower($class_name);
				$lower_class_alias = strtolower($class_alias);
				$type = null;
				
				if (
					$lower_name == "insert" || 
					$lower_name == "insert$lower_class_name" || 
					$lower_name == "insert$lower_class_alias"
				) 
					$type = "insert";
				else if (
					$lower_name == "update" || 
					$lower_name == "update$lower_class_name" || 
					$lower_name == "update$lower_class_alias"
				) 
					$type = "update";
				else if (
					$lower_name == "updateprimarykeys" || 
					$lower_name == "updatepks" || 
					$lower_name == "update{$lower_class_name}primarykeys" || 
					$lower_name == "update{$lower_class_alias}primarykeys" || 
					$lower_name == "update{$lower_class_name}pks" || 
					$lower_name == "update{$lower_class_alias}pks"
				) 
					$type = "update_pks";
				else if (
					$lower_name == "updateall" || 
					$lower_name == "updates" || 
					$lower_name == "updateall{$lower_class_name}" || 
					$lower_name == "updateall{$lower_class_alias}" || 
					$lower_name == "updateall{$lower_class_name}items" || 
					$lower_name == "updateall{$lower_class_alias}items" || 
					$lower_name == "update{$lower_class_name}all" || 
					$lower_name == "update{$lower_class_alias}all" || 
					$lower_name == "update{$lower_class_name}allitems" || 
					$lower_name == "update{$lower_class_alias}allitems" || 
					$lower_name == "update{$lower_class_name}items" || 
					$lower_name == "update{$lower_class_alias}items"
				) 
					$type = "update_all";
				else if (
					$lower_name == "delete" || 
					$lower_name == "delete$lower_class_name" || 
					$lower_name == "delete$lower_class_alias"
				) 
					$type = "delete";
				else if (
					$lower_name == "deleteall" || 
					$lower_name == "deletes" || 
					$lower_name == "deleteall{$lower_class_name}" || 
					$lower_name == "deleteall{$lower_class_alias}" || 
					$lower_name == "deleteall{$lower_class_name}items" || 
					$lower_name == "deleteall{$lower_class_alias}items" || 
					$lower_name == "delete{$lower_class_name}all" || 
					$lower_name == "delete{$lower_class_alias}all" || 
					$lower_name == "delete{$lower_class_name}allitems" || 
					$lower_name == "delete{$lower_class_alias}allitems" || 
					$lower_name == "delete{$lower_class_name}items" || 
					$lower_name == "delete{$lower_class_alias}items"
				) 
					$type = "delete_all";
				else if (
					$lower_name == "get" || 
					$lower_name == "get$lower_class_name" || 
					$lower_name == "get$lower_class_alias" || 
					$lower_name == "findbyid"
				) 
					$type = "get";
				else if (
					$lower_name == "getall" || 
					$lower_name == "getall{$lower_class_name}items" || 
					$lower_name == "getall{$lower_class_alias}items" || 
					$lower_name == "get{$lower_class_name}items" || 
					$lower_name == "get{$lower_class_alias}items" || 
					$lower_name == "get{$lower_class_name}s" || 
					$lower_name == "get{$lower_class_alias}s" || 
					$lower_name == "getitems" || 
					$lower_name == "gets" || 
					$lower_name == "find"
				)
					$type = "get_all";
				else if (
					$lower_name == "countall" || 
					$lower_name == "counts" || 
					$lower_name == "count" || 
					$lower_name == "countall{$lower_class_name}items" || 
					$lower_name == "countall{$lower_class_alias}items" || 
					$lower_name == "count{$lower_class_name}items" || 
					$lower_name == "count{$lower_class_alias}items" || 
					$lower_name == "count{$lower_class_name}s" || 
					$lower_name == "count{$lower_class_alias}s" || 
					$lower_name == "countitems"
				) 
					$type = "count";
				else if (!empty($foreign_methods[$name]))
					$type = "relationships";
				else if (!empty($foreign_count_methods[$name]))
					$type = "relationships_count";
				
				if ($type) {
					$sn = stripos($opl, "/{$snl}.php") !== false ? $service_name : $service_alias;
					
					$prop = array(
						"path" => isset($obj["path"]) ? $obj["path"] : null,
						"module_id" => isset($obj["path"]) ? dirname($obj["path"]) : null,
						//"module_id" => str_replace("/", ".", dirname($obj["path"])),  //2021-01-17 JP: Or it could be this code. It doesn't really matter. Even if there are folders with "." in the names, the system detects it. The module_id with "/" is faster before cache happens, but after the first call for this module, it doesn't really matter anymore bc the module_path is cached with the correspondent module_id.
						"module_id_type" => "string",
						"service_id" => "$sn.$name",
						"service_id_type" => "string",
						"obj" => $sn,
						"method" => $name,
						"options_type" => "array",
						"options" => array(
							array(
								"key" => "no_cache",
								"key_type" => "string",
								"value" => true,
								"value_type" => "",
							)
						),
					);
					
					if ($db_broker && $include_db_broker) 
						$prop["options"][] = array(
							"key" => "db_broker",
							"key_type" => "string",
							"value" => $db_broker,
							"value_type" => "string",
						);
					
					if ($db_driver && $include_db_driver) 
						$prop["options"][] = array(
							"key" => "db_driver",
							"key_type" => "string",
							"value" => $db_driver,
							"value_type" => "string",
						);
					
					if ($type == "relationships") {
						$tn = isset($foreign_methods[$name]) ? $foreign_methods[$name] : null;
						$props[$type][$tn] = $prop;
					}
					else if ($type == "relationships_count") {
						$tn = isset($foreign_count_methods[$name]) ? $foreign_count_methods[$name] : null;
						$props[$type][$tn] = $prop;
					}
					else
						$props[$type] = $prop;
				}
			}
		}
	}	
	
	//echo "<pre>";print_r($props);die();
	//echo "<pre>";print_r($objs);die();
	return $props;
}

function getHibernateProps($objs, $table_name, $table, $fks, $db_broker, $db_driver, $include_db_broker, $include_db_driver, $selected_tables_alias) {
	$props = array();
	
	//PREPARING TABLE SETTINGS:
	$table_alias = getTableAlias($table_name, $selected_tables_alias);
	$tnl = strtolower($table_name);
	$parsed_tnl = str_replace(".", "_", $tnl); //Table name may have schema
	$tal = strtolower($table_alias);
	
	//PREPARING FOREIGN METHODS SETTINGS:
	$foreign_queries_name = getForeignQueriesName($fks, $table_name, $selected_tables_alias);
	//echo "<pre>";print_r($foreign_queries_name);die();
	//echo "<pre>";print_r($fks);die();
	
	
	if ($fks) {
		$t = count($fks); 
		for ($i = 0; $i < $t; $i++) {
			$fk = $fks[$i];
			$fk_type = isset($fk["type"]) ? $fk["type"] : null;
			$fk_child_table = isset($fk["child_table"]) ? $fk["child_table"] : null;
			$fk_parent_table = isset($fk["parent_table"]) ? $fk["parent_table"] : null;
			
			$foreign_table_name = strtolower($fk_child_table) == $tnl ? $fk_parent_table : $fk_child_table;
			$foreign_table_alias = getTableAlias($foreign_table_name, $selected_tables_alias);
			
			$relationship_name = WorkFlowDataAccessHandler::getForeignTableQueryName($table_name, $foreign_table_name, $fk_type);
			$relationship_name = $relationship_name ? substr($relationship_name, strlen("get_")) : $relationship_name;//remove get_
			$foreign_queries_name[$relationship_name] = $foreign_table_name;
			
			if ($table_alias) {
				if ($foreign_table_alias) {
					$relationship_name = WorkFlowDataAccessHandler::getForeignTableQueryName($table_alias, $foreign_table_alias, $fk_type);
					$relationship_name = $relationship_name ? substr($relationship_name, strlen("get_")) : $relationship_name;//remove get_
					$foreign_queries_name[$relationship_name] = $foreign_table_name;
				}
				
				$relationship_name = WorkFlowDataAccessHandler::getForeignTableQueryName($table_alias, $foreign_table_name, $fk_type);
				$relationship_name = $relationship_name ? substr($relationship_name, strlen("get_")) : $relationship_name;//remove get_
				$foreign_queries_name[$relationship_name] = $foreign_table_name;
			}
		}
	}
	
	//echo "<pre>";print_r($foreign_queries_name);die();
	//echo "<pre>";print_r($objs);die();
	
	//PREPARING ALL METHODS:
	if ($objs) {
		$t = count($objs);
		
		for ($i = 0; $i < $t; $i++) {
			$obj = $objs[$i];
			$otl = isset($obj["table"]) ? strtolower($obj["table"]) : null;
			$opl = isset($obj["path"]) ? strtolower($obj["path"]) : null;
			
			$cond = isset($obj["item_type"]) && $obj["item_type"] == "obj" && 
				($otl == $tnl || ($table_alias && $otl == $tal)) && 
				(stripos($opl, "/{$tnl}.xml") !== false || stripos($opl, "/{$parsed_tnl}.xml") !== false || ($table_alias && stripos($opl, "/{$tal}.xml") !== false));
			
			if ($cond) {
				$prop = array(
					"path" => isset($obj["path"]) ? $obj["path"] : null,
					"module_id" => isset($obj["path"]) ? dirname($obj["path"]) : null,
					//"module_id" => str_replace("/", ".", dirname($obj["path"])), //2021-01-17 JP: Or it could be this code. It doesn't really matter. Even if there are folders with "." in the names, the system detects it. The module_id with "/" is faster before cache happens, but after the first call for this module, it doesn't really matter anymore bc the module_path is cached with the correspondent module_id.
					"module_id_type" => "string",
					"service_id" => isset($obj["name"]) ? $obj["name"] : null,
					"service_id_type" => "string",
					"service_method_type" => "string",
					"sma_options_type" => "array",
					"sma_options" => array(
						array(
							"key" => "no_cache",
							"key_type" => "string",
							"value" => true,
							"value_type" => "",
						)
					),
				);
				
				if ($db_broker || $db_driver) {
					$prop["options_type"] = "array";
					$prop["options"] = array();
					
					if ($db_broker && $include_db_broker)
						$prop["options"][] = array(
							"key" => "db_broker",
							"key_type" => "string",
							"value" => $db_broker,
							"value_type" => "string",
						);
					
					if ($db_driver && $include_db_driver)
						$prop["options"][] = array(
							"key" => "db_driver",
							"key_type" => "string",
							"value" => $db_driver,
							"value_type" => "string",
						);
				}
				
				$queries = isset($obj["childs"]) ? $obj["childs"] : null;
				
				//This part is for the queries which are foregin keys, because the other ones will be replaced by the native methods.
				$queries_props = getIbatisProps($queries, $table_name, $table, $fks, $db_broker, $db_driver, $include_db_broker, $include_db_driver, $selected_tables_alias);
				foreach ($queries_props as $type => $p) {
					if ($type == "relationships" || $type == "relationships_count") {
						foreach ($p as $tn => $rp) {
							$prop["service_method"] = "call" . (isset($rp["service_type"]) ? ucfirst(strtolower($rp["service_type"])) : "");
							$prop["sma_query_id"] = isset($rp["service_id"]) ? $rp["service_id"] : null;
							$prop["sma_query_id"] = isset($rp["service_id_type"]) ? $rp["service_id_type"] : null;
							
							$props[$type][$tn] = $prop;
						}
					}
					else {
						$prop["service_method"] = "call" . (isset($p["service_type"]) ? ucfirst(strtolower($p["service_type"])) : "");
						$prop["sma_query_id"] = isset($p["service_id"]) ? $p["service_id"] : null;
						$prop["sma_query_id_type"] = isset($p["service_id_type"]) ? $p["service_id_type"] : null;
						$props[$type] = $prop;
					}
				}
				unset($prop["sma_query_id"]);
				unset($prop["sma_query_id_type"]);
				
				if ($queries) {
					$t = count($queries);
					for ($i = 0; $i < $t; $i++) {
						$query = $queries[$i];
						$query_name = isset($query["name"]) ? $query["name"] : null;
						
						if (isset($query["item_type"]) && $query["item_type"] == "relationship" && !empty($foreign_queries_name[$query_name])) {
							$prop["service_method"] = "findRelationship";
							$prop["sma_rel_name"] = $query_name;
							$prop["sma_rel_name_type"] = "string";
							
							$tn = $foreign_queries_name[$query_name];
							$props["relationships"][$tn] = $prop;
							
							$prop["service_method"] = "countRelationship";
							$props["relationships_count"][$tn] = $prop;
						}
					}
				}
				unset($prop["sma_rel_name"]);
				unset($prop["sma_rel_name_type"]);
				
				$prop["service_method"] = "insert";
				$props["insert"] = $prop;
				
				$prop["service_method"] = "update";
				$props["update"] = $prop;
			
				$prop["service_method"] = "updatePrimaryKeys";
				$props["update_pks"] = $prop;
			
				$prop["service_method"] = "updateAll";
				$props["update_all"] = $prop;
			
				$prop["service_method"] = "delete";
				$props["delete"] = $prop;
			
				$prop["service_method"] = "deleteAll";
				$props["delete_all"] = $prop;
			
				$prop["service_method"] = "findById";
				$props["get"] = $prop;
			
				$prop["service_method"] = "find";
				$props["get_all"] = $prop;
			
				$prop["service_method"] = "count";
				$props["count"] = $prop;
			}
		}
	}
	
	//echo "<pre>";print_r($props);die();
	return $props;
}

function getIbatisProps($objs, $table_name, $table, $fks, $db_broker, $db_driver, $include_db_broker, $include_db_driver, $selected_tables_alias) {
	$props = array();
	
	//PREPARING TABLE SETTINGS:
	$table_alias = getTableAlias($table_name, $selected_tables_alias);
	$tnl = strtolower($table_name);
	$parsed_tnl = str_replace(".", "_", $tnl); //Table name may have schema
	$tal = strtolower($table_alias);
	
	//PREPARING FOREIGN METHODS SETTINGS:
	$foreign_queries_name = getForeignQueriesName($fks, $table_name, $selected_tables_alias);
	$foreign_queries_count_name = getForeignQueriesCountName($fks, $table_name, $selected_tables_alias);
	
	//echo "<pre>";print_r($foreign_queries_name);die();
	//echo "<pre>";print_r($objs);die();
	
	//PREPARING ALL METHODS:
	if ($objs) {
		$t = count($objs);
		for ($i = 0; $i < $t; $i++) {
			$obj = $objs[$i];
			$opl = isset($obj["path"]) ? strtolower($obj["path"]) : null;
			
			$cond = isset($obj["item_type"]) && $obj["item_type"] == "query" && 
				(stripos($opl, "/{$tnl}.xml") !== false || stripos($opl, "/{$parsed_tnl}.xml") !== false || ($table_alias && stripos($opl, "/{$tal}.xml") !== false));
			
			if ($cond) {
				$name = isset($obj["name"]) ? $obj["name"] : null;
				$query_type = isset($obj["query_type"]) ? $obj["query_type"] : null;
				$type = getQueryPropType($table_name, $table_alias, $foreign_queries_name, $foreign_queries_count_name, $query_type, $name);
				
				if ($type) {
					$prop = array(
						"path" => isset($obj["path"]) ? $obj["path"] : null,
						"module_id" => isset($obj["path"]) ? dirname($obj["path"]) : null, 
						//"module_id" => str_replace("/", ".", dirname($obj["path"])),  //2021-01-17 JP: Or it could be this code. It doesn't really matter. Even if there are folders with "." in the names, the system detects it. The module_id with "/" is faster before cache happens, but after the first call for this module, it doesn't really matter anymore bc the module_path is cached with the correspondent module_id.
						"module_id_type" => "string",
						"service_type" => $query_type,
						"service_type_type" => "string",
						"service_id" => $name,
						"service_id_type" => "string",
						"options_type" => "array",
						"options" => array(
							array(
								"key" => "no_cache",
								"key_type" => "string",
								"value" => true,
								"value_type" => "",
							)
						),
					);
					
					if ($db_broker && $include_db_broker)
						$prop["options"][] = array(
							"key" => "db_broker",
							"key_type" => "string",
							"value" => $db_broker,
							"value_type" => "string",
						);
					
					if ($db_driver && $include_db_driver)
						$prop["options"][] = array(
							"key" => "db_driver",
							"key_type" => "string",
							"value" => $db_driver,
							"value_type" => "string",
						);
					
					if ($type == "relationships") {
						$tn = isset($foreign_queries_name[$name]) ? $foreign_queries_name[$name] : null;
						$props[$type][$tn] = $prop;
					}
					else if ($type == "relationships_count") {
						$tn = isset($foreign_queries_count_name[$name]) ? $foreign_queries_count_name[$name] : null;
						$props[$type][$tn] = $prop;
					}
					else
						$props[$type] = $prop;
				}
			}
		}
	}
		
	//echo "<pre>";print_r($props);die();
	return $props;
}

function getDBTableProps($table_name, $table, $fks, $db_broker, $db_driver, $include_db_broker, $include_db_driver, $selected_tables_alias, $WorkFlowDataAccessHandler) {
	$props = array();
	
	//PREPARING TABLE SETTINGS:
	$table_alias = getTableAlias($table_name, $selected_tables_alias);
	$tnl = strtolower($table_name);
	$parsed_tnl = str_replace(".", "_", $tnl); //Table name may have schema
	$tal = strtolower($table_alias);
	
	//PREPARING FOREIGN METHODS SETTINGS:
	$foreign_queries_name = getForeignQueriesName($fks, $table_name, $selected_tables_alias);
	$foreign_queries_count_name = getForeignQueriesCountName($fks, $table_name, $selected_tables_alias);
	
	//echo "<pre>";print_r($foreign_queries_name);die();
	
	$arr = $WorkFlowDataAccessHandler->getQueryObjectsArrayFromDBTaskFlow($table_name);
	//print_r($arr);die();
	
	if ($arr && !empty($arr["queries"][0]["childs"]))
		foreach ($arr["queries"][0]["childs"] as $query_type => $type_queries) 
			if ($type_queries) 
				foreach ($type_queries as $query) {
					$name = isset($query["@"]["id"]) ? $query["@"]["id"] : null;
					$sql = isset($query["value"]) ? $query["value"] : null;
					
					$type = getQueryPropType($table_name, $table_alias, $foreign_queries_name, $foreign_queries_count_name, $query_type, $name);
					
					if ($type) {
						$prop = array(
							"sql" => $sql,
							"sql_type" => "string",
							"options_type" => "array",
							"options" => array(
								array(
									"key" => "no_cache",
									"key_type" => "string",
									"value" => true,
									"value_type" => "",
								)
							),
						);
						
						if ($db_broker && $include_db_broker)
							$prop["options"][] = array(
								"key" => "db_broker",
								"key_type" => "string",
								"value" => $db_broker,
								"value_type" => "string",
							);
						
						if ($db_driver && $include_db_driver)
							$prop["options"][] = array(
								"key" => "db_driver",
								"key_type" => "string",
								"value" => $db_driver,
								"value_type" => "string",
							);
						
						if ($type == "relationships") {
							$tn = isset($foreign_queries_name[$name]) ? $foreign_queries_name[$name] : null;
							$props[$type][$tn] = $prop;
						}
						else if ($type == "relationships_count") {
							$tn = isset($foreign_queries_count_name[$name]) ? $foreign_queries_count_name[$name] : null;
							$props[$type][$tn] = $prop;
						}
						else
							$props[$type] = $prop;
					}
				}
	
	return $props;
}

function getQueryPropType($table_name, $table_alias, $foreign_queries_name, $foreign_queries_count_name, $query_type, $query_name) {
	$type = null;
	
	$table_name_plural = CMSPresentationFormSettingsUIHandler::getPlural($table_name);
	$table_alias_plural = $table_alias ? CMSPresentationFormSettingsUIHandler::getPlural($table_alias) : null;
	
	$parsed_table_name = str_replace(".", "_", $table_name); //$table_name may have the schema
	$parsed_table_name_plural = str_replace(".", "_", $table_name_plural); //$table_name_plural may have the schema
	
	if (
		$query_type == "insert" && 
		(
			$query_name == "insert_$table_name" || 
			$query_name == "insert_$parsed_table_name"
		)
	) 
		$type = "insert";
	else if (
		$table_alias && 
		$query_type == "insert" && 
		$query_name == "insert_$table_alias"
	) 
		$type = "insert";
	else if (
		$query_type == "update" && 
		(
			$query_name == "update_$table_name" || 
			$query_name == "update_$parsed_table_name"
		)
	) 
		$type = "update";
	else if (
		$table_alias && 
		$query_type == "update" && 
		$query_name == "update_$table_alias"
	) 
		$type = "update";
	else if (
		$query_type == "update" && 
		(
			$query_name == "update_{$table_name}_primary_keys" || 
			$query_name == "update_{$table_name}_pks" || 
			$query_name == "update_{$parsed_table_name}_primary_keys" || 
			$query_name == "update_{$parsed_table_name}_pks"
		)
	) 
		$type = "update_pks";
	else if (
		$table_alias && 
		$query_type == "update" && 
		(
			$query_name == "update_{$table_alias}_primary_keys" || 
			$query_name == "update_{$table_alias}_pks"
		)
	) 
		$type = "update_pks";
	else if (
		$query_type == "update" && 
		(
			$query_name == "update_all_{$table_name}" || 
			$query_name == "update_{$table_name}_all" || 
			$query_name == "update_all_{$parsed_table_name}" || 
			$query_name == "update_{$parsed_table_name}_all" || 
			$query_name == "update_{$table_name}_all_items" || 
			$query_name == "update_{$parsed_table_name}_all_items" || 
			$query_name == "update_all_{$table_name}_items" || 
			$query_name == "update_all_{$parsed_table_name}_items"
		)
	) 
		$type = "update_all";
	else if (
		$table_alias && 
		$query_type == "update" && 
		(
			$query_name == "update_all_{$table_alias}" || 
			$query_name == "update_{$table_alias}_all" || 
			$query_name == "update_all_{$table_alias}_items" || 
			$query_name == "update_{$table_alias}_all_items"
		)
	) 
		$type = "update_all";
	else if (
		$query_type == "delete" && 
		(
			$query_name == "delete_$table_name" || 
			$query_name == "delete_$parsed_table_name"
		)
	) 
		$type = "delete";
	else if (
		$table_alias && 
		$query_type == "delete" && 
		$query_name == "delete_$table_alias"
	) 
		$type = "delete";
	else if (
		$query_type == "delete" && 
		(
			$query_name == "delete_all_{$table_name}" || 
			$query_name == "delete_{$table_name}_all" || 
			$query_name == "delete_all_{$parsed_table_name}" || 
			$query_name == "delete_{$parsed_table_name}_all" || 
			$query_name == "delete_{$table_name}_all_items" || 
			$query_name == "delete_{$parsed_table_name}_all_items" || 
			$query_name == "delete_all_{$table_name}_items" || 
			$query_name == "delete_all_{$parsed_table_name}_items"
		)
	) 
		$type = "delete_all";
	else if (
		$table_alias && 
		$query_type == "delete" && 
		(
			$query_name == "delete_all_{$table_alias}" || 
			$query_name == "delete_{$table_alias}_all" || 
			$query_name == "delete_all_{$table_alias}_items" || 
			$query_name == "delete_{$table_alias}_all_items"
		)
	) 
		$type = "delete_all";
	else if (
		$query_type == "select" && 
		(
			$query_name == "get_$table_name" || 
			$query_name == "get_$parsed_table_name"
		)
	) 
		$type = "get";
	else if (
		$table_alias && 
		$query_type == "select" && 
		$query_name == "get_$table_alias"
	) 
		$type = "get";
	else if (
		$query_type == "select" && 
		(
			$query_name == "get_all_{$table_name}_items" || 
			$query_name == "get_{$table_name}_items" || 
			$query_name == "get_{$table_name_plural}" || 
			$query_name == "get_all_{$parsed_table_name}_items" || 
			$query_name == "get_{$parsed_table_name}_items" || 
			$query_name == "get_{$parsed_table_name_plural}"
		)
	) 
		$type = "get_all";
	else if (
		$table_alias && 
		$query_type == "select" && 
		(
			$query_name == "get_all_{$table_alias}_items" || 
			$query_name == "get_{$table_alias}_items" || 
			$query_name == "get_{$table_alias_plural}"
		)
	) 
		$type = "get_all";
	else if (
		$query_type == "select" && 
		(
			$query_name == "count_all_{$table_name}_items" || 
			$query_name == "count_{$table_name}_items" || 
			$query_name == "count_{$table_name_plural}"
		)
	) 
		$type = "count";
	else if (
		$table_alias && 
		$query_type == "select" && 
		(
			$query_name == "count_all_{$table_alias}_items" || 
			$query_name == "count_{$table_alias}_items" || 
			$query_name == "count_{$table_alias_plural}"
		)
	) 
		$type = "count";
	else if (!empty($foreign_queries_name[$query_name]) && $query_type == "select")
		$type = "relationships";
	else if (!empty($foreign_queries_count_name[$query_name]) && $query_type == "select")
		$type = "relationships_count";
	
	return $type;
}

function getForeignQueriesName($foreign_keys, $table_name, $selected_tables_alias) {
	$names = array();
	
	if ($foreign_keys) {
		$table_alias = getTableAlias($table_name, $selected_tables_alias);
		//echo "<pre>$table_name:";print_r($foreign_keys);
		
		$ltn = strtolower($table_name);
		$lta = $table_alias ? strtolower($table_alias) : null;
		$parsed_ltn = str_replace(".", "_", $ltn); //$ltn may have the schema
		
		$t = count($foreign_keys); 
		for ($i = 0; $i < $t; $i++) {
			$fk = $foreign_keys[$i];
			$fk_type = isset($fk["type"]) ? $fk["type"] : null;
			$fk_child_table = isset($fk["child_table"]) ? $fk["child_table"] : null;
			$fk_parent_table = isset($fk["parent_table"]) ? $fk["parent_table"] : null;
			$lfkct = strtolower($fk_child_table);
			$foreign_table_name = $lfkct == $ltn ? $fk_parent_table : $fk_child_table;
			$foreign_table_alias = getTableAlias($foreign_table_name, $selected_tables_alias);
			
			$lftn = strtolower($foreign_table_name);
			$lfta = $foreign_table_alias ? strtolower($foreign_table_alias) : null;
			$parsed_lftn = str_replace(".", "_", $lftn); //$lftn may have the schema
			
			$lftnp = CMSPresentationFormSettingsUIHandler::getPlural($lftn);
			$lftap = $lfta ? CMSPresentationFormSettingsUIHandler::getPlural($lfta) : null;
			$parsed_lftnp = str_replace(".", "_", $lftnp); //$lftnp may have the schema
			
			$query_name = WorkFlowDataAccessHandler::getForeignTableQueryName($table_name, $foreign_table_name, $fk_type);
			$names[$query_name] = $foreign_table_name;
			//echo "$lfkct != $ltn => " . ($lfkct != $ltn) . " => $query_name\n<br>";
			
			if ($table_alias) {
				if ($foreign_table_alias) {
					$query_name = WorkFlowDataAccessHandler::getForeignTableQueryName($table_alias, $foreign_table_alias, $fk_type);
					$names[$query_name] = $foreign_table_name;
				}
				
				$query_name = WorkFlowDataAccessHandler::getForeignTableQueryName($table_alias, $foreign_table_name, $fk_type);
				$names[$query_name] = $foreign_table_name;
			}
			
			$names["get_" . $lftn] = $foreign_table_name;
			$names["get_" . $parsed_lftn] = $foreign_table_name;
			
			if ($lfta)
				$names["get_" . $lfta] = $foreign_table_name;
			
			$names["get_" . $ltn . "_" . $lftn] = $foreign_table_name;
			$names["get_" . $parsed_ltn . "_" . $parsed_lftn] = $foreign_table_name;
			$names["get_" . $parsed_ltn . "_" . $lftn] = $foreign_table_name;
			$names["get_" . $ltn . "_" . $parsed_lftn] = $foreign_table_name;
			
			if ($lfta) {
				$names["get_" . $ltn . "_" . $lfta] = $foreign_table_name;
				$names["get_" . $parsed_ltn . "_" . $lfta] = $foreign_table_name;
			}
			
			if ($lta) {
				$names["get_" . $lta . "_" . $lftn] = $foreign_table_name;
				$names["get_" . $lta . "_" . $parsed_lftn] = $foreign_table_name;
			
				if ($lfta)
					$names["get_" . $lta . "_" . $lfta] = $foreign_table_name;
			}
			
			$names["select_" . $lftn] = $foreign_table_name;
			$names["select_" . $parsed_lftn] = $foreign_table_name;
			
			if ($lfta)
				$names["select_" . $lfta] = $foreign_table_name;
			
			$names["select_" . $ltn . "_" . $lftn] = $foreign_table_name;
			$names["select_" . $parsed_ltn . "_" . $parsed_lftn] = $foreign_table_name;
			$names["select_" . $parsed_ltn . "_" . $lftn] = $foreign_table_name;
			$names["select_" . $ltn . "_" . $parsed_lftn] = $foreign_table_name;
			
			if ($lfta) {
				$names["select_" . $ltn . "_" . $lfta] = $foreign_table_name;
				$names["select_" . $parsed_ltn . "_" . $lfta] = $foreign_table_name;
			}
			
			if ($lta) {
				$names["select_" . $lta . "_" . $lftn] = $foreign_table_name;
				$names["select_" . $lta . "_" . $parsed_lftn] = $foreign_table_name;
				
				if ($lfta)
					$names["select_" . $lta . "_" . $lfta] = $foreign_table_name;
			}
			
			$names["get_" . $lftnp] = $foreign_table_name;
			$names["get_" . $parsed_lftnp] = $foreign_table_name;
			
			if ($lftap) 
				$names["get_" . $lftap] = $foreign_table_name;
			
			$names["get_" . $ltn . "_" . $lftnp] = $foreign_table_name;
			$names["get_" . $parsed_ltn . "_" . $parsed_lftnp] = $foreign_table_name;
			$names["get_" . $parsed_ltn . "_" . $lftnp] = $foreign_table_name;
			$names["get_" . $ltn . "_" . $parsed_lftnp] = $foreign_table_name;
			
			if ($lftap) {
				$names["get_" . $ltn . "_" . $lftap] = $foreign_table_name;
				$names["get_" . $parsed_ltn . "_" . $lftap] = $foreign_table_name;
			}
			
			if ($lta) {
				$names["get_" . $lta . "_" . $lftnp] = $foreign_table_name;
				$names["get_" . $lta . "_" . $parsed_lftnp] = $foreign_table_name;
				
				if ($lftap)
					$names["get_" . $lta . "_" . $lftap] = $foreign_table_name;
			}
			
			$names["select_" . $lftnp] = $foreign_table_name;
			$names["select_" . $parsed_lftnp] = $foreign_table_name;
			
			if ($lftap)
				$names["select_" . $lftap] = $foreign_table_name;
			
			$names["select_" . $ltn . "_" . $lftnp] = $foreign_table_name;
			$names["select_" . $parsed_ltn . "_" . $parsed_lftnp] = $foreign_table_name;
			$names["select_" . $parsed_ltn . "_" . $lftnp] = $foreign_table_name;
			$names["select_" . $ltn . "_" . $parsed_lftnp] = $foreign_table_name;
			
			if ($lftap) {
				$names["select_" . $ltn . "_" . $lftap] = $foreign_table_name;
				$names["select_" . $parsed_ltn . "_" . $lftap] = $foreign_table_name;
			}
			
			if ($lta) {
				$names["select_" . $lta . "_" . $lftnp] = $foreign_table_name;
				$names["select_" . $lta . "_" . $parsed_lftnp] = $foreign_table_name;
				
				if ($lftap)
					$names["select_" . $lta . "_" . $lftap] = $foreign_table_name;
			}
		}
	}
	
	return $names;
}

function getForeignQueriesCountName($foreign_keys, $table_name, $selected_tables_alias) {
	$names = array();
	
	if ($foreign_keys) {
		$table_alias = getTableAlias($table_name, $selected_tables_alias);
		//echo "<pre>$table_name:";print_r($foreign_keys);
		
		$ltn = strtolower($table_name);
		$lta = $table_alias ? strtolower($table_alias) : null;
		$parsed_ltn = str_replace(".", "_", $ltn); //$ltn may have the schema
		
		$t = count($foreign_keys); 
		for ($i = 0; $i < $t; $i++) {
			$fk = $foreign_keys[$i];
			$fk_type = isset($fk["type"]) ? $fk["type"] : null;
			$fk_child_table = isset($fk["child_table"]) ? $fk["child_table"] : null;
			$fk_parent_table = isset($fk["parent_table"]) ? $fk["parent_table"] : null;
			$lfkct = strtolower($fk_child_table);
			$foreign_table_name = $lfkct == $ltn ? $fk_parent_table : $fk_child_table;
			$foreign_table_alias = getTableAlias($foreign_table_name, $selected_tables_alias);
			
			$lftn = strtolower($foreign_table_name);
			$lfta = $foreign_table_alias ? strtolower($foreign_table_alias) : null;
			$parsed_lftn = str_replace(".", "_", $lftn); //$lftn may have the schema
			
			$lftnp = CMSPresentationFormSettingsUIHandler::getPlural($lftn);
			$lftap = $lfta ? CMSPresentationFormSettingsUIHandler::getPlural($lfta) : null;
			$parsed_lftnp = str_replace(".", "_", $lftnp); //$lftnp may have the schema
			
			$query_name = WorkFlowDataAccessHandler::getForeignTableQueryCountName($table_name, $foreign_table_name, $fk_type);
			$names[$query_name] = $foreign_table_name;
			//echo "$lfkct != $ltn => " . ($lfkct != $ltn) . " => $query_name\n<br>";
			
			if ($table_alias) {
				if ($foreign_table_alias) {
					$query_name = WorkFlowDataAccessHandler::getForeignTableQueryCountName($table_alias, $foreign_table_alias, $fk_type);
					$names[$query_name] = $foreign_table_name;
				}
				
				$query_name = WorkFlowDataAccessHandler::getForeignTableQueryCountName($table_alias, $foreign_table_name, $fk_type);
				$names[$query_name] = $foreign_table_name;
			}
			
			$names["total_number_of_" . $lftn] = $foreign_table_name;
			$names["total_number_of_" . $parsed_lftn] = $foreign_table_name;
			
			if ($lfta) 
				$names["total_number_of_" . $lfta] = $foreign_table_name;
			
			$names["total_number_of_" . $ltn . "_" . $lftn] = $foreign_table_name;
			$names["total_number_of_" . $parsed_ltn . "_" . $parsed_lftn] = $foreign_table_name;
			$names["total_number_of_" . $parsed_ltn . "_" . $lftn] = $foreign_table_name;
			$names["total_number_of_" . $ltn . "_" . $parsed_lftn] = $foreign_table_name;
			
			if ($lfta) {
				$names["total_number_of_" . $ltn . "_" . $lfta] = $foreign_table_name;
				$names["total_number_of_" . $parsed_ltn . "_" . $lfta] = $foreign_table_name;
			}
			
			if ($lta) {
				$names["total_number_of_" . $lta . "_" . $lftn] = $foreign_table_name;
				$names["total_number_of_" . $lta . "_" . $parsed_lftn] = $foreign_table_name;
				
				if ($lfta)
					$names["total_number_of_" . $lta . "_" . $lfta] = $foreign_table_name;
			}
			
			$names["total_num_of_" . $lftn] = $foreign_table_name;
			$names["total_num_of_" . $parsed_lftn] = $foreign_table_name;
			
			if ($lfta)
				$names["total_num_of_" . $lfta] = $foreign_table_name;
			
			$names["total_num_of_" . $ltn . "_" . $lftn] = $foreign_table_name;
			$names["total_num_of_" . $parsed_ltn . "_" . $parsed_lftn] = $foreign_table_name;
			$names["total_num_of_" . $parsed_ltn . "_" . $lftn] = $foreign_table_name;
			$names["total_num_of_" . $ltn . "_" . $parsed_lftn] = $foreign_table_name;
			
			if ($lfta) {
				$names["total_num_of_" . $ltn . "_" . $lfta] = $foreign_table_name;
				$names["total_num_of_" . $parsed_ltn . "_" . $lfta] = $foreign_table_name;
			}
			
			if ($lta) {
				$names["total_num_of_" . $lta . "_" . $lftn] = $foreign_table_name;
				$names["total_num_of_" . $lta . "_" . $parsed_lftn] = $foreign_table_name;
			
				if ($lfta)
					$names["total_num_of_" . $lta . "_" . $lfta] = $foreign_table_name;
			}
			
			$names["total_" . $lftn] = $foreign_table_name;
			$names["total_" . $parsed_lftn] = $foreign_table_name;
			
			if ($lfta)
				$names["total_" . $lfta] = $foreign_table_name;
			
			$names["total_" . $ltn . "_" . $lftn] = $foreign_table_name;
			$names["total_" . $parsed_ltn . "_" . $parsed_lftn] = $foreign_table_name;
			$names["total_" . $parsed_ltn . "_" . $lftn] = $foreign_table_name;
			$names["total_" . $ltn . "_" . $parsed_lftn] = $foreign_table_name;
			
			if ($lfta) {
				$names["total_" . $ltn . "_" . $lfta] = $foreign_table_name;
				$names["total_" . $parsed_ltn . "_" . $lfta] = $foreign_table_name;
			}
			
			if ($lta) {
				$names["total_" . $lta . "_" . $lftn] = $foreign_table_name;
				$names["total_" . $parsed_ltn . "_" . $parsed_lftn] = $foreign_table_name;
				$names["total_" . $parsed_ltn . "_" . $lftn] = $foreign_table_name;
				$names["total_" . $lta . "_" . $parsed_lftn] = $foreign_table_name;
			
				if ($lfta)
					$names["total_" . $lta . "_" . $lfta] = $foreign_table_name;
			}
			
			$names["total_of_" . $lftnp] = $foreign_table_name;
			$names["total_of_" . $parsed_lftnp] = $foreign_table_name;
			
			if ($lfta)
				$names["total_of_" . $lftap] = $foreign_table_name;
			
			$names["total_of_" . $ltn . "_" . $lftnp] = $foreign_table_name;
			$names["total_of_" . $parsed_ltn . "_" . $parsed_lftnp] = $foreign_table_name;
			$names["total_of_" . $parsed_ltn . "_" . $lftnp] = $foreign_table_name;
			$names["total_of_" . $ltn . "_" . $parsed_lftnp] = $foreign_table_name;
			
			if ($lfta) {
				$names["total_of_" . $ltn . "_" . $lftap] = $foreign_table_name;
				$names["total_of_" . $parsed_ltn . "_" . $lftap] = $foreign_table_name;
			}
			
			if ($lta) {
				$names["total_of_" . $lta . "_" . $lftnp] = $foreign_table_name;
				$names["total_of_" . $lta . "_" . $parsed_lftnp] = $foreign_table_name;
			
				if ($lfta)
					$names["total_of_" . $lta . "_" . $lftap] = $foreign_table_name;
			}
			
			$names["count_of_" . $lftnp] = $foreign_table_name;
			$names["count_of_" . $parsed_lftnp] = $foreign_table_name;
			
			if ($lfta)
				$names["count_of_" . $lftap] = $foreign_table_name;
			
			$names["count_of_" . $ltn . "_" . $lftnp] = $foreign_table_name;
			$names["count_of_" . $parsed_ltn . "_" . $parsed_lftnp] = $foreign_table_name;
			$names["count_of_" . $parsed_ltn . "_" . $lftnp] = $foreign_table_name;
			$names["count_of_" . $ltn . "_" . $parsed_lftnp] = $foreign_table_name;
			
			if ($lfta) {
				$names["count_of_" . $ltn . "_" . $lftap] = $foreign_table_name;
				$names["count_of_" . $parsed_ltn . "_" . $lftap] = $foreign_table_name;
			}
			
			if ($lta) {
				$names["count_of_" . $lta . "_" . $lftnp] = $foreign_table_name;
				$names["count_of_" . $lta . "_" . $parsed_lftnp] = $foreign_table_name;
				
				if ($lfta)
					$names["count_of_" . $lta . "_" . $lftap] = $foreign_table_name;
			}
			
			$names["count_" . $lftnp] = $foreign_table_name;
			$names["count_" . $parsed_lftnp] = $foreign_table_name;
			
			if ($lfta)
				$names["count_" . $lftap] = $foreign_table_name;
			
			$names["count_" . $ltn . "_" . $lftnp] = $foreign_table_name;
			$names["count_" . $parsed_ltn . "_" . $parsed_lftnp] = $foreign_table_name;
			$names["count_" . $parsed_ltn . "_" . $lftnp] = $foreign_table_name;
			$names["count_" . $ltn . "_" . $parsed_lftnp] = $foreign_table_name;
			
			if ($lfta) {
				$names["count_" . $ltn . "_" . $lftap] = $foreign_table_name;
				$names["count_" . $parsed_ltn . "_" . $lftap] = $foreign_table_name;
			}
			
			if ($lta) {
				$names["count_" . $lta . "_" . $lftnp] = $foreign_table_name;
				$names["count_" . $lta . "_" . $parsed_lftnp] = $foreign_table_name;
			
				if ($lfta)
					$names["count_" . $lta . "_" . $lftap] = $foreign_table_name;
			}
		}
	}
	
	return $names;
}

function parseBeanObjects($bean_objs, $allowed_types, $folder_types, $UserAuthenticationHandler, $layer_object_id, $filter_by_layout) {
	$objs = array();
	
	$filter_by_layout_permission = array(UserAuthenticationHandler::$PERMISSION_BELONG_NAME, UserAuthenticationHandler::$PERMISSION_REFERENCED_NAME);
	
	foreach ($bean_objs as $bn => $bo) {
		$bean_item_type = isset($bo["properties"]["item_type"]) ? $bo["properties"]["item_type"] : null;
		$allowed_folder_type = in_array($bean_item_type, $folder_types);
		$allowed_type = in_array($bean_item_type, $allowed_types);
		
		if ($allowed_folder_type || $allowed_type) {
			if (!empty($bo["properties"]["path"])) {
				$object_id = $layer_object_id . $bo["properties"]["path"];
				
				if (!$UserAuthenticationHandler->isInnerFilePermissionAllowed($object_id, "layer", "access"))
					continue;
				else if ($filter_by_layout && !$UserAuthenticationHandler->isLayoutInnerFilePermissionAllowed($object_id, $filter_by_layout, "layer", $filter_by_layout_permission)) //if filter_by_layout: check if sub_files belong or is referenced to that project
					continue;
			}
			
			if ($allowed_folder_type) {
				$sub_objs = parseBeanObjects($bo, $allowed_types, $folder_types, $UserAuthenticationHandler, $layer_object_id, $filter_by_layout);
				
				if (in_array($bean_item_type, $allowed_types)) {
					$bo["properties"]["name"] = $bn;
					$bo["properties"]["childs"] = $sub_objs;
					$objs[] = isset($bo["properties"]) ? $bo["properties"] : null;
				}
				else {
					$objs = array_merge($objs, $sub_objs);
				}
			}
			else if ($allowed_type) {
				$bo["properties"]["name"] = $bn;
				$objs[] = isset($bo["properties"]) ? $bo["properties"] : null;
			}	
		}
	}
	
	return $objs;
}

function getTableAlias($table_name, $selected_tables_alias) {
	$tnl = strtolower($table_name);
	
	if ($selected_tables_alias)
		foreach ($selected_tables_alias as $tn => $table_alias) 
			if (strtolower($tn) == $tnl)
				return $table_alias;
	
	return null;
}
?>
