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

class CodeResultGuesser {
	
	private $Layer;
	private $UserAuthenticationHandler;
	private $user_global_variables_file_path;
	private $user_beans_folder_path;
	private $project_url_prefix;
	private $db_driver;
	
	private $layer_brokers_settings;
	private $DBDriver;
	
	public function __construct($Layer, $UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $project_url_prefix, $db_driver) {
		$this->Layer = $Layer;
		$this->UserAuthenticationHandler = $UserAuthenticationHandler;
		$this->user_global_variables_file_path = $user_global_variables_file_path;
		$this->user_beans_folder_path = $user_beans_folder_path;
		$this->project_url_prefix = $project_url_prefix;
		$this->db_driver = $db_driver;
		
		$this->layer_brokers_settings = WorkFlowBeansFileHandler::getLayerBrokersSettings($user_global_variables_file_path, $user_beans_folder_path, $Layer->getBrokers(), '');
		
		//prepare DBDriver obj
		$db_driver_props = WorkFlowBeansFileHandler::getLayerDBDriverProps($user_global_variables_file_path, $user_beans_folder_path, $Layer, $db_driver);
		
		if ($db_driver_props) {
			$DBDriverWorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $db_driver_props[1], $user_global_variables_file_path);
			$this->DBDriver = $DBDriverWorkFlowBeansFileHandler->getBeanObject($db_driver_props[2]);
		}
	}
	
	public function getCodeResultAttributes($code) {
		$get_query_result_properties_url = $this->project_url_prefix . "phpframework/dataaccess/get_query_result_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&db_driver=" . $this->db_driver . "&module_id=#module_id#&query_type=#query_type#&query=#query#&rel_name=#rel_name#&obj=#obj#&relationship_type=#relationship_type#";
		$get_business_logic_result_properties_url = $this->project_url_prefix . "phpframework/businesslogic/get_business_logic_result_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&module_id=#module_id#&service=#service#&db_driver=" . $this->db_driver;
		
		$method_names = array("callBusinessLogic", "callSelect", "getQuery", "getData", "getSQL", "findObjects", "findRelationshipObjects", "findById", "find", "findRelationships", "findRelationship");
		
		//remove php comments from $contents, bc if the code was generated from the workflow, it will create comments that will mess up with this method.
		$php_code = "<?php\n$code\n?>";
		$methods = CMSFileHandler::getContentsMethodParams($php_code, $method_names);
		$props = null;
		
		if ($methods)
			foreach ($methods as $method) {
				if (empty($method["static"])) {
					$method_name = isset($method["method"]) ? $method["method"] : null;
					
					switch($method_name) {
						case "callBusinessLogic": //Business logic method
							$module_id = null;
							$service_id = null;
							
							if (isset($method["params"][0]["type"])) {
								if ($method["params"][0]["type"] == "variable" && isset($method["params"][0]["referenced_type"]) && $method["params"][0]["referenced_type"] == "string")
									$module_id = $method["params"][0]["referenced_value"];
								else if ($method["params"][0]["type"] == "string")
									$module_id = $method["params"][0]["value"];
							}
							
							if (isset($method["params"][1]["type"])) {
								if ($method["params"][1]["type"] == "variable" && isset($method["params"][1]["referenced_type"]) && $method["params"][1]["referenced_type"] == "string")
									$service_id = $method["params"][1]["referenced_value"];
								else if ($method["params"][1]["type"] == "string")
									$service_id = $method["params"][1]["value"];
							}
							
							if ($module_id && $service_id) {
								$broker_props = $this->getBrokerProps(isset($method["class_obj"]) ? $method["class_obj"] : null);
								
								if (!$broker_props && !empty($this->layer_brokers_settings["business_logic_brokers"]))
									$broker_props = $this->layer_brokers_settings["business_logic_brokers"][0];
								
								if ($broker_props) {
									$url = $get_business_logic_result_properties_url;
									$url = str_replace("#bean_name#", $broker_props[2], $url);
									$url = str_replace("#bean_file_name#", $broker_props[1], $url);
									$url = str_replace("#module_id#", $module_id, $url);
									$url = str_replace("#service#", $service_id, $url);
									
									//make the request to get business logic service attributes
									$props = $this->UserAuthenticationHandler->getURLContent($url);
									$props = $props ? json_decode($props, true) : null;
								}
							}
							
							break;
						case "callSelect": //Data Access method (Ibatis or Hibernate)
							$module_id = null;
							$rule_id = null;
							
							if (isset($method["params"][0]["type"])) {
								if ($method["params"][0]["type"] == "variable" && isset($method["params"][0]["referenced_type"]) && $method["params"][0]["referenced_type"] == "string")
									$module_id = $method["params"][0]["referenced_value"];
								else if ($method["params"][0]["type"] == "string")
									$module_id = $method["params"][0]["value"];
							}
							
							if (isset($method["params"][1]["type"])) {
								if ($method["params"][1]["type"] == "variable" && isset($method["params"][1]["referenced_type"]) && $method["params"][1]["referenced_type"] == "string")
									$rule_id = $method["params"][1]["referenced_value"];
								else if ($method["params"][1]["type"] == "string")
									$rule_id = $method["params"][1]["value"];
							}
							
							$obj_props = $this->getHibernateObjProps($php_code, isset($method["class_obj"]) ? $method["class_obj"] : null); //get the obj id based in $method["class_obj"]
							
							if ($module_id && ($rule_id || $obj_props)) {
								$broker_props = $this->getBrokerProps(isset($method["class_obj"]) ? $method["class_obj"] : null);
								
								if (!$broker_props && !empty($this->layer_brokers_settings["data_access_brokers"]))
									$broker_props = $this->layer_brokers_settings["data_access_brokers"][0];
								
								if ($broker_props) {
									$url = $get_query_result_properties_url;
									$url = str_replace("#bean_name#", $broker_props[2], $url);
									$url = str_replace("#bean_file_name#", $broker_props[1], $url);
									$url = str_replace("#query_type#", "select", $url);
									$url = str_replace("#rel_name#", "", $url);
									$url = str_replace("#relationship_type#", "queries", $url);
									
									if ($obj_props) {
										$hbn_url = $url;
										$hbn_url = str_replace("#module_id#", isset($obj_props["module_id"]) ? $obj_props["module_id"] : null, $hbn_url);
										$hbn_url = str_replace("#query#", $module_id, $hbn_url);
										$hbn_url = str_replace("#obj#", isset($obj_props["obj_id"]) ? $obj_props["obj_id"] : null, $hbn_url);
										
										//make the request to get hbn rule attributes
										$props = $this->UserAuthenticationHandler->getURLContent($hbn_url);
										$props = $props ? json_decode($props, true) : null;
									}
									
									//make the request to get ibatis rule attributes
									if (!$props && $rule_id) {
										$url = str_replace("#module_id#", $module_id, $url);
										$url = str_replace("#query#", $rule_id, $url);
										$url = str_replace("#obj#", "", $url);
										
										$props = $this->UserAuthenticationHandler->getURLContent($url);
										$props = $props ? json_decode($props, true) : null;
									}
								}
							}
							break;
						case "getData": //DB method
						case "getSQL": //DB method
							$sql = null;
							
							if (isset($method["params"][0]["type"])) {
								if ($method["params"][0]["type"] == "variable" && isset($method["params"][0]["referenced_type"]) && $method["params"][0]["referenced_type"] == "string")
									$sql = $method["params"][0]["referenced_value"];
								else if ($method["params"][0]["type"] == "string")
									$sql = $method["params"][0]["value"];
							}
							
							if ($sql) {
								//parse sql and get table name
								$sql_data = DB::convertDefaultSQLToObject($sql);
								
								//get attributes from sql
								if (isset($sql_data["type"]) && $sql_data["type"] == "select" && !empty($sql_data["table"])) {
									$attributes = array();
									
									if (empty($sql_data["attributes"]) || count($sql_data["attributes"]) == 0 || (isset($sql_data["attributes"][0]["column"]) && $sql_data["attributes"][0]["column"] == "*")) {
										//get all db table attributes
										if ($this->DBDriver) {
											$db_attributes = $this->DBDriver->listTableFields($sql_data["table"]);
											
											if ($db_attributes)
												foreach ($db_attributes as $attr_name => $attr)
													$attributes[] = array(
														"column" => $attr_name
													);
										}
									}
									else {
										foreach ($sql_data["attributes"] as $attr) {
											if (!empty($attr["name"]))
												$attributes[] = array(
													"column" => $attr["name"]
												);
											else if (!empty($attr["column"]))
												$attributes[] = array(
													"column" => $attr["column"]
												);
										}
									}
									
									//check if is_multiple
									$start = isset($sql_data["start"]) && is_numeric($sql_data["start"]) ? $sql_data["start"] : 0;
									$is_multiple = empty($sql_data["limit"]) || $sql_data["limit"] - $start > 1;
									
									//prepare props
									$props = array(
										"attributes" => $attributes,
										"is_multiple" => $is_multiple,
									);
								}
							}
							break;
						case "findObjects": //DB method
							$table_name = null;
							
							if (isset($method["params"][0]["type"])) {
								if ($method["params"][0]["type"] == "variable" && isset($method["params"][0]["referenced_type"]) && $method["params"][0]["referenced_type"] == "string")
									$table_name = $method["params"][0]["referenced_value"];
								else if ($method["params"][0]["type"] == "string")
									$table_name = $method["params"][0]["value"];
							}
							
							if ($table_name && $this->DBDriver) {
								//get all db table attributes
								$db_attributes = $this->DBDriver->listTableFields($table_name);
								$attributes = array();
								
								if ($db_attributes)
									foreach ($db_attributes as $attr_name => $attr)
										$attributes[] = array(
											"column" => $attr_name
										);
								
								$props = array(
									"attributes" => $attributes,
									"is_multiple" => true,
								);
							}
							break;
						case "findRelationshipObjects": //DB method
							//TODO
							break;
						case "findById": //Hibernate method
						case "find": //Hibernate method
						case "findRelationships": //Hibernate method
						case "findRelationship": //Hibernate method
							$obj_props = $this->getHibernateObjProps($php_code, isset($method["class_obj"]) ? $method["class_obj"] : null); //get the obj id based in $method["class_obj"]
							
							if ($obj_props) {
								$broker_props = $this->getBrokerProps(isset($obj_props["class_obj"]) ? $obj_props["class_obj"] : null);
								
								if (!$broker_props && !empty($this->layer_brokers_settings["hibernate_brokers"]))
									$broker_props = $this->layer_brokers_settings["hibernate_brokers"][0];
								
								if ($broker_props) {
									$rel_name = "";
									
									if ($method_name == "findRelationship" && isset($method["params"][0]["type"])) {
										if ($method["params"][0]["type"] == "variable" && isset($method["params"][0]["referenced_type"]) && $method["params"][0]["referenced_type"] == "string")
											$rel_name = $method["params"][0]["referenced_value"];
										else if ($method["params"][0]["type"] == "string")
											$rel_name = $method["params"][0]["value"];
									}
									
									$url = $get_query_result_properties_url;
									$url = str_replace("#bean_name#", $broker_props[2], $url);
									$url = str_replace("#bean_file_name#", $broker_props[1], $url);
									$url = str_replace("#module_id#", isset($obj_props["module_id"]) ? $obj_props["module_id"] : null, $url);
									$url = str_replace("#query_type#", "", $url);
									$url = str_replace("#query#", $method_name, $url);
									$url = str_replace("#rel_name#", $rel_name, $url);
									$url = str_replace("#obj#", isset($obj_props["obj_id"]) ? $obj_props["obj_id"] : null, $url);
									$url = str_replace("#relationship_type#", "native", $url);
									
									//make the request to get hbn rule attributes
									$props = $this->UserAuthenticationHandler->getURLContent($url);
									$props = $props ? json_decode($props, true) : null;
								}
							}
							break;
					}
					
					if ($props)
						return $props;
				}
			}
		
		return null;
	}
	
	private function getHibernateObjProps($php_code, $var_to_compare) {
		$methods = CMSFileHandler::getContentsMethodParams($php_code, "callObject");
		$props = null;
		//print_r($methods);die();
		
		if ($methods)
			foreach ($methods as $method) {
				if (empty($method["static"])) {
					$module_id = null;
					$obj_id = null;
					
					if (isset($method["params"][0]["type"])) {
						if ($method["params"][0]["type"] == "variable" && isset($method["params"][0]["referenced_type"]) && $method["params"][0]["referenced_type"] == "string")
							$module_id = $method["params"][0]["referenced_value"];
						else if ($method["params"][0]["type"] == "string")
							$module_id = $method["params"][0]["value"];
					}
					
					if (isset($method["params"][1]["type"])) {
						if ($method["params"][1]["type"] == "variable" && isset($method["params"][1]["referenced_type"]) && $method["params"][1]["referenced_type"] == "string")
							$obj_id = $method["params"][1]["referenced_value"];
						else if ($method["params"][1]["type"] == "string")
							$obj_id = $method["params"][1]["value"];
					}
					
					if ($module_id && $obj_id) {
						$props = array(
							"class_obj" => isset($method["class_obj"]) ? $method["class_obj"] : null,
							"module_id" => $module_id,
							"obj_id" => $obj_id
						);
						
						if (isset($method["match"]) && preg_match("/" . preg_quote($var_to_compare, "/") . "\s*=\s*" . preg_quote($method["match"], "/") . "/", $php_code))
							break;
					}
				}
			}
		
		return $props;
	}
	
	private function getBrokerProps($class_obj) {
		if ($this->layer_brokers_settings) {
			preg_match('/\s*\->\s*getBroker\s*\(\s*"([^"]+)"\s*\)/', $class_obj, $matches, PREG_OFFSET_CAPTURE);
			
			if (!$matches)
				preg_match("/\s*\->\s*getBroker\s*\(\s*'([^']+)'\s*\)/", $class_obj, $matches, PREG_OFFSET_CAPTURE);
			
			$broker_name = $matches && !empty($matches[1]) ? $matches[1][0] : null;
			
			if ($broker_name) { //get bean and bena_file for broker name
				$keys = array("business_logic_brokers", "data_access_brokers", "db_brokers");
				
				foreach ($keys as $key) {
					$brokers = isset($this->layer_brokers_settings[$key]) ? $this->layer_brokers_settings[$key] : null;
					
					if ($brokers)
						foreach ($brokers as $broker_props)
							if (isset($broker_props[0]) && $broker_props[0] == $broker_name)
								return $broker_props;
				}
			}
		}
		
		return null;
	}
}
?>
