<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.util.HashTagParameter");

class SequentialLogicalActivity {
	
	private $xss_sanitize_lib_included = false;
	private $EVC;
	
	public function setEVC($EVC) {
		$this->EVC = $EVC;
	}
	
	public function getEVC() {
		return $this->EVC;
	}
	
	public function execute($actions, &$results = null) {
		$this->initResults($results);
		
		//execute and populate results
		$html = $this->executeActions($actions, $results);
		//echo "<pre>";print_r(array_keys($results));die();
		
		//remove default vars
		unset($results["EVC"]);
		unset($results["_GET"]);
		unset($results["_POST"]);
		unset($results["_REQUEST"]);
		unset($results["_FILES"]);
		unset($results["_COOKIE"]);
		unset($results["_ENV"]);
		unset($results["_SERVER"]);
		unset($results["_SESSION"]);
		unset($results["GLOBALS"]);
		
		return $html;
	}
	
	public function existActionsValidCondition($actions, &$results) {
		$this->initResults($results);
		
		if (is_array($actions)) 
			foreach ($actions as $idx => $item_settings) {
				$condition_type = isset($item_settings["condition_type"]) ? $item_settings["condition_type"] : null;
				$condition_value = isset($item_settings["condition_value"]) ? $item_settings["condition_value"] : null;
				$status = $condition_type == "execute_always" || $this->executeCondition($condition_type, $condition_value, $results);
				
				if ($status) {
					/*if ($condition_type == "execute_always") {
						$action_type = isset($item_settings["action_type"]) ? $item_settings["action_type"] : null;
						
						if ($action_type == "group" || $action_type == "loop") {
							$action_actions = isset($item_settings["action_value"]["actions"]) ? $item_settings["action_value"]["actions"] : null;
							if ($this->existActionsValidCondition($action_actions, $results))
								return true;
						}
						else
							return true;
					}
					else*/
						return true;
				}
			}
		
		return false;
	}
	
	private function initResults(&$results = null) {
		$EVC = $this->EVC;
		$common_project_name = $EVC->getCommonProjectName();
		
		include_once get_lib("org.phpframework.util.web.html.HtmlFormHandler");
		
		//include some modules if installed. @ is in case the modules are not installed
		@include_once $EVC->getModulePath("common/CommonModuleUI", $common_project_name);
		@include_once $EVC->getModulePath("object/ObjectUtil", $common_project_name);
		@include_once $EVC->getModulePath("user/UserUtil", $common_project_name);
		
		//add default vars
		$globals = array(
			"EVC" => isset($EVC) ? $EVC : null,
			"_GET" => isset($_GET) ? $_GET : null,
			"_POST" => isset($_POST) ? $_POST : null,
			"_REQUEST" => isset($_REQUEST) ? $_REQUEST : null,
			"_FILES" => isset($_FILES) ? $_FILES : null,
			"_COOKIE" => isset($_COOKIE) ? $_COOKIE : null,
			"_ENV" => isset($_ENV) ? $_ENV : null,
			"_SERVER" => isset($_SERVER) ? $_SERVER : null,
			"_SESSION" => isset($_SESSION) ? $_SESSION : null,
			"GLOBALS" => isset($GLOBALS) ? $GLOBALS : null,
		);
		$results = is_array($results) ? array_merge($globals, $results) : $globals; //results may overwrite the globals
		
		return $results;
	}
	
	private function executeActions($actions, &$results, &$stop = false, &$die = false) {
		$html = '';
		
		if (is_array($actions)) 
			foreach ($actions as $idx => $item_settings) 
				if (!$stop) {
					$condition_type = isset($item_settings["condition_type"]) ? $item_settings["condition_type"] : null;
					$condition_value = isset($item_settings["condition_value"]) ? $item_settings["condition_value"] : null;
					$status = $this->executeCondition($condition_type, $condition_value, $results);
					
					if ($status) {
						$action_type = isset($item_settings["action_type"]) ? strtolower($item_settings["action_type"]) : "";
						$action_value = isset($item_settings["action_value"]) ? $item_settings["action_value"] : null;
						
						$assignment_operator = null;
						$result = $this->executeAction($action_type, $action_value, $results, $stop, $die, $assignment_operator);
						
						//set result inside of results
						$result_var_name = isset($item_settings["result_var_name"]) ? trim($item_settings["result_var_name"]) : "";
						$results_var_name = $this->prepareResultVarName($result_var_name, $results);
						
						if ($result_var_name) {
							//parse result_var_name replacing the variables inside of the result_var_name, if apply. Note that this is very usefull to create associative arrays based in records list, through the LOOP action, where we set the result_var_name as: xxx[#item[id]#]. In this case the string '#item[id]#', will be replaced by the correspondent value and the result_var_name will be xxx[1], xxx[2], xxx[3], etc...
							if (substr($result_var_name, -2) == "[]") { //this allows the user to configure multiple groups where the output can be INSIDE OF an array variable. Example: "$result_var_name = 'arr[]';". This is very usefull to concatenate multiple outputs by implode this array variable later on...
								$result_var_name = substr($result_var_name, 0, -2);
								
								eval('$is_array = isset($' . $results_var_name . '[$result_var_name]) && is_array($' . $results_var_name . '[$result_var_name]);');
								if (!$is_array)
									eval('$' . $results_var_name . '[$result_var_name] = array();');
								
								eval('$' . $results_var_name . '[$result_var_name ][] = $result;');
							}
							else if (strpos($result_var_name, "[") !== false || strpos($result_var_name, "]") !== false) { //if result_var_name == "[0]name" or "[1][name]", set $results[0]["name"] = $result
								preg_match_all("/([^\[\]]+)/u", trim($result_var_name), $sub_matches, PREG_PATTERN_ORDER); //'/u' means converts to unicode.
								$sub_matches = isset($sub_matches[1]) ? $sub_matches[1] : null;
								$keys = array();
								
								if ($sub_matches)
									for ($i = 0, $t = count($sub_matches); $i < $t; $i++) {
										$sub_match = trim($sub_matches[$i]);
										
										if (strlen($sub_match)) { //ignores empty keys
											if (strpos($sub_match, "'") === false && strpos($sub_match, '"') === false) { //avoid php errors because one of the keys is a RESERVED PHP CODE string.
												//$sub_match_type = PHPUICodeExpressionHandler::getValueType($sub_match, array("non_set_type" => "string", "empty_string_type" => "string"));
												//$sub_matches[$i] = PHPUICodeExpressionHandler::getArgumentCode($sub_match, $sub_match_type);
												$sub_matches[$i] = '"' . $sub_match . '"';
											}
											
											$keys[] = $sub_matches[$i];
										}
									}
								
								if ($keys) {
									if (!$assignment_operator)
										$assignment_operator = "=";
									
									eval('$' . $results_var_name . '[' . implode('][', $keys) . '] ' . $assignment_operator . ' $result;');
								}
								else {
									eval('$is_set = isset($' . $results_var_name . '[$result_var_name]);');
									
									if ($is_set) {
										if ($assignment_operator == "+=")
											eval('$result = $' . $results_var_name . '[$result_var_name] + $result;');
										else if ($assignment_operator == "-=")
											eval('$result = $' . $results_var_name . '[$result_var_name] - $result;');
									}
									
									eval('$' . $results_var_name . '[$result_var_name] = $result;');
								}
							}
							else {
								eval('$is_set = isset($' . $results_var_name . '[$result_var_name]);');
								
								if ($is_set) {
									if ($assignment_operator == "+=")
										eval('$result = $' . $results_var_name . '[$result_var_name] + $result;');
									else if ($assignment_operator == "-=")
										eval('$result = $' . $results_var_name . '[$result_var_name] - $result;');
								}
								
								eval('$' . $results_var_name . '[$result_var_name] = $result;');
							}
							
							//if ($results_var_name=="_GET")error_log("\nresult from $results_var_name for \${$results_var_name}[$result_var_name] (it should be empty):".print_r(${$results_var_name}[ $result_var_name ], 1)."\ndirectly from GET:".print_r($_GET[ $result_var_name ], 1), 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
							//else error_log("\nresult from $results_var_name for \${$results_var_name}[$result_var_name]:".print_r(${$results_var_name}[ $result_var_name ], 1)."\ndirectly from results:".print_r($results[ $result_var_name ], 1), 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
						}
						else
							$html .= $result; //only add to html if not result_var_name
						
						if ($die) {
							echo $html;
							die();
						}
						else if ($stop)
							break;
					}
				}
		
		return $html;
	}
	
	private function prepareResultVarName(&$result_var_name, $results) {
		if ($result_var_name) {
			if (substr($result_var_name, 0, 1) == '$')
				$result_var_name = substr($result_var_name, 1);
			else if (substr($result_var_name, 0, 2) == '@$')
				$result_var_name = substr($result_var_name, 2);
		}
		
		$result_var_name = $this->getParsedValueFromData($result_var_name, $results);
		$result_var_name = trim($result_var_name);
		
		if ($result_var_name && preg_match(HashTagParameter::HTML_SUPER_GLOBAL_VAR_NAME_FULL_REGEX, $result_var_name, $matches, PREG_OFFSET_CAPTURE) && $matches) {
			$global_var_name = $matches[0][0];
			
			if ($global_var_name) {
				$result_var_name = substr($result_var_name, strlen($global_var_name)); //$global_var_name already includes "[" (if apply)
				
				if (substr($global_var_name, -1) == "[") {
					$global_var_name = substr($global_var_name, 0, -1);
					
					if (substr($result_var_name, -1) == "]")
						$result_var_name = substr($result_var_name, 0, -1);
				}
			}
		}
		//error_log("\nglobal_var_name:$global_var_name\nresult_var_name:$result_var_name", 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
		
		return !empty($global_var_name) ? $global_var_name : "results";
	}
	
	private function executeCondition($condition_type, $condition_value, &$results) {
		$status = true;
		
		if ($condition_type) {
			$condition_type = strtolower($condition_type);
			
			switch($condition_type) {
				case "execute_if_var": //Only execute if variable exists
				case "execute_if_not_var": //Only execute if variable doesn't exists
					$status = false;
					$var = trim($condition_value);
					
					if (!empty($var)) {
						$var = substr($var, 0, 1) == '$' || substr($var, 0, 2) == '@$' || substr($var, 0, 2) == '\\$' || substr($var, 0, 3) == '@\\$' ? $var : '$' . $var;
						
						$code = '<?= !empty(' . $var . ') ? 1 : 0 ?>'; //empty here is very important, bc some var is '_POST', which will give a PHP Warning: Array to string conversion in...
						$result = \PHPScriptHandler::parseContent($code, $results);
						$status = !empty($result);
					}
					
					if (strpos($condition_type, "_not_") !== false)
						$status = !$status;
					
					break;
				
				case "execute_if_post_button": //Only execute if submit button was clicked via POST
					$button_name = trim($condition_value);
					$button_name = substr($button_name, 0, 1) == '$' ? substr($button_name, 1) : (substr($button_name, 0, 2) == '@$' ? substr($button_name, 2) : $button_name);
					
					$status = $button_name ? !empty($_POST[$button_name]) : false;
					break;
				case "execute_if_not_post_button": //Only execute if submit button was not clicked via POST
					$button_name = trim($condition_value);
					$button_name = substr($button_name, 0, 1) == '$' ? substr($button_name, 1) : (substr($button_name, 0, 2) == '@$' ? substr($button_name, 2) : $button_name);
					
					$status = $button_name ? empty($_POST[$button_name]) : true;
					break;
				
				case "execute_if_get_button": //Only execute if submit button was clicked via GET
					$button_name = trim($condition_value);
					$button_name = substr($button_name, 0, 1) == '$' ? substr($button_name, 1) : (substr($button_name, 0, 2) == '@$' ? substr($button_name, 2) : $button_name);
					
					$status = $button_name ? !empty($_GET[$button_name]) : false;
					break;
				case "execute_if_not_get_button": //Only execute if submit button was not clicked via GET
					$button_name = trim($condition_value);
					$button_name = substr($button_name, 0, 1) == '$' ? substr($button_name, 1) : (substr($button_name, 0, 2) == '@$' ? substr($button_name, 2) : $button_name);
					
					$status = $button_name ? empty($_GET[$button_name]) : true;
					break;
				
				case "execute_if_post_resource": //Only execute if resource is equal to via POST
					$resource_name = trim($condition_value);
					$resource_name = substr($resource_name, 0, 1) == '$' ? substr($resource_name, 1) : (substr($resource_name, 0, 2) == '@$' ? substr($resource_name, 2) : $resource_name);
					$post_resource = isset($_POST["resource"]) ? $_POST["resource"] : null;
					
					$status = $post_resource == $resource_name;
					break;
				case "execute_if_not_post_resource": //Only execute resource is different than via POST
					$resource_name = trim($condition_value);
					$resource_name = substr($resource_name, 0, 1) == '$' ? substr($resource_name, 1) : (substr($resource_name, 0, 2) == '@$' ? substr($resource_name, 2) : $resource_name);
					$post_resource = isset($_POST["resource"]) ? $_POST["resource"] : null;
					
					$status = $post_resource != $resource_name;
					break;
				
				case "execute_if_get_resource": //Only execute if resource is equal to via GET
					$resource_name = trim($condition_value);
					$resource_name = substr($resource_name, 0, 1) == '$' ? substr($resource_name, 1) : (substr($resource_name, 0, 2) == '@$' ? substr($resource_name, 2) : $resource_name);
					$get_resource = isset($_GET["resource"]) ? $_GET["resource"] : null;
					
					$status = $get_resource == $resource_name;
					break;
				case "execute_if_not_get_resource": //Only execute resource is different than via GET
					$resource_name = trim($condition_value);
					$resource_name = substr($resource_name, 0, 1) == '$' ? substr($resource_name, 1) : (substr($resource_name, 0, 2) == '@$' ? substr($resource_name, 2) : $resource_name);
					$get_resource = isset($_GET["resource"]) ? $_GET["resource"] : null;
					
					$status = $get_resource != $resource_name;
					break;
				
				case "execute_if_previous_action": //Only execute if previous action executed correctly
					$status = $results ? !empty($results[count($results) - 1]) : false;
					break;
				case "execute_if_not_previous_action": //Only execute if previous action was not executed correctly
					$status = $results ? empty($results[count($results) - 1]) : true;
					break;
				
				case "execute_if_condition": //Only execute if condition is valid
				case "execute_if_not_condition": //Only execute if condition is invalid
				case "execute_if_code": //Only execute if code is valid
				case "execute_if_not_code": //Only execute if code is invalid
					$status = false;
					
					if (is_numeric($condition_value))
						$status = !empty($condition_value);
					else if ($condition_value === true)
						$status = true;
					else if ((is_array($condition_value) || is_object($condition_value)) && !empty($condition_value))
						$status = true;
					else if (!empty($condition_value)) {
						$code = '<?= !empty(' . $condition_value . '); ?>'; //empty here is very important, bc some conditions are ilke '\$_POST', which will give a PHP Warning: Array to string conversion in...
						$result = \PHPScriptHandler::parseContent($code, $results);
						$status = !empty($result);
					}
					
					if (strpos($condition_type, "_not_") !== false)
						$status = !$status;
					
					break;
			}
		}
		
		return $status;
	}

	private function executeAction($action_type, $action_value, &$results, &$stop = false, &$die = false, &$assignment_operator = false) {
		$result = null;
		
		if ($action_type) {
			$action_type = strtolower($action_type);
			$EVC = $this->getEVC();
			
			switch ($action_type) {
				case "html":
					if (is_array($action_value)) {
						@translateProjectFormSettings($EVC, $action_value);
						$action_value["CacheHandler"] = $EVC->getPresentationLayer()->getPHPFrameWork()->getObject("UserCacheHandler");
						
						if (isset($action_value["ptl"]["code"])) {
							if (isset($action_value["ptl"]["external_vars"]) && is_array($action_value["ptl"]["external_vars"]))
								$action_value["ptl"]["external_vars"] = array_merge($results, $action_value["ptl"]["external_vars"]);
							else
								$action_value["ptl"]["external_vars"] = $results;
						}
						
						$result = \HtmlFormHandler::createHtmlForm($action_value, $results);
					}
					else
						$result = $action_value;
					break;
				
				case "callbusinesslogic":
					/*"action_value" => array(
						"method_obj" => $EVC->getBroker("soa"),
						"module_id" => "module.article",
						"service_id" => "ArticleService.getArticle",
						"parameters" => array(
							"article_id" => $_GET['article_id']
						),
						"options" => array(
							"no_cache" => true,
							"db_driver" => "test"
						)
					)
					
					$EVC->getBroker("soa")->callBusinessLogic("module.article", "ArticleService.getArticle", array(
						"article_id" => $_GET['article_id']
					), array(
						"no_cache" => true,
						"db_driver" => "test"
					))*/
					
					$method_obj = isset($action_value["method_obj"]) ? $action_value["method_obj"] : null;
					$method_obj = $this->getParsedValueFromData($method_obj, $results);
					
					if ($method_obj && method_exists($method_obj, "callBusinessLogic")) {
						$module_id = isset($action_value["module_id"]) ? $action_value["module_id"] : null;
						$service_id = isset($action_value["service_id"]) ? $action_value["service_id"] : null;
						$parameters = isset($action_value["parameters"]) ? $action_value["parameters"] : null;
						$options = isset($action_value["options"]) ? $action_value["options"] : null;
						
						$module_id = $this->getParsedValueFromData($module_id, $results);
						$service_id = $this->getParsedValueFromData($service_id, $results);
						$parameters = $this->getParsedValueFromData($parameters, $results);
						$options = $this->getParsedValueFromData($options, $results);
						
						$result = $method_obj->callBusinessLogic($module_id, $service_id, $parameters, $options);
					}
					else
						launch_exception(new \Exception('$action_value["method_obj"] cannot be null and must contain callBusinessLogic method!'));
					
					break;
					
				case "callibatisquery":
					/*
					"action_value" => array(
						"method_obj" => $EVC->getBroker("iorm"),
						"module_id" => "condo",
						"service_id" => "insert_ag",
						"service_type" => "insert",
						"parameters" => array(
							"ag_id" => "",
							"condo_id" => "",
							"begin_date" => "",
							"end_date" => "",
							"solicitation" => "",
							"email_worker_id" => "",
							"closed" => "",
							"created_date" => "",
							"modified_date" => ""
						),
						"options" => "dfxcgbgbcfgcf"
					)
					
					$EVC->getBroker("iorm")->callInsert("condo", "insert_ag", array(
						"ag_id" => "",
						"condo_id" => "",
						"begin_date" => "",
						"end_date" => "",
						"solicitation" => "",
						"email_worker_id" => "",
						"closed" => "",
						"created_date" => "",
						"modified_date" => ""
					), array(
						"no_cache" => true
					))
					*/
					
					$exist_method_type = false;
					$service_type = isset($action_value["service_type"]) ? $action_value["service_type"] : null;
					
					switch($service_type) {
						case "insert":  $method_name = "callInsert"; $exist_method_type = true; break;
						case "update":  $method_name = "callUpdate"; $exist_method_type = true; break;
						case "delete":  $method_name = "callDelete"; $exist_method_type = true; break;
						case "select":  $method_name = "callSelect"; $exist_method_type = true; break;
						case "procedure":  $method_name = "callProcedure"; $exist_method_type = true; break;
						default: $method_name = "callQuery";
					}
					
					$method_obj = isset($action_value["method_obj"]) ? $action_value["method_obj"] : null;
					$method_obj = $this->getParsedValueFromData($method_obj, $results);
					
					if ($method_obj && method_exists($method_obj, $method_name)) {
						$module_id = isset($action_value["module_id"]) ? $action_value["module_id"] : null;
						$service_id = isset($action_value["service_id"]) ? $action_value["service_id"] : null;
						$service_type = isset($action_value["service_type"]) ? $action_value["service_type"] : null;
						$parameters = isset($action_value["parameters"]) ? $action_value["parameters"] : null;
						$options = isset($action_value["options"]) ? $action_value["options"] : null;
						
						$module_id = $this->getParsedValueFromData($module_id, $results);
						$service_id = $this->getParsedValueFromData($service_id, $results);
						$service_type = $this->getParsedValueFromData($service_type, $results);
						$parameters = $this->getParsedValueFromData($parameters, $results);
						$options = $this->getParsedValueFromData($options, $results);
						
						if ($exist_method_type)
							$result = $method_obj->$method_name($module_id, $service_id, $parameters, $options);
						else
							$result = $method_obj->$method_name($module_id, $service_type, $service_id, $parameters, $options);
					}
					else
						launch_exception(new \Exception('$action_value["method_obj"] cannot be null and must contain ' . $method_name . ' method!'));
					
					break;
					
				case "callhibernatemethod":
					/*
					"action_value" => array(
						"broker_method_obj_type" => "EVC->getBroker(\"horm\")", //"exists_hbn_var",
						"method_obj" => $EVC->getBroker("horm"),
						"module_id" => "test",
						"service_id" => "City",
						"options" => array(
							"no_cache" => false
						),
						"service_method" => "insert",
						"sma_query_type" => "insert",
						"sma_query_id" => "",
						"sma_data" => array(
							"state_id" => null,
							"name" => "",
							"created_date" => "",
							"modified_date" => ""
						),
						"sma_statuses" => null,
						"sma_ids" => ids,
						"sma_parent_ids" => "",
						"sma_sql" => "",
						"sma_options" => $asdasd
					)
					
					$EVC->getBroker("horm")->callObject("danielgarage", "Car")->insert(array(), null, array(
						"no_cache" => false
					))
					*/
					
					$method_obj = isset($action_value["method_obj"]) ? $action_value["method_obj"] : null;
					$method_obj = $this->getParsedValueFromData($method_obj, $results);
					
					if ($method_obj) {
						$broker_method_obj_type = isset($action_value["broker_method_obj_type"]) ? $action_value["broker_method_obj_type"] : null;
						
						if ($broker_method_obj_type != "exists_hbn_var") {
							if (method_exists($method_obj, "callObject")) {
								$module_id = isset($action_value["module_id"]) ? $action_value["module_id"] : null;
								$service_id = isset($action_value["service_id"]) ? $action_value["service_id"] : null;
								$options = isset($action_value["options"]) ? $action_value["options"] : null;
								
								$module_id = $this->getParsedValueFromData($module_id, $results);
								$service_id = $this->getParsedValueFromData($service_id, $results);
								$options = $this->getParsedValueFromData($options, $results);
						
								$method_obj = $method_obj->callObject($module_id, $service_id, $options);
							}
							else
								launch_exception(new \Exception('$action_value["method_obj"] must contain callObject method that returns a Hibernate Object!'));
						}
						
						$service_method = isset($action_value["service_method"]) ? $action_value["service_method"] : null;
						
						if (method_exists($method_obj, $service_method)) {
							$methods_args = array(
								"insert" => array("data", "ids", "options"),
								"insertAll" => array("data", "statuses", "ids", "options"),
								"update" => array("data", "options"),
								"updateAll" => array("data", "statuses", "options"),
								"insertOrUpdate" => array("data", "ids", "options"),
								"insertOrUpdateAll" => array("data", "statuses", "ids", "options"),
								"delete" => array("data", "options"),
								"deleteAll" => array("data", "statuses", "options"),
								"updatePrimaryKeys" => array("data", "options"),
								"findById" => array("data", "data", "options"),
								"find" => array("data", "options"),
								"count" => array("data", "options"),
								"findRelationships" => array("parent_ids", "options"),
								"findRelationship" => array("rel_name", "parent_ids", "options"),
								"countRelationships" => array("parent_ids", "options"),
								"countRelationship" => array("rel_name", "parent_ids", "options"),
								"callQuerySQL" => array("query_type", "query_id", "data", "options"),
								"callQuery" => array("query_type", "query_id", "data", "options"),
								"callInsertSQL" => array("query_id", "data", "options"),
								"callInsert" => array("query_id", "data", "options"),
								"callUpdateSQL" => array("query_id", "data", "options"),
								"callUpdate" => array("query_id", "data", "options"),
								"callDeleteSQL" => array("query_id", "data", "options"),
								"callDelete" => array("query_id", "data", "options"),
								"callSelectSQL" => array("query_id", "data", "options"),
								"callSelect" => array("query_id", "data", "options"),
								"callProcedureSQL" => array("query_id", "data", "options"),
								"callProcedure" => array("query_id", "data", "options"),
								"getFunction" => array("function_name", "data", "options"),
								"getData" => array("sql", "options"),
								"setData" => array("sql", "options"),
								"getInsertedId" => array("options"),
							);
							
							$method_args = isset($methods_args[$service_method]) ? $methods_args[$service_method] : null;
							$args = array();
							$ids = null;
							$var_ids_name = null;
							
							if ($method_args)
								foreach ($method_args as $arg) {
									$v = isset($action_value["sma_" . $arg]) ? $action_value["sma_" . $arg] : null;
									$v = $this->getParsedValueFromData($v, $results);
									
									if ($arg == "ids") { //sma_ids
										$var_ids_name = $v;
										$args[] = &$ids; //passing by reference to $v, so I can get this var later on...
									}
									else
										$args[] = $v;
								}
								
							$result = @call_user_func_array(array($method_obj, $service_method), $args); //Note that the @ is very important here bc in PHP 8 this gives an warning, this is: 'Warning: Array to string conversion in...'
							
							if ($var_ids_name) //setting the ids from the hibernate insert method and adding them to the $results, so we can use them in other actions...
								$results[$var_ids_name] = $ids;
						}
						else
							launch_exception(new \Exception('$action_value["method_obj"] must contain ' . $service_method . ' method!'));
					}
					else 
						launch_exception(new \Exception('$action_value["method_obj"] cannot be null!'));
					
					break;
					
				case "getquerydata":
				case "setquerydata":
					/*
					"action_value" => array(
						"method_obj" => $EVC->getBroker("iorm"),
						"sql" => "select *from ",
						"options" => null
					)
					
					$EVC->getBroker("iorm")->getData("select * from article")
					*/
					
					$method_obj = isset($action_value["method_obj"]) ? $action_value["method_obj"] : null;
					$method_obj = $this->getParsedValueFromData($method_obj, $results);
					
					if ($method_obj && method_exists($method_obj, "getData")) {
						$sql = isset($action_value["sql"]) ? $action_value["sql"] : null;
						$options = isset($action_value["options"]) ? $action_value["options"] : null;
						
						$sql = $this->getParsedValueFromData($sql, $results);
						$options = $this->getParsedValueFromData($options, $results);
						
						if ($action_type == "getquerydata")
							$result = $method_obj->getData($sql, $options); //Note that $options already contains (by default) the item "return_type" => "result", so it can return the DB data directly;
						else
							$result = $method_obj->setData($sql, $options);
					}
					else
						launch_exception(new \Exception('$action_value["method_obj"] cannot be null and must contain getData method!'));
					
					break;
					
				case "callfunction":
					/*
					"action_value" => array(
						"func_name" => "foo",
						"func_args" => array(
							"asd",
							$asd
						)
					)
					
					foo("asd", $asd)
					*/
					
					//include file if exists
					$this->includeActionFile($action_value, $results);
					
					//call function
					$func_name = isset($action_value["func_name"]) ? $action_value["func_name"] : null;
					$func_name = $this->getParsedValueFromData($func_name, $results);
					$func_args = !empty($action_value["func_args"]) ? $this->getParsedValueFromData($action_value["func_args"], $results) : array();
					
					if ($func_name && function_exists($func_name)) {
						$func_args = is_array($func_args) ? array_values($func_args) : $func_args;
						$result = @call_user_func_array($func_name, $func_args); //Note that the @ is very important here bc in PHP 8 this gives an warning, this is: 'Warning: Array to string conversion in...'
					}
					else
						launch_exception(new \Exception('$func_name "' . $func_name . '" is not a function!'));
					
					break;
					
				case "callobjectmethod":
					/*
					"action_value" => array(
						"method_obj" => $obj, //Obj
						"method_name" => "foo",
						"method_static" => 0, //1
						"method_args" => array(
							"as",
							"as"?1:0
						)
					)
					
					$obj->foo("as", "as"?1:0)
					Obj::foo("as", "as"?1:0)
					*/
					
					//include file if exists
					$this->includeActionFile($action_value, $results);
					
					//call method
					$method_static = isset($action_value["method_static"]) ? $action_value["method_static"] : null;
					$method_obj = isset($action_value["method_obj"]) ? $action_value["method_obj"] : null;
					$method_name = isset($action_value["method_name"]) ? $action_value["method_name"] : null;
					
					$method_static = $this->getParsedValueFromData($method_static, $results);
					$method_obj = $this->getParsedValueFromData($method_obj, $results);
					$method_name = $this->getParsedValueFromData($method_name, $results);
					$method_args = !empty($action_value["method_args"]) ? $this->getParsedValueFromData($action_value["method_args"], $results) : array();
					
					$method_args = is_array($method_args) ? array_values($method_args) : $method_args;
					
					if ($method_static) {
						if (method_exists("\\" . $method_obj, $method_name)) 
							$result = @call_user_func_array("\\$method_obj::$method_name", $method_args); //Note that the @ is very important here bc in PHP 8 this gives an warning, this is: 'Warning: Array to string conversion in...'
						else
							launch_exception(new \Exception("\\" . $method_obj . ' class must contain ' . $method_name . ' static method!'));
					}
					else if ($method_obj && method_exists($method_obj, $method_name))
						$result = @call_user_func_array(array($method_obj, $method_name), $method_args); //Note that the @ is very important here bc in PHP 8 this gives an warning, this is: 'Warning: Array to string conversion in...'
					else
						launch_exception(new \Exception('$action_value["method_obj"] cannot be null and must contain ' . $method_name . ' method!'));
					
					break;
				
				case "restconnector":
					if (is_array($action_value)) {
						include_once get_lib("org.phpframework.connector.RestConnector");
						
						$data = isset($action_value["data"]) ? $action_value["data"] : null;
						$result_type = isset($action_value["result_type"]) ? $action_value["result_type"] : null;
						
						$result = \RestConnector::connect($data, $result_type);
					}
					else
						launch_exception(new \Exception('$action_value is not an array with the RestConnector::connect\'s arguments'));
					
					break;
					
				case "soapconnector":
					if (is_array($action_value)) {
						include_once get_lib("org.phpframework.connector.SoapConnector");
						
						$data = isset($action_value["data"]) ? $action_value["data"] : null;
						$result_type = isset($action_value["result_type"]) ? $action_value["result_type"] : null;
						
						$result = \SoapConnector::connect($data, $result_type);
					}
					else
						launch_exception(new \Exception('$action_value is not an array with the SoapConnector::connect\'s arguments'));
					
					break;
				
				case "insert":
				case "update":
				case "delete":
				case "select":
				case "count":
				case "procedure":
				case "getinsertedid":
					$dal_broker = isset($action_value["dal_broker"]) ? $action_value["dal_broker"] : null;
					$dal_broker = $this->getParsedValueFromData($dal_broker, $results);
					
					$db_driver = isset($action_value["db_driver"]) ? $action_value["db_driver"] : null;
					$db_driver = $this->getParsedValueFromData($db_driver, $results);
					
					if (!$dal_broker)
						launch_exception(new \Exception("DAL Broker not selected!"));
					
					$broker = $EVC->getBroker($dal_broker);
					
					if (!$broker) 
						launch_exception(new \Exception("Broker '" . $dal_broker . "' does NOT exist!"));
					
					$options = isset($action_value["options"]) ? $action_value["options"] : null;
					$options = $this->getParsedValueFromData($options, $results);
					
					if ($db_driver) {
						$options = is_array($options) ? $options : ($options ? array($options) : array());
						$options["db_driver"] = $db_driver;
					}
					
					if ($action_type == "getinsertedid") 
						$result = $broker->getInsertedId($options);
					else {
						$table = isset($action_value["table"]) ? $action_value["table"] : null;
						$table = $this->getParsedValueFromData($table, $results);
						$sql = isset($action_value["sql"]) ? $action_value["sql"] : null;
						
						if ($table && $action_type != "procedure") {
							$attributes = isset($action_value["attributes"]) ? $action_value["attributes"] : null;
							$attributes = $this->getParsedValueFromData($attributes, $results);
							
							$conditions = isset($action_value["conditions"]) ? $action_value["conditions"] : null;
							$conditions = $this->getParsedValueFromData($conditions, $results);
							
							$attrs = array();
							$conds = array();
							
							if (is_array($attributes)) 
								foreach ($attributes as $attr) 
									if (!empty($attr["column"])) {
										$attr_name = isset($attr["name"]) ? $attr["name"] : null;
										$attr_value = isset($attr["value"]) ? $attr["value"] : null;
										
										$attrs[ $attr["column"] ] = $action_type == "select" ? $attr_name : $attr_value;
									}
							
							if (is_array($conditions)) 
								foreach ($conditions as $condition) 
									if (!empty($condition["column"]))
										$conds[ $condition["column"] ] = isset($condition["value"]) ? $condition["value"] : null;
							
							switch ($action_type) {
								case "insert":
									$result = $broker->insertObject($table, $attrs, $options);
									break;
								case "update":
									$result = $broker->updateObject($table, $attrs, $conds, $options);
									break;
								case "delete":
									$result = $broker->deleteObject($table, $conds, $options);
									break;
								case "select":
									$result = $broker->findObjects($table, $attrs, $conds, $options);
									break;
								case "count":
									$result = $broker->countObjects($table, $conds, $options);
									break;
							}
						}
						else if (!$sql)
							launch_exception(new \Exception('Sql cannot be empty for "' . $action_type . '" action!'));
						else {
							$sql = $this->getParsedValueFromData($sql, $results);
							
							if ($action_type == "select" || $action_type == "count" || $action_type == "procedure") {
								if (is_array($options))
									unset($options["return_type"]); //just in case if it exists
								
								$result = $broker->getData($sql, $options);
								$result = isset($result["result"]) ? $result["result"] : null;
								
								//get first value from count query results
								if ($action_type == "count") {
									if ($result && isset($result[0]) && is_array($result[0]))
										$result = array_shift(array_values($result[0]));
									else
										$result = null;
								}
							}
							else
								$result = $broker->setData($sql, $options);
						}
					}
					
					break;
				
				case "show_ok_msg":
				case "show_ok_msg_and_stop":
				case "show_ok_msg_and_die":
				case "show_ok_msg_and_redirect":
				case "show_error_msg":
				case "show_error_msg_and_stop":
				case "show_error_msg_and_die":
				case "show_error_msg_and_redirect":
					$message = isset($action_value["message"]) ? $action_value["message"] : null;
					$message = $this->getParsedValueFromData($message, $results);
					$ok_message = strpos($action_type, "_ok_") ? $message : null;
					$error_message = strpos($action_type, "_error_") ? $message : null;
					$redirect_url = strpos($action_type, "_redirect") && isset($action_value["redirect_url"]) ? $this->getParsedValueFromData($action_value["redirect_url"], $results) : null;
					
					$result = class_exists("\CommonModuleUI") ? \CommonModuleUI::getModuleMessagesHtml($EVC, $ok_message, $error_message, $redirect_url) : null;
					
					if (strpos($action_type, "_die"))
						$die = true;
					else if (strpos($action_type, "_stop"))
						$stop = true;
					
					break;
					
				case "alert_msg":
				case "alert_msg_and_stop":
				case "alert_msg_and_redirect":
					$message = isset($action_value["message"]) ? $action_value["message"] : null;
					$message = $this->getParsedValueFromData($message, $results);
					$redirect_url = strpos($action_type, "_redirect") && isset($action_value["redirect_url"]) ? $this->getParsedValueFromData($action_value["redirect_url"], $results) : null;
					
					$result = '<script>
						' . ($message ? 'alert("' . addcslashes($message, '"') . '");' : '') . '
						' . ($redirect_url ? 'document.location="' . addcslashes($redirect_url, '"') . '";' : '') . '
					</script>';
					
					if (strpos($action_type, "_stop"))
						$stop = true;
					
					break;
				
				case "redirect":
					$redirect_type = null;
					$redirect_url = null;
					
					if (is_array($action_value)) {
						$redirect_type = isset($action_value["redirect_type"]) ? $action_value["redirect_type"] : null;
						$redirect_type = $this->getParsedValueFromData($redirect_type, $results);
						
						$redirect_url = isset($action_value["redirect_url"]) ? $action_value["redirect_url"] : null;
						$redirect_url = $this->getParsedValueFromData($redirect_url, $results);
					}
					else
						$redirect_url = $this->getParsedValueFromData($action_value, $results);
					
					if ($redirect_url) {
						if ($redirect_type == "server" || $redirect_type == "server_client")
							header("Location: $redirect_url");
						
						if (!$redirect_type || $redirect_type == "client" || $redirect_type == "server_client")
							$result = '<script>document.location="' . addcslashes($redirect_url, '"') . '";</script>';
					}
					break;
				
				case "refresh":
					$result = '<script>var url = document.location; document.location=url;</script>';
					break;
				
				case "return_previous_record":
				case "return_next_record":
				case "return_specific_record":
					$records_variable_name = isset($action_value["records_variable_name"]) ? $action_value["records_variable_name"] : null;
					$records_variable_name = $this->getParsedValueFromData($records_variable_name, $results);
					$records = !is_array($records_variable_name) ? (isset($results[$records_variable_name]) ? $results[$records_variable_name] : null) : $records_variable_name;
					
					if (is_array($records)) {
						$index_variable_name = isset($action_value["index_variable_name"]) ? $action_value["index_variable_name"] : null;
						$index_variable_name = $this->getParsedValueFromData($index_variable_name, $results);
						$current_index = is_numeric($index_variable_name) ? $index_variable_name : (isset($_GET[$index_variable_name]) ? $_GET[$index_variable_name] : null);
						$current_index = is_numeric($current_index) ? $current_index : 0;
						
						if ($action_type == "return_previous_record")
							$current_index--;
						else if ($action_type == "return_next_record")
							$current_index++;
						
						$result = isset($records[$current_index]) ? $records[$current_index] : null;
					}
					
					break;
				
				case "check_logged_user_permissions":
					$all_permissions_checked = isset($action_value["all_permissions_checked"]) ? $action_value["all_permissions_checked"] : null;
					$all_permissions_checked = $this->getParsedValueFromData($all_permissions_checked, $results);
					$users_perms = isset($action_value["users_perms"]) ? $action_value["users_perms"] : null;
					$entity_path = isset($action_value["entity_path"]) ? $action_value["entity_path"] : null;
					$logged_user_id = isset($action_value["logged_user_id"]) ? $action_value["logged_user_id"] : null;
					
					$result = $this->validateUserPermissions($entity_path, $logged_user_id, $users_perms, $all_permissions_checked);
					break;
				
				case "code": //Only execute if code is invalid
					$return_values = array();
					$action_value = $this->getParsedValueFromData($action_value, $results, false);
					
					$result = \PHPScriptHandler::parseContent($action_value, $results, $return_values);
					
					if (isset($return_values[0]) && (version_compare(PHP_VERSION, '7', '>=') || $return_values[0] !== false)) //bc eval returns false on error (if PHP <7) and null if no return...
						$result = $return_values[0];
					
					break;
				
				case "string":
					if (is_array($action_value)) {
						$string = isset($action_value["string"]) ? $action_value["string"] : null;
						$assignment_operator = isset($action_value["operator"]) ? $action_value["operator"] : null;
						
						$result = $this->getParsedValueFromData($string, $results);
					}
					else
						$result = $this->getParsedValueFromData($action_value, $results);
					
					break;
					
				case "variable":
					//note that the app/__system/layer/presentation/phpframework/src/util/SequentialLogicalActivitySettingsCodeCreator.php already add the '$' to the variable name if it was applicable
					if (is_array($action_value)) {
						$variable = isset($action_value["variable"]) ? $action_value["variable"] : null;
						$assignment_operator = isset($action_value["operator"]) ? $action_value["operator"] : null;
						
						$result = $this->getParsedValueFromData($variable, $results);
					}
					else
						$result = $this->getParsedValueFromData($action_value, $results);
					
					break;
					
				case "sanitize_variable":
					if (!$this->xss_sanitize_lib_included)
						include_once get_lib("org.phpframework.util.web.html.XssSanitizer"); //leave this here, otherwise it could be over-loading for every request to include without need it...
					
					$this->xss_sanitize_lib_included = true;
					
					$result = $this->getParsedValueFromData($action_value, $results);
					$result = \XssSanitizer::sanitizeVariable($result);
					
					break;
				
				case "validate_variable":
					$method = isset($action_value["method"]) ? $action_value["method"] : null;
					$method = $this->getParsedValueFromData($method, $results);
					
					$variable = isset($action_value["variable"]) ? $action_value["variable"] : null;
					$variable = $this->getParsedValueFromData($variable, $results);
					
					$offset = isset($action_value["offset"]) ? $action_value["offset"] : null;
					$offset = $this->getParsedValueFromData($offset, $results);
					
					if ($method) {
						if (strpos($method, "TextValidator::") === 0)
							include_once get_lib("org.phpframework.util.text.TextValidator");
						else if (strpos($method, "ObjTypeHandler::") === 0)
							include_once get_lib("org.phpframework.object.ObjTypeHandler");
						
						$is_check_method = strpos($method, "TextValidator::check") === 0;
						
						if (!$is_check_method || is_numeric($offset)) {
							$code = "<?php return $method(\$variable";
							
							if ($is_check_method)
								$code .= ", $offset";
							
							$code .= "); ?>";
							
							$external_vars = array("variable" => $variable);
							\PHPScriptHandler::parseContent($code, $external_vars, $return_values);
							$result = !empty($return_values[0]);
						}
						else
							$result = false;
					}
					break;
				
				case "list_report":
					$type = isset($action_value["type"]) ? $action_value["type"] : null;
					$continue = isset($action_value["continue"]) ? $action_value["continue"] : null;
					$doc_name = isset($action_value["doc_name"]) ? $action_value["doc_name"] : null;
					$var = isset($action_value["variable"]) ? $action_value["variable"] : null;
					$list = $this->getParsedValueFromData($var, $results);
					
					//set header
					$content_type = $type == "xls" ? "application/vnd.ms-excel" : ($type == "csv" ? "text/csv" : "text/plain");
					header("Content-Type: $content_type");
					header('Content-Disposition: attachment; filename="' . $doc_name . '.' . $type . '"');
					
					//set output
					$str = "";
					
					if ($list && is_array($list) && count($list)) {
						$first_row = $list[ array_keys($list)[0] ];
						
						if (is_array($first_row)) {
							$columns = array_keys($first_row);
							$columns_length = count($columns);
							
							$rows_delimiter = "\n";
							$columns_delimiter = "\t";
							$enclosed_by = "";
							
							if ($type == "csv") {
								$columns_delimiter = ",";
								$enclosed_by = '"';
								
								$str .= "sep=$columns_delimiter$rows_delimiter"; //Alguns programas, como o Microsoft Excel 2010, requerem ainda um indicador "sep=" na primeira linha do arquivo, apontando o caráter de separação.
							}
							
							//prepare columns
							for ($i = 0; $i < $columns_length; $i++)
								$str .= ($i > 0 ? $columns_delimiter : "") . $enclosed_by . addcslashes($columns[$i], $columns_delimiter . $enclosed_by . "\\") . $enclosed_by;
							
							//prepare rows
							if ($str) {
								$str .= $rows_delimiter;
								
								foreach ($list as $row)
									if (is_array($row)) {
										for ($i = 0; $i < $columns_length; $i++) {
											$row_column = isset($row[ $columns[$i] ]) ? $row[ $columns[$i] ] : null;
											
											$str .= ($i > 0 ? $columns_delimiter : "") . $enclosed_by . addcslashes($row_column, $columns_delimiter . $enclosed_by . "\\") . $enclosed_by;
										}
										
										$str .= $rows_delimiter;
									}
							}
						}
					}
					
					$result = $str;
					
					if ($continue == "die")
						$die = true;
					else if ($continue == "stop")
						$stop = true;
					
					break;
				
				case "call_block":
					$block = isset($action_value["block"]) ? trim($action_value["block"]) : "";
					$project = isset($action_value["project"]) ? trim($action_value["project"]) : "";
					
					$result = $block ? $this->getBlockHtml($block, $project) : "";
					break;
				
				case "call_view":
					$view = isset($action_value["view"]) ? trim($action_value["view"]) : "";
					$project = isset($action_value["project"]) ? trim($action_value["project"]) : "";
					
					$result = $view ? $this->getViewHtml($view, $project) : "";
					break;
				
				case "include_file":
					$path = isset($action_value["path"]) ? trim($action_value["path"]) : "";
					$path = $this->getParsedValueFromData($path, $results);
					
					if ($path) {
						$once = !empty($action_value["once"]);
						$code = 'include' . ($once ? '_once' : '') . ' "' . addcslashes($path, '\\"') . '";';
						
						$result = eval($code);
					}
					break;
				
				case "draw_graph":
					if (is_array($action_value)) {
						if (array_key_exists("code", $action_value)) {
							$return_values = array();
							
							$code = isset($action_value["code"]) ? $action_value["code"] : null;
							$code = $this->getParsedValueFromData($code, $results, false);
							
							$result = \PHPScriptHandler::parseContent($code, $results, $return_values);
							
							if (isset($return_values[0]) && $return_values[0] !== false) //bc eval returns false on error and null if no return...
								$result = $return_values[0];
						}
						else {
							$include_graph_library = isset($action_value["include_graph_library"]) ? $action_value["include_graph_library"] : null;
							$width = isset($action_value["width"]) ? $action_value["width"] : null;
							$height = isset($action_value["height"]) ? $action_value["height"] : null;
							$labels_variable = isset($action_value["labels_variable"]) ? $action_value["labels_variable"] : null;
							
							$include_graph_library = $this->getParsedValueFromData($include_graph_library, $results);
							$width = $this->getParsedValueFromData($width, $results);
							$height = $this->getParsedValueFromData($height, $results);
							$labels_variable = $this->getParsedValueFromData($labels_variable, $results);
							
							$data_sets_result = '';
							$default_type = null;
							
							if (!empty($action_value["data_sets"])) {
								$data_sets = $action_value["data_sets"];
								
								if (isset($data_sets["values_variable"]))
									$data_sets = array($data_sets);
								
								$options_names = array(
									"values_variable" => "data",
									"item_label" => "label", 
									"background_colors" => "backgroundColor", 
									"border_colors" => "borderColor", 
									"border_width" => "borderWidth"
								);
								
								foreach ($data_sets as $data_set) {
									if ($data_set) {
										//parse data_set into an object
										$parsed_data_set = array();
										$composite_keys_obj = array();
										
										foreach ($data_set as $key => $value) {
											$key = preg_replace("/(^\.+|\.+$)/", "", preg_replace("/\s*/", "", $key)); //remove all spaces and '.' at the begining and end of string
											
											if (strpos($key, ".") !== false) { //if is a composite option inside of an object
												$parts = explode(".", $key);
												
												$part_obj = &$parsed_data_set;
												$part_composite_keys_obj = &$composite_keys_obj;
												
												for ($i = 0, $t = count($parts); $i < $t; $i++) {
													$part = $parts[$i];
													
													if ($part || is_numeric($part)) {
														if ($i + 1 == $t)
															$part_obj[$part] = $value;
														else {
															if (!isset($part_obj[$part]) || !is_array($part_obj[$part])) {
																$part_obj[$part] = array();
																$part_composite_keys_obj[$part] = array();
															}
															
															$part_obj = &$part_obj[$part];
															$part_composite_keys_obj = &$part_composite_keys_obj[$part];
														}
													}
												}
											}
											else
												$parsed_data_set[$key] = $value;
										}
										//echo "<pre>";print_r($parsed_data_set);echo "</pre>";
										
										$data_set_result = '';
										
										foreach ($parsed_data_set as $key => $value) {
											if (is_array($value) && is_array($composite_keys_obj) && array_key_exists($key, $composite_keys_obj)) {
												$data_set_result .= ($data_set_result ? ",\n                 " : "") . $key . ': {';
												$data_set_result .= $this->getDrawGraphDataSetCode($value, $composite_keys_obj[$key], $results, "                     ");
												$data_set_result .= ($data_set_result ? "\n              	   " : "") . '}';
											}
											else {
												$value = $this->getParsedValueFromData($value, $results);
												
												if ($key) {
													$option_name = !empty($options_names[$key]) ? $options_names[$key] : $key;
													$is_valid = !empty($value) || is_numeric($value) || !isset($options_names[$key]);
													
													if ($key == "type") {
														if (!$default_type)
															$default_type = $value;
													
														$is_valid = $is_valid && $value != $default_type;
													}
													else if ($key == "border_width")
														$is_valid = $is_valid || is_numeric($value);
													
													if ($is_valid)
														$data_set_result .= ($data_set_result ? ",\n                 " : "") . $option_name . ': ' . json_encode($value);
												}
											}
										}
										
										$data_sets_result .= '
		     {
		         ' . $data_set_result . '
		     },';
									}
								}
							}
							
							$rand = rand(0, 1000);
							
							$result = '';
							
							if ($include_graph_library == "cdn_even_if_exists")
								$result .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>' . "\n\n";
							else if ($include_graph_library == "cdn_if_not_exists")
								$result .= '<script>
if (typeof Chart != "function")
	document.write(\'<scr\' + \'ipt src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></scr\' + \'ipt>\');
</script>' . "\n\n";
							
							$result .= '<canvas id="my_chart_' . $rand . '"' . (is_numeric($width) ? ' width="' . $width . '"' : '') . (is_numeric($height) ? ' height="' . $height . '"' : '') . '></canvas>

<script>
var canvas = document.getElementById("my_chart_' . $rand . '");
var myChart_' . $rand . ' = new Chart(canvas, {
    type: "' . $default_type . '",
    data: {
        ' . ($labels_variable ? 'labels: ' . json_encode($labels_variable) . ',' : '') . '
        datasets: [' . $data_sets_result . '
        ]
    }
});
</script>';
						}
					}
					break;
				
				case "loop":
					$records_variable_name = isset($action_value["records_variable_name"]) ? $action_value["records_variable_name"] : null;
					$records_variable_name = $this->getParsedValueFromData($records_variable_name, $results);
					$records = !is_array($records_variable_name) ? $results[$records_variable_name] : $records_variable_name;
					
					if (is_array($records)) {
						$records_start_index = isset($action_value["records_start_index"]) ? $action_value["records_start_index"] : null;
						$records_end_index = isset($action_value["records_end_index"]) ? $action_value["records_end_index"] : null;
						$array_item_key_variable_name = isset($action_value["array_item_key_variable_name"]) ? $action_value["array_item_key_variable_name"] : null;
						$array_item_value_variable_name = isset($action_value["array_item_value_variable_name"]) ? $action_value["array_item_value_variable_name"] : null;
						
						$records_start_index = $this->getParsedValueFromData($records_start_index, $results);
						$records_end_index = $this->getParsedValueFromData($records_end_index, $results);
						$array_item_key_variable_name = $this->getParsedValueFromData($array_item_key_variable_name, $results);
						$array_item_value_variable_name = $this->getParsedValueFromData($array_item_value_variable_name, $results);
						
						$records_start_index = is_numeric($records_start_index) ? $records_start_index : 0;
						$records_end_index = is_numeric($records_end_index) ? $records_end_index : count($records);
						
						$sub_actions = isset($action_value["actions"]) ? $action_value["actions"] : null;
						$result = '';
						$i = 0;
						
						foreach ($records as $k => $v) {
							if ($i >= $records_end_index || $stop)
								break;
							else if ($i >= $records_start_index) {
								$results[ $array_item_key_variable_name ] = $k;
								$results[ $array_item_value_variable_name ] = $v;
								
								$result .= $this->executeActions($sub_actions, $results, $stop, $die);
							}
							
							++$i;
						}
					}
					
					break;
				
				case "group":
					//Preparing sub-actions
					$sub_actions = isset($action_value["actions"]) ? $action_value["actions"] : null;
					
					$results_aux = $results;
					$result = $this->executeActions($sub_actions, $results_aux, $stop, $die);
					
					//Preparing the new results vars according with the $group_name. This result will contain all the local variables
					$group_name = isset($action_value["group_name"]) ? $action_value["group_name"] : null;
					$group_name = $this->getParsedValueFromData($group_name, $results);
					
					if ($group_name) {
						$group_name_result_vars = array_diff_key($results_aux, $results);
					
						//Preparing to overwrite the results with the same results array, but with the updated values if they were changed inside of the group...
						$results = array_intersect_key($results_aux, $results); //$results will be with last updated values.
						
						//Adding new group name vars to $results if group_name exists!
						$results[$group_name] = $group_name_result_vars;
					}
					else 
						$results = $results_aux; //if no $group_name adds the vars created in the group to the $results
					
					break;
				
				default:
					$result = $this->getParsedValueFromData($action_value, $results);
			}
		}
		
		return $result;
	}
	
	private function getDrawGraphDataSetCode($parsed_data_set, $composite_keys_obj, $results, $prefix) {
		$data_set_code = "";
		
		if ($parsed_data_set)
			foreach ($parsed_data_set as $key => $value) {
				$data_set_code .= ($data_set_code ? "," : "") . "\n$prefix$key: ";
				
				if (is_array($value) && is_array($composite_keys_obj) && array_key_exists($key, $composite_keys_obj)) {
					$data_set_code .= '{';
					$data_set_code .= $this->getDrawGraphDataSetCode(isset($value[$key]) ? $value[$key] : null, $composite_keys_obj[$key], $results, $prefix . "    ");
					$data_set_code .= "\n$prefix}";
				}
				else {
					$value = $this->getParsedValueFromData($value, $results);
					$data_set_code .= json_encode($value);
				}
			}
		
		return $data_set_code;
	}
	
	private function includeActionFile($action_value, &$data) {
		$path = isset($action_value["include_file_path"]) ? trim($action_value["include_file_path"]) : "";
		$path = $this->getParsedValueFromData($path, $data);
		
		if ($path) {
			$once = !empty($action_value["include_once"]);
			
			$EVC = $this->getEVC(); //set EVC by defult. This is very important otherwise when we include utils from projects, they may call other utils through the EVC. and if the EVC is not defined, it will give a php error.
			
			if ($once)
				include_once $path;
			else
				include $path;
		}
	}
	
	private function getParsedValueFromData($value, &$data, $parse_php = true) {
		if (is_array($value))
			foreach ($value as $key => $item)
				$value[$key] = $this->getParsedValueFromData($item, $data);
		else if ($value && is_string($value)) {
			//parse #_POST["activity"][$row_index]#
			if ($parse_php && $value && is_string($value) && strpos($value, '$') !== false) { 
				$code = '<?= "' . addcslashes($value, '"') . '" ?>';
				$value = \PHPScriptHandler::parseContent($code, $data);
				//echo "code:$code\n";
				//echo "value:$value\n";
			}
			
			//parse #some[thing][0]#
			if (strpos($value, '#') !== false) {
				$HtmlFormHandler = new \HtmlFormHandler();
				$value = $HtmlFormHandler->getParsedValueFromData($value, $data);
			}
			
			//parse {$some[thing][0]}. Not sure about this
			if ($parse_php && $value && is_string($value) && strpos($value, '$') !== false) { //Not well tested...
				$code = '<?= "' . addcslashes($value, '"') . '" ?>';
				$value = \PHPScriptHandler::parseContent($code, $data);
				//echo "code:$code\n";
				//echo "value:$value\n";
			}
		}
		
		return $value;
	}
	
	private function validateUserPermissions($entity_path, $logged_user_id, $users_perms, $all_permissions_checked) {
		if (!$users_perms || !class_exists("\UserUtil"))
			return true;
		
		//prepare users_perms
		$exists_public_access = false;
		$new_users_perms = array();
		
		foreach ($users_perms as $user_perm) 
			if (isset($user_perm["user_type_id"]) && $user_perm["user_type_id"] == \UserUtil::PUBLIC_USER_TYPE_ID) {
				$exists_public_access = true;
				break;
			}
			else
				$new_users_perms[] = $user_perm;
		
		//if public and only need 1 check
		if ($exists_public_access && !$all_permissions_checked) 
			return true;
		
		//if no logged user
		if (!$logged_user_id) 
			return false;
		
		//if no new_users_perms it means there is notjing to check so there is no permissions and everything is allowed!
		if (!$new_users_perms)
			return true;
		
		//set users_perms with new_users_perms without the public perms
		$users_perms = $new_users_perms; 
		
		//get user type current page activities
		$object_type_id = class_exists("\ObjectUtil") ? \ObjectUtil::PAGE_OBJECT_TYPE_ID : 1;
		$object_id = $entity_path;
		
		if (!$object_id)
			return false;
		
		$object_id = str_replace(APP_PATH, "", $object_id);
		$object_id = \HashCode::getHashCodePositive($object_id);
		
		$brokers = $this->getEVC()->getBrokers();
		$utaos = \UserUtil::getUserTypeActivityObjectsByUserIdAndConditions($brokers, $logged_user_id, array(
			"object_type_id" => $object_type_id, 
			"object_id" => $object_id
		), null);
		
		//if no logged user permissions, returns false
		if (!$utaos)
			return false;
		
		//check user permssions
		$entered = false;
		$result = true;
		
		foreach ($users_perms as $user_perm) 
			if (isset($user_perm["user_type_id"]) && isset($user_perm["activity_id"]) && is_numeric($user_perm["user_type_id"]) && is_numeric($user_perm["activity_id"])) {
				if (!$entered && !$all_permissions_checked) //only happens on the first iteration and if $all_permissions_checked is false
					$result = false;
				
				$entered = true;
				
				$user_perm_exists = false;
				foreach ($utaos as $utao)
					if (isset($utao["user_type_id"]) && isset($utao["activity_id"]) && $utao["user_type_id"] == $user_perm["user_type_id"] && $utao["activity_id"] == $user_perm["activity_id"]) {
						$user_perm_exists = true;
						break;
					}
				
				if ($all_permissions_checked && !$user_perm_exists)
					return false;
				else if (!$all_permissions_checked && $user_perm_exists)
					return true;
			}
		
		return $result;
	}
	
	private function getBlockHtml($block, $project) {
		$EVC = $this->getEVC();
		$bfp = $EVC->getBlockPath($block, $project);
		
		if (file_exists($bfp)) {
			$config_path = $EVC->getConfigPath("config"); //Do not add project, bc we want the config from the selected project
			
			if (file_exists($config_path))
				include $config_path;
			
			@include_once $EVC->getModulePath("translator/include_text_translator_handler", $EVC->getCommonProjectName());//@ in case it doens't exist
			
			$block_local_variables = array();
			include $bfp;
			
			return $EVC->getCMSLayer()->getCMSBlockLayer()->getCurrentBlock();
		}
		
		return "";
	}
	
	private function getViewHtml($view, $project) {
		$EVC = $this->getEVC();
		$vfp = $EVC->getViewPath($view, $project);
		
		if (file_exists($vfp)) {
			$config_path = $EVC->getConfigPath("config"); //Do not add project, bc we want the config from the selected project
			
			if (file_exists($config_path))
				include $config_path;
			
			@include_once $EVC->getModulePath("translator/include_text_translator_handler", $EVC->getCommonProjectName());//@ in case it doens't exist
			
			ob_start(null, 0);
			
			include $vfp;
			
			$view_output = ob_get_contents();
			ob_end_clean();
			
			return $view_output;
		}
		
		return "";
	}
}
?>
