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

include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once $EVC->getUtilPath("WorkFlowDataAccessHandler");
include_once $EVC->getUtilPath("WorkFlowBusinessLogicHandler");
include_once $EVC->getUtilPath("FlushCacheHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");
include_once $EVC->getUtilPath("SequentialLogicalActivityBLResourceCreator");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null;

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
$PHPVariablesFileHandler->startUserGlobalVariables();

$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);

if ($obj && is_a($obj, "BusinessLogicLayer")) {
	$layer_path = $obj->getLayerPathSetting();
	$folder_path = $layer_path . $path;
	
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($folder_path, "layer", "access");
	
	$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
	
	//PREPARING DATA ACCESS BROKERS (This will be used in the step 0 and in step 1)
	$brokers = $obj->getBrokers();
	$layer_brokers_settings = WorkFlowBeansFileHandler::getLayerBrokersSettings($user_global_variables_file_path, $user_beans_folder_path, $brokers);
	$data_access_brokers = !empty($layer_brokers_settings["data_access_brokers"]) ? $layer_brokers_settings["data_access_brokers"] : array();
	$db_brokers = !empty($layer_brokers_settings["db_brokers"]) ? $layer_brokers_settings["db_brokers"] : array();
	
	$related_brokers = array_merge($db_brokers, $data_access_brokers);
	
	if (!empty($_POST["step_1"])) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);
		UserAuthenticationHandler::checkActionsMaxNum($UserAuthenticationHandler);
		
		//echo "<pre>";print_r($_POST);die();
		$files = isset($_POST["files"]) ? $_POST["files"] : null;
		$aliases = isset($_POST["aliases"]) ? $_POST["aliases"] : null;
		$db_driver = isset($_POST["db_driver"]) ? $_POST["db_driver"] : null;
		$include_db_driver = isset($_POST["include_db_driver"]) ? $_POST["include_db_driver"] : null;
		$db_type = isset($_POST["type"]) ? $_POST["type"] : null;
		$resource_services = isset($_POST["resource_services"]) ? $_POST["resource_services"] : null;
		$overwrite = isset($_POST["overwrite"]) ? $_POST["overwrite"] : null;
		$namespace = isset($_POST["namespace"]) ? trim($_POST["namespace"]) : "";
		$json = isset($_POST["json"]) ? $_POST["json"] : null;
		
		//echo "<pre>";print_r($_POST);die();
		
		$reserved_sql_keywords = array();
		$common_namespace = null;
		$common_service_file_path = isset($obj->settings["business_logic_modules_service_common_file_path"]) ? $obj->settings["business_logic_modules_service_common_file_path"] : null;
		if ($common_service_file_path && file_exists($common_service_file_path)) {
			include_once $common_service_file_path;
			
			$common_namespace = PHPCodePrintingHandler::getNamespacesFromFile($common_service_file_path);
			$common_namespace = isset($common_namespace[0]) ? $common_namespace[0] : null;
			$common_namespace = substr($common_namespace, 0, 1) == "\\" ? substr($common_namespace, 1) : $common_namespace;
			$common_namespace = substr($common_namespace, -1) == "\\" ? substr($common_namespace, 0, -1) : $common_namespace;
			
			eval("\$reserved_sql_keywords = \\$common_namespace\\CommonService::getReservedSQLKeywords();");
		}
		
		$statuses = array();
		
		if (is_array($files)) {
			$UserAuthenticationHandler->incrementUsedActionsTotal();
			
			$t = count($related_brokers);
			
			//echo "<pre>$path|$folder_path:\n";print_r($files);print_r($aliases);die();
			foreach ($files as $file => $items) {
				if (isset($items["all"]))
					$items = array("all" => $items["all"]);//CREATE A NEW ARRAY REMOVING ALL THE OTHERS NON ALL ITEMS.
				
				if (is_array($items)) {
					foreach ($items as $node_id => $broker_name) {
						$bfn = $bn = null;
						for ($i = 0; $i < $t; $i++) {
							$b = $related_brokers[$i];
							
							if (isset($b[0]) && $b[0] == $broker_name) {
								$bfn = $b[1];
								$bn = $b[2];
							}
						}
						
						if ($broker_name && $bfn && $bn) {
							$WBFH = new WorkFlowBeansFileHandler($user_beans_folder_path . $bfn, $user_global_variables_file_path);
							$layer_obj = $WBFH->getBeanObject($bn);
							
							$tasks_file_path = $db_type == "diagram" ? WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $db_driver) : null;
							
							$alias = isset($aliases[$file][$node_id]) ? trim($aliases[$file][$node_id]) : null;
							$broker_code_prefix = '$this->getBusinessLogicLayer()->getBroker("' . $broker_name . '")';
							$new_statuses_index = count($statuses);
							$tables_props = array();
							$db_broker = null;
							
							if (!$layer_obj)
								$statuses[] = array($file, null, false, null);
							else if (is_a($layer_obj, "DataAccessLayer")) {
								$data_access_layer_path = $layer_obj->getLayerPathSetting();
								$data_access_file_path = $data_access_layer_path . $file;
								//echo "data_access_file_path:$data_access_file_path<br>";
								
								if (file_exists($data_access_file_path)) {
									$layer_obj->getSQLClient()->loadXML($data_access_file_path);
									$xml_data = $layer_obj->getSQLClient()->getNodesData();
									//echo "<pre>";print_r($xml_data);echo "</pre>";die();
									
									$module_id = getModuleId($file);
									$dst_file_path = $folder_path . ($path ? basename($file) : $file); //if no path, this means is the root of the business logic layer, then write the full $file with folders according with what comes from the data-access layers. Otherwise, if $path exists, write all new services files to the $path folder.
									//echo $dst_file_path;die();
									
									//check if $folder_path belongs to filter_by_layout and if not, add it.
									$LayoutTypeProjectHandler->createLayoutTypePermissionsForFilePathAndLayoutTypeName($filter_by_layout, dirname($dst_file_path));
									
									$db_broker = WorkFlowBeansFileHandler::getLayerLocalDBBrokerNameForChildBrokerDBDriver($user_global_variables_file_path, $user_beans_folder_path, $layer_obj, $db_driver);
									
									if ($layer_obj->getType() == "hibernate") {
										if ($node_id != "all")
											$xml_data = array(
												$node_id => isset($xml_data["class"][$node_id]) ? $xml_data["class"][$node_id] : null
											);
										else
											$xml_data = isset($xml_data["class"]) ? $xml_data["class"] : null;
										//echo "<pre>";print_r($xml_data);echo "</pre>";die();
									
										if (is_array($xml_data)) {
											foreach ($xml_data as $obj_id => $obj_data) {
												$d = createHibernateBusinessLogicFile($layer_obj, $db_broker, $db_driver, $include_db_driver, $tasks_file_path, $dst_file_path, $obj_id, $module_id, $obj_data, $broker_code_prefix, $reserved_sql_keywords, $tables_props, $overwrite, $common_namespace, $namespace, $alias);
												$d[0] = isset($d[0]) ? substr($d[0], strlen($layer_path)) : "";
												$statuses[] = $d;
											}
										}
									}
									else {
										$d = createIbatisBusinessLogicFile($layer_obj, $db_broker, $db_driver, $include_db_driver, $tasks_file_path, $dst_file_path, $module_id, $xml_data, $broker_code_prefix, $reserved_sql_keywords, $tables_props, $overwrite, $common_namespace, $namespace, $alias);
										$d[0] = isset($d[0]) ? substr($d[0], strlen($layer_path)) : "";
										$statuses[] = $d;
									}
								}
							}
							else if (is_a($layer_obj, "DBLayer")) {
								//$file: table name
								//$node_id: all
								//$alias: service alias
								//echo "$file && $node_id && $alias<br>";die();
								
								$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
								
								if ($tasks_file_path) { //TRYING TO GET THE DB TABLES FROM THE TASK FLOW
									$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $db_driver);
									$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
								}
								else { //TRYING TO GET THE DB TABLES DIRECTLY FROM DB
									$tables_data = array();
									$tables = $layer_obj->getFunction("listTables", null, array("db_driver" => $db_driver));
									$t = count($tables);
									for ($i = 0; $i < $t; $i++) {
										$table = $tables[$i];
							
										if (!empty($table)) {
											$table_name = isset($table["name"]) ? $table["name"] : null;
											$attrs = $layer_obj->getFunction("listTableFields", $table_name, array("db_driver" => $db_driver));
											$fks = $layer_obj->getFunction("listForeignKeys", $table_name, array("db_driver" => $db_driver));
											
											$tables_data[$table_name] = array($attrs, $fks, $table);
										}
									}
									
									$tasks = WorkFlowDBHandler::getUpdateTaskDBDiagramFromTablesData($tables_data);
									$WorkFlowDataAccessHandler->setTasks($tasks);
								}
								
								$nodes = $WorkFlowDataAccessHandler->getQueryObjectsArrayFromDBTaskFlow($file);
								$xml_data = SQLMapClient::getDataAccessNodesConfigured(isset($nodes["queries"][0]["childs"]) ? $nodes["queries"][0]["childs"]: null);
								//echo "<pre>";print_r($xml_data);die();
								
								$db_broker = WorkFlowBeansFileHandler::getLayerLocalDBBrokerNameForChildBrokerDBDriver($user_global_variables_file_path, $user_beans_folder_path, $layer_obj, $db_driver);
								
								$d = createTableBusinessLogicFile($layer_obj, $db_broker, $db_driver, $include_db_driver, $tasks_file_path, $folder_path, $file, $xml_data, $broker_code_prefix, $reserved_sql_keywords, $tables_props, $overwrite, $common_namespace, $namespace, $alias);
								$d[0] = isset($d[0]) ? substr($d[0], strlen($layer_path)) : null;
								$statuses[] = $d;
								
								//check if $folder_path belongs to filter_by_layout and if not, add it.
								$LayoutTypeProjectHandler->createLayoutTypePermissionsForFilePathAndLayoutTypeName($filter_by_layout, $folder_path);
							}
							
							//create resource services
							if ($resource_services && $new_statuses_index < count($statuses) && $statuses[$new_statuses_index]) { //it means the service was added
								$status = $statuses[$new_statuses_index];
								$service_file = $status[0];
								$created_status = $status[2];
								$table_name = $status[3];
								
								if ($created_status && $table_name) {
									$tn = strtolower($table_name);
									
									if (empty($tables_props[$tn]))
										$tables_props[$tn] = $data_access_obj->getFunction("listTableFields", $table_name, array("db_broker" => $db_broker, "db_driver" => $db_driver));
									
									if (!empty($tables_props[$tn])) {
										$resource_service_file_path = $layer_path . preg_replace("/(Service)?\.php/", 'ResourceService.php', $service_file);
										$resource_service_class_name = pathinfo($resource_service_file_path, PATHINFO_FILENAME);
										$service_file_path = $layer_path . $service_file;
										$service_class_name = pathinfo($service_file_path, PATHINFO_FILENAME);
										$module_id = getModuleId($service_file);
										
										$class_props = PHPCodePrintingHandler::getClassFromFile($service_file_path, $service_class_name);
										
										if (!empty($class_props["methods"])) {
											$resource_service_file_relative_path = substr($resource_service_file_path, strlen(LAYER_PATH));
											$error_message = null;
											
											//note that the resource methods are only created if not exist yet, so if overwrite is present, we must first remove the resource file, so all methods get created again with the latest configurations.
											if ($overwrite && file_exists($resource_service_file_path))
												unlink($resource_service_file_path);
											
											$SequentialLogicalActivityBLResourceCreator = new SequentialLogicalActivityBLResourceCreator($resource_service_file_path, $resource_service_class_name, $tables_props, $table_name);
											$resource_file_exists = $SequentialLogicalActivityBLResourceCreator->createBLResourceServiceFile($service_file_path, $service_class_name, $error_message);
											
											if (!$resource_file_exists) 
												$error_message = $error_message ? $error_message : "Error trying to create file '" . $resource_service_file_relative_path . "'!";
											else if (!$error_message) {
												//get methods names list
												$methods_names = array();
												foreach ($class_props["methods"] as $method)
													$methods_names[] = $method["name"];
												
												//prepare resources methods
												foreach ($class_props["methods"] as $method) {
													$service_id = $class_props["name"] . "." . $method["name"];
													$resource_status = true;
													$resource_error_message = null;
													
													switch ($method["name"]) {
														case "insert":
															$resource_status = $SequentialLogicalActivityBLResourceCreator->createInsertMethod($module_id, $service_id, $resource_error_message);
															break;
														case "update":
															$get_service_id = in_array("get", $methods_names) ? $class_props["name"] . ".get" : null;
															$update_pks_service_id = in_array("updatePrimaryKeys", $methods_names) ? $class_props["name"] . ".updatePrimaryKeys" : null;
															$resource_status = $SequentialLogicalActivityBLResourceCreator->createUpdateMethod($module_id, $get_service_id, $service_id, $update_pks_service_id, $resource_error_message);
															
															if ($resource_status && $get_service_id)
																$SequentialLogicalActivityBLResourceCreator->createUpdateAttributeMethod($module_id, $get_service_id, $service_id, $resource_error_message);
															
															if ($resource_status && in_array("insert", $methods_names)) {
																$insert_service_id = $class_props["name"] . ".insert";
																$resource_status = $SequentialLogicalActivityBLResourceCreator->createMultipleSaveMethod($module_id, $insert_service_id, $service_id, $resource_error_message);
																
																if ($resource_status && $get_service_id)
																	$resource_status = $SequentialLogicalActivityBLResourceCreator->createInsertUpdateAttributeMethod($module_id, $get_service_id, $insert_service_id, $service_id, $resource_error_message);
															}
															break;
														case "delete":
															$resource_status = $SequentialLogicalActivityBLResourceCreator->createDeleteMethod($module_id, $service_id, $resource_error_message) && $SequentialLogicalActivityBLResourceCreator->createMultipleDeleteMethod($module_id, $service_id, $resource_error_message);
															
															if ($resource_status && in_array("get", $methods_names) && in_array("insert", $methods_names)) {
																$get_service_id = $class_props["name"] . ".get";
																$insert_service_id = $class_props["name"] . ".insert";
																$resource_status = $SequentialLogicalActivityBLResourceCreator->createInsertDeleteAttributeMethod($module_id, $get_service_id, $insert_service_id, $service_id, $resource_error_message);
															}
															break;
														case "deleteAll":
															if (in_array("insert", $methods_names)) {
																$insert_service_id = $class_props["name"] . ".insert";
																$resource_status = $SequentialLogicalActivityBLResourceCreator->createMultipleInsertDeleteAttributeMethod($module_id, $service_id, $insert_service_id, $resource_error_message);
															}
															break;
														case "get":
															$resource_status = $SequentialLogicalActivityBLResourceCreator->createGetMethod($module_id, $service_id, $resource_error_message);
															break;
														case "getAll":
															$resource_status = $SequentialLogicalActivityBLResourceCreator->createGetAllMethod($module_id, $service_id, $resource_error_message);
															
															if ($resource_status) {
																/*$attrs = $tables_props[$tn];
																$pks_exists = false;
																
																if ($attrs)
																	foreach ($attrs as $attr_name => $attr)
																		if (!empty($attr["primary_key"])) {
																			$pks_exists = true;
																			break;
																		}
																
																if ($pks_exists)*/
																	$resource_status = $SequentialLogicalActivityBLResourceCreator->createGetAllOptionsMethod($module_id, $service_id, $resource_error_message);
															}
															break;
														case "countAll":
														case "count":
															$resource_status = $SequentialLogicalActivityBLResourceCreator->createCountMethod($module_id, $service_id, $resource_error_message);
															break;
													}
													
													if (!$resource_status || $resource_error_message)
														$error_message = ($error_message ? $error_message : "") . ($resource_error_message ? $resource_error_message : "Error trying to create service $resource_service_class_name." . $method["name"] . " at file: '" . $resource_service_file_relative_path . "'!");
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
			
			//delete cache bc of the previously cached business logic services
			FlushCacheHandler::flushCache($EVC, $webroot_cache_folder_path, $webroot_cache_folder_url, $workflow_paths_id, $user_global_variables_file_path, $user_beans_folder_path, $css_and_js_optimizer_webroot_cache_folder_path, $deployments_temp_folder_path); //flush cache
		}
		
		if ($json) {
			echo json_encode($statuses);
			die();
		}
	}
	else {
		$brokers_db_drivers_name = array();
		foreach ($brokers as $broker_name => $broker) 
			if (is_a($broker, "IDataAccessBrokerClient") || is_a($broker, "IDBBrokerClient")) {
				$brokers_db_drivers_name[$broker_name] = WorkFlowBeansFileHandler::getBrokersDBDrivers($user_global_variables_file_path, $user_beans_folder_path, array($broker_name => $broker), true);
				
				$LayoutTypeProjectHandler->filterLayerBrokersDBDriversPropsFromLayoutName($brokers_db_drivers_name[$broker_name], $filter_by_layout); //filter db_drivers by $filter_by_layout
			}
		
		$default_broker_name = isset($related_brokers[0][0]) ? $related_brokers[0][0] : null;
		
		$db_drivers = isset($brokers_db_drivers_name[$default_broker_name]) ? $brokers_db_drivers_name[$default_broker_name] : null;
		$default_db_driver = key($db_drivers);
		
		$WBFH = new WorkFlowBeansFileHandler($user_beans_folder_path . $related_brokers[0][1], $user_global_variables_file_path);
		$layer_obj = $WBFH->getBeanObject($related_brokers[0][2]);
		$is_db_layer = is_a($layer_obj, "DBLayer");
		
		if ($is_db_layer) {
			$db_driver_tables = $layer_obj->getFunction("listTables", null, array("db_driver" => $default_db_driver));
			$default_db_driver_table = isset($db_driver_tables[0]["name"]) ? $db_driver_tables[0]["name"] : null;
		}
		
		$db_brokers_bean_file_by_bean_name = array();
		foreach ($db_brokers as $b) {
			$b_bean_file_name = isset($b[1]) ? $b[1] : null;
			$b_bean_name = isset($b[2]) ? $b[2] : null;
			$db_brokers_bean_file_by_bean_name[$b_bean_name] = $b_bean_file_name;
		}
	}
}

$PHPVariablesFileHandler->endUserGlobalVariables();


/**** FUNCTIONS ****/

function createHibernateBusinessLogicFile($data_access_obj, $db_broker, $db_driver, $include_db_driver, $tasks_file_path, $file_path, $obj_id, $module_id, $obj_data, $broker_code_prefix, $reserved_sql_keywords, &$tables_props, $overwrite, $common_namespace, $namespace = false, $alias = false) {
	$folder_name = dirname($file_path);
	
	if (file_exists($folder_name) || mkdir($folder_name, 0755, true)) {
		if ($alias) {
			$class_name = $alias;
			$file_path = "$folder_name/{$alias}.php";
		}
		else {
			//$class_name = WorkFlowDataAccessHandler::getClassName($obj_id);
			$class_name = str_replace(" ", "", ucwords(str_replace(array("_", "-", "."), " ", $obj_id))); // I cannot use the WorkFlowDataAccessHandler::getClassName bc it converts the $obj_id to lower case, and if the $obj_id=="CarCategory" it will return the class_name Carcategory, which will mess the rest of the automatic creation of interfaces. Note that the obj_id may have the schema
			$file_path = "$folder_name/{$class_name}Service.php";
		}
		
		while (!$overwrite && file_exists($file_path)) {
			$rand = rand(0, 100);
			$class_name .= $rand;
			$file_path = "$folder_name/{$class_name}" . ($alias ? "" : "Service") . ".php";
		}
		
		//PREPARING RELATIONSHIPS PARAMETER MAP
		if (!empty($obj_data["childs"]["parameter_map"][0])) {
			$map_id = isset($obj_data["childs"]["parameter_map"][0]["attrib"]["id"]) ? $obj_data["childs"]["parameter_map"][0]["attrib"]["id"] : null;
			
			if (!$map_id) {
				$map_id = "MainParameterMap";
				$obj_data["childs"]["parameter_map"][0]["attrib"]["id"] = $map_id;
			}
		
			$obj_data["childs"]["relationships"]["parameter_map"][$map_id] = $obj_data["childs"]["parameter_map"][0];
		}
		
		//echo "<pre>";print_r($obj_data);echo "</pre>";
		$rels = isset($obj_data["childs"]["relationships"]) ? $obj_data["childs"]["relationships"] : null;
		$queries = isset($obj_data["childs"]["queries"]) ? $obj_data["childs"]["queries"] : null;
		
		//prepare hbn parameters and other variables
		$hbn_obj_parameters = WorkFlowDataAccessHandler::getHbnObjParameters($data_access_obj, $db_broker, $db_driver, $tasks_file_path, $obj_data, $tables_props);
		
		//disable add_sql_slashes bc the hibernate engine already adds slashes automatically
		WorkFlowBusinessLogicHandler::disableAddSqlSlashesInParameters($hbn_obj_parameters);
		
		//prepare ids and default codes
		$options_code = getDBDriverOptionsCode($db_driver, $include_db_driver);
		$with_ids = checkTypeOfExistentPrimaryKeys($hbn_obj_parameters, $auto_increment_pk_name);
		$default_value_code = WorkFlowBusinessLogicHandler::prepareAttributesDefaultValueCode($hbn_obj_parameters, false);
		$update_default_value_code = WorkFlowBusinessLogicHandler::prepareAttributesDefaultValueCode($hbn_obj_parameters, false, true);
		
		$no_pks = empty($with_ids);
		
		//prepare parameters update
		$hbn_obj_parameters_for_update = $hbn_obj_parameters;
		
		//if exist, remove created_date attr, bc we dont want to change this attr in the update method. Only in the insert method.
		foreach ($hbn_obj_parameters_for_update as $param_name => $param_props)
			if (ObjTypeHandler::isDBAttributeNameACreatedDate($param_name))
				unset($hbn_obj_parameters_for_update[$param_name]);
		
		$hbn_obj_parameters_for_update_all = $hbn_obj_parameters_for_update;
		$hbn_obj_parameters_for_update_pks = array();
		$hbn_obj_parameters_for_get_and_delete = array();
		
		if (is_array($hbn_obj_parameters))
			foreach ($hbn_obj_parameters as $param_name => $param_props)
				if (!empty($param_props["primary_key"])) {
					$pn = !empty($param_props["name"]) ? $param_props["name"] : $param_name;
					
					$hbn_obj_parameters_for_update_pks["new_$param_name"] = $param_props;
					$hbn_obj_parameters_for_update_pks["new_$param_name"]["name"] = "new_$pn";
					$hbn_obj_parameters_for_update_pks["old_$param_name"] = $param_props;
					$hbn_obj_parameters_for_update_pks["old_$param_name"]["name"] = "old_$pn";
					
					$hbn_obj_parameters_for_get_and_delete[$param_name] = $param_props;
				}
		
		//if no PKS, set the parameters with new and old
		if ($no_pks)
			foreach ($hbn_obj_parameters_for_update as $param_name => $param_props)
				if (!ObjTypeHandler::isDBAttributeNameACreatedDate($param_name) && !ObjTypeHandler::isDBAttributeNameACreatedUserId($param_name) && !ObjTypeHandler::isDBAttributeNameAModifiedDate($param_name) && !ObjTypeHandler::isDBAttributeNameAModifiedUserId($param_name)) {
					$pn = !empty($param_props["name"]) ? $param_props["name"] : $param_name;
					
					$hbn_obj_parameters_for_update["new_$param_name"] = $param_props;
					$hbn_obj_parameters_for_update["new_$param_name"]["name"] = "new_$pn";
					$hbn_obj_parameters_for_update["old_$param_name"] = $param_props;
					$hbn_obj_parameters_for_update["old_$param_name"]["name"] = "old_$pn";
					
					$hbn_obj_parameters_for_update_pks["new_$param_name"] = $hbn_obj_parameters_for_update["new_$param_name"];
					$hbn_obj_parameters_for_update_pks["old_$param_name"] = $hbn_obj_parameters_for_update["old_$param_name"];
					
					$hbn_obj_parameters_for_get_and_delete[$param_name] = $param_props;
					
					unset($hbn_obj_parameters_for_update[$param_name]);
				}
		
		//prepare updateAll annotations
		$update_all_annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($hbn_obj_parameters_for_update_all, false, true, true, true, true, true, false);
		$update_all_annotations .= WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($hbn_obj_parameters, true, true, false, false, true, false, true);
		$update_all_annotations = preg_replace("|\s*\*/\s*/\*\*\s*|", "\n\t ", $update_all_annotations);
		
		//echo '<pre>';print_r($hbn_obj_parameters);die();
		
		$code = '<?php';
		
		if ($namespace)
			$code .= '
namespace ' . $namespace . ';
';
		
		$code .= '
include_once $vars["business_logic_modules_service_common_file_path"];
	
class ' . $class_name . ($alias ? "" : "Service") . ' extends ' . ($common_namespace && $namespace != $common_namespace ? "\\$common_namespace\\" : '') . 'CommonService {
	
	private function getDataAccessBroker() {
		$broker = ' . $broker_code_prefix . ';
		
		return $broker;
	}

	public function callHbnObject($options = null) {
		$obj = $this->getDataAccessBroker()->callObject("' . $module_id . '", "' . $obj_id . '", $options);
		return $obj;
	}

' . WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($hbn_obj_parameters, $with_ids, true, true, true, true, false, false) . '
	public function insert($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		$this->mergeOptionsWithBusinessLogicLayer($options);
		unset($data["options"]);
		
		' . $options_code . $default_value_code . '
		$obj = $this->callHbnObject($options);
		$status = null;
		
		if ($obj)
			$status = $obj->insert($data, $ids, $options);
		
		' . ($auto_increment_pk_name ? '$id = $status ? (isset($ids["' . $auto_increment_pk_name . '"]) ? $ids["' . $auto_increment_pk_name . '"] : null) : false; //hibernate->insert method already returns the getInsertedId in: $ids[xxx]. This code supposes that there is only 1 auto increment pk.' : '$id = $status;') . '
		
		return $id;
	}

' . WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($hbn_obj_parameters_for_update, true, true, true, true, true, true, false) . '
	public function update($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		$this->mergeOptionsWithBusinessLogicLayer($options);
		unset($data["options"]);
		
		' . $options_code . $update_default_value_code . '
		$obj = $this->callHbnObject($options);
		$status = null;
		
		if ($obj)
			$status = $obj->update($data, $options);
	
		return $status;
	}

' . $update_all_annotations . '
	public function updateAll($data) {
		if ($data && (!empty($data["conditions"]) || !empty($data["all"]))) {
			$options = isset($data["options"]) ? $data["options"] : null;
			$this->mergeOptionsWithBusinessLogicLayer($options);
			unset($data["options"]);
			
			' . $options_code . $update_default_value_code . '
			$obj = $this->callHbnObject($options);
			$status = null;
			
			if ($obj) {
				$attributes = array(';
		
		foreach ($hbn_obj_parameters_for_update_all as $param_name => $param_props) {
			$pn = $param_props["name"] ? $param_props["name"] : $param_name;
			
			$code .= '
					"' . $pn . '" => $data["' . $pn . '"],';
		}
		
		$code .= '
				);
				
				$status = $obj->updateByConditions(array(
					"attributes" => $attributes,
					"conditions" => isset($data["conditions"]) ? $data["conditions"] : null,
					"conditions_join" => isset($data["conditions_join"]) ? $data["conditions_join"] : null,
					"all" => isset($data["all"]) ? $data["all"] : null,
				), $options);
			}
			
			return $status;
		}
	}
	
' . ($no_pks ? WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($hbn_obj_parameters_for_update_pks, true, true, true, false, true, true, false) : WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($hbn_obj_parameters_for_update_pks, true, false, true, false, true, true, false)) . '
	public function updatePrimaryKeys($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		$this->mergeOptionsWithBusinessLogicLayer($options);
		unset($data["options"]);
		
		' . $options_code . '
		$obj = $this->callHbnObject($options);
		$status = null;
		
		if ($obj)
			$status = $obj->updatePrimaryKeys($data, $options);
		
		return $status;
	}

' . ($no_pks ? WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($hbn_obj_parameters_for_get_and_delete, true, true, true, false, true, false, false) : WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($hbn_obj_parameters_for_get_and_delete, true, false, true, false, true, false, false)) . '
	public function delete($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		$this->mergeOptionsWithBusinessLogicLayer($options);
		unset($data["options"]);
		
		' . $options_code . '
		$obj = $this->callHbnObject($options);
		$status = null;
		
		if ($obj)
			$status = $obj->delete($data, $options);
	
		return $status;
	}
	
' . WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($hbn_obj_parameters, true, true, false, false, true, false, true) . '
	public function deleteAll($data) {
		if ($data && (!empty($data["conditions"]) || !empty($data["all"]))) {
			$options = isset($data["options"]) ? $data["options"] : null;
			$this->mergeOptionsWithBusinessLogicLayer($options);
			unset($data["options"]);
			
			' . $options_code . '
			$obj = $this->callHbnObject($options);
			$status = null;
			
			if ($obj)
				$status = $obj->deleteByConditions($data, $options);
			
			return $status;
		}
	}

' . ($no_pks ? WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($hbn_obj_parameters_for_get_and_delete, true, true, true, false, true, false, false) : WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($hbn_obj_parameters_for_get_and_delete, true, false, true, false, true, false, false)) . '
	public function get($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		$this->mergeOptionsWithBusinessLogicLayer($options);
		unset($data["options"]);
		
		' . $options_code . '
		$obj = $this->callHbnObject($options);
		$res = null;
		
		if ($obj)
			$res = $obj->findById($data, $options);
		
		return $res;
	}

' . WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($hbn_obj_parameters, true, true, false, false, true, false, true) . '
	public function getAll($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		$this->mergeOptionsWithBusinessLogicLayer($options);
		unset($data["options"]);
		
		' . $options_code . '
		$obj = $this->callHbnObject($options);
		$res = null;
		
		if ($obj)
			$res = $obj->find($data, $options);
		
		return $res;
	}

' . WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($hbn_obj_parameters, true, true, false, false, true, false, true) . '
	public function countAll($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		$this->mergeOptionsWithBusinessLogicLayer($options);
		unset($data["options"]);
		
		' . $options_code . '
		$obj = $this->callHbnObject($options);
		$res = null;
		
		if ($obj)
			$res = $obj->count($data, $options);
		
		return $res;
	}
	' . prepareDataAccessNodes($data_access_obj, $db_broker, $db_driver, $include_db_driver, $tasks_file_path, $rels, $obj_id, $module_id, $broker_code_prefix, $reserved_sql_keywords, $tables_props, true, $obj_data, $hbn_obj_parameters) . '
	' . prepareDataAccessNodes($data_access_obj, $db_broker, $db_driver, $include_db_driver, $tasks_file_path, $queries, $obj_id, $module_id, $broker_code_prefix, $reserved_sql_keywords, $tables_props, true) . '
}
?>';
	
		//echo "creating $file_path\n<br>";
		//echo "<pre>$code</pre>";die();
		$obj_table_name = isset($obj_data["@"]["table"]) ? trim($obj_data["@"]["table"]) : null;
		return array($file_path, $obj_id, file_put_contents($file_path, $code) > 0, $obj_table_name);
	}
	return false;
}

function createIbatisBusinessLogicFile($data_access_obj, $db_broker, $db_driver, $include_db_driver, $tasks_file_path, $file_path, $module_id, $queries, $broker_code_prefix, $reserved_sql_keywords, &$tables_props, $overwrite, $common_namespace, $namespace = false, $alias = false) {
	$folder_name = dirname($file_path);
	
	if (file_exists($folder_name) || mkdir($folder_name, 0755, true)) {
		$path_info = pathinfo($file_path);
		$obj_id = $path_info["filename"];
		
		if ($alias) {
			$class_name = $alias;
			$file_path = "$folder_name/{$alias}.php";
		}
		else {
			$class_name = WorkFlowDataAccessHandler::getClassName($obj_id);
			$file_path = "$folder_name/{$class_name}Service.php";
		}
		
		while (!$overwrite && file_exists($file_path)) {
			$rand = rand(0, 100);
			$class_name .= $rand;
			$file_path = "$folder_name/{$class_name}" . ($alias ? "" : "Service") . ".php";
		}
		
		$code = '<?php';
		
		if ($namespace)
			$code .= '
namespace ' . $namespace . ';
';
		
		$code .= '
include_once $vars["business_logic_modules_service_common_file_path"];

class ' . $class_name . ($alias ? "" : "Service") . ' extends ' . ($common_namespace && $namespace != $common_namespace ? "\\$common_namespace\\" : '') . 'CommonService {
	
	private function getDataAccessBroker() {
		$broker = ' . $broker_code_prefix . ';
		
		return $broker;
	}
	' . prepareDataAccessNodes($data_access_obj, $db_broker, $db_driver, $include_db_driver, $tasks_file_path, $queries, $obj_id, $module_id, $broker_code_prefix, $reserved_sql_keywords, $tables_props) . '
}
?>';
		
		//echo "creating $file_path\n<br>";
		//echo "<pre>$code</pre>";die();
		$main_table_name = getSQLStatementsMainTableName($queries, $data_access_obj, $db_broker, $db_driver);
		return array($file_path, $obj_id, file_put_contents($file_path, $code) > 0, $main_table_name);
	}
	return false;
}

function createTableBusinessLogicFile($db_layer_obj, $db_broker, $db_driver, $include_db_driver, $tasks_file_path, $folder_path, $table_name, $queries, $broker_code_prefix, $reserved_sql_keywords, &$tables_props, $overwrite, $common_namespace, $namespace = false, $alias = false) {
	
	if (file_exists($folder_path) || mkdir($folder_path, 0755, true)) {
		if ($alias) {
			$class_name = $alias;
			$file_path = "$folder_path/{$alias}.php";
		}
		else {
			$class_name = WorkFlowDataAccessHandler::getClassName($table_name);
			$file_path = "$folder_path/{$class_name}Service.php";
		}
		
		while (!$overwrite && file_exists($file_path)) {
			$rand = rand(0, 100);
			$class_name .= $rand;
			$file_path = "$folder_path/{$class_name}" . ($alias ? "" : "Service") . ".php";
		}
		
		$attrs = $db_layer_obj->getFunction("listTableFields", $table_name, array("db_driver" => $db_driver));
		$attributes = $pks = array();
		$auto_increment_pks = array_keys( getTableAutoIncrementedPrimaryKeys($attrs) );
		
		if ($attrs) {
			foreach ($attrs as $attr) {
				$attr_name = isset($attr["name"]) ? $attr["name"] : null;
				$attributes[] = $attr_name;
				
				if (!empty($attr["primary_key"]))
					$pks[] = $attr_name;
			}
		
			if (empty($pks)) //if talbe has no pks
				foreach ($attrs as $attr) {
					$attr_name = isset($attr["name"]) ? $attr["name"] : null;
					
					if (!ObjTypeHandler::isDBAttributeNameACreatedDate($attr_name) && !ObjTypeHandler::isDBAttributeNameACreatedUserId($attr_name) && !ObjTypeHandler::isDBAttributeNameAModifiedDate($attr_name) && !ObjTypeHandler::isDBAttributeNameAModifiedUserId($attr_name))
						$pks[] = $attr_name;
				}
		}
		
		$with_ids = count($pks) > 0 ? (count($auto_increment_pks) > 0 ? 2 : 1) : false;
		
		$code = '<?php';
		
		if ($namespace)
			$code .= '
namespace ' . $namespace . ';
';
		
		$code .= '
include_once $vars["business_logic_modules_service_common_file_path"];

class ' . $class_name . ($alias ? "" : "Service") . ' extends ' . ($common_namespace && $namespace != $common_namespace ? "\\$common_namespace\\" : '') . 'CommonService {
	
	private function getDBBroker() {
		$broker = ' . $broker_code_prefix . ';
		
		return $broker;
	}
	
	private function getTableName() {
		return "' . $table_name . '";
	}
	
	private function getTableAttributes() {
		$attributes = array(' . ($attributes ? '"' . implode('", "', $attributes) . '"' : '') . ');
		return $attributes;
	}
	
	private function getTablePrimaryKeys() {
		$pks = array(' . ($pks ? '"' . implode('", "', $pks) . '"' : '') . ');
		return $pks;
	}
	
	private function getTableAutoIncrementPrimaryKeys() {
		$aipks = array(' . ($auto_increment_pks ? '"' . implode('", "', $auto_increment_pks) . '"' : '') . ');
		return $aipks;
	}
	
	private function filterDataByTableAttributes($data, $do_not_include_pks = true) {
		if ($data) {
			$attributes = $this->getTableAttributes();
			$pks = $do_not_include_pks ? self::getTablePrimaryKeys() : array();
			
			foreach ($data as $k => $v) {
				$is_attribute = in_array($k, $attributes) || preg_match("/(^|\(|\.|`)(" . implode("|", $attributes) . ")($|\)|`)/", $k);
				$is_pk = in_array($k, $pks) || preg_match("/(^|\(|\.|`)(" . implode("|", $pks) . ")($|\)|`)/", $k);
				
				if (!$is_attribute || $is_pk)
					unset($data[$k]);
			}
		}
		
		return $data;
	}
	
	private function filterDataByTablePrimaryKeys($data) {
		if ($data) {
			$pks = $this->getTablePrimaryKeys();
			
			foreach ($data as $k => $v)
				if (!in_array($k, $pks))
					unset($data[$k]);
		}
		
		return $data;
	}
	
	private function filterDataExcludingTableAutoIncrementPrimaryKeys($data) {
		if ($data) {
			$pks = $this->getTableAutoIncrementPrimaryKeys();
			
			foreach ($data as $k => $v)
				if (in_array($k, $pks))
					unset($data[$k]);
		}
		
		return $data;
	}
	
	private function filterConditionsByTableAttributes($conditions) {
		if ($conditions) {
			$attributes = $this->getTableAttributes();
			$joins = array("or", "and", "&&", "||");
			
			foreach ($conditions as $k => $v) {
				if (in_array(strtolower($k), $joins) || is_numeric($k)) {
					if (is_array($v))
						$conditions[$k] = $this->filterConditionsByTableAttributes($v);
					//else leave it as it is. For more info check DBSQLConverter::getSQLConditions method
				}
				else {
					$is_attribute = in_array($k, $attributes) || preg_match("/(^|\(|\.|`)(" . implode("|", $attributes) . ")($|\)|`)/", $k);
					
					if (!$is_attribute)
						unset($conditions[$k]);
				}
			}
		}
		
		return $conditions;
	}
	' . prepareTableNodes($db_layer_obj, $db_broker, $db_driver, $include_db_driver, $tasks_file_path, $table_name, $queries, $broker_code_prefix, $reserved_sql_keywords, $tables_props, $with_ids) . '
}
?>';
		
		//echo "creating $file_path\n<br>";
		//echo "<pre>$code</pre>";die();
		return array($file_path, $table_name, file_put_contents($file_path, $code) > 0, $table_name);
	}
	return false;
}

function prepareTableNodes($db_layer_obj, $db_broker, $db_driver, $include_db_driver, $tasks_file_path, $table_name, $rels, $broker_code_prefix, $reserved_sql_keywords, &$tables_props, $with_ids) {
	$code = "";
	
	$class_name = WorkFlowDataAccessHandler::getClassName($table_name);
	$tables_props = array();
	$db_broker_prefix_code = "\$this->getDBBroker()";
	$tn = strtolower($table_name);
	//echo"table_name:$table_name<pre>";print_r($rels);die();
	
	if (is_array($rels)) {
		foreach ($rels as $rel_type => $rel) {
			foreach ($rel as $rel_id => $rel_data) {
				$name = getFunctionName($rel_id);
				$parameters_code = $func_code = "";
				
				switch($rel_type) {
					case "insert":
						//get right query to set the correct parameters for the annotations
						if (substr($rel_id, - strlen("_with_ai_pk")) == "_with_ai_pk") {
							//ignore insert_with_ai_pk, bc it will be already included in the insert query, but only if simple insert exists. Otherwise it will execute this code twice.
							if (!empty($rel[ substr($rel_id, 0, - strlen("_with_ai_pk")) ]))
								continue 2; 
						}
						else if (!empty($rel[$rel_id . "_with_ai_pk"])) {
							$name = getFunctionName($rel_id); //before it changes the rel_id with "_with_ai_pk" string
							$rel_id = $rel_id . "_with_ai_pk";
							$rel_data = $rel[$rel_id];
						}
						
						//prepare code
						$name = stripos($name, "insert") !== false || stripos($name, "add") !== false ? $name : "insert" . ucfirst($name);
						$name = $name == "insert$class_name" ? "insert" : $name;
						
						$options_code = getDBDriverOptionsCode($db_driver, $include_db_driver);
						$parameters_code = prepareSQLStatementCode($rel_data, $rels, $db_layer_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $parameters);
						WorkFlowBusinessLogicHandler::disableAddSqlSlashesInParameters($parameters);
						$addcslashes_code = "";//WorkFlowBusinessLogicHandler::prepareAddcslashesCode($parameters); //DB DAO already adds slaslhes automatically
						$default_value_code = WorkFlowBusinessLogicHandler::prepareAttributesDefaultValueCode($parameters, false);
						$string_to_numeric_code = WorkFlowBusinessLogicHandler::prepareNumericAttributesStringValueCode($parameters);
						
						//check if table has pks
						$no_pks = true;
						
						if (!empty($tables_props[$tn]))
							foreach ($tables_props[$tn] as $attr)
								if (!empty($attr["primary_key"])) {
									$no_pks = false;
									break;
								}
						
						//prepare annotations
						$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters, $with_ids, true, true, true, true, false, false);
						//echo "<pre>".$return_code;print_r($parameters);echo "<textarea>$annotations</textarea>";echo "<textarea>$parameters_code</textarea>";die();
						
						//prepare code
						if ($default_value_code)
							$func_code .= $default_value_code . "
		";
						
						if ($string_to_numeric_code)
							$func_code .= $string_to_numeric_code . "
		";
						
						$func_code .= "\$result = false;
		\$attributes = \$this->filterDataByTableAttributes(\$data, false);
		
		if (\$attributes) {
			";
						
						if ($no_pks) //if table has no pks
							$func_code .= "\$result = {$db_broker_prefix_code}->insertObject(\$this->getTableName(), \$attributes, \$options);
			\$result = \$result ? true : false;";
						else
							$func_code .= "\$ai_pks = \$this->getTableAutoIncrementPrimaryKeys();
			\$set_ai_pk = null;
			
			//This code supposes that there is only 1 auto increment pk
			foreach (\$ai_pks as \$pk_name) 
				if (!empty(\$data[\$pk_name])) {
					\$set_ai_pk = \$pk_name;
					break;
				}
			
			if (\$set_ai_pk || empty(\$ai_pks)) {
				\$options[\"hard_coded_ai_pk\"] = true;
				\$result = {$db_broker_prefix_code}->insertObject(\$this->getTableName(), \$attributes, \$options);
				//\$result = \$result && isset(\$data[\$set_ai_pk]) ? \$data[\$set_ai_pk] : false;
				
				if (\$result) {
		    			if (\$set_ai_pk)
		    			    \$result = isset(\$data[\$set_ai_pk]) ? \$data[\$set_ai_pk] : null;
		    			else { //in case of primary keys with no auto increment.
		    			    \$pks = \$this->getTablePrimaryKeys();
		    			    
		    			    foreach (\$pks as \$pk_name) 
				  			if (!empty(\$data[\$pk_name])) {
				  				\$result = \$data[\$pk_name];
				  				break;
				  			}
		    			}
				}
			}
			else {
				\$attributes = \$this->filterDataExcludingTableAutoIncrementPrimaryKeys(\$attributes);
				\$result = {$db_broker_prefix_code}->insertObject(\$this->getTableName(), \$attributes, \$options);
				\$result = \$result ? (
					\$ai_pks ? {$db_broker_prefix_code}->getInsertedId(\$options) : true
				) : false;
			}";
						
						$func_code .= "
		}";
						
						$code .= getBusinessLogicServiceFunctionCode($name, $parameters, $addcslashes_code, $parameters_code, $func_code, $annotations, $options_code);
						break;
					case "update":
						$name = stripos($name, "update") !== false || stripos($name, "edit") !== false ? $name : "update" . ucfirst($name);
						$name = $name == "update$class_name" ? "update" : $name;
						$name = $name == "update{$class_name}PrimaryKeys" || $name == "update{$class_name}Pks" ? "updatePrimaryKeys" : $name;
						$name = $name == "updateAll{$class_name}Items" ? "updateAll" : $name;
						
						$options_code = getDBDriverOptionsCode($db_driver, $include_db_driver);
						$parameters_code = prepareSQLStatementCode($rel_data, $rels, $db_layer_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $parameters);
						WorkFlowBusinessLogicHandler::disableAddSqlSlashesInParameters($parameters);
						$addcslashes_code = "";//WorkFlowBusinessLogicHandler::prepareAddcslashesCode($parameters); //DB DAO already adds slaslhes automatically
						$default_value_code = WorkFlowBusinessLogicHandler::prepareAttributesDefaultValueCode($parameters, false, true);
						$string_to_numeric_code = WorkFlowBusinessLogicHandler::prepareNumericAttributesStringValueCode($parameters);
						
						//check if table has pks
						$no_pks = true;
						
						if (!empty($tables_props[$tn]))
							foreach ($tables_props[$tn] as $attr)
								if (!empty($attr["primary_key"])) {
									$no_pks = false;
									break;
								}
						
						//prepare annotations
						$annotations = null;
						
						if ($name == "updateAll") {
							$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters, false, true, true, true, true, true, false);
							
							//add conditions parameters
							$annotations_parameters = $parameters;
							prepareSelectAllSQLParameters($rel_data, $rels, $db_layer_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $annotations_parameters, $table_name);
							WorkFlowBusinessLogicHandler::disableAddSqlSlashesInParameters($annotations_parameters);
							
							$annotations .= WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($annotations_parameters, true, true, false, false, true, false, true);
							
							//join parameters
							$annotations = preg_replace("|\s*\*/\s*/\*\*\s*|", "\n\t ", $annotations);
						}
						else if ($name == "updatePrimaryKeys") {
							if ($no_pks) {
								$parameters_aux = $parameters;
								
								foreach ($parameters_aux as $param_name => $param_props) {
									if (ObjTypeHandler::isDBAttributeNameACreatedDate($param_name) || ObjTypeHandler::isDBAttributeNameACreatedUserId($param_name) || ObjTypeHandler::isDBAttributeNameAModifiedDate($param_name) ||ObjTypeHandler::isDBAttributeNameAModifiedUserId($param_name))
										unset($parameters_aux[$param_name]);
									else
										$parameters_aux[$param_name]["mandatory"] = true;
								}
								
								$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters_aux, true, true, true, true, true, true, false);
							}
							else
								$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters, true, false, true, false, true, true, false);
						}
						else {
							if ($no_pks) {
								$parameters_aux = $parameters;
								
								foreach ($parameters_aux as $param_name => $param_props) {
									if (!ObjTypeHandler::isDBAttributeNameACreatedDate($param_name) && !ObjTypeHandler::isDBAttributeNameACreatedUserId($param_name) && !ObjTypeHandler::isDBAttributeNameAModifiedDate($param_name) && !ObjTypeHandler::isDBAttributeNameAModifiedUserId($param_name))
										$parameters_aux[$param_name]["mandatory"] = true;
								}
								
								$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters_aux, true, true, true, true, true, true, false);
							}
							else
								$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters, true, true, true, true, true, true, false);
						}
						
						//prepare code
						if ($default_value_code)
							$func_code .= $default_value_code . "
		";
						
						if ($string_to_numeric_code)
							$func_code .= $string_to_numeric_code . "
		";
						
						if ($name == "updateAll")
							$func_code .= "\$attributes = \$this->filterDataByTableAttributes(\$data);
		\$conditions = isset(\$data[\"conditions\"]) ? \$data[\"conditions\"] : null;
		
		if (\$conditions)
			\$conditions = \$this->filterConditionsByTableAttributes(\$conditions);
		
		\$options[\"all\"] = isset(\$data[\"all\"]) ? \$data[\"all\"] : null;
		\$result = {$db_broker_prefix_code}->updateObject(\$this->getTableName(), \$attributes, \$conditions, \$options);";
						else if ($name == "updatePrimaryKeys")
							$func_code .= "\$attributes = \$conditions = array();
		\$pks = self::getTablePrimaryKeys();
		
		foreach (\$pks as \$pk) {
			\$attributes[\$pk] = isset(\$data[\"new_\" . \$pk]) ? \$data[\"new_\" . \$pk] : null;
			\$conditions[\$pk] = isset(\$data[\"old_\" . \$pk]) ? \$data[\"old_\" . \$pk] : null;
		}
		
		\$result = {$db_broker_prefix_code}->updateObject(\$this->getTableName(), \$attributes, \$conditions, \$options);";
						else {
							if ($no_pks) 
								$func_code .= '$attributes = array();
		$conditions = array();
		
		foreach ($data as $key => $value) {
			if (substr($key, 0, 4) == "old_")
				$conditions[ substr($key, 4) ] = $value;
			else if (substr($key, 0, 4) == "new_")
				$attributes[ substr($key, 4) ] = $value;
			else if (!array_key_exists($key, $attributes))
				$attributes[$key] = $value;
		}
		
		$attributes = $this->filterDataByTableAttributes($attributes, false);
		$conditions = $this->filterDataByTablePrimaryKeys($conditions);';
							else
								$func_code .= "\$attributes = \$this->filterDataByTableAttributes(\$data);
		\$conditions = \$this->filterDataByTablePrimaryKeys(\$data);";
		
							$func_code .= "
		\$result = {$db_broker_prefix_code}->updateObject(\$this->getTableName(), \$attributes, \$conditions, \$options);";
						}
						
						$code .= getBusinessLogicServiceFunctionCode($name, $parameters, $addcslashes_code, $parameters_code, $func_code, $annotations, $options_code);
						break;
					case "delete":
						$name = stripos($name, "delete") !== false || stripos($name, "remove") !== false ? $name : "delete" . ucfirst($name);
						$name = $name == "delete$class_name" ? "delete" : $name;
						$name = $name == "deleteAll{$class_name}Items" ? "deleteAll" : $name;
						
						$options_code = getDBDriverOptionsCode($db_driver, $include_db_driver);
						$parameters_code = prepareSQLStatementCode($rel_data, $rels, $db_layer_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $parameters);
						WorkFlowBusinessLogicHandler::disableAddSqlSlashesInParameters($parameters);
						$addcslashes_code = "";//WorkFlowBusinessLogicHandler::prepareAddcslashesCode($parameters); //DB DAO already adds slaslhes automatically
						
						//check if table has pks
						$no_pks = true;
						
						if (!empty($tables_props[$tn]))
							foreach ($tables_props[$tn] as $attr)
								if (!empty($attr["primary_key"])) {
									$no_pks = false;
									break;
								}
						
						//prepare annotations
						$annotations = null;
						
						if ($name != "deleteAll") {
							if ($no_pks) {
								$parameters_aux = $parameters;
								
								foreach ($parameters_aux as $param_name => $param_props)
									$parameters_aux[$param_name]["mandatory"] = true;
								
								$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters_aux, true, true, true, false, true, false, false);
							}
							else 
								$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters, true, false, true, false, true, false, false);
						}
						else {
							$annotations_parameters = $parameters;
							prepareSelectAllSQLParameters($rel_data, $rels, $db_layer_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $annotations_parameters, $table_name);
							WorkFlowBusinessLogicHandler::disableAddSqlSlashesInParameters($annotations_parameters);
							
							$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($annotations_parameters, true, true, false, false, true, false, true);
						}
						
						//prepare code
						if ($name == "deleteAll")
							$func_code .= "\$conditions = isset(\$data[\"conditions\"]) ? \$data[\"conditions\"] : null;
		
		if (\$conditions)
			\$conditions = \$this->filterConditionsByTableAttributes(\$conditions);
		
		\$options[\"all\"] = isset(\$data[\"all\"]) ? \$data[\"all\"] : null;
		\$result = {$db_broker_prefix_code}->deleteObject(\$this->getTableName(), \$conditions, \$options);";
						else
							$func_code .= "\$conditions = \$this->filterDataByTablePrimaryKeys(\$data);
		\$result = {$db_broker_prefix_code}->deleteObject(\$this->getTableName(), \$conditions, \$options);";
						
						$code .= getBusinessLogicServiceFunctionCode($name, $parameters, $addcslashes_code, $parameters_code, $func_code, $annotations, $options_code);
						break;
					case "select":
						$is_count = isCountFunctionName($name);
						$is_get = !$is_count && (stripos($name, "get") !== false || stripos($name, "select") !== false);
						$name = $is_count || $is_get ? $name : "get" . ucfirst($name);
						
						$name = $name == "get$class_name" ? "get" : $name;
						$name = $name == "count$class_name" ? "count" : $name;
						$name = $name == "get{$class_name}Items" ? "getAll" : $name;
						$name = $name == "count{$class_name}Items" ? "countAll" : $name;
						
						$options_code = getDBDriverOptionsCode($db_driver, $include_db_driver);
						$parameters_code = prepareSQLStatementCode($rel_data, $rels, $db_layer_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $parameters);
						WorkFlowBusinessLogicHandler::disableAddSqlSlashesInParameters($parameters);
						$addcslashes_code = "";//WorkFlowBusinessLogicHandler::prepareAddcslashesCode($parameters); //DB DAO already adds slaslhes automatically
						
						//check if table has pks
						$no_pks = true;
						
						if (!empty($tables_props[$tn]))
							foreach ($tables_props[$tn] as $attr)
								if (!empty($attr["primary_key"])) {
									$no_pks = false;
									break;
								}
						
						//prepare annotations
						if ($name != "getAll" && $name != "countAll") {
							if ($no_pks/* && $name == "get"*/) { //for get and relationships methods
								$parameters_aux = $parameters;
								
								foreach ($parameters_aux as $param_name => $param_props)
									$parameters_aux[$param_name]["mandatory"] = true;
								
								$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters_aux, true, true, true, false, true, false, false);
							}
							else
								$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters, true, true, true, false, true, false, false);
						}
						else {
							$annotations_parameters = $parameters;
							prepareSelectAllSQLParameters($rel_data, $rels, $db_layer_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $annotations_parameters, $table_name);
							WorkFlowBusinessLogicHandler::disableAddSqlSlashesInParameters($annotations_parameters);
							
							$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($annotations_parameters, true, true, false, false, true, false, true);
						}
						
						//prepare code
						$func_code .= "if (!empty(\$data[\"conditions\"]))
			\$data[\"conditions\"] = \$this->filterConditionsByTableAttributes(\$data[\"conditions\"]);
		
		self::prepareInputData(\$data);
		
		if (!empty(\$data[\"searching_condition\"]))
			\$options[\"sql_conditions\"] = \"1=1\" . \$data[\"searching_condition\"];
		
		";
						
						if ($name == "get")
							$func_code .= "\$conditions = \$this->filterDataByTablePrimaryKeys(\$data);
		\$result = {$db_broker_prefix_code}->findObjects(\$this->getTableName(), null, \$conditions, \$options);
		\$result = \$result ? \$result[0] : null;";
						else if ($name == "countAll")
							$func_code .= "\$result = {$db_broker_prefix_code}->countObjects(\$this->getTableName(), null, \$options);";
						else if ($name == "getAll")
							$func_code .= "\$result = {$db_broker_prefix_code}->findObjects(\$this->getTableName(), null, null, \$options);";
						else {
							$sql = isset($rel_data["value"]) ? $rel_data["value"] : null;
							$sql_data = $db_layer_obj->getFunction("convertDefaultSQLToObject", $sql, array("db_driver" => $db_driver));
							//echo"<pre>";print_r($sql_data);die();
							$keys = array();
							
							if (!empty($sql_data["keys"]))
								foreach ($sql_data["keys"] as $key) {
									$ptable = isset($key["ptable"]) ? $key["ptable"] : null;
									
									$keys[] = $ptable == $table_name ? $key : array(
										"ptable" => isset($key["ftable"]) ? $key["ftable"] : null,
										"pcolumn" => isset($key["fcolumn"]) ? $key["fcolumn"] : null,
										"ftable" => $ptable,
										"fcolumn" => isset($key["pcolumn"]) ? $key["pcolumn"] : null,
										"value" => isset($key["value"]) ? $key["value"] : null,
										"join" => isset($key["join"]) ? $key["join"] : null,
										"operator" => isset($key["operator"]) ? $key["operator"] : null,
									);
								}
							
							$keys_code = str_replace("'$table_name'", '$this->getTableName()', str_replace("\n", "\n\t\t", var_export($keys, 1)));
							
							$func_code .= "\$keys = " . $keys_code . ";
		
		\$rel_elm = array(\"keys\" => \$keys);
		\$parent_conditions = \$this->filterDataByTablePrimaryKeys(\$data);
		";
							
							if ($is_count) // if foreign table count
								$func_code .= "\$result = {$db_broker_prefix_code}->countRelationshipObjects(\$this->getTableName(), \$rel_elm, \$parent_conditions, \$options);";
							else // if foreign table select
								$func_code .= "\$result = {$db_broker_prefix_code}->findRelationshipObjects(\$this->getTableName(), \$rel_elm, \$parent_conditions, \$options);";
						}
						
						$code .= getBusinessLogicServiceFunctionCode($name, $parameters, $addcslashes_code, $parameters_code, $func_code, $annotations, $options_code);
						break;
				}
			}
		}
	}
	
	//echo "<pre>";print_r($tables_props);die();
	
	return $code;
}

function prepareDataAccessNodes($data_access_obj, $db_broker, $db_driver, $include_db_driver, $tasks_file_path, $rels, $obj_id, $module_id, $broker_code_prefix, $reserved_sql_keywords, &$tables_props, $is_hibernate_obj = false, $hbn_obj_data = false, $hbn_obj_parameters = false) {
	$code = "";
	
	$class_name = WorkFlowDataAccessHandler::getClassName($obj_id);
	$tables_props = array();
	
	$call_object_code = $is_hibernate_obj ? "\$obj = \$this->callHbnObject(\$options);" : "";
	$data_access_broker_prefix_code = $is_hibernate_obj ? "\$obj" : "\$this->getDataAccessBroker()";
	$module_id_code = $is_hibernate_obj ? "" : "'$module_id', ";
	
	if (is_array($rels)) {
		foreach ($rels as $rel_type => $rel) {
			foreach ($rel as $rel_id => $rel_data) {
				$name = getFunctionName($rel_id);
				$parameters_code = $func_code = "";
				
				switch($rel_type) {
					case "insert":
						$exists_insert_without_pk = false;
						
						if (substr($rel_id, - strlen("_with_ai_pk")) == "_with_ai_pk") {
							//ignore insert_with_ai_pk, bc it will be already included in the insert query, but only if simple insert exists. Otherwise it will execute this code twice.
							if (!empty($rel[ substr($rel_id, 0, - strlen("_with_ai_pk")) ]))
								continue 2; 
						}
						else if (!empty($rel[$rel_id . "_with_ai_pk"])) {
							$rel_data = $rel[$rel_id . "_with_ai_pk"];
							$exists_insert_without_pk = true;
						}
						
						//prepare code
						$name = stripos($name, "insert") !== false || stripos($name, "add") !== false ? $name : "insert" . ucfirst($name);
						$name = !$is_hibernate_obj && $name == "insert$class_name" ? "insert" : $name;
						
						$options_code = getDBDriverOptionsCode($db_driver, $include_db_driver);
						$parameters_code = prepareSQLStatementCode($rel_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $parameters);
						$addcslashes_code = WorkFlowBusinessLogicHandler::prepareAddcslashesCode($parameters);
						$with_ids = checkTypeOfExistentPrimaryKeys($parameters, $auto_increment_pk_name);
						$default_value_code = WorkFlowBusinessLogicHandler::prepareAttributesDefaultValueCode($parameters, true);
						
						//check if table has pks
						$no_pks = empty($with_ids);
						
						//prepare annotations
						$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters, $with_ids, true, true, true, true, false, false);
						
						//echo "<pre>".$return_code;print_r($parameters);echo "<textarea>$annotations</textarea>";echo "<textarea>$parameters_code</textarea>";die();
						//echo "<pre>";print_r($parameters);print_r($tables_props);die();
						
						//prepare code
						if ($call_object_code)
							$func_code .= $call_object_code . "
		";
						
						if ($default_value_code)
							$func_code .= $default_value_code . "
		";
						
						//this only applies to ibatis and if exists 2 inserts: "insert without pk" and "insert with pk" queries and if '$rel_id_with_pk' is a sql with the auto increment pk, and that '$rel_id' is a sql without auto increment pks.
						if ($auto_increment_pk_name && $exists_insert_without_pk) {
							$rel_id_with_pk = $rel_id . "_with_ai_pk";
							
							$func_code .= "//This code supposes that there is only 1 auto increment pk and that '$rel_id_with_pk' is a sql with the auto increment pk, and that '$rel_id' is a sql without auto increment pks.
		if (!empty(\$data[\"$auto_increment_pk_name\"])) {
			\$options[\"hard_coded_ai_pk\"] = true;
			\$result = {$data_access_broker_prefix_code}->callInsert({$module_id_code}'$rel_id_with_pk', \$data, \$options);
			\$result = \$result && isset(\$data[\"$auto_increment_pk_name\"]) ? \$data[\"$auto_increment_pk_name\"] : false;
		}
		else {
			\$result = {$data_access_broker_prefix_code}->callInsert({$module_id_code}'$rel_id', \$data, \$options);
			\$result = \$result ? {$data_access_broker_prefix_code}->getInsertedId(\$options) : false;
		}
		";
						}
						else if ($no_pks) {
							$func_code .= "\$result = {$data_access_broker_prefix_code}->callInsert({$module_id_code}'$rel_id', \$data, \$options);
		\$result = \$result ? true : false;";
						}
						else { //if not ibatis or if only exists 1 "insert without pk" or 1 "insert with pk"
							$table_name = WorkFlowDataAccessHandler::getSQLStatementTable($rel_data, $data_access_obj, $db_broker, $db_driver);
							
							if ($auto_increment_pk_name)
								$func_code .= "//This code supposes that there is only 1 auto increment pk
		\$options[\"hard_coded_ai_pk\"] = true;
		
		if (empty(\$data[\"$auto_increment_pk_name\"]))
			\$data[\"$auto_increment_pk_name\"] = {$data_access_broker_prefix_code}->findObjectsColumnMax(\"$table_name\", \"$auto_increment_pk_name\") + 1; //DO NOT SET IT TO 'DEFAULT' BC IT WON'T WORK IN MS-SQL-SERVER. 'DEFAULT' ONLY WORKS IN MYSQL AND POSTGRES!
		";
							
							$func_code .= "\$result = {$data_access_broker_prefix_code}->callInsert({$module_id_code}'$rel_id', \$data, \$options);
		";
							
							if ($auto_increment_pk_name)
								$func_code .= "\$result = \$result && isset(\$data[\"$auto_increment_pk_name\"]) ? \$data[\"$auto_increment_pk_name\"] : false;
		";
							else { //if there is not any auto_increment PK in the sql query, but the DB table has a auto increment PK, we must get the last inserted id.
								$table_props = WorkFlowDBHandler::getTableFromTables($tables_props, $table_name);
								
								if ($table_name && getTableAutoIncrementedPrimaryKeys($table_props)) //If no PK in insert sql, but exists auto_increment PK in database table.
									$func_code .= "\$result = \$result ? {$data_access_broker_prefix_code}->getInsertedId(\$options) : false;";
								else //If there is no PK.
									$func_code .= "\$result = \$result ? true : false;";
							}
						}
						
						$code .= getBusinessLogicServiceFunctionCode($name, $parameters, $addcslashes_code, $parameters_code, $func_code, $annotations, $options_code);
						break;
					case "update":
						$name = stripos($name, "update") !== false || stripos($name, "edit") !== false ? $name : "update" . ucfirst($name);
						$name = !$is_hibernate_obj && $name == "update$class_name" ? "update" : $name;
						$name = !$is_hibernate_obj && ($name == "update{$class_name}PrimaryKeys" || $name == "update{$class_name}Pks") ? "updatePrimaryKeys" : $name;
						$name = !$is_hibernate_obj && $name == "updateAll{$class_name}Items" ? "updateAll" : $name;
						
						$options_code = getDBDriverOptionsCode($db_driver, $include_db_driver);
						$parameters_code = prepareSQLStatementCode($rel_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $parameters);
						$addcslashes_code = WorkFlowBusinessLogicHandler::prepareAddcslashesCode($parameters);
						$with_ids = checkTypeOfExistentPrimaryKeys($parameters, $aux);
						$default_value_code = WorkFlowBusinessLogicHandler::prepareAttributesDefaultValueCode($parameters, true, true);
						
						//check if table has pks
						$no_pks = empty($with_ids);
						
						//prepare annotations
						if ($name == "updateAll") {
							$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters, false, true, true, true, true, true, false);
							
							//add conditions parameters
							$annotations_parameters = $parameters;
							$table_name = WorkFlowDataAccessHandler::getSQLStatementTable($rel_data, $data_access_obj, $db_broker, $db_driver);
							prepareSelectAllSQLParameters($rel_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $annotations_parameters, $table_name);
							$annotations .= WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($annotations_parameters, true, true, false, false, true, false, true);
							
							//join parameters
							$annotations = preg_replace("|\s*\*/\s*/\*\*\s*|", "\n\t ", $annotations);
						}
						else if ($name == "updatePrimaryKeys") {
							if ($no_pks) {
								$parameters_aux = $parameters;
								
								foreach ($parameters_aux as $param_name => $param_props) {
									if (ObjTypeHandler::isDBAttributeNameACreatedDate($param_name) || ObjTypeHandler::isDBAttributeNameACreatedUserId($param_name) || ObjTypeHandler::isDBAttributeNameAModifiedDate($param_name) ||ObjTypeHandler::isDBAttributeNameAModifiedUserId($param_name))
										unset($parameters_aux[$param_name]);
									else
										$parameters_aux[$param_name]["mandatory"] = true;
								}
								
								$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters_aux, true, true, true, true, true, true, false);
							}
							else
								$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters, true, false, true, false, true, true, false);
						}
						else {
							if ($no_pks) {
								$parameters_aux = $parameters;
								
								foreach ($parameters_aux as $param_name => $param_props) {
									if (!ObjTypeHandler::isDBAttributeNameACreatedDate($param_name) && !ObjTypeHandler::isDBAttributeNameACreatedUserId($param_name) && !ObjTypeHandler::isDBAttributeNameAModifiedDate($param_name) && !ObjTypeHandler::isDBAttributeNameAModifiedUserId($param_name))
										$parameters_aux[$param_name]["mandatory"] = true;
								}
								
								$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters_aux, true, true, true, true, true, true, false);
							}
							else
								$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters, true, true, true, true, true, true, false);
						}
						
						//prepare code
						if ($default_value_code)
							$func_code .= $default_value_code . "
		";
						 
						if ($call_object_code)
							$func_code .= $call_object_code . "
		";
						
						if ($name == "updateAll")
							$func_code .= "if (!empty(\$data[\"conditions\"]) || !empty(\$data[\"all\"])) {
			self::prepareInputData(\$data);
			\$result = {$data_access_broker_prefix_code}->callUpdate({$module_id_code}'$rel_id', \$data, \$options);
		}";
						else
							$func_code .= "\$result = {$data_access_broker_prefix_code}->callUpdate({$module_id_code}'$rel_id', \$data, \$options);";
						
						$code .= getBusinessLogicServiceFunctionCode($name, $parameters, $addcslashes_code, $parameters_code, $func_code, $annotations, $options_code);
						break;
					case "delete":
						$name = stripos($name, "delete") !== false || stripos($name, "remove") !== false ? $name : "delete" . ucfirst($name);
						$name = !$is_hibernate_obj && $name == "delete$class_name" ? "delete" : $name;
						$name = !$is_hibernate_obj && $name == "deleteAll{$class_name}Items" ? "deleteAll" : $name;
						
						$options_code = getDBDriverOptionsCode($db_driver, $include_db_driver);
						$parameters_code = prepareSQLStatementCode($rel_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $parameters);
						$addcslashes_code = WorkFlowBusinessLogicHandler::prepareAddcslashesCode($parameters);
						$with_ids = checkTypeOfExistentPrimaryKeys($parameters, $aux);
						
						//check if table has pks
						$no_pks = empty($with_ids);
						
						//prepare annotations
						$annotations = null;
						
						if ($name != "deleteAll") {
							if ($no_pks) {
								$parameters_aux = $parameters;
								
								foreach ($parameters_aux as $param_name => $param_props)
									$parameters_aux[$param_name]["mandatory"] = true;
								
								$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters_aux, true, true, true, false, true, false, false);
							}
							else 
								$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters, true, false, true, false, true, false, false);
						}
						else {
							$annotations_parameters = $parameters;
							$table_name = WorkFlowDataAccessHandler::getSQLStatementTable($rel_data, $data_access_obj, $db_broker, $db_driver);
							prepareSelectAllSQLParameters($rel_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $annotations_parameters, $table_name);
							$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($annotations_parameters, true, true, false, false, true, false, true);
						}
						
						//prepare code
						if ($call_object_code)
							$func_code .= $call_object_code . "
		";
						
						if ($name == "deleteAll")
							$func_code .= "if (!empty(\$data[\"conditions\"]) || !empty(\$data[\"all\"])) {
			self::prepareInputData(\$data);
			\$result = {$data_access_broker_prefix_code}->callDelete({$module_id_code}'$rel_id', \$data, \$options);
		}";
						else
							$func_code .= "\$result = {$data_access_broker_prefix_code}->callDelete({$module_id_code}'$rel_id', \$data, \$options);";
						
						$code .= getBusinessLogicServiceFunctionCode($name, $parameters, $addcslashes_code, $parameters_code, $func_code, $annotations, $options_code);
						break;
					case "select":
						$is_count = isCountFunctionName($name);
						$is_get = !$is_count && (stripos($name, "get") !== false || stripos($name, "select") !== false);
						$name = $is_count || $is_get ? $name : "get" . ucfirst($name);
						
						$name = !$is_hibernate_obj && $name == "get$class_name" ? "get" : $name;
						$name = !$is_hibernate_obj && $name == "count$class_name" ? "count" : $name;
						$name = !$is_hibernate_obj && $name == "get{$class_name}Items" ? "getAll" : $name;
						$name = !$is_hibernate_obj && $name == "count{$class_name}Items" ? "countAll" : $name;
						
						$options_code = getDBDriverOptionsCode($db_driver, $include_db_driver);
						$parameters_code = prepareSQLStatementCode($rel_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $parameters);
						$with_ids = checkTypeOfExistentPrimaryKeys($parameters, $aux);
						$addcslashes_code = WorkFlowBusinessLogicHandler::prepareAddcslashesCode($parameters);
						
						//check if table has pks
						$no_pks = empty($with_ids);
						
						//prepare annotations
						if ($name != "getAll" && $name != "countAll") {
							if ($no_pks/* && $name == "get"*/) { //for get and relationships methods
								$parameters_aux = $parameters;
								
								foreach ($parameters_aux as $param_name => $param_props)
									$parameters_aux[$param_name]["mandatory"] = true;
								
								$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters_aux, true, true, true, false, true, false, false);
							}
							else
								$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters, true, true, true, false, true, false, false);
						}
						else {
							$annotations_parameters = $parameters;
							$table_name = WorkFlowDataAccessHandler::getSQLStatementTable($rel_data, $data_access_obj, $db_broker, $db_driver);
							prepareSelectAllSQLParameters($rel_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $annotations_parameters, $table_name);
							$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($annotations_parameters, true, true, false, false, true, false, true);
						}
						
						//prepare code
						if ($call_object_code)
							$func_code .= $call_object_code . "
		";
						
						$func_code .= "self::prepareInputData(\$data);
		\$result = {$data_access_broker_prefix_code}->callSelect({$module_id_code}'$rel_id', \$data, \$options);";
		
						if ($name == "get")
							$func_code .= "
		\$result = \$result ? \$result[0] : null;";
						else if ($is_count)
							$func_code .= "
		\$result = \$result && isset(\$result[0][\"total\"]) ? \$result[0][\"total\"] : null;";
						
						$code .= getBusinessLogicServiceFunctionCode($name, $parameters, $addcslashes_code, $parameters_code, $func_code, $annotations, $options_code);
						break;
					case "procedure":
						$name = stripos($name, "call") !== false ? $name : "call" . ucfirst($name);
						
						$options_code = getDBDriverOptionsCode($db_driver, $include_db_driver);
						$parameters_code = prepareSQLStatementCode($rel_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $parameters);
						$addcslashes_code = WorkFlowBusinessLogicHandler::prepareAddcslashesCode($parameters);
						$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters, true, true, false, true, true, false, false);
						
						if ($call_object_code)
							$func_code .= $call_object_code . "
		";
						
						$func_code .= "\$result = {$data_access_broker_prefix_code}->callProcedure({$module_id_code}'$rel_id', \$data, \$options);";
						
						$code .= getBusinessLogicServiceFunctionCode($name, $parameters, $addcslashes_code, $parameters_code, $func_code, $annotations, $options_code);
						break;
					case "one_to_one":
					case "many_to_one":
					case "one_to_many":
					case "many_to_many":
						//Add find relationship method
						$name = stripos($name, "findrelationship") !== false || stripos($name, "get") !== false || stripos($name, "select") !== false ? $name : "get" . ucfirst($name);//findRelationship or get. Get looks better!
						
						$options_code = getDBDriverOptionsCode($db_driver, $include_db_driver);
						$parameters_code = prepareRelationshipCode($rel_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $hbn_obj_data, $parameters);
						
						if (empty($rel_data["@"]["parameter_class"]))
							WorkFlowDataAccessHandler::addPrimaryKeysToParameters($hbn_obj_parameters, $parameters);
						
						$annotations = WorkFlowBusinessLogicHandler::getAnnotationsFromParameters($parameters, true, false, true, false, true, false, false);
						
						$func_code = "unset(\$data[\"options\"]);
		
		\$obj = \$this->callHbnObject(\$options);
		\$result = \$obj ? \$obj->findRelationship('$rel_id', \$data, \$options) : null;";
						
						$code .= getBusinessLogicServiceFunctionCode($name, $parameters, "", $parameters_code, $func_code, $annotations, $options_code);
						
						//Add count relationship method
						if (stripos($name, "findrelationship") !== false)
							$name = "countRelationship" . ucfirst(str_ireplace("findrelationship", "", $name));
						else 
							$name = "count" . ucfirst(str_ireplace(array("numberof", "number", "total", "get", "select"), "", $name));
						
						$func_code = "\$obj = \$this->callHbnObject(\$options);
		\$result = \$obj ? \$obj->countRelationship('$rel_id', \$data, \$options) : null;";
						
						$code .= getBusinessLogicServiceFunctionCode($name, $parameters, "", $parameters_code, $func_code, $annotations, $options_code);
						break;
				}
			}
		}
	}
	
	return $code;
}

function getBusinessLogicServiceFunctionCode($name, $parameters, $addcslashes_code, $parameters_code, $func_code, $annotations = null, $options_code = null) {
	$code = "";
	
	if ($name) {
		//echo "<pre>";print_r($parameters);echo "</pre>";
		//echo "<pre>$annotations</pre><br>";
		
		$code .= '
' . $annotations . '
	public function ' . $name . '($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		$this->mergeOptionsWithBusinessLogicLayer($options);
		$result = null;
		';
		
		if ($options_code)
			$code .= $options_code . '
		';
		
		if (!$annotations && $parameters_code) {
			if ($addcslashes_code) {
				$code .= $addcslashes_code . '
		';
			}
		
			$code .= '
		' . $parameters_code . '
		
		if (!empty($status)) {
			' . str_replace("\n", "\n\t", $func_code) . '
		}';
		}
		else {
			$code .= '
		' . $func_code;
		}
		
		$code .= '
		
		return $result;
	}
	';
	}
	
	return $code;
}

function getSQLStatementsMainTableName($rels, $data_access_obj, $db_broker, $db_driver) {
	if (is_array($rels)) {
		$rel_types = array("insert", "update", "delete", "select");
		
		foreach ($rel_types as $rel_type)
			if (!empty($rels[$rel_type]) && is_array($rels[$rel_type]))
				foreach ($rels[$rel_type] as $rel_id => $rel_data) {
					$table_name = WorkFlowDataAccessHandler::getSQLStatementTable($rel_data, $data_access_obj, $db_broker, $db_driver);
					
					if ($table_name)
						return $table_name;
				}
	}
	
	return null;
}

function prepareRelationshipCode(&$rel_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, &$tables_props, $hbn_obj_data, &$parameters) {
	//echo "<pre>";print_r($rel_data);echo "</pre>";
	//echo "<pre>";print_r($rels);echo "</pre>";
	//echo "<pre>";print_r($hbn_obj_data);echo "</pre>";
	
	WorkFlowDataAccessHandler::prepareRelationshipParameters($rel_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $hbn_obj_data, $parameters);
	
	$code = prepareParametersCode($rel_data, $rels, $parameters);
	//echo "<pre>$code!</pre>";die();
	
	return $code;
}

function prepareSQLStatementCode($obj_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, &$tables_props, $reserved_sql_keywords, &$parameters) {
	//echo "<pre>";print_r($obj_data);echo "</pre>";
	//echo "<pre>";print_r($rels);echo "</pre>";
	
	WorkFlowDataAccessHandler::prepareSQLStatementParameters($obj_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $parameters);
	
	$code = prepareParametersCode($obj_data, $rels, $parameters);
	//echo "<pre>$code!</pre>";die();
	
	return $code;
}

function prepareSelectAllSQLParameters($obj_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, &$tables_props, $reserved_sql_keywords, &$parameters, $table_name) {
	if ($table_name) {
		//get parameters from insert query if exists
		if (!empty($rels["insert"]["insert_" . $table_name . "_with_ai_pk"])) {
			prepareSQLStatementCode($rels["insert"]["insert_" . $table_name . "_with_ai_pk"], $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $parameters);
		}
		else if (!empty($rels["insert"]["insert_" . $table_name])) {
			prepareSQLStatementCode($rels["insert"]["insert_" . $table_name], $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $parameters);
		}
		//create dummy sql to get the parameters
		else {
			$tn = strtolower($table_name);
			$attrs = isset($tables_props[$tn]) ? $tables_props[$tn] : null;
			//echo "$table_name";print_r($attrs);die();
			
			if (!$attrs)
				$attrs = $data_access_obj->getFunction("listTableFields", $table_name, array("db_broker" => $db_broker, "db_driver" => $db_driver));
			
			if ($attrs) {
				$sql = "select * from $table_name where 1=1";
				
				foreach ($attrs as $attr_name => $attr)
					if (isset($attr["type"]) && ObjTypeHandler::isDBTypeNumeric($attr["type"]))
						$sql .= " and $attr_name=#$attr_name#";
					else
						$sql .= " and $attr_name='#$attr_name#'";
				
				$obj_data["value"] = $sql;
				
				prepareSQLStatementCode($obj_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $reserved_sql_keywords, $parameters);
			}
		}
	}
}

//remove all parameters that are already in the OUTPUT_NAME of map entries, but where the OUTPUT_NAME != INPUT_NAME. The parameters that have the same input and output name should NOT be removed.
//This items should be removed because we don't want to do the validation twice, this is, one in the business logic layer and another in the data access layer, so we need to remove the duplicated entries from the $parameters array.
function removeMapEntriesFromParameters($obj_data, $rels, &$parameters) {
	if (empty($obj_data["@"]["parameter_class"])) {
		$parameter_map = isset($obj_data["@"]["parameter_map"]) ? $obj_data["@"]["parameter_map"] : null;
		$map_entries = $parameter_map && isset($rels["parameter_map"][$parameter_map]["parameter"]) ? $rels["parameter_map"][$parameter_map]["parameter"] : null;
		//echo "<pre>";print_r($map_entries);echo "</pre>";
	
		if ($map_entries) {
			$t = count($map_entries);
			
			$inputs = array();
			for ($i = 0; $i < $t; $i++) 
				$inputs[] = isset($map_entries[$i]["input_name"]) ? $map_entries[$i]["input_name"] : null;
			
			for ($i = 0; $i < $t; $i++) {
				$entry = $map_entries[$i];
				$on = isset($entry["output_name"]) ? $entry["output_name"] : null;
				
				if ($on && isset($parameters[$on]) && !in_array($on, $inputs) && (!empty($entry["input_type"]) || !empty($entry["output_type"])))
					unset($parameters[$on]);
			}
		}
	}
}

function prepareParametersCode($obj_data, $rels, $parameters) {
	removeMapEntriesFromParameters($obj_data, $rels, $parameters);
	//echo "<pre>";print_r($parameters);echo "</pre>";
	
	if ($parameters) {
		$code = '$status = true;';
		
		$types = array();
		foreach ($parameters as $attr_name => $attr) {
			$name = !empty($attr["name"]) ? $attr["name"] : $attr_name;
			$type = isset($attr["type"]) ? $attr["type"] : null;
			$mandatory = isset($attr["mandatory"]) ? $attr["mandatory"] : null;
			
			if ($type) {
				if (!empty($types[$type])) {
					$rand = $types[$type];
				}
				else {
					$rand = rand(0, 1000);
					$types[$type] = $rand;
					
					$t = ObjTypeHandler::convertSimpleTypeIntoCompositeType($type);
					$code .= '
		
		$obj_' . $rand . ' = $status ? ObjectHandler::createInstance("' . $t . '") : null;
		$status = $status && ObjectHandler::checkIfObjType($obj_' . $rand . ');';
				}
				
				$code .= '
		$status = $status && ( ';
				$code .= $mandatory ? 'isset($data["' . $name . '"]) && ' : 'empty($data["' . $name . '"]) || ';
				$code .= '$obj_' . $rand . '->setInstance($data["' . $name . '"]) );';
			}
			else if ($mandatory) {
				$code .= '
		$status = $status && isset($data["' . $name . '"]);';
			}
		}
	
		return $code;
	}
	
	return "";
}

function getFunctionName($rel_id) {
	return lcfirst( WorkFlowDataAccessHandler::getClassName($rel_id) );
}

function getModuleId($file) {
	$file = str_replace("//", "/", $file);
	$file = substr($file, 0, 1) == "/" ? substr($file, 1) : $file;
	
	$pos = strrpos($file, "/");
	$pos = $pos !== false ? $pos : 0;
	
	$module_id = substr($file, 0, $pos);
	//$module_id = str_replace("/", ".", $module_id);  //2021-01-17 JP: Or it could be this code. It doesn't really matter. Even if there are folders with "." in the names, the system detects it. The module_id with "/" is faster before cache happens, but after the first call for this module, it doesn't really matter anymore bc the module_path is cached with the correspondent module_id.
	
	return $module_id;
}

function getTableAutoIncrementedPrimaryKeys($table_attrs) {
	$pks = array();
	
	if ($table_attrs)
		foreach ($table_attrs as $att_name => $att)
			if (!empty($att["primary_key"]) && WorkFlowDataAccessHandler::isAutoIncrementedAttribute($att))
				$pks[$att_name] = $att;
	
	return $pks;
}

function checkTypeOfExistentPrimaryKeys($parameters, &$auto_increment_pk_name) {
	$pks_type = false;
	$auto_increment_pk_name = null;
	//echo "<pre>";print_r($parameters);die();
	
	if (is_array($parameters))
		foreach ($parameters as $name => $parameter)
			if (!empty($parameter["primary_key"])) {
				if (WorkFlowDataAccessHandler::isAutoIncrementedAttribute($parameter)) {
					$pks_type = 2;
					$auto_increment_pk_name = !empty($parameter["name"]) ? $parameter["name"] : $name;
				}
				else
					$pks_type = 1;
			}
	
	return $pks_type;
}

function isCountFunctionName($name) {
	$keywords = array("count", "total");
	
	$t = count($keywords);
	for ($i = 0; $i < $t; $i++) {
		$keyword = $keywords[$i];
		$pos = stripos($name, "count");
		
		if ($pos !== false) {
			$next_char = substr($name, $pos + strlen($keyword), 1);
			if (empty($next_char) || $next_char == strtoupper($next_char))
				return true;
		}
	}
	return false;
}

function getDBDriverOptionsCode($db_driver, $include_db_driver) {
	$code = '';
	
	if ($db_driver && $include_db_driver)
		$code = '$options["db_driver"] = "' . $db_driver . '";';
	
	return $code;
}
?>
