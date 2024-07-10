<?php
include_once get_lib("org.phpframework.util.MyArray");
include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once get_lib("org.phpframework.workflow.WorkFlowTask");
include_once get_lib("org.phpframework.phpscript.PHPUICodeExpressionHandler");
//include $EVC->getUtilPath("CMSPresentationFormSettingsUIHandler");

class SequentialLogicalActivitySettingsCodeCreator {
	
	public static function getActionsCode($webroot_cache_folder_path, $webroot_cache_folder_url, $actions_settings, $prefix = "") {
		if (is_array($actions_settings)) {
			$actions_settings_bkp = $actions_settings;
			MyArray::arrKeysToLowerCase($actions_settings, true);
			
			$allowed_tasks = array("createform", "callbusinesslogic", "callibatisquery", "callhibernatemethod", "getquerydata", "setquerydata", "callfunction", "callobjectmethod", "restconnector", "soapconnector");
			$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
			$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
			$WorkFlowTaskHandler->setAllowedTaskTags($allowed_tasks);
			$WorkFlowTaskHandler->initWorkFlowTasks();
			
			$code = self::getActionItemsCode($actions_settings, $WorkFlowTaskHandler, $prefix, $actions_settings_bkp);
			
			return $code;
		}
	}
	
	public static function getActionItemsCode($items, $WorkFlowTaskHandler, $prefix = "", $original_items = null) {
		$items_code = "";
		
		if (is_array($items))
			foreach ($items as $idx => $item_settings) {
				$action_type = strtolower($item_settings["action_type"]);
				$action_value = $item_settings["action_value"];
				$original_action_value = isset($original_items[$idx]["action_value"]) ? $original_items[$idx]["action_value"] : array();
				$condition_type = strtolower($item_settings["condition_type"]);
				$condition_value = $item_settings["condition_value"];
				$action_description = $item_settings["action_description"];
				
				if ($condition_type != "execute_if_code" && $condition_type != "execute_if_not_code")
					$condition_value = self::prepareStringValue($condition_value);
				
				$items_code .= ($items_code ? "," : "") . "\n{$prefix}array(";
				$items_code .= "\n$prefix\t" . '"result_var_name" => ' . self::prepareStringValue($item_settings["result_var_name"]);
				$items_code .= ",\n$prefix\t" . '"action_type" => ' . self::prepareStringValue($action_type);
				$items_code .= ",\n$prefix\t" . '"condition_type" => ' . self::prepareStringValue($condition_type);
				$items_code .= ",\n$prefix\t" . '"condition_value" => ' . $condition_value;
				$items_code .= ",\n$prefix\t" . '"action_description" => ' . self::prepareStringValue($action_description);
				
				switch ($action_type) {
					case "html": //getting design form html settings
						$task = $WorkFlowTaskHandler->getTasksByTag("createform");
						$task = $task[0];
						$task["properties"] = array("form_settings_data_type" => $action_value["form_settings_data_type"], "form_settings_data" => $action_value["form_settings_data"]);
						$task["obj"]->data = $task;
						
						$form_code = trim($task["obj"]->printCode(null, null, "$prefix\t"));
						$form_code = substr($form_code, strlen("HtmlFormHandler::createHtmlForm("), strlen(", null);") * -1);
						
						$items_code .= $form_code ? ",\n$prefix\t" . '"action_value" => ' . trim($form_code) : '';
						break;
						
					case "callbusinesslogic":
					case "callibatisquery":
					case "callhibernatemethod":
					case "getquerydata":
					case "setquerydata":
					case "callfunction":
					case "callobjectmethod":
					case "restconnector":
					case "soapconnector":
						if (!$action_value) //$action_value could not exist if the presentation stop been connected with another layer.
							$action_value = array();
						
						$items_code .= ",\n$prefix\t" . '"action_value" => array(';
						$broker_code = '';
						
						$is_soap_data_options = $action_type == "soapconnector" && array_key_exists("data_type", $action_value) && $action_value["data_type"] == "options" && is_array($action_value["data"]);
						$is_rest_data_options = $action_type == "restconnector" && array_key_exists("data_type", $action_value) && $action_value["data_type"] == "array" && is_array($action_value["data"]);
						
						//for soapconnector
						if ($is_soap_data_options) {
							$orig_action_value = $action_value;
							$action_value = $action_value["data"];
							$prefix .= "\t";
						}
						
						foreach ($action_value as $key => $v) 
							switch ($key) {
								case "method_obj":
									if ($v) {
										$static_pos = strpos($v, "::") || ($action_type == "callobjectmethod" && $action_value["method_static"] == 1);
										$non_static_pos = strpos($v, "->");
										$v = substr($v, 0, 1) != '$' && (!$static_pos || ($non_static_pos && $static_pos > $non_static_pos)) ? '$' . $v : $v;
										$v = substr($v, 0, 1) == '$' ? $v : '"' . $v . '"';
										
										$broker_code .= ($broker_code ? ',' : '') . "\n$prefix\t\t" . '"' . $key . '" => ' . $v;
									}
									break;
								
								case "broker_method_obj_type":
								case "func_name":
								case "method_name":
								case "method_static":
								case "include_once":
									$broker_code .= ($broker_code ? ',' : '') . "\n$prefix\t\t" . '"' . $key . '" => ' . self::prepareStringValue($v);
									break;
								
								case "func_args":
								case "method_args":
									$arr_code = '';
									
									if (is_array($v))
										foreach ($v as $vv)
											if (is_array($vv["childs"]["value"][0]) && array_key_exists("value", $vv["childs"]["value"][0])) {
												$vv_value = $vv["childs"]["value"][0]["value"];
												$vv_type = $vv["childs"]["type"][0]["value"];
											
												$vv_value = WorkFlowTask::getVariableValueCode($vv_value, $vv_type);
												$vv_value = strlen($vv_value) ? $vv_value : "null";
											
												$arr_code .= ($arr_code ? ',' : '') . "\n$prefix\t\t\t" . $vv_value;
											}
									
									$broker_code .= ($broker_code ? ',' : '') . "\n$prefix\t\t" . '"' . $key . '" => array(';
									$broker_code .= $arr_code . "\n$prefix\t\t)";
									break;
									
								case "sma_ids": //very important otherwise it will convert the sma_ids value to a variable by default and we want to have a string with the variable name to be created after it executes the hibernate insert action!
									$v = substr($v, 0, 1) == '$' ? $v : '"' . $v . '"';
									$broker_code .= ($broker_code ? ',' : '') . "\n$prefix\t\t" . '"' . $key . '" => ' . $v;
									break;
								
								default:
									//for soapconnector
									if ($is_soap_data_options && ($key == "options" || $key == "headers") && $action_value[$key . "_type"] == "options" && is_array($v)) {
										$arr_code = '';
										
										if ($key == "options") {
											foreach ($v as $vv) 
												if ($vv["name"]) {
													$vv_value = $vv["value"];
													$vv_type = $vv["var_type"];
													
													$vv_value = WorkFlowTask::getVariableValueCode($vv_value, $vv_type);
													$vv_value = strlen($vv_value) ? $vv_value : "null";
													
													$arr_code .= ($arr_code ? ',' : '') . "\n$prefix\t\t\t" . '"' . $vv["name"] . '" => ' . $vv_value;
												}
										}
										else if ($key == "headers") {
											$headers_keys = array("namespace", "name", "must_understand", "actor", "parameters");
											
											foreach ($v as $vv) {
												$arr_item_code = '';
												
												foreach ($headers_keys as $hk) 
													if (array_key_exists($hk, $vv)) {
														$vv_value = $vv[$hk];
														$vv_type = $vv[$hk . "_type"];
														
														if ($hk == "must_understand" && $vv_type == "options" && !is_numeric($vv_value))
															$vv_value = WorkFlowTask::getVariableValueCode($vv_value, "string");
														else if ($hk == "parameters" && $vv_type == "array" && is_array($vv_value))
															$vv_value = str_replace("\n", "\n$prefix\t\t\t\t", WorkFlowTask::getArrayString($vv_value));
														else {
															$vv_value = WorkFlowTask::getVariableValueCode($vv_value, $vv_type);
															$vv_value = strlen($vv_value) ? $vv_value : "null";
														}
														
														$arr_item_code .= ($arr_item_code ? ',' : '') . "\n$prefix\t\t\t\t" . '"' . $hk . '" => ' . $vv_value;
													}
												
												$arr_code .= ($arr_code ? ',' : '') . "\n$prefix\t\t\t" . 'array(';
												$arr_code .= $arr_item_code . "\n$prefix\t\t\t)";
											}
										}
										
										$broker_code .= ($broker_code ? ',' : '') . "\n$prefix\t\t" . '"' . $key . '" => array(';
										$broker_code .= $arr_code . "\n$prefix\t\t)";
										
										break;
									}
									//for restconnector
									else if ($is_rest_data_options && $key == "data" && $action_value[$key . "_type"] == "array" && is_array($v)) {
										foreach ($v as $idx => $vv) {
											if ($vv["key_type"] == "options")
												$v[$idx]["key_type"] = "string";
											
											if ($vv["key"] == "settings" && is_array($vv["items"]))
												foreach ($vv["items"] as $idj => $sub_vv) {
													if ($sub_vv["key_type"] == "options")
														$v[$idx]["items"][$idj]["key_type"] = "string";
												}
										}
										
										$v = str_replace("\n", "\n$prefix\t\t", WorkFlowTask::getArrayString($v));
										$broker_code .= ($broker_code ? ',' : '') . "\n$prefix\t\t" . '"' . $key . '" => ' . (strlen($v) ? $v : "null");
									}
									//for all
									else if (array_key_exists($key . "_type", $action_value)) { //only do this for the real attributes. the Types will be ignored!
										$key_type = $action_value[$key . "_type"];
										if ($key_type == "array")
											$v = str_replace("\n", "\n$prefix\t\t", WorkFlowTask::getArrayString($v));
										else {
											if (($is_soap_data_options || $is_rest_data_options) && $key_type == "options") //for soap->data[type_type], rest->result_type_type, etc...
												$key_type = "string";
											
											$v = WorkFlowTask::getVariableValueCode($v, $key_type);
										}
										
										$broker_code .= ($broker_code ? ',' : '') . "\n$prefix\t\t" . '"' . $key . '" => ' . (strlen($v) ? $v : "null");
									}
							}
						
						//for soapconnector
						if ($is_soap_data_options) {
							$prefix = substr($prefix, 0, -1);
							$broker_code = "\n$prefix\t\t" . '"data" => array(' . $broker_code;
							$broker_code .= "\n$prefix\t\t" . ')';
							
							$action_value = $orig_action_value;
							
							if (array_key_exists("result_type", $action_value)) {
								$vt = $action_value["result_type_type"] == "options" ? "string" : $action_value["result_type_type"];
								$v = WorkFlowTask::getVariableValueCode($action_value["result_type"], $vt);
								$broker_code .= ',' . "\n$prefix\t\t" . '"result_type" => ' . (strlen($v) ? $v : "null");
							}
						}
						
						$items_code .= $broker_code . "\n$prefix\t)";
						break;
					
					case "insert":
					case "update":
					case "delete":
					case "select":
					case "count":
					case "procedure":
					case "getinsertedid":
						$items_code .= ",\n$prefix\t" . '"action_value" => array(';
						
						//prepare header fields
						$items_code .= "\n$prefix\t\t" . '"dal_broker" => ' . self::prepareStringValue($action_value["dal_broker"]);
						$items_code .= ",\n$prefix\t\t" . '"db_driver" => ' . self::prepareStringValue($action_value["db_driver"]);
						$items_code .= ",\n$prefix\t\t" . '"db_type" => ' . self::prepareStringValue($action_value["db_type"]);
						
						if ($action_type != "getinsertedid") {
							//prepare table and sql fields
							if ($action_value["table"]) {
								$items_code .= ",\n$prefix\t\t" . '"table" => ' . self::prepareStringValue($action_value["table"]);
								$attributes = $action_value["attributes"];
								if ($attributes) {
									$items_code .= ",\n$prefix\t\t" . '"attributes" => array(';
									$attributes_code = '';
									
									foreach ($attributes as $attribute) { 
										$attributes_code .= ($attributes_code ? "," : "") . "\n$prefix\t\t\tarray("
											. "\n$prefix\t\t\t\t" . '"column" => ' . self::prepareStringValue($attribute["column"])
											. ",\n$prefix\t\t\t\t" . '"value" => ' . self::prepareStringValue($attribute["value"])
										. ",\n$prefix\t\t\t)";
									}
									
									$items_code .= $attributes_code . "\n$prefix\t\t" . ')';
								}
								
								$conditions = $action_value["conditions"];
								if ($conditions) {
									$items_code .= ",\n$prefix\t\t" . '"conditions" => array(';
									$conditions_code = '';
									
									foreach ($conditions as $condition) { 
										$conditions_code .= ($conditions_code ? "," : "") . "\n$prefix\t\t\tarray("
											. "\n$prefix\t\t\t\t" . '"column" => ' . self::prepareStringValue($condition["column"])
											. ",\n$prefix\t\t\t\t" . '"value" => ' . self::prepareStringValue($condition["value"])
										. ",\n$prefix\t\t\t)";
									}
									
									$items_code .= $conditions_code . "\n$prefix\t\t" . ')';
								}
							}
							else
								$items_code .= ",\n$prefix\t\t" . '"sql" => ' . self::prepareStringValue($action_value["sql"]);
						}
						
						//prepare footer fields
						$options_code = "";
						if ($action_value["options_type"] == "array")
							$options_code = str_replace("\n", "\n$prefix\t\t", WorkFlowTask::getArrayString($action_value["options"]));
						else
							$options_code = WorkFlowTask::getVariableValueCode($action_value["options"], $action_value["options_type"]);
						
						$items_code .= ",\n$prefix\t\t" . '"options" => ' . (strlen($options_code) ? $options_code : "null");
						
						//close action value
						$items_code .= "\n$prefix\t" . ')';
						break;
					
					case "show_ok_msg":
					case "show_ok_msg_and_stop":
					case "show_ok_msg_and_die":
					case "show_ok_msg_and_redirect":
					case "show_error_msg":
					case "show_error_msg_and_stop":
					case "show_error_msg_and_die":
					case "show_error_msg_and_redirect":
					case "alert_msg":
					case "alert_msg_and_stop":
					case "alert_msg_and_redirect":
						$items_code .= ",\n$prefix\t" . '"action_value" => array(';
						$items_code .= "\n$prefix\t\t" . '"message" => ' . self::prepareStringValue($action_value["message"]);
						$items_code .= ",\n$prefix\t\t" . '"redirect_url" => ' . self::prepareStringValue($action_value["redirect_url"]);
						$items_code .= "\n$prefix\t" . ')';
						break;
						
					case "redirect": //getting redirect settings
						$redirect_type = null;
						$redirect_url = null;
						
						if (is_array($action_value)) {
							$redirect_type = $action_value["redirect_type"];
							$redirect_url = $action_value["redirect_url"];
						}
						else
							$redirect_url = $action_value;
						
						$items_code .= ",\n$prefix\t" . '"action_value" => array(';
						$items_code .= "\n$prefix\t\t" . '"redirect_type" => ' . self::prepareStringValue($redirect_type);
						$items_code .= ",\n$prefix\t\t" . '"redirect_url" => ' . self::prepareStringValue($redirect_url);
						$items_code .= "\n$prefix\t" . ')';
						break;
					
					case "return_previous_record":
					case "return_next_record":
					case "return_specific_record":
						$items_code .= ",\n$prefix\t" . '"action_value" => array(';
						$items_code .= "\n$prefix\t\t" . '"records_variable_name" => ' . (substr($action_value["records_variable_name"], 0, 1) == '$' ? $action_value["records_variable_name"] : self::prepareStringValue($action_value["records_variable_name"])); //it could be a real variable with already an array inside
						$items_code .= ",\n$prefix\t\t" . '"index_variable_name" => ' . self::prepareStringValue($action_value["index_variable_name"]);
						$items_code .= "\n$prefix\t" . ')';
						break;
						
					case "check_logged_user_permissions":
						$items_code .= ",\n$prefix\t" . '"action_value" => array(';
						$items_code .= "\n$prefix\t\t" . '"all_permissions_checked" => ' . ($action_value["all_permissions_checked"] ? 1 : 0);
						
						$entity_path_var_name = trim($action_value["entity_path_var_name"]) ? trim($action_value["entity_path_var_name"]) : '$entity_path';
						$entity_path_var_name = (substr($entity_path_var_name, 0, 1) != '$' ? '$' : '') . $entity_path_var_name;
						$items_code .= ",\n$prefix\t\t" . '"entity_path" => ' . $entity_path_var_name;
						
						$luid = substr($action_value["logged_user_id"], 0, 1) == '$' ? $action_value["logged_user_id"] : self::prepareStringValue($action_value["logged_user_id"]);
						$items_code .= ",\n$prefix\t\t" . '"logged_user_id" => ' . $luid;
						
						$users_perms = $action_value["users_perms"];
						if ($users_perms) {
							$items_code .= ",\n$prefix\t\t" . '"users_perms" => array(';
							$users_perms_code = '';
							
							foreach ($users_perms as $user_perm) { 
								$users_perms_code .= ($users_perms_code ? "," : "") . "\n$prefix\t\t\tarray("
									. "\n$prefix\t\t\t\t" . '"user_type_id" => ' . self::prepareStringValue($user_perm["user_type_id"])
									. ",\n$prefix\t\t\t\t" . '"activity_id" => ' . self::prepareStringValue($user_perm["activity_id"])
								. ",\n$prefix\t\t\t)";
							}
							
							$items_code .= $users_perms_code . "\n$prefix\t\t" . ')';
						}
						
						$items_code .= "\n$prefix\t" . ')';
						break;
						
					case "code": //getting code settings
						$action_value = trim($action_value);
						$fc = substr($action_value, 0, 1);
						$lc = substr($action_value, -1);
						$at = PHPUICodeExpressionHandler::getValueType($action_value, array("non_set_type" => "string", "empty_string_type" => "string"));
						
						//action_value can be an html or a php code wrapped in PHP open/close tags. In either cases, must be wrapped in quotes.
						if (!$at && ($fc != '"' || $lc != '"') && ($fc != "'" || $lc != "'")) //if not wrapped in quotes, wrapped it.
							$at = "string";
						
						$items_code .= $action_value ? ",\n$prefix\t" . '"action_value" => ' . PHPUICodeExpressionHandler::getArgumentCode($action_value, $at) : '';
						break;
						
					case "array": //getting array settings
						$task = $WorkFlowTaskHandler->getTasksByTag("createform");
						$task = $task[0];
						$task["properties"] = array("form_input_data_type" => "array", "form_input_data" => $action_value);
						$task["obj"]->data = $task;
						
						$form_code = trim($task["obj"]->printCode(null, null));
						$form_code = substr($form_code, strlen("HtmlFormHandler::createHtmlForm(null, "), strlen(");") * -1);
						
						$items_code .= $form_code ? ",\n$prefix\t" . '"action_value" => ' . str_replace("\n", "\n$prefix\t", $form_code) : '';
						break;
						
					case "string": //getting string settings
						$string = $action_value;
						$operator = null;
						
						if (is_array($action_value)) {
							$string = $action_value["string"];
							$operator = $action_value["operator"];
						}
						
						$type = PHPUICodeExpressionHandler::getValueType($string, array("non_set_type" => "string", "empty_string_type" => "string"));
						
						if ($type == "string")
							$string = self::prepareStringValue($string);
						
						$items_code .= ",\n$prefix\t" . '"action_value" => array(';
						$items_code .= "\n$prefix\t\t" . '"string" => ' . $string;
						$items_code .= ",\n$prefix\t\t" . '"operator" => "' . $operator . '"';
						$items_code .= "\n$prefix\t" . ')';
						break;
						
					//getting variable settings. It could be a simply variable name, or a variable with $ or something like #foo[bar]# or a composite type like: "#" . $x . "[bar]#"
					case "variable": 
						$var = $action_value;
						$operator = null;
						
						if (is_array($action_value)) {
							$var = $action_value["variable"];
							$operator = $action_value["operator"];
						}
						
						$var = trim($var);
						
						if ($var) {
							$var = self::prepareVariableNameValue($var);
							
							$items_code .= ",\n$prefix\t" . '"action_value" => array(';
							$items_code .= "\n$prefix\t\t" . '"variable" => ' . $var;
							$items_code .= ",\n$prefix\t\t" . '"operator" => "' . $operator . '"';
							$items_code .= "\n$prefix\t" . ')';
						}
						break;
						
					case "sanitize_variable":
						$var = trim($action_value);
						
						if ($var) {
							$var = self::prepareVariableNameValue($var);
							$items_code .= ",\n$prefix\t" . '"action_value" => ' . $var;
						}
						break;
					
					case "validate_variable":
						$method = $action_value["method"];
						$variable = $action_value["variable"];
						$offset = $action_value["offset"];
						
						if ($method && $variable) {
							$items_code .= ",\n$prefix\t" . '"action_value" => array(';
							$items_code .= "\n$prefix\t\t" . '"method" => ' . self::prepareStringValue($method);
							$items_code .= ",\n$prefix\t\t" . '"variable" => ' . self::prepareVariableNameValue($variable);
							$items_code .= ",\n$prefix\t\t" . '"offset" => ' . self::prepareStringValue($offset);
							$items_code .= "\n$prefix\t" . ')';
						}
						break;
					
					case "list_report": //getting variable settings. It could be a simply variable name, or a variable with $ or something like #foo[bar]# or a composite type like: "#" . $x . "[bar]#"
						$var = trim($action_value["variable"]);
						
						if ($var) {
							$var = self::prepareVariableNameValue($var);
							$type = trim($action_value["type"]);
							$doc_name = trim($action_value["doc_name"]);
							$continue = trim($action_value["continue"]);
							
							$items_code .= ",\n$prefix\t" . '"action_value" => array(';
							$items_code .= "\n$prefix\t\t" . '"type" => ' . self::prepareStringValue($type);
							$items_code .= ",\n$prefix\t\t" . '"doc_name" => ' . self::prepareStringValue($doc_name);
							$items_code .= ",\n$prefix\t\t" . '"variable" => ' . $var;
							$items_code .= ",\n$prefix\t\t" . '"continue" => ' . self::prepareStringValue($continue);
							$items_code .= "\n$prefix\t" . ')';
						}
						
						break;
					
					case "call_block": 
						$block = trim($action_value["block"]);
						$project = trim($action_value["project"]);
						$block_local_variables_var_name = trim($action_value["block_local_variables_var_name"]);
						
						$items_code .= ",\n$prefix\t" . '"action_value" => array(';
						$items_code .= "\n$prefix\t\t" . '"block" => ' . self::prepareStringValue($block);
						$items_code .= ",\n$prefix\t\t" . '"project" => ' . self::prepareStringValue($project);
						$items_code .= "\n$prefix\t" . ')';
						
						break;
					
					case "call_view": 
						$view = trim($action_value["view"]);
						$project = trim($action_value["project"]);
						
						$items_code .= ",\n$prefix\t" . '"action_value" => array(';
						$items_code .= "\n$prefix\t\t" . '"view" => ' . self::prepareStringValue($view);
						$items_code .= ",\n$prefix\t\t" . '"project" => ' . self::prepareStringValue($project);
						$items_code .= "\n$prefix\t" . ')';
						
						break;
						
					case "include_file":
						$path = trim($action_value["path"]);
						$once = trim($action_value["once"]);
						
						$type = PHPUICodeExpressionHandler::getValueType($path, array("non_set_type" => "string", "empty_string_type" => "string"));
						
						if ($type == "string")
							$path = self::prepareStringValue($path);
						
						$items_code .= ",\n$prefix\t" . '"action_value" => array(';
						$items_code .= "\n$prefix\t\t" . '"path" => ' . $path;
						$items_code .= ",\n$prefix\t\t" . '"once" => ' . ($once ? 1 : 0);
						$items_code .= "\n$prefix\t" . ')';
						
						break;
					
					case "draw_graph":
						$items_code .= ",\n$prefix\t" . '"action_value" => array(';
						
						if (is_array($action_value) && array_key_exists("code", $action_value)) {
							$code = $action_value["code"];
							$fc = substr($code, 0, 1);
							$lc = substr($code, -1);
							$at = PHPUICodeExpressionHandler::getValueType($code, array("non_set_type" => "string", "empty_string_type" => "string"));
							
							//action_value can be an html or a php code wrapped in PHP open/close tags. In either cases, must be wrapped in quotes.
							if (!$at && ($fc != '"' || $lc != '"') && ($fc != "'" || $lc != "'")) //if not wrapped in quotes, wrapped it.
								$at = "string";
							
							$code = PHPUICodeExpressionHandler::getArgumentCode($code, $at);
							
							$items_code .= "\n$prefix\t\t" . '"code" => ' . $code;
						}
						else {
							//it could be a real variable with already an array inside
							$include_graph_library = self::prepareStringValue($action_value["include_graph_library"]); 
							$width = self::prepareStringValue($action_value["width"]); 
							$height = self::prepareStringValue($action_value["height"]);
							$labels_variable = trim($action_value["labels_variable"]);
							$labels_variable = $labels_variable ? self::prepareVariableNameValue($labels_variable) : self::prepareStringValue($labels_variable);
							
							$data_sets_code = '';
							$data_sets = !empty($original_action_value["data_sets"]) ? $original_action_value["data_sets"] : (!empty($action_value["data_sets"]) ? $action_value["data_sets"] : array()); //get original action_value["data_sets"] bc the keys of other options are not lowercase.
							
							if ($data_sets) {
								foreach ($data_sets as $data_set) {
									$data_set_code = '';
									
									if ($data_set)
										foreach ($data_set as $key => $value) {
											if ($key == "values_variable") {
												$value = trim($value);
												$value = $value ? self::prepareVariableNameValue($value) : self::prepareStringValue($value);
											}
											else
												$value = self::prepareStringValue($value); 
											
											if ($key)
												$data_set_code .= ($data_set_code ? "," : "") . "\n$prefix\t\t\t\t" . '"' . $key . '" => ' . $value;
										}
									
									$data_sets_code .= ($data_sets_code ? "," : "") . "\n$prefix\t\t\tarray(";
									$data_sets_code .= $data_set_code;
									$data_sets_code .= "\n$prefix\t\t\t)";
								}
							}
							
							$items_code .= "\n$prefix\t\t" . '"include_graph_library" => ' . $include_graph_library;
							$items_code .= ",\n$prefix\t\t" . '"width" => ' . $width;
							$items_code .= ",\n$prefix\t\t" . '"height" => ' . $height;
							$items_code .= ",\n$prefix\t\t" . '"labels_variable" => ' . $labels_variable;
							$items_code .= ",\n$prefix\t\t" . '"data_sets" => array(';
							$items_code .= $data_sets_code;
							$items_code .= "\n$prefix\t\t" . ')';
						}
						
						$items_code .= "\n$prefix\t" . ')';
						break;
					
					case "loop": //getting string settings
						$items_code .= ",\n$prefix\t" . '"action_value" => array(';
						$items_code .= "\n$prefix\t\t" . '"records_variable_name" => ' . (substr($action_value["records_variable_name"], 0, 1) == '$' ? $action_value["records_variable_name"] : self::prepareStringValue($action_value["records_variable_name"])); //it could be a real variable with already an array inside
						$items_code .= ",\n$prefix\t\t" . '"records_start_index" => ' . self::prepareStringValue($action_value["records_start_index"]);
						$items_code .= ",\n$prefix\t\t" . '"records_end_index" => ' . self::prepareStringValue($action_value["records_end_index"]);
						$items_code .= ",\n$prefix\t\t" . '"array_item_key_variable_name" => ' . self::prepareStringValue($action_value["array_item_key_variable_name"]);
						$items_code .= ",\n$prefix\t\t" . '"array_item_value_variable_name" => ' . self::prepareStringValue($action_value["array_item_value_variable_name"]);
						$items_code .= ",\n$prefix\t\t" . '"actions" => ' . self::getActionItemsCode($action_value["actions"], $WorkFlowTaskHandler, $prefix . "\t\t\t", $original_action_value["actions"]);
						$items_code .= "\n$prefix\t" . ')';
						break;
						
					case "group": //getting string settings
						$items_code .= ",\n$prefix\t" . '"action_value" => array(';
						$items_code .= "\n$prefix\t\t" . '"group_name" => ' . self::prepareStringValue($action_value["group_name"]);
						$items_code .= ",\n$prefix\t\t" . '"actions" => ' . self::getActionItemsCode($action_value["actions"], $WorkFlowTaskHandler, $prefix . "\t\t\t", $original_action_value["actions"]);
						$items_code .= "\n$prefix\t" . ')';
						break;
				}
				
				$items_code .= "\n$prefix" . ')';
			}
		
		return "array(" . $items_code . ($items_code ? "\n" . substr($prefix, 0, -1) : "") . ")";
	}

	public static function prepareVariableNameValue($var) {
		$fc = substr($var, 0, 1);
		$lc = substr($var, -1);
		
		if (($fc == "#" && $lc == "#") || ($fc == '"' && $lc == '"') || ($fc == "'" && $lc == "'"))
			$var = self::prepareStringValue($var);
		else if ($fc != '$') {
			$type = PHPUICodeExpressionHandler::getValueType($var, array("non_set_type" => "string", "empty_string_type" => "string"));
			
			if ($type == "string")
				$var = '$'. $var;
		}
		
		return $var;
	}

	public static function prepareStringValue($value) {
		$type = PHPUICodeExpressionHandler::getValueType($value, array("non_set_type" => "string", "empty_string_type" => "string"));
		return PHPUICodeExpressionHandler::getArgumentCode($value, $type);
		/* OLD CODE
		if (is_numeric($value))
			return $value;
		
		$fc = substr($value, 0, 1);
		$lc = substr($value, -1);
		
		if (($fc == '"' && $lc == '"') || ($fc == "'" && $lc == "'"))
			return $value;
		
		if ($fc == '$') {
			$vars = CMSPresentationFormSettingsUIHandler::getVariablesFromText($value);
			
			if ($vars[0] == trim($value))
				return $value;
		}
		
		return '"' . addcslashes($value, '"') . '"';*/
	}
}
?>
