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

include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once get_lib("org.phpframework.util.HashTagParameter");
include_once get_lib("org.phpframework.phpscript.PHPUICodeExpressionHandler");

$common_project_name = $EVC->getCommonProjectName();
$user_module_path = $EVC->getModulesPath($common_project_name) . "user/";

if (file_exists($user_module_path))
	include_once $EVC->getModulePath("user/UserUtil", $common_project_name);

class SequentialLogicalActivityCodeConverter {
	
	public static function convertActionsSettingsToCode($EVC, $webroot_cache_folder_path, $webroot_cache_folder_url, $actions_settings) {
		if (is_array($actions_settings)) {
			$allowed_tasks = array("createform", "callbusinesslogic", "callibatisquery", "callhibernatemethod", "getquerydata", "setquerydata", "callfunction", "callobjectmethod", "restconnector", "soapconnector");
			$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
			$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
			$WorkFlowTaskHandler->setAllowedTaskTags($allowed_tasks);
			$WorkFlowTaskHandler->initWorkFlowTasks();
			
			$actions_settings_bkp = $actions_settings;
			MyArray::arrKeysToLowerCase($actions_settings, true);
			
			$head_code = array();
			$actions_settings = self::replaceEscapedVariables($actions_settings);
			$actions_settings_bkp = self::replaceEscapedVariables($actions_settings_bkp);
			
			$actions_code = self::getActionsCode($EVC, $WorkFlowTaskHandler, $actions_settings, '$results', $head_code, "", $actions_settings_bkp);
			
			if ($actions_code) {
				$head_code = $head_code ? '$common_project_name = $EVC->getCommonProjectName();' . "\n" . implode("\n", array_unique($head_code)) . "\n" : "";
				$results_var_init_code = "\n" . '$results = array();' . "\n";
				
				$code = "<?php\n";
				$code .= $head_code . $results_var_init_code . $actions_code;
				$code .= "\n?>";
				
				return $code;
			}
		}
	}

	public static function getIfCode($condition_type, $condition_value, $result_var_prefix) {
		$code = "";
		
		if ($condition_type) {
			$condition_type = strtolower($condition_type);
			$is_not = strpos($condition_type, "_not_") !== false;
			
			switch($condition_type) {
				case "execute_if_var": //Only execute if variable exists
				case "execute_if_not_var": //Only execute if variable doesn't exists
					$var = trim($condition_value);
					
					if (!empty($var)) {
						$var = substr($var, 0, 1) == '$' || substr($var, 0, 2) == '@$' ? $var : '$' . $var;
						$code = ($is_not ? "" : "!") . "empty($var)";
					}
					break;
				
				case "execute_if_post_button": //Only execute if submit button was clicked via POST
				case "execute_if_not_post_button": //Only execute if submit button was not clicked via POST
				case "execute_if_get_button": //Only execute if submit button was clicked via GET
				case "execute_if_not_get_button": //Only execute if submit button was not clicked via GET
					$button_name = trim($condition_value);
					
					if ($button_name)
						$button_name = self::prepareStringValue($button_name);
						$code = ($is_not ? "" : "!") . "empty(" . (strpos($condition_type, "_get_") !== false ? '$_GET' : '$_POST') . '[' . $button_name . '])';
					break;
				
				case "execute_if_post_resource": //Only execute if resource is equal to via POST
				case "execute_if_not_post_resource": //Only execute if resource is different than via POST
				case "execute_if_get_resource": //Only execute if resource is equal to via GET
				case "execute_if_not_get_resource": //Only execute if resource is different than via GET
					$resource_name = trim($condition_value);
					
					if ($resource_name)
						$resource_name = self::prepareStringValue($resource_name);
						$var = (strpos($condition_type, "_get_") !== false ? '$_GET' : '$_POST') . '["resource"]';
						$code = $is_not ? "!isset($var) || $var != $resource_name" : "isset($var) && $var == $resource_name";
					break;
				
				case "execute_if_previous_action": //Only execute if previous action executed correctly
				case "execute_if_not_previous_action": //Only execute if previous action was not executed correctly
					$var = $result_var_prefix . '[count(' . $result_var_prefix . ') - 1]';
					$code = ($is_not ? "" : "!") . "empty($var)";
					break;
				
				case "execute_if_condition": //Only execute if condition is valid
				case "execute_if_not_condition": //Only execute if condition is invalid
				case "execute_if_code": //Only execute if code is valid
				case "execute_if_not_code": //Only execute if code is invalid
					if (is_numeric($condition_value))
						$code = $is_not ? "empty($condition_value)" : $condition_value;
					else if ($condition_value === true)
						$code = $is_not ? "false" : "true";
					else if ($condition_value === false)
						$code = $is_not ? "true" : "false";
					else if (is_array($condition_value) || is_object($condition_value))
						$code = $is_not ? (empty($condition_value) ? "false" : "true") : (empty($condition_value) ? "true" : "false");
					else if (!empty($condition_value))
						$code = ($is_not ? "" : "!") . "empty($condition_value)";
					break;
			}
			
			$code = $code ? "if ($code) {" : "";
		}
		
		return $code;
	}

	public static function getActionsCode($EVC, $WorkFlowTaskHandler, $actions, $result_var_prefix, &$head_code, $prefix = "", $original_actions = null) {
		$code = "";
		
		if (is_array($actions))
			foreach ($actions as $idx => $action_settings)
				$code .= self::getActionCode($EVC, $WorkFlowTaskHandler, $action_settings, $result_var_prefix, $head_code, $prefix, isset($original_actions[$idx]) ? $original_actions[$idx] : null);
			
		return $code;
	}

	public static function getActionCode($EVC, $WorkFlowTaskHandler, $action_settings, $result_var_prefix, &$head_code, $prefix = "", $original_actions = null) {
		$code = "";
		
		$result_var_name = isset($action_settings["result_var_name"]) ? trim($action_settings["result_var_name"]) : "";
		$action_type = isset($action_settings["action_type"]) ? strtolower($action_settings["action_type"]) : "";
		$action_value = isset($action_settings["action_value"]) ? $action_settings["action_value"] : null;
		$original_action_value = isset($original_actions["action_value"]) ? $original_actions["action_value"] : array();
		$condition_type = isset($action_settings["condition_type"]) ? strtolower($action_settings["condition_type"]) : "";
		$condition_value = isset($action_settings["condition_value"]) ? $action_settings["condition_value"] : null;
		
		$result_var_code = "";
		if ($result_var_name) 
			$result_var_code = $result_var_prefix . "[" . self::prepareStringValue($result_var_name) . "] = ";
		
		$if = self::getIfCode($condition_type, $condition_value, $result_var_prefix);
		
		if ($if) 
			$prefix .= "\t";
		
		switch ($action_type) {
			case "html": //getting design form html settings
				$task = $WorkFlowTaskHandler->getTasksByTag("createform");
				$task = isset($task[0]) ? $task[0] : null;
				$task["properties"] = array(
					"form_settings_data_type" => isset($action_value["form_settings_data_type"]) ? $action_value["form_settings_data_type"] : null, 
					"form_settings_data" => isset($action_value["form_settings_data"]) ? $action_value["form_settings_data"] : null,
					"form_input_data_type" => '',
					"form_input_data" => '$results',
				);
				$task["obj"]->data = $task;
				
				$task_code = trim($task["obj"]->printCode(null, null));
				
				if ($task_code) {
					$head_code[] = 'include_once get_lib("org.phpframework.util.web.html.HtmlFormHandler");';
					
					$code .= $prefix . ($result_var_code ? $result_var_code : "echo ") . str_replace("\n", "\n$prefix", $task_code) . "\n";
				}
				
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
				$action_value = self::replaceActionValuesHashTagWithVariables($action_value);
				$action_value = self::searchParametersForVariablesWithWrongType($action_value);
				
				$task = $WorkFlowTaskHandler->getTasksByTag($action_type);
				$task = $task[0];
				$task["properties"] = $action_value;
				$task["obj"]->data = $task;
				
				$task_code = trim($task["obj"]->printCode(null, null));
				
				if ($task_code) {
					if ($action_type == "callfunction" || $action_type == "callobjectmethod") {
						$path = isset($action_value["include_file_path"]) ? trim($action_value["include_file_path"]) : "";
						$path = self::replaceActionValuesHashTagWithVariables($path);
						
						if ($path) {
							$path = PHPUICodeExpressionHandler::getArgumentCode($path, isset($action_value["include_file_path_type"]) ? $action_value["include_file_path_type"] : null);
							$once = !empty($action_value["include_once"]);
							
							$code .= $prefix . 'include' . ($once ? '_once' : '') . ' ' . $path . ";\n";
						}
					}
					
					$code .= $prefix . $result_var_code . str_replace("\n", "\n$prefix", $task_code) . "\n";
				}
				
				break;
			
			case "insert":
			case "update":
			case "delete":
			case "select":
			case "count":
			case "procedure":
			case "getinsertedid":
				$action_value = self::replaceActionValuesHashTagWithVariables($action_value);
				$action_value_options = isset($action_value["options"]) ? $action_value["options"] : null;
				$action_value_options_type = isset($action_value["options_type"]) ? $action_value["options_type"] : null;
				
				//prepare options
				if ($action_value_options_type == "array") {
					$options = is_array($action_value_options) ? $action_value_options : array();
					
					if (!empty($action_value["db_driver"]))
						$options["db_driver"] = array(
							"key" => "db_driver",
							"key_type" => "string",
							"value" => $action_value["db_driver"],
							"value_type" => "string",
						);
					
					$code .= $prefix . '$options = ' . trim(WorkFlowTask::getArrayString($options, $prefix)) . ';' . "\n";
				}
				else if ($action_value_options_type == "variable") {
					$code .= $prefix . '$options = ' . PHPUICodeExpressionHandler::getArgumentCode($action_value_options, $action_value_options_type) . ';' . "\n";
					
					if (!empty($action_value["db_driver"]))
						$code .= $prefix . '$options["db_driver"] = ' . PHPUICodeExpressionHandler::getArgumentCode($action_value["db_driver"], "string") . ';' . "\n";
				}
				else if ($action_value_options && !empty($action_value["db_driver"])) //if string, overwrites it with db_driver. If no db_driver discards string
					$code .= $prefix . '$options = array("db_driver" => ' . self::prepareStringValue($action_value["db_driver"]) . ');' . "\n";
				else
					$code .= $prefix . '$options = array();' . "\n";
				
				//prepare sql
				$broker = '$EVC->getBroker(' . self::prepareStringValue(isset($action_value["dal_broker"]) ? $action_value["dal_broker"] : null) . ')';
				
				if ($action_type == "getinsertedid")
					$code .= $prefix . $result_var_code . $broker . '->getInsertedId($options);' . "\n";
				else {
					$sql = isset($action_value["sql"]) ? $action_value["sql"] : null;
					
					if (!empty($action_value["table"]) && $action_type != "procedure") {
						$data = array(
							"type" => $action_type,
							"main_table" => $action_value["table"],
							"attributes" => isset($action_value["attributes"]) ? $action_value["attributes"] : null,
							"conditions" => isset($action_value["conditions"]) ? $action_value["conditions"] : null,
						);
						
						$code .= $prefix . '$sql_data = ' . trim(var_export($data, true)) . ';' . "\n";
						$code .= $prefix . '$sql = ' . $broker . '->getFunction("convertObjectToSQL", array($sql_data), $options);' . "\n";
					}
					
					//prepare get or set sql to DB
					if ($action_type == "select" || $action_type == "count" || $action_type == "procedure") {
						$code .= $prefix . 'unset($options["return_type"]); //just in case if it exists' . "\n";
						$code .= $prefix . '$result = ' . $broker . '->getData($sql, $options);' . "\n";
						
						if ($action_type == "count") {
							$code .= $prefix . '$result = isset($result["result"]) ? $result["result"] : null;' . "\n";
							$code .= $prefix . "\n";
							$code .= $prefix . 'if ($result && is_array($result[0])) ' . "\n";
							$code .= $prefix . '	' . $result_var_code . 'array_shift(array_values($result[0]));' . "\n";
							$code .= $prefix . 'else' . "\n";
							$code .= $prefix . '	' . $result_var_code . 'null;' . "\n";
						}
						else
							$code .= $prefix . $result_var_code . 'isset($result["result"]) ? $result["result"] : null;' . "\n";
					}
					else
						$code .= $prefix . $result_var_code . $broker . '->setData($sql, $options);' . "\n";
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
				$action_value = self::replaceActionValuesHashTagWithVariables($action_value);
				
				$message = isset($action_value["message"]) ? $action_value["message"] : null;
				$ok_message = strpos($action_type, "_ok_") ? $message : null;
				$error_message = strpos($action_type, "_error_") ? $message : null;
				$redirect_url = strpos($action_type, "_redirect") && isset($action_value["redirect_url"]) ? $action_value["redirect_url"] : null;
				
				$head_code[] = 'include_once $EVC->getModulePath("common/CommonModuleUI", $common_project_name);';
				echo "ok_message:$ok_message\nerror_message:$error_message\nredirect_url:$redirect_url\n";
				
				$code .= $prefix . $result_var_code . '\CommonModuleUI::getModuleMessagesHtml($EVC, ' . self::prepareStringValue($ok_message) . ', ' . self::prepareStringValue($error_message) . ', ' . self::prepareStringValue($redirect_url) . ');' . "\n";
				
				if (strpos($action_type, "_die"))
					$code .= $prefix . "die();\n";
				else if (strpos($action_type, "_stop"))
					$code .= $prefix . "return;\n";
				break;
				
			case "alert_msg":
			case "alert_msg_and_stop":
			case "alert_msg_and_redirect":
				$action_value = self::replaceActionValuesHashTagWithVariables($action_value);
				$message = isset($action_value["message"]) ? $action_value["message"] : null;
				$redirect_url = strpos($action_type, "_redirect") && isset($action_value["redirect_url"]) ? $action_value["redirect_url"] : null;
				
				$code .= $prefix . 'echo \'<script>'
					. ($message ? addcslashes('alert("' . addcslashes($message, '"') . '");', "'") : '')
					. ($redirect_url ? addcslashes('document.location="' . addcslashes($redirect_url, '"') . '";', "'") : '')
				. '</script>\';' . "\n";
				
				if (strpos($action_type, "_stop"))
					$code .= "return;\n";
				
				break;
				
			case "redirect": //getting redirect settings
				$redirect_type = null;
				$redirect_url = null;
				
				if (is_array($action_value)) {
					$action_value = self::replaceActionValuesHashTagWithVariables($action_value);
					$redirect_type = isset($action_value["redirect_type"]) ? $action_value["redirect_type"] : null;
					$redirect_url = isset($action_value["redirect_url"]) ? $action_value["redirect_url"] : null;
				}
				else
					$redirect_url = $action_value;
				
				if ($redirect_type == "server" || $redirect_type == "server_client")
					$code .= $prefix . 'header("Location: ' . addcslashes($redirect_url, '"') . '");' . "\n";
				
				if (!$redirect_type || $redirect_type == "client" || $redirect_type == "server_client")
					$code .= $prefix . ($result_var_code ? $result_var_code : "echo ") . '\'<script>document.location="' . addcslashes($redirect_url, '"') . '";</script>\';' . "\n";
				
				break;
			
			case "return_previous_record":
			case "return_next_record":
			case "return_specific_record":
				$action_value = self::replaceActionValuesHashTagWithVariables($action_value);
				
				$records_variable_name = isset($action_value["records_variable_name"]) ? trim($action_value["records_variable_name"]) : "";
				$index_variable_name = isset($action_value["index_variable_name"]) ? trim($action_value["index_variable_name"]) : "";
				
				//it could be a real variable with already an array inside
				if (substr($records_variable_name, 0, 1) == '$')
					$code .= $prefix . '$records = isset(' . $records_variable_name . ') ? : ' . $records_variable_name . ' null;' . "\n";
				else if (substr($records_variable_name, 0, 2) == '@$')
					$code .= $prefix . '$records = isset(' . substr($records_variable_name, 0, 1) . ') ? : ' . $records_variable_name . ' null;' . "\n";
				else
					$code .= $prefix . '$records = isset($results[' . self::prepareStringValue($records_variable_name) . ']) ? : $results[' . self::prepareStringValue($records_variable_name) . '] : null;' . "\n";
				
				$code .= $prefix . "\n";
				$code .= $prefix . 'if (is_array($records)) {' . "\n";
				$code .= $prefix . '	$index = ' . self::prepareStringValue($index_variable_name) . ";\n";
				$code .= $prefix . '	$index = $index && !is_numeric($index) && is_string($index) ? (isset($_GET[$index]) ? $_GET[$index] : null) : $index;' . "\n";
				$code .= $prefix . '	$index = is_numeric($index) ? $index : 0;' . "\n";
				$code .= $prefix . "\n";
				
				if ($action_type == "return_previous_record")
					$code .= $prefix . '	$index--;'. "\n";
				else if ($action_type == "return_next_record")
					$code .= $prefix . '	$index++;'. "\n";
				
				$code .= $prefix . "\n";
				$code .= $prefix . "\t" . $result_var_code . 'isset($records[$index]) ? $records[$index] : null;' . "\n";
				$code .= $prefix . "}\n";
				break;
				
			case "check_logged_user_permissions":
				$action_value = self::replaceActionValuesHashTagWithVariables($action_value);
				
				$all_permissions_checked = !empty($action_value["all_permissions_checked"]);
				$users_perms = isset($action_value["users_perms"]) ? $action_value["users_perms"] : null;
				$entity_path_var_name = isset($action_value["entity_path_var_name"]) && trim($action_value["entity_path_var_name"]) ? trim($action_value["entity_path_var_name"]) : '$entity_path';
				$entity_path_var_name = (substr($entity_path_var_name, 0, 1) != '$' && substr($entity_path_var_name, 0, 2) != '@$' ? '$' : '') . $entity_path_var_name;
				$entity_path = $entity_path_var_name;
				$logged_user_id = isset($action_value["logged_user_id"]) ? $action_value["logged_user_id"] : null;
				
				if ($users_perms && class_exists("UserUtil")) {
					//prepare users_perms
					$exists_public_access = false;
					$new_users_perms = array();
					
					foreach ($users_perms as $user_perm) {
						if (isset($user_perm["user_type_id"]) && $user_perm["user_type_id"] == UserUtil::PUBLIC_USER_TYPE_ID) {
							$exists_public_access = true;
							break;
						}
						else
							$new_users_perms[] = $user_perm;
					}
					
					if (!$exists_public_access || $all_permissions_checked)  {
						if ($logged_user_id && $new_users_perms && $entity_path) {
							$users_perms = $new_users_perms; 
							
							$head_code[] = 'include_once $EVC->getModulePath("object/ObjectUtil", $common_project_name);';
							$head_code[] = 'include_once $EVC->getModulePath("user/UserUtil", $common_project_name);';
							
							$code .= $prefix . '$logged_user_id = ' . self::prepareStringValue($logged_user_id) . ';' . "\n";
							$code .= $prefix . '$object_id = str_replace(APP_PATH, "", ' . self::prepareStringValue($entity_path) . ');' . "\n";
							$code .= $prefix . '$user_has_permission = false;' . "\n";
							
							$code .= $prefix . "\n";
							$code .= $prefix . 'if ($logged_user_id && $object_id) {' . "\n";
							$code .= $prefix . '	$user_has_permission = true;' . "\n";
							
							$code .= $prefix . "\n";
							$code .= $prefix . '	$object_type_id = \ObjectUtil::PAGE_OBJECT_TYPE_ID;' . "\n";
							$code .= $prefix . '	$object_id = \HashCode::getHashCodePositive($object_id);' . "\n";
							$code .= $prefix . '	$brokers = $EVC->getBrokers();' . "\n";
							$code .= $prefix . "\n";
							$code .= $prefix . '	$utaos = \UserUtil::getUserTypeActivityObjectsByUserIdAndConditions($brokers, $logged_user_id, array("object_type_id" => $object_type_id, "object_id" => $object_id), null);' . "\n";
							
							$code .= $prefix . "\n";
							$code .= $prefix . '	if ($utaos) {' . "\n";
							$code .= $prefix . '		$entered = false;' . "\n";
							
							$code .= $prefix . "\n";
							$code .= $prefix . '		$users_perms = ' . trim(WorkFlowTask::getArrayString($users_perms, "$prefix\t\t")) . ';' . "\n";
							$code .= $prefix . '		$all_permissions_checked = ' . ($all_permissions_checked ? "true" : "false") . ';' . "\n";
							
							$code .= $prefix . "\n";
							$code .= $prefix . '		foreach ($users_perms as $user_perm) ' . "\n";
							$code .= $prefix . '			if (isset($user_perm["user_type_id"]) && is_numeric($user_perm["user_type_id"]) && isset($user_perm["activity_id"]) && is_numeric($user_perm["activity_id"])) {' . "\n";
							$code .= $prefix . '				if (!$entered && !$all_permissions_checked) //only happens on the first iteration and if $all_permissions_checked is false' . "\n";
							$code .= $prefix . '					$user_has_permission = false;' . "\n";
									
							$code .= $prefix . "\n";
							$code .= $prefix . '				$entered = true;' . "\n";
									
							$code .= $prefix . "\n";
							$code .= $prefix . '				$user_perm_exists = false;' . "\n";
							$code .= $prefix . '				foreach ($utaos as $utao) {' . "\n";
							$code .= $prefix . '					$utao_user_type_id = isset($utao["user_type_id"]) ? $utao["user_type_id"] : null;' . "\n";
							$code .= $prefix . '					$utao_activity_id = isset($utao["activity_id"]) ? $utao["activity_id"] : null;' . "\n";
							$code .= $prefix . '					' . "\n";
							$code .= $prefix . '					if ($utao_user_type_id == $user_perm["user_type_id"] && $utao_activity_id == $user_perm["activity_id"]) {' . "\n";
							$code .= $prefix . '						$user_perm_exists = true;' . "\n";
							$code .= $prefix . '						break;' . "\n";
							$code .= $prefix . '					}' . "\n";
							$code .= $prefix . '				}' . "\n";
									
							$code .= $prefix . "\n";
							$code .= $prefix . '				if ($all_permissions_checked && !$user_perm_exists) {' . "\n";
							$code .= $prefix . '					$user_has_permission = false;' . "\n";
							$code .= $prefix . '					break;' . "\n";
							$code .= $prefix . '				}' . "\n";
							$code .= $prefix . '				else if (!$all_permissions_checked && $user_perm_exists) {' . "\n";
							$code .= $prefix . '					$user_has_permission = true;' . "\n";
							$code .= $prefix . '					break;' . "\n";
							$code .= $prefix . '				}' . "\n";
							$code .= $prefix . '			}' . "\n";
							$code .= $prefix . '	}' . "\n";
							$code .= $prefix . '}' . "\n";
							
							if ($result_var_code)
								$code .= $prefix . $result_var_code . '$user_has_permission;' . "\n";
						}
					}
				}
				break;
				
			case "code": //getting code settings
				$action_value = trim($action_value);
				
				if ($action_value) {
					if ($result_var_code)
						$code .= $prefix . "ob_start(null, 0);\n";
					
					$start = 0;
					do {
						$pos = strpos($action_value, "<?", $start);
						
						if ($pos !== false) {
							$html = substr($action_value, $start, $pos - $start);
							if ($html)
								$html = $prefix . 'echo ' . PHPUICodeExpressionHandler::getArgumentCode($html, "string") . ";\n";
							
							$pos += substr($action_value, $pos, 5) == "<?php" ? 5 : 2;
							$end = strpos($action_value, "?>", $pos);
							$end = $end !== false ? $end : strlen($action_value);
							$php = substr($action_value, $pos, $end - $pos);
							$php = $php ? $prefix . str_replace("\n", "\n$prefix", trim($php)) . "\n" : "";
							
							$code .= $html . $php;
							$start = $end + 2;
						}
					}
					while ($pos !== false);
					
					$last_html = substr($action_value, $start);
					if ($last_html)
						$code .= $prefix . 'echo ' . PHPUICodeExpressionHandler::getArgumentCode($last_html, "string") . ";\n";
					
					if ($result_var_code) {
						$code .= $prefix . $result_var_code . "ob_get_contents();\n";
						$code .= $prefix . "ob_end_clean();\n";
					}
				}
				break;
				
			case "array": //getting array settings
				$action_value = self::replaceActionValuesHashTagWithVariables($action_value);
				
				$task = $WorkFlowTaskHandler->getTasksByTag("createform");
				$task = isset($task[0]) ? $task[0] : null;
				$task["properties"] = array("form_input_data_type" => "array", "form_input_data" => $action_value);
				$task["obj"]->data = $task;
				
				$task_code = trim($task["obj"]->printCode(null, null));
				$task_code = substr($task_code, strlen("HtmlFormHandler::createHtmlForm(null, "), strlen(");") * -1);
				
				if ($task_code)
					$code .= "$prefix$result_var_code" . str_replace("\n", "\n$prefix", $task_code) . ";\n";
				break;
			
			case "string": //getting string settings
				if ($result_var_code) {
					$action_value = self::replaceActionValuesHashTagWithVariables($action_value);
					$string = null;
					$operator = null;
					
					if (is_array($action_value)) {
						$string = self::prepareStringValue(isset($action_value["string"]) ? $action_value["string"] : null);
						$operator = isset($action_value["operator"]) ? $action_value["operator"] : null;
					}
					else
						$string = self::prepareStringValue($action_value);
					
					if (!$operator)
						$operator = '=';
					
					$code .= $prefix . rtrim(substr(rtrim($result_var_code), 0, -1))  . " $operator $string;\n"; //rtrim and substr is to remove the operator from result_var_code
				}
				break;
				
			//getting variable settings. It could be a simply variable name, or a variable with $ or something like #foo[bar]# or a composite type like: "#" . $x . "[bar]#"
			case "variable":
				if ($result_var_code) {
					$action_value = self::replaceActionValuesHashTagWithVariables($action_value);
					$var = $action_value;
					$operator = null;
					
					if (is_array($action_value)) {
						$var = isset($action_value["variable"]) ? $action_value["variable"] : null;
						$operator = isset($action_value["operator"]) ? $action_value["operator"] : null;
					}
					
					if (!$operator)
						$operator = '=';
					
					$var = trim($var);
					
					if ($var) {
						$var = self::prepareVariableNameValue($var);
						$code .= $prefix . rtrim(substr(rtrim($result_var_code), 0, -1))  . " $operator $var;\n"; //rtrim and substr is to remove the operator from result_var_code
					}
				}
				break;
			
			case "sanitize_variable":
				$action_value = self::replaceActionValuesHashTagWithVariables($action_value);
				$var = trim($action_value);
				
				if ($var && $result_var_code) {
					$var = self::prepareVariableNameValue($var);
					$code .= "$prefix$result_var_code$var;\n";
				}
				break;
				
			case "validate_variable":
				$action_value = self::replaceActionValuesHashTagWithVariables($action_value);
				$method = isset($action_value["method"]) ? $action_value["method"] : null;
				$variable = isset($action_value["variable"]) ? $action_value["variable"] : null;
				$offset = isset($action_value["offset"]) ? $action_value["offset"] : null;
				
				if ($method && $variable) {
					$is_check_method = strpos($method, "TextValidator::check") === 0;
					
					if (!$is_check_method || strlen($offset)) {
						$variable = self::prepareVariableNameValue($variable);
						
						$code = "";
						
						if (strpos($method, "TextValidator::") === 0)
							$code .= 'include_once get_lib("org.phpframework.util.text.TextValidator");' . "\n";
						else if (strpos($method, "ObjTypeHandler::") === 0)
							$code .= 'include_once get_lib("org.phpframework.object.ObjTypeHandler");' . "\n";
						
						$code .= "$prefix$result_var_code$method($variable";
						
						if ($is_check_method)
							$code .= ", $offset";
						
						$code .= ");\n";
					}
				}
				
				break;
				
			case "list_report":
				$var = isset($action_value["variable"]) ? $action_value["variable"] : null;
				$var = self::replaceActionValuesHashTagWithVariables($var);
				$var = trim($var);
				
				if ($var) {
					$var = self::prepareVariableNameValue($var);
					$type = isset($action_value["type"]) ? $action_value["type"] : null;
					$doc_name = isset($action_value["doc_name"]) ? $action_value["doc_name"] : null;
					$continue = isset($action_value["continue"]) ? $action_value["continue"] : null;
					$content_type = $type == "xls" ? "application/vnd.ms-excel" : "text/plain";
					
					$code .= $prefix . 'header("Content-Type: ' . $content_type . '");' . "\n";
					$code .= $prefix . 'header("Content-Disposition: attachment; filename=\'' . $doc_name . '.' . $type . '\'");' . "\n";
					$code .= $prefix . "\n";
					$code .= $prefix . '$list = ' . $var . ';' . "\n";
					$code .= $prefix . '$str = "";' . "\n";
					$code .= $prefix . "\n";
					$code .= $prefix . 'if ($list && is_array($list)) {' . "\n";
					$code .= $prefix . '	$first_row = $list[ array_keys($list)[0] ];' . "\n";
					$code .= $prefix . "	\n";
					$code .= $prefix . '	if (is_array($first_row)) {' . "\n";
					$code .= $prefix . '		$columns = array_keys($first_row);' . "\n";
					$code .= $prefix . '		$columns_length = count($columns);' . "\n";
					$code .= $prefix . "		\n";
					$code .= $prefix . '		//prepare columns' . "\n";
					$code .= $prefix . '		for ($i = 0; $i < $columns_length; $i++)' . "\n";
					$code .= $prefix . '			$str .= ($i > 0 ? "\t" : "") . $columns[$i];' . "\n";
					$code .= $prefix . "		\n";
					$code .= $prefix . '		//prepare rows' . "\n";
					$code .= $prefix . '		if ($str) {' . "\n";
					$code .= $prefix . '			$str .= "\n";' . "\n";
					$code .= $prefix . "			\n";
					$code .= $prefix . '			foreach ($list as $row)' . "\n";
					$code .= $prefix . '				if (is_array($row)) {' . "\n";
					$code .= $prefix . '					for ($i = 0; $i < $columns_length; $i++)' . "\n";
					$code .= $prefix . '						$str .= ($i > 0 ? "\t" : "") . $row[ $columns[$i] ];' . "\n";
					$code .= $prefix . "					\n";
					$code .= $prefix . '					$str .= "\n";' . "\n";
					$code .= $prefix . "				}\n";
					$code .= $prefix . "		}\n";
					$code .= $prefix . "	}\n";
					$code .= $prefix . "}\n";
					
					if ($result_var_code)
							$code .= "$prefix$result_var_code" . "\$str;\n";
					else
						$code .= $prefix . "echo \$str;\n";
					
					if ($continue == "die")
						$code .= $prefix . "die();\n";
					else if ($continue == "stop")
						$code .= $prefix . "return;\n";
				}
				break;
				
			case "call_block":
				$block = isset($action_value["block"]) ? trim($action_value["block"]) : "";
				$block = self::replaceActionValuesHashTagWithVariables($block);
				
				if ($block) {
					$project = isset($action_value["project"]) ? trim($action_value["project"]) : "";
					$project = self::replaceActionValuesHashTagWithVariables($project);
					
					$code .= $prefix . '$block_local_variables = array();' . "\n";
					$code .= $prefix . 'include $EVC->getBlockPath(' . self::prepareStringValue($block) . ($project ? ', ' . self::prepareStringValue($project) : '') . ');' . "\n";
					
					if ($result_var_code)
						$code .= "$prefix$result_var_code" . "\$EVC->getCMSLayer()->getCMSBlockLayer()->getCurrentBlock();\n";
					else
						$code .= $prefix . "echo \$EVC->getCMSLayer()->getCMSBlockLayer()->getCurrentBlock();\n";
				}
				break;
				
			case "call_view":
				$view = isset($action_value["view"]) ? trim($action_value["view"]) : "";
				$view = self::replaceActionValuesHashTagWithVariables($view);
				
				if ($view) {
					$project = isset($action_value["project"]) ? trim($action_value["project"]) : "";
					$project = self::replaceActionValuesHashTagWithVariables($project);
					
					$code .= $prefix . 'include $EVC->getViewPath(' . self::prepareStringValue($view) . ($project ? ', ' . self::prepareStringValue($project) : '') . ');' . "\n";
					
					if ($result_var_code)
						$code .= "$prefix$result_var_code" . "\$EVC->getCMSLayer()->getCMSViewLayer()->getCurrentView();\n";
					else
						$code .= $prefix . "echo \$EVC->getCMSLayer()->getCMSViewLayer()->getCurrentView();\n";
				}
				break;
				
			case "include_file":
				$path = isset($action_value["path"]) ? trim($action_value["path"]) : "";
				$path = self::replaceActionValuesHashTagWithVariables($path);
				
				if ($path) {
					$path = self::prepareStringValue($path);
					$once = !empty($action_value["once"]);
					
					$code .= $prefix . ($result_var_code ? $result_var_code : "") . 'include' . ($once ? '_once' : '') . ' ' . $path . ";\n";
				}
				break;
			
			case "draw_graph":
				if (is_array($action_value)) {
					if (array_key_exists("code", $action_value))
						$code .= "?>\n" . self::replaceActionValuesHashTagWithVariables($action_value["code"]) . "\n<?php\n"; //no prefix here bc is html
					else {
						$include_graph_library = isset($action_value["include_graph_library"]) ? self::replaceActionValuesHashTagWithVariables($action_value["include_graph_library"]) : null;
						$width = isset($action_value["width"]) ? self::replaceActionValuesHashTagWithVariables($action_value["width"]) : null;
						$height = isset($action_value["height"]) ? self::replaceActionValuesHashTagWithVariables($action_value["height"]) : null;
						$labels_variable = isset($action_value["labels_variable"]) ? self::replaceActionValuesHashTagWithVariables($action_value["labels_variable"]) : null;
						
						$labels_variable_code = $labels_variable ? self::prepareVariableNameValue($labels_variable) : self::prepareStringValue($labels_variable);
						$data_sets_code = '';
						$default_type = null;
						
						$data_sets = !empty($original_action_value["data_sets"]) ? $original_action_value["data_sets"] : (!empty($action_value["data_sets"]) ? $action_value["data_sets"] : array()); //get original action_value["data_sets"] bc the keys of other options are not lowercase.
						
						if ($data_sets) {
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
									//echo "<pre>";print_r($parsed_data_set);print_r($composite_keys_obj);echo "</pre>";
									
									$data_set_code = '';
									
									foreach ($parsed_data_set as $key => $value) {
										if (is_array($value) && is_array($composite_keys_obj) && array_key_exists($key, $composite_keys_obj)) {
											$data_set_code .= ($data_set_code ? ",\n                 " : "") . $key . ': {';
											$data_set_code .= self::getDrawGraphDataSetCode($value, $composite_keys_obj[$key], "                     ");
											$data_set_code .= ($data_set_code ? "\n                 " : "") . '}';
										}
										else {
											$value = self::replaceActionValuesHashTagWithVariables($value);
											
											if ($key) {
												$option_name = !empty($options_names[$key]) ? $options_names[$key] : $key;
												$is_valid = !empty($value) || is_numeric($value) || !isset($options_names[$key]);
												
												if ($key == "type") {
													if (!$default_type)
														$default_type = $value;
												
													$is_valid = $is_valid && $value != $default_type;
													$value = self::prepareStringValue($value);
												}
												else if ($key == "border_width") {
													$is_valid = $is_valid || is_numeric($value);
													$value = self::prepareStringValue($value);
												}
												else if ($is_valid) {
													if ($key == "values_variable")
														$value = $value ? self::prepareVariableNameValue($value) : self::prepareStringValue($value);
													else
														$value = self::prepareStringValue($value);
												}
												
												if ($is_valid)
													$data_set_code .= ($data_set_code ? ",\n                 " : "") . $option_name . ': \' . json_encode(' . $value . ') . \'';
											}
										}
									}
										
									$data_sets_code .= '
		     {
		         ' . $data_set_code . '
		     },';
		     					}
							}
						}
						
						$rand = rand(0, 1000);
						
						$code .= $prefix . 'echo \'';
						
						if ($include_graph_library == "cdn_even_if_exists")
							$code .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>' . "\n\n";
						else if ($include_graph_library == "cdn_if_not_exists")
							$code .= '<script>
	if (typeof Chart != "function")
		document.write("<scr" + "ipt src=\"https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js\"></scr" + "ipt>");
	</script>' . "\n\n";
						
						$code .= '
	<canvas id="my_chart_' . $rand . '"' . ($width || is_numeric($width) ? ' width="\' . ' . self::prepareStringValue($width) . ' . \'"' : '') . ($height || is_numeric($height) ? ' height="\' . ' . self::prepareStringValue($height) . ' . \'"' : '') . '></canvas>

	<script>
	var canvas_' . $rand . ' = document.getElementById("my_chart_' . $rand . '");
	var myChart_' . $rand . ' = new Chart(canvas_' . $rand . ', {
	    type: "\' . ' . self::prepareStringValue($default_type) . ' . \'",
	    data: {
		   ' . ($labels_variable_code || is_numeric($labels_variable_code) ? 'labels: \' . json_encode(' . $labels_variable_code . ') . \',' : '') . '
		   datasets: [' . $data_sets_code . '
		   ]
	    }
	});
	</script>\';';
					}
				}
				break;
			
			case "loop": //getting string settings
				if (!empty($action_value["actions"])) {
					if ($result_var_code)
						$code .= $prefix . "ob_start(null, 0);\n\n";
					
					$records_variable_name = isset($action_value["records_variable_name"]) ? self::replaceActionValuesHashTagWithVariables(trim($action_value["records_variable_name"])) : null;
					$records_start_index = isset($action_value["records_start_index"]) ? self::replaceActionValuesHashTagWithVariables(trim($action_value["records_start_index"])) : null;
					$records_end_index = isset($action_value["records_end_index"]) ? self::replaceActionValuesHashTagWithVariables(trim($action_value["records_end_index"])) : null;
					$array_item_key_variable_name = isset($action_value["array_item_key_variable_name"]) ? self::replaceActionValuesHashTagWithVariables(trim($action_value["array_item_key_variable_name"])) : null;
					$array_item_value_variable_name = isset($action_value["array_item_value_variable_name"]) ? self::replaceActionValuesHashTagWithVariables(trim($action_value["array_item_value_variable_name"])) : null;
					
					//it could be a real variable with already an array inside
					if (substr($records_variable_name, 0, 1) == '$')
						$code .= $prefix . '$records = isset(' . $records_variable_name . ') ? ' . $records_variable_name . ' : null;' . "\n";
					else if (substr($records_variable_name, 0, 2) == '@$')
						$code .= $prefix . '$records = isset(' . substr($records_variable_name, 0, 2). ') ? ' . $records_variable_name . ' : null;' . "\n";
					else
						$code .= $prefix . '$records = isset($results[' . self::prepareStringValue($records_variable_name) . ']) ? $results[' . self::prepareStringValue($records_variable_name) . '] : null;' . "\n";
					
					$code .= $prefix . "\n";
					$code .= $prefix . 'if (is_array($records)) {' . "\n";
					$code .= $prefix . '	$records_start_index = ' . self::prepareStringValue($records_start_index) . ";\n";
					$code .= $prefix . '	$records_start_index = is_numeric($records_start_index) ? $records_start_index : 0;' . "\n";
					$code .= $prefix . '	$records_end_index = ' . self::prepareStringValue($records_end_index) . ";\n";
					$code .= $prefix . '	$records_end_index = is_numeric($records_end_index) ? $records_end_index : count($records);' . "\n";
					
					$code .= $prefix . "\n";
					$code .= $prefix . '	$i = 0;' . "\n";
					$code .= $prefix . '	foreach ($records as $k => $v) {' . "\n";
					$code .= $prefix . '		if ($i >= $records_end_index)' . "\n";
					$code .= $prefix . '			break;' . "\n";
					$code .= $prefix . '		else if ($i >= $records_start_index) {' . "\n";
					
					if ($array_item_key_variable_name)
						$code .= $prefix . '		$' . $array_item_key_variable_name . ' = $k;' . "\n";
					
					if ($array_item_value_variable_name)
						$code .= $prefix . '		$' . $array_item_value_variable_name . ' = $v;' . "\n";
					
					$code .= self::getActionsCode($EVC, $WorkFlowTaskHandler, $action_value["actions"], $result_var_prefix, $head_code, "$prefix\t\t", isset($original_action_value["actions"]) ? $original_action_value["actions"] : null);
					$code .= $prefix . '		}' . "\n";
					$code .= $prefix . "\n";
					$code .= $prefix . '		++$i;' . "\n";
					$code .= $prefix . '	}' . "\n";
					$code .= $prefix . "}\n";
					
					if ($result_var_code) {
						$code .= $prefix . "\n";
						$code .= $prefix . $result_var_code . "ob_get_contents();\n";
						$code .= $prefix . "ob_end_clean();\n";
					}
				}
				break;
				
			case "group": //getting string settings
				if (!empty($action_value["actions"])) {
					if ($result_var_code)
						$code .= $prefix . "ob_start(null, 0);\n\n";
					
					$group_name = isset($action_value["group_name"]) ? self::replaceActionValuesHashTagWithVariables(trim($action_value["group_name"])) : "";
					
					if ($group_name) {
						$group_name = $result_var_prefix . "[" . self::prepareStringValue($group_name) . "] = ";
						$code .= self::getActionsCode($EVC, $WorkFlowTaskHandler, $action_value["actions"], $group_name, $head_code, $prefix, isset($original_action_value["actions"]) ? $original_action_value["actions"] : null);
					}
					else
						$code .= self::getActionsCode($EVC, $WorkFlowTaskHandler, $action_value["actions"], $result_var_prefix, $head_code, $prefix, isset($original_action_value["actions"]) ? $original_action_value["actions"] : null);
					
					if ($result_var_code) {
						$code .= $prefix . "\n";
						$code .= $prefix . $result_var_code . "ob_get_contents();\n";
						$code .= $prefix . "ob_end_clean();\n";
					}
				}
				break;
		}
		
		if ($code) {
			$comment = "/*** ACTION: " . strtoupper($action_type) . "***/\n";
			$result_var_extra_code = $result_var_code ? "$prefix" . '$' . $result_var_name . " = &" . trim(str_replace(" = ", "", $result_var_code)) . ";\n" : "";
			$action_code = $code;
			$code = "";
			
			if ($if) {
				$prefix = substr($prefix, 0, -1);
				
				$code .= "\n$prefix$comment";
				$code .= $prefix . $if . "\n";
				$code .= $action_code;
				$code .= $result_var_extra_code;
				$code .= $prefix . "}\n";
			}
			else
				$code .= "\n$prefix$comment$action_code$result_var_extra_code";
		}
		
		return $code;
	}
	
	private static function getDrawGraphDataSetCode($parsed_data_set, $composite_keys_obj, $prefix) {
		$data_set_code = "";
		
		if ($parsed_data_set)
			foreach ($parsed_data_set as $key => $value) {
				$data_set_code .= ($data_set_code ? "," : "") . "\n$prefix$key: ";
				
				if (is_array($value) && is_array($composite_keys_obj) && array_key_exists($key, $composite_keys_obj)) {
					$data_set_code .= '{';
					$data_set_code .= self::getDrawGraphDataSetCode($value[$key], $composite_keys_obj[$key], $prefix . "    ");
					$data_set_code .= "\n$prefix}";
				}
				else {
					$value = self::replaceActionValuesHashTagWithVariables($value);
					
					if (is_string($value))
						$value = self::prepareStringValue($value);
					else
						$value = var_export($value, true);
					
					$data_set_code .= '\' . json_encode(' . $value . ') . \'';
				}
			}
		
		return $data_set_code;
	}

	public static function replaceEscapedVariables($value) {
		if (is_array($value))
			foreach ($value as $key => $item)
				$value[$key] = self::replaceEscapedVariables($item);
		else if ($value && is_string($value) && strpos($value, '$') !== false) {
			$odq = $osq = false;
			$ophpt = true;
			$t = strlen($value);
			$new_value = "";
			
			for ($i = 0; $i < $t; $i++) {
				$char = $value[$i];
				$next_char = isset($value[$i + 1]) ? $value[$i + 1] : null;
				
				if ($char == "<" && $next_char == "?" && !$odq && !$osq)
					$ophpt = true;
				else if ($char == "?" && $next_char == ">" && !$odq && !$osq)
					$ophpt = false;
				else if ($char == '"' && $ophpt && !$osq && !TextSanitizer::isCharEscaped($value, $i))
					$odq = !$odq;
				else if ($char == "'" && $ophpt && !$odq && !TextSanitizer::isCharEscaped($value, $i))
					$osq = !$osq;
				else if ($char == '$' && ($next_char == "{" || preg_match("/\w/u", $next_char)) && $ophpt && TextSanitizer::isCharEscaped($value, $i)) //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars. 
					$new_value = substr($new_value, 0, -1);
				
				$new_value .= $char;
			}
			
			$value = $new_value;
		}
		
		return $value;
	}

	public static function replaceActionValuesHashTagWithVariables($value) {
		if (is_array($value))
			foreach ($value as $key => $item)
				$value[$key] = self::replaceActionValuesHashTagWithVariables($item);
		else if ($value && is_string($value) && strpos($value, "#") !== false) {
			$regex = HashTagParameter::HTML_HASH_TAG_PARAMETER_FULL_REGEX;
			preg_match_all($regex, $value, $matches, PREG_OFFSET_CAPTURE);//PREG_PATTERN_ORDER 
			
			if ($matches[1]) {
				$global_vars = array("_POST", "_GET", "_REQUEST", "_FILES", "_COOKIE", "_ENV", "_SERVER", "_SESSION", "GLOBALS");
				$t = count($matches[1]);
				
				for ($i = 0; $i < $t; $i++) {
					$m = $matches[1][$i][0];
					$replacement = "";
					//echo "m($value):$m<br>";
					
					$exists_global_var = false;
					foreach ($global_vars as $gv)
						if (stripos($m, $gv) === 0) {
							$exists_global_var = true;
							break;
						}
					
					if (strpos($m, "[") !== false) { //if value == #[0]name# or #[$idx - 1][name]#, returns $results[0]["name"] or $results[$idx - 1]["name"]
						preg_match_all("/([^\[\]]+)/u", trim($m), $sub_matches, PREG_PATTERN_ORDER); //'/u' means converts to unicode.
						$sub_matches = isset($sub_matches[1]) ? $sub_matches[1] : null;
						
						if ($sub_matches) {
							//echo "1:";print_r($sub_matches);
							
							if ($exists_global_var)
								$gv = array_shift($sub_matches);
							
							$t2 = count($sub_matches);
							for ($j = 0; $j < $t2; $j++)
								$sub_matches[$j] = self::prepareStringValue($sub_matches[$j]);
							
							$replacement = ($exists_global_var ? '$' . strtoupper($gv) : '$results') . '[' . implode('][', $sub_matches) . ']';
						}
					}
					else if ($exists_global_var) //if #_POST# or #_GET#
						$replacement = '$' . $m;
					else //if $value == #name#, returns $results["name"]
						$replacement = '$results["' . $m . '"]';
					
					if ($replacement)
						$value = str_replace("#$m#", $replacement, $value);
				}
			}
		}
		
		return $value;
	}

	public static function searchParametersForVariablesWithWrongType($value) {
		if (is_array($value))
			foreach ($value as $key => $item) 
				if (is_string($item) && array_key_exists($key . "_type", $value) && $value[$key . "_type"] == "string" && PHPUICodeExpressionHandler::isSimpleVariable($item))
					$value[$key . "_type"] = "";
				else if (is_array($item))
					$value[$key] = self::searchParametersForVariablesWithWrongType($item);
		
		return $value;
	}

	public static function prepareStringValue($value) {
		if ($value && substr($value, 0, 2) == '\\$' && PHPUICodeExpressionHandler::isSimpleVariable(substr($value, 1))) //is escaped var
			return substr($value, 1);
		
		$type = PHPUICodeExpressionHandler::getValueType($value, array("non_set_type" => "string", "empty_string_type" => "string"));
		return PHPUICodeExpressionHandler::getArgumentCode($value, $type);
	}
	
	public static function prepareVariableNameValue($var) {
		$fc = substr($var, 0, 1);
		$lc = substr($var, -1);
		
		if (($fc == "#" && $lc == "#") || ($fc == '"' && $lc == '"') || ($fc == "'" && $lc == "'"))
			$var = self::prepareStringValue($var);
		else if ($fc != '$' && substr($var, 0, 2) != '@$') {
			$type = PHPUICodeExpressionHandler::getValueType($var, array("non_set_type" => "string", "empty_string_type" => "string"));
			
			if ($type == "string")
				$var = '$'. $var;
		}
		
		return $var;
	}
}
?>
