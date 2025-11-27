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

include_once get_lib("org.phpframework.object.ObjTypeHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once $EVC->getUtilPath("WorkFlowDataAccessHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$db_driver = isset($_GET["db_driver"]) ? $_GET["db_driver"] : null;
$db_type = isset($_GET["type"]) ? $_GET["type"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$hbn_obj_id = isset($_GET["obj"]) ? $_GET["obj"] : null;
$query_id = isset($_GET["query"]) ? $_GET["query"] : null;
$query_type = isset($_GET["query_type"]) ? $_GET["query_type"] : null;
$relationship_type = isset($_GET["relationship_type"]) ? $_GET["relationship_type"] : null;

$path = str_replace("../", "", $path);//for security reasons

$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
$PHPVariablesFileHandler->startUserGlobalVariables();

$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);

if ($obj && is_a($obj, "DataAccessLayer")) {
	$layer_path = $obj->getLayerPathSetting();
	$file_path = $layer_path . $path;
	
	if ($path && file_exists($file_path)) {
		$parameters = array();
		
		$obj->getSQLClient()->loadXML($file_path);
		$nodes = $obj->getSQLClient()->getNodesData();
		//print_r($nodes);
		
		if ($query_id) {
			$db_broker = WorkFlowBeansFileHandler::getLayerLocalDBBrokerNameForChildBrokerDBDriver($user_global_variables_file_path, $user_beans_folder_path, $obj, $db_driver);
			$tasks_file_path = $db_type == "diagram" ? WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $db_driver) : null;
			
			if ($obj->getType() == "ibatis") {
				//http://jplpinto.localhost/__system/phpframework/dataaccess/get_query_properties?bean_name=DalIbatis&bean_file_name=iorm_dal.xml&path=test/service/price_type.xml&query_type=insert&query=insert_price_type&obj=
				$rels = $nodes;
				
				$query_type = $query_type ? $query_type : getNodeType($rels, $query_id);
				$node = isset($nodes[$query_type][$query_id]) ? $nodes[$query_type][$query_id] : null;
				
				WorkFlowDataAccessHandler::prepareSQLStatementParameters($node, $rels, $obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, null, $parameters);
			}
			else if ($hbn_obj_id) {
				if ($relationship_type == "queries") {
					//http://jplpinto.localhost/__system/phpframework/dataaccess/get_query_properties?bean_name=DalHibernate&bean_file_name=horm_dal.xml&path=test/item_subitem.xml&query_type=select&query=select_all_by_status&obj=ItemObj&relationship_type=queries
					$hbn_obj_data = isset($nodes["class"][$hbn_obj_id]) ? $nodes["class"][$hbn_obj_id] : null;
					$rels = isset($hbn_obj_data["childs"][$relationship_type]) ? $hbn_obj_data["childs"][$relationship_type] : null;
					
					$query_type = $query_type ? $query_type : getNodeType($rels, $query_id);
					$node = isset($rels[$query_type][$query_id]) ? $rels[$query_type][$query_id] : null;
					
					WorkFlowDataAccessHandler::prepareSQLStatementParameters($node, $rels, $obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, null, $parameters);
				}
				else if ($relationship_type == "relationships") {
					//http://jplpinto.localhost/__system/phpframework/dataaccess/get_query_properties?bean_name=DalHibernate&bean_file_name=horm_dal.xml&path=module/article/article.xml&query_type=one_to_many&query=tags&obj=Article&relationship_type=relationships
					$hbn_obj_data = isset($nodes["class"][$hbn_obj_id]) ? $nodes["class"][$hbn_obj_id] : null;
					$rels = isset($hbn_obj_data["childs"][$relationship_type]) ? $hbn_obj_data["childs"][$relationship_type] : null;
					
					$query_type = $query_type ? $query_type : getNodeType($rels, $query_id);
					$node = isset($rels[$query_type][$query_id]) ? $rels[$query_type][$query_id] : null;
					WorkFlowDataAccessHandler::prepareRelationshipParameters($node, $rels, $obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $hbn_obj_data, $parameters);
					
					if (empty($node["@"]["parameter_class"])) {
						$hbn_obj_parameters = WorkFlowDataAccessHandler::getHbnObjParameters($obj, $db_broker, $db_driver, $tasks_file_path, $hbn_obj_data, $tables_props);
						WorkFlowDataAccessHandler::addPrimaryKeysToParameters($hbn_obj_parameters, $parameters);
					}
				}
				else if ($relationship_type == "native") {
					//For Native functions of the Hibernate Objects
					//http://jplpinto.localhost/__system/phpframework/dataaccess/get_query_properties?bean_name=DalHibernate&bean_file_name=horm_dal.xml&path=module/article/article.xml&query_type=&query=findById&obj=Article&relationship_type=native
					$hbn_obj_data = isset($nodes["class"][$hbn_obj_id]) ? $nodes["class"][$hbn_obj_id] : null;
					$hbn_obj_parameters = WorkFlowDataAccessHandler::getHbnObjParameters($obj, $db_broker, $db_driver, $tasks_file_path, $hbn_obj_data, $tables_props);
					$parameters = array();
					
					//check if table has pks
					$no_pks = true;
					
					if ($tables_props) {
						$table_name = key($tables_props);
						$attrs = isset($tables_props[$table_name]) ? $tables_props[$table_name] : null;
						
						if ($attrs)
							foreach ($attrs as $attr_name => $attr_props)
								if (!empty($attr_props["primary_key"])) {
									$no_pks = false;
									break;
								}
					}
					
					switch (strtolower($query_id)) {
						case "insert": 
						case "insertall": 
							$parameters = $hbn_obj_parameters;
							WorkFlowDataAccessHandler::removePrimaryKeysFromParameters($hbn_obj_parameters, $parameters); 
							break;
						case "update": 
							$parameters = $hbn_obj_parameters;
							
							foreach ($parameters as $param_name => $param_props)
								if (ObjTypeHandler::isDBAttributeNameACreatedDate($param_name))
									unset($parameters[$param_name]);
							
							//if no PKS and query_id is update and updateAll: set the parameters with new and old
							if ($no_pks) {
								foreach ($parameters as $param_name => $param_props)
									if (!ObjTypeHandler::isDBAttributeNameACreatedDate($param_name) && !ObjTypeHandler::isDBAttributeNameACreatedUserId($param_name) && !ObjTypeHandler::isDBAttributeNameAModifiedDate($param_name) && !ObjTypeHandler::isDBAttributeNameAModifiedUserId($param_name)) {
										$pn = !empty($param_props["name"]) ? $param_props["name"] : $param_name;
										
										$parameters["new_$param_name"] = $param_props;
										$parameters["new_$param_name"]["name"] = "new_$pn";
										$parameters["old_$param_name"] = $param_props;
										$parameters["old_$param_name"]["name"] = "old_$pn";
										unset($parameters[$param_name]);
									}
							}
							break;
						case "updateall": 
						case "insertorupdate": 
						case "insertorupdateall": 
						case "deleteall": 
						case "find": 
						case "count": 
							$parameters = $hbn_obj_parameters;
							break;
						case "updateprimarykeys": 
							$parameters = array();
							
							if (is_array($hbn_obj_parameters)) {
								foreach ($hbn_obj_parameters as $param_name => $param_props) {
									$add = false;
									
									if ($no_pks && !ObjTypeHandler::isDBAttributeNameACreatedDate($param_name) && !ObjTypeHandler::isDBAttributeNameACreatedUserId($param_name) && !ObjTypeHandler::isDBAttributeNameAModifiedDate($param_name) && !ObjTypeHandler::isDBAttributeNameAModifiedUserId($param_name))
										$add = true;
									else if (!empty($param_props["primary_key"]))
										$add = true;
									
									if ($add) {
										$pn = !empty($param_props["name"]) ? $param_props["name"] : $param_name;
										
										$parameters["new_$param_name"] = $param_props;
										$parameters["new_$param_name"]["name"] = "new_$pn";
										$parameters["old_$param_name"] = $param_props;
										$parameters["old_$param_name"]["name"] = "old_$pn";
									}
								}
							}
							break;
						case "delete": 
						case "findbyid": 
						case "findrelationships": 
						case "findrelationship": 
						case "countrelationships": 
						case "countrelationship": 
							$parameters = $hbn_obj_parameters;
							
							//if no PKS and query_id is update and updateAll: set the parameters with new and old
							if ($no_pks) {
								foreach ($parameters as $param_name => $param_props)
									if (ObjTypeHandler::isDBAttributeNameACreatedDate($param_name) || ObjTypeHandler::isDBAttributeNameACreatedUserId($param_name) || ObjTypeHandler::isDBAttributeNameAModifiedDate($param_name) || ObjTypeHandler::isDBAttributeNameAModifiedUserId($param_name))
										unset($parameters[$param_name]);
							}
							else
								$parameters = WorkFlowDataAccessHandler::getPrimaryKeysFromParameters($hbn_obj_parameters, $parameters);
							
							break;
					}
				}
				else {
					//INVALID TYPE
				}
			}
		}
		//echo "<pre>";print_r($parameters);print_r($tables_props);die();
		
		if ($parameters) {
			$props = array();
			foreach ($parameters as $attr_name => $param) {
				$name = !empty($param["name"]) ? $param["name"] : $attr_name;
				$type = isset($param["type"]) ? ObjTypeHandler::convertCompositeTypeIntoSimpleType($param["type"]) : null;
				
				$props[$name] = $type && !ObjTypeHandler::isPHPTypeNumeric($type) && !ObjTypeHandler::isDBTypeNumeric($type) ? "string" : "";
			}
			//echo "<pre>";print_r($props);die();
		}
	}
}

$PHPVariablesFileHandler->endUserGlobalVariables();

function getNodeType($rels, $node_id) {
	if (is_array($rels)) {
		foreach ($rels as $node_type => $rel) {
			if (!empty($rel[$node_id])) {
				return $node_type;
			}
		}
	}
	return null;
}
?>
