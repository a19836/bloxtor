<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once get_lib("org.phpframework.util.HashCode");
include_once get_lib("org.phpframework.util.web.html.HtmlFormHandler");
include_once get_lib("org.phpframework.phpscript.PHPUICodeExpressionHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once $EVC->getUtilPath("WorkFlowDataAccessHandler");
include_once $EVC->getUtilPath("WorkFlowDBHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

class CMSPresentationFormSettingsUIHandler {
	
	public static $editable_input_types = array("text", "password", "file", "search", "url", "email", "tel", "number", "range", "date", "month", "week", "time", "datetime", "datetime-local", "color", "hidden", "checkbox", "radio", "select", "textarea");
	
	public static function getFormSettings($user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $db_layer_file, $dal_broker, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $type, $tables_alias, $settings, $is_ajax = false, $existent_generic_javascript = "", $output_var_name = false, $permissions = false, &$settings_php_codes_list = array()) {
		//print_r($settings);
		//echo "$user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $db_layer_file, $dal_broker, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $type, $tables_alias, $settings, $is_ajax, $existent_generic_javascript, $output_var_name, $permissions\n\n";
		
		$form_settings = null;
		$path = str_replace("../", "", $path);//for security reasons
		
		if ($path) {
			$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
			$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
			
			if ($PEVC) {
				$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
				$PHPVariablesFileHandler->startUserGlobalVariables();
				
				$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
				
				if ($type == "diagram") {//TRYING TO GET THE DB TABLES FROM THE TASK FLOW
					$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $db_driver);
					$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
				}
				else {//TRYING TO GET THE DB TABLES DIRECTLY FROM DB
					if (!$db_layer_file) {
						$db_layer_file = WorkFlowBeansFileHandler::getLayerDBDriverProps($user_global_variables_file_path, $user_beans_folder_path, $PEVC->getPresentationLayer(), $db_driver);
						$db_layer_file = $db_layer_file && isset($db_layer_file[1]) ? $db_layer_file[1] : null;
					}
					
					if ($db_layer_file) {
						$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
						$tasks = $WorkFlowDBHandler->getUpdateTaskDBDiagram($db_layer_file, $db_driver);
						$WorkFlowDataAccessHandler->setTasks($tasks);
					}
				}
				
				$tables = $WorkFlowDataAccessHandler->getTasksAsTables();
				//print_r($tables);die();
				
				if ($tables_alias) 
					foreach ($tables_alias as $table_name => $table_alias)
						$tables_alias[$table_name] = strtolower(str_replace(array("-", " "), "_", $table_alias));
				
				$statuses = array();
				
				//Preparing settings
				if (is_array($settings) && $settings) {
					//print_r($settings);die();
						
					$allowed_tasks = array("callbusinesslogic", "callibatisquery", "callhibernatemethod", "getquerydata", "setquerydata");
					$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
					$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
					$WorkFlowTaskHandler->setAllowedTaskTags($allowed_tasks);
					$WorkFlowTaskHandler->initWorkFlowTasks();
					
					$actions_settings = array();
					$generic_javascript = "";
					$ajax_vars_to_output = array();
					
					$has_data_access_layer = self::hasDataAccessLayer($PEVC);
					
					foreach ($settings as $table_name => $table_settings) {
						MyArray::arrKeysToLowerCase($table_settings, true);
						$task_table_name = WorkFlowDBHandler::getTableTaskRealNameFromTasks($tables, $table_name);
						
						$attributes = isset($table_settings["attributes"]) ? $table_settings["attributes"] : null;
						
						if ($attributes) {
							//be sure that the $attributes have the right attributes names case sensitive, this is, if there is a DB attribute with an upper case character and the $attributes contains only lower case values, the php code bellow won't work. So we must be sure that the $attributes var contains the real DB attributes names!
							$attributes = array_flip($attributes);
							self::prepareAttributesSettingsWithRealAttributeNames($attributes, $tables, $table_name);
							$attributes = array_flip($attributes);
						}
						else
							$attributes = !empty($tables[$task_table_name]) ? array_keys($tables[$task_table_name]) : null;
						
						//print_r($attributes);
						
						if ($attributes) {
							$panel_type = isset($table_settings["panel_type"]) ? $table_settings["panel_type"] : null;
							$panel_id = isset($table_settings["panel_id"]) ? $table_settings["panel_id"] : null;
							$panel_class = isset($table_settings["panel_class"]) ? $table_settings["panel_class"] : null;
							$panel_previous_html = isset($table_settings["panel_previous_html"]) ? $table_settings["panel_previous_html"] : null;
							$panel_next_html = isset($table_settings["panel_next_html"]) ? $table_settings["panel_next_html"] : null;
							$form_type = isset($table_settings["form_type"]) ? $table_settings["form_type"] : null;
							$broker_settings = isset($table_settings["brokers"]) ? $table_settings["brokers"] : null;
							$actions_props = isset($table_settings["actions"]) ? $table_settings["actions"] : null;
							$conditions = isset($table_settings["conditions"]) ? $table_settings["conditions"] : null;
							$table_parent = isset($table_settings["table_parent"]) ? $table_settings["table_parent"] : null; //if table_parent exists, it means that the table_name is related with the table_parent, this is, inner join the table_name with the table_parent. This is only used if there is a data_access_layer and if there is not $broker_settings["get_all"] and $broker_settings["count"]. In this case the appropriate sql will be created.
							$pagination = isset($table_settings["pagination"]) ? $table_settings["pagination"] : null;
							$pagination = $pagination ? $pagination : array();
							
							$get_all = isset($broker_settings["get_all"]) ? $broker_settings["get_all"] : null;
							$count = isset($broker_settings["count"]) ? $broker_settings["count"] : null;
							$get = isset($broker_settings["get"]) ? $broker_settings["get"] : null;
							$insert = isset($broker_settings["insert"]) ? $broker_settings["insert"] : null;
							$update = isset($broker_settings["update"]) ? $broker_settings["update"] : null;
							$update_pks = isset($broker_settings["update_pks"]) ? $broker_settings["update_pks"] : null;
							$delete = isset($broker_settings["delete"]) ? $broker_settings["delete"] : null;
							$relationships = isset($broker_settings["relationships"]) ? $broker_settings["relationships"] : null;
							
							$table_alias = isset($tables_alias[$table_name]) ? $tables_alias[$table_name] : null;
							$tn = self::getParsedTableName($table_alias ? $table_alias : $table_name); //$table name can have schema
							$tn_label = self::getName($tn);
							$tn_plural = self::getPlural($tn);
							
							$generic_javascript .= !$is_ajax ? self::getAjaxJavascript($actions_props, $existent_generic_javascript . $generic_javascript, $panel_type, $pagination) : "";
							
							if ($form_type != "ptl") //only if not ptl
								$settings_php_codes_list = self::getSettingsPHPCodeList($actions_props, $settings_php_codes_list);
							
							$pks = array();
							$pks_auto_increment = array();
							
							if (!empty($tables[$task_table_name]))
								foreach ($tables[$task_table_name] as $attr_name => $attr) 
									if (!empty($attr["primary_key"])) {
										$pks[] = $attr_name;
										
										if (WorkFlowDataAccessHandler::isAutoIncrementedAttribute($attr))
											$pks_auto_increment[] = $attr_name;
									}
							
							$rows_per_page = isset($pagination["rows_per_page"]) ? $pagination["rows_per_page"] : null;
							
							if ($panel_type == "multiple_form" && !is_numeric($rows_per_page))
								$rows_per_page = 1;
							
							$rows_per_page = $rows_per_page ? $rows_per_page : 100;
							$is_pagination_active = !$pagination || !empty($pagination["active"]);
							
							$child_tables = self::getForeignChildTables($tables, $table_name, $tables_alias, $pks);
							//print_r($child_tables);die();
							
							$table_actions_settings = array();
							$pagination_used = false;
							$is_panel_list = $panel_type == "list_table" || $panel_type == "list_form";
							
							//if any actions exists, check if attributes have pks, otherwise add them but as hidden fields. If this is not done, then we will have an interface without PK and with actions, which will give errors when we execute the insert/update/delete actions.
							if (
								!empty($actions_props["multiple_insert_update"]) || 
								!empty($actions_props["multiple_insert"]) || 
								!empty($actions_props["multiple_update"]) || 
								!empty($actions_props["multiple_delete"]) || 
								!empty($actions_props["single_insert"]) || 
								!empty($actions_props["single_update"]) || 
								!empty($actions_props["single_delete"])
							)
								self::prepareAttributesWithPKs($attributes, $pks, $actions_props);
							
							//prepare permissions
							$access_permissions = array();
							$write_permissions = array();
							$delete_permissions = array();
							
							if ($permissions) 
								foreach ($permissions as $permission) 
									if (!empty($permission["user_type_id"]) && $permission["user_type_id"] != UserUtil::PUBLIC_USER_TYPE_ID) {
										$activity_id = isset($permission["activity_id"]) ? $permission["activity_id"] : null;
										
										if ($activity_id == UserUtil::ACCESS_ACTIVITY_ID)
											$access_permissions[] = $permission;
										else if ($activity_id == UserUtil::WRITE_ACTIVITY_ID)
											$write_permissions[] = $permission;
										else if ($activity_id == UserUtil::DELETE_ACTIVITY_ID)
											$delete_permissions[] = $permission;
									}
							
							if ($access_permissions) {
								$table_actions_settings[] = array(
									"result_var_name" => "has_access_permission",
									"action_type" => "check_logged_user_permissions",
									"condition_type" => "execute_always",
									"condition_value" => "",
									"action_value" => array(
										"all_permissions_checked" => 0,
										"entity_path" => "\"\" . \$entity_path . \"\"",
										"logged_user_id" => "\"\" . (isset(\$GLOBALS[\"logged_user_id\"]) ? \$GLOBALS[\"logged_user_id\"] : null) . \"\"",
										"users_perms" => $access_permissions
									)
								);
								
								$table_actions_settings[] = array(
									"result_var_name" => "",
									"action_type" => "alert_msg_and_stop",
									"condition_type" => "execute_if_not_var",
									"condition_value" => "has_access_permission",
									"action_value" => array(
										"message" => "Access Denied!!!",
									)
								);
							}
							
							//Preparing actions
							//prepare $actions_props["attributes_settings"], this is, verify if exists available values for each attribute, by checking if exists a related db table. Then create correspondent actions and add the variable name with all table's records to $actions_props["attributes_settings"][attribute_anme]["available_values"].
							$attributes_settings = isset($actions_props["attributes_settings"]) ? $actions_props["attributes_settings"] : null;
							
							self::prepareAttributeAvailableValues($table_actions_settings, $attributes_settings, $has_data_access_layer, $settings, $tables, $table_name, $broker_settings, $dal_broker, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $type);
							
							//prepare table: list table and list form
							if ($is_panel_list || $is_ajax) { //If table list (normal table) or ajax
								//prepare multiple actions
								$is_multiple_insert_update = !empty($actions_props["multiple_insert_update"]) && empty($actions_props["multiple_insert_update"]["action_type"]);
								$is_multiple_insert = !empty($actions_props["multiple_insert"]) && empty($actions_props["multiple_insert"]["action_type"]);
								$is_multiple_update = !empty($actions_props["multiple_update"]) && empty($actions_props["multiple_update"]["action_type"]);
								$is_multiple_delete = !empty($actions_props["multiple_delete"]) && empty($actions_props["multiple_delete"]["action_type"]);
								
								if ($is_multiple_insert_update || $is_multiple_insert || $is_multiple_update || $is_multiple_delete) {
									$insert_update_delete_actions = array();
									$entered_in_multiple_insert = $entered_in_multiple_update = $entered_in_multiple_delete = false;
									
									//create generic data var
									if ($is_ajax)
										$insert_update_delete_actions[] = array(
											"result_var_name" => "data",
											"action_type" => "variable",
											"condition_type" => "execute_always",
											"condition_value" => "",
											"action_value" => "#_POST[$tn]#"
										);
									
									//prepare write permissions
									if ($write_permissions && ($is_multiple_insert || $is_multiple_insert_update || $is_multiple_update) && ($insert || $update || $has_data_access_layer)) 
										$insert_update_delete_actions[] = array(
											"result_var_name" => "has_write_permission",
											"action_type" => "check_logged_user_permissions",
											"condition_type" => "execute_if_condition",
											"condition_value" => "\\\$_POST", // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
											"action_value" => array(
												"all_permissions_checked" => 0,
												"entity_path" => "\"\" . \$entity_path . \"\"",
												"logged_user_id" => "\"\" . (isset(\$GLOBALS[\"logged_user_id\"]) ? \$GLOBALS[\"logged_user_id\"] : null) . \"\"",
												"users_perms" => $write_permissions
											)
										);
									
									//prepare multiple insert
									if (($is_multiple_insert || $is_multiple_insert_update) && ($insert || $has_data_access_layer)) {
										$insert_actions = array();
										$loop_actions = array();
										$group_actions = array();
										$entered_in_multiple_insert = true;
										
										//prepare main insert status
										$insert_update_delete_actions[] = array(
											"result_var_name" => "insert_status",
											"action_type" => "variable",
											"condition_type" => "execute_always",
											"condition_value" => "",
											"action_value" => "\"\" . (\$_POST[\"{$tn}_add" . ($is_multiple_insert_update ? "_save" : "") . "\"] && \$_POST[\"multiple_insert_selection\"][\"$tn\"] ? 1 : 0) . \"\""
										);
										
										if ($write_permissions)
											$insert_update_delete_actions[] = array(
												"result_var_name" => "insert_status",
												"action_type" => "variable",
												"condition_type" => "execute_if_not_var",
												"condition_value" => "has_write_permission",
												"action_value" => "0"
											);
										
										//Preparing broker if not exist
										if (!$insert || empty($insert["brokers_layer_type"])) {
											$attrs = array();
											foreach ($attributes as $attr) 
												if (!in_array($attr, $pks_auto_increment))
													$attrs[] = array("column" => $attr, "value" => '#' . $tn . '[' . $attr . ']#');
											
											$insert = array(
												"brokers_layer_type" => "insert",
												"dal_broker" => $dal_broker,
												"db_driver" => $db_driver,
												"db_type" => $type,
												"table" => $table_name,
												"attributes" => $attrs
											);
										}
										else { //transforms $_POST[$tn][...] to #tn[...]#
											self::prepareGlobalVarsInArrayWithSingleTableVariable($table_name, $tn, $insert, '$_POST');
											self::prepareGlobalVarsInArray($insert);
										}
										
										//Preparing code to be passed to the form module
										self::prepareBrokerSettings($insert, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $tables);
										
										//Preparing insert previous code - preparing the post attributes
										$code = self::getInsertActionPreviousCode($tables, $table_name, $tn, $attributes, $insert, $WorkFlowTaskHandler);
										if ($code)
											$group_actions[] = array(
												"result_var_name" => "",
												"action_type" => "code",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => $code
											);
										
										//Preparing action
										$group_actions[] = array(
											"result_var_name" => $tn . "_status",
											"action_type" => isset($insert["brokers_layer_type"]) ? $insert["brokers_layer_type"] : null,
											"condition_type" => "execute_always",
											"condition_value" => "",
											"action_value" => $insert
										);
										
										//Preparing insert next code - getting the inserted id
										$broker_external_var_name = null;
										$code = self::getInsertActionNextCode($tn, $insert, $pks_auto_increment, $WorkFlowTaskHandler, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $broker_external_var_name);
										if ($code) {
											if ($broker_external_var_name) //adding external var that the broker uses
												$group_actions[] = array(
													"result_var_name" => $broker_external_var_name,
													"action_type" => "variable",
													"condition_type" => "execute_always",
													"condition_value" => "",
													"action_value" => '$' . $broker_external_var_name
												);
											
											$group_actions[] = array(
												"result_var_name" => "",
												"action_type" => "code",
												"condition_type" => "execute_if_var",
												"condition_value" => $tn . "_status",
												"action_value" => $code
											);
										}
										
										//prepare main status
										$group_actions[] = array(
											"result_var_name" => "insert_status",
											"action_type" => "string",
											"condition_type" => "execute_if_not_var",
											"condition_value" => $tn . "_status",
											"action_value" => "0"
										);
										
										//prepare data variable
										if ($is_ajax)
											$group_actions[] = array(
												"result_var_name" => "",
												"action_type" => "code",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => "'<?
\$data[\"\$row_index\"] = \$$tn;
\$data[\"\$row_index\"][\"{$tn}_status\"] = \${$tn}_status;
?>'"
											);
										
										//prepare loop
										//prepare loop - table item var
										$loop_actions[] = array(
											"result_var_name" => $tn,
											"action_type" => "variable",
											"condition_type" => "execute_always",
											"condition_value" => "",
											"action_value" => "#_POST[\"$tn\"][\\\$row_index]#"
										);
										
										//prepare loop - add group
										$loop_actions[] = array(
											"result_var_name" => "",
											"action_type" => "group",
											"condition_type" => "execute_if_var",
											"condition_value" => $tn,
											"action_value" => array(
												"group_name" => "",
												"actions" => $group_actions
											)
										);
										
										//prepare main insert actions
										$insert_actions[] = array(
											"result_var_name" => "",
											"action_type" => "loop",
											"condition_type" => "execute_always",
											"condition_value" => "",
											"action_value" => array(
												"records_variable_name" => "\$_POST[\"multiple_insert_selection\"][\"$tn\"]",
												"records_start_index" => "",
												"records_end_index" => "",
												"array_item_key_variable_name" => "row_index",
												"array_item_value_variable_name" => "",
												"actions" => $loop_actions
											)
										);
										
										//set main status var
										$insert_actions[] = array(
											"result_var_name" => $tn . "_status",
											"action_type" => "string",
											"condition_type" => "execute_always",
											"condition_value" => "",
											"action_value" => "#insert_status#"
										);
										
										$insert_update_delete_actions[] = array(
											"result_var_name" => "",
											"action_type" => "group",
											"condition_type" => "execute_if_condition",
											"condition_value" => ($write_permissions ? "\\\$has_write_permission && " : "") . "\\\$_POST[\"{$tn}_add" . ($is_multiple_insert_update ? "_save" : "") . "\"] && \\\$_POST[\"multiple_insert_selection\"][\"$tn\"]", // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
											"action_value" => array(
												"group_name" => "",
												"actions" => $insert_actions
											)
										);
									}
									
									if ($is_multiple_update || $is_multiple_insert_update || $is_multiple_delete) {
										$update_delete_actions = array();
										$loop_actions = array();
										
										//prepare loop - table item var
										$loop_actions[] = array(
											"result_var_name" => $tn,
											"action_type" => "variable",
											"condition_type" => "execute_always",
											"condition_value" => "",
											"action_value" => "#_POST[\"$tn\"][\\\$row_index]#"
										);
										
										//prepare multiple update
										if (($is_multiple_update || $is_multiple_insert_update) && ($update || $has_data_access_layer)) {
											$group_actions = array();
											$entered_in_multiple_update = true;
											
											//prepare main update status
											$insert_update_delete_actions[] = array(
												"result_var_name" => "update_status",
												"action_type" => "variable",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => "\"\" . (\$_POST[\"{$tn}" . ($is_multiple_insert_update ? "_add" : "") . "_save\"] && \$_POST[\"multiple_selection\"][\"$tn\"] ? 1 : 0) . \"\""
											);
											
											if ($write_permissions)
												$insert_update_delete_actions[] = array(
													"result_var_name" => "update_status",
													"action_type" => "variable",
													"condition_type" => "execute_if_not_var",
													"condition_value" => "has_write_permission",
													"action_value" => "0"
												);
												
											//Preparing broker if not exist
											if (!$update || empty($update["brokers_layer_type"])) {
												$attrs = array();
												foreach ($attributes as $attr) 
													if (!in_array($attr, $pks_auto_increment)) //auto incremented pks are hidden
														$attrs[] = array("column" => $attr, "value" => '#' . $tn . '[' . $attr . ']#');
												
												$conds = array();
												foreach ($pks as $pk)
													$conds[] = array("column" => $pk, "value" => '#' . $tn . '[' . $pk . ']#');
												
												$update = array(
													"brokers_layer_type" => "update",
													"dal_broker" => $dal_broker,
													"db_driver" => $db_driver,
													"db_type" => $type,
													"table" => $table_name,
													"attributes" => $attrs,
													"conditions" => $conds
												);
											}
											else { //transforms $_POST[$tn][...] to #tn[...]#
												self::prepareGlobalVarsInArrayWithSingleTableVariable($table_name, $tn, $update, '$_POST');
												self::prepareGlobalVarsInArray($update);
											}
											
											//Preparing broker pks if not exist
											if (!$update_pks || empty($update_pks["brokers_layer_type"])) {
												$attrs = array();
												foreach ($pks as $pk) 
													$attrs[] = array("column" => $pk, "value" => '#' . $tn . '[new_' . $pk . ']#');
												
												$conds = array();
												foreach ($pks as $pk)
													$conds[] = array("column" => $pk, "value" => '#' . $tn . '[old_' . $pk . ']#');
												
												$update_pks = array(
													"brokers_layer_type" => "update",
													"dal_broker" => $dal_broker,
													"db_driver" => $db_driver,
													"db_type" => $type,
													"table" => $table_name,
													"attributes" => $attrs,
													"conditions" => $conds
												);
											}
											else { //transforms $_POST[$tn][...] to #tn[...]#
												self::prepareGlobalVarsInArrayWithSingleTableVariable($table_name, $tn, $update_pks, '$_POST');
												self::prepareGlobalVarsInArray($update_pks);
											}
											
											//Preparing code to be passed to the form module
											self::prepareBrokerSettings($update, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $tables);
											self::prepareBrokerSettings($update_pks, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $tables);
											
											//Preparing update previous code - preparing the post attributes
											$code = self::getUpdateActionPreviousCode($tables, $table_name, $tn, $attributes, $pks, $update, $WorkFlowTaskHandler);
											if ($code)
												$group_actions[] = array(
													"result_var_name" => "",
													"action_type" => "code",
													"condition_type" => "execute_always",
													"condition_value" => "",
													"action_value" => $code
												);
											
											//Preparing update pks
											$condition_to_validate = '\\$_POST["' . $tn . '_save_pks"]';
											foreach ($pks as $pk)
												$condition_to_validate .= ' && \\$' . $tn . '["old_' . $pk . '"] && \\$' . $tn . '["new_' . $pk . '"]';
											
											$group_actions[] = array(
												"result_var_name" => $tn . "_status",
												"action_type" => isset($update_pks["brokers_layer_type"]) ? $update_pks["brokers_layer_type"] : null,
												"condition_type" => "execute_if_condition",
												"condition_value" => $condition_to_validate,
												"action_value" => $update_pks
											);
											
											//Preparing update
											$condition_to_validate = '(!\\$_POST["' . $tn . '_save_pks"] || \\$' . $tn . '_status)'; //$_POST["save_pks"] is set in the getUpdateActionPreviousCode
											foreach ($pks as $pk)
												$condition_to_validate .= ' && \\$' . $tn . '["' . $pk . '"]';
											
											$group_actions[] = array(
												"result_var_name" => $tn . "_status",
												"action_type" => isset($update["brokers_layer_type"]) ? $update["brokers_layer_type"] : null,
												"condition_type" => "execute_if_condition",
												"condition_value" => $condition_to_validate,
												"action_value" => $update
											);
											
											//prepare main status
											$group_actions[] = array(
												"result_var_name" => "update_status",
												"action_type" => "string",
												"condition_type" => "execute_if_not_var",
												"condition_value" => $tn . "_status",
												"action_value" => "0"
											);
											
											//prepare data variable
											if ($is_ajax)
												$group_actions[] = array(
													"result_var_name" => "",
													"action_type" => "code",
													"condition_type" => "execute_always",
													"condition_value" => "",
													"action_value" => "'<?
\$data[\"\$row_index\"] = \$$tn;
\$data[\"\$row_index\"][\"{$tn}_status\"] = \${$tn}_status;
?>'"
												);
											
											//add group
											$loop_actions[] = array(
												"result_var_name" => "",
												"action_type" => "group",
												"condition_type" => "execute_if_condition",
												"condition_value" => "\\\$_POST[\"{$tn}" . ($is_multiple_insert_update ? "_add" : "") . "_save\"] && \\\${$tn}", // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
												"action_value" => array(
													"group_name" => "",
													"actions" => $group_actions
												)
											);
										}
										
										if ($is_multiple_delete && ($delete || $has_data_access_layer)) {
											$group_actions = array();
											$entered_in_multiple_delete = true;
											
											//prepare delete permissions
											if ($delete_permissions)
												$insert_update_delete_actions[] = array(
													"result_var_name" => "has_delete_permission",
													"action_type" => "check_logged_user_permissions",
													"condition_type" => "execute_if_condition",
													"condition_value" => "\\\$_POST", // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
													"action_value" => array(
														"all_permissions_checked" => 0,
														"entity_path" => "\"\" . \$entity_path . \"\"",
														"logged_user_id" => "\"\" . (isset(\$GLOBALS[\"logged_user_id\"]) ? \$GLOBALS[\"logged_user_id\"] : null) . \"\"",
														"users_perms" => $delete_permissions
													)
												);
											
											//prepare main delete status
											$insert_update_delete_actions[] = array(
												"result_var_name" => "delete_status",
												"action_type" => "variable",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => "\"\" . (\$_POST[\"{$tn}_delete\"] && \$_POST[\"multiple_selection\"][\"$tn\"] ? 1 : 0) . \"\""
											);
											
											if ($delete_permissions)
												$insert_update_delete_actions[] = array(
													"result_var_name" => "delete_status",
													"action_type" => "variable",
													"condition_type" => "execute_if_not_var",
													"condition_value" => "has_delete_permission",
													"action_value" => "0"
												);
												
											//Preparing broker if not exist
											if (!$delete || empty($delete["brokers_layer_type"])) {
												$conds = array();
												foreach ($pks as $pk)
													$conds[] = array("column" => $pk, "value" => '#' . $tn . '[' . $pk . ']#');
												
												$delete = array(
													"brokers_layer_type" => "delete",
													"dal_broker" => $dal_broker,
													"db_driver" => $db_driver,
													"db_type" => $type,
													"table" => $table_name,
													"conditions" => $conds
												);
											}
											else { //transforms $_POST[$tn][...] to #tn[...]#
												self::prepareGlobalVarsInArrayWithSingleTableVariable($table_name, $tn, $delete, '$_POST');
												self::prepareGlobalVarsInArray($delete);
											}
											
											//Preparing code to be passed to the form module
											self::prepareBrokerSettings($delete, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $tables);
											
											//Preparing update previous code - preparing the post attributes
											$code = self::getDeleteActionPreviousCode($tn, $pks);
											if ($code)
												$group_actions[] = array(
													"result_var_name" => "",
													"action_type" => "code",
													"condition_type" => "execute_always",
													"condition_value" => "",
													"action_value" => $code
												);
											
											//Preparing actions
											$condition_to_validate = '';
											foreach ($pks as $pk)
												$condition_to_validate .= ($condition_to_validate ? ' && ' : '') . '\\$' . $tn . '["' . $pk . '"]';
											$group_actions[] = array(
												"result_var_name" => $tn . "_status",
												"action_type" => isset($delete["brokers_layer_type"]) ? $delete["brokers_layer_type"] : null,
												"condition_type" => "execute_if_condition",
												"condition_value" => $condition_to_validate,
												"action_value" => $delete
											);
											
											//prepare main status
											$group_actions[] = array(
												"result_var_name" => "delete_status",
												"action_type" => "string",
												"condition_type" => "execute_if_not_var",
												"condition_value" => $tn . "_status",
												"action_value" => "0"
											);
											
											//prepare data variable
											if ($is_ajax)
												$group_actions[] = array(
													"result_var_name" => "",
													"action_type" => "code",
													"condition_type" => "execute_always",
													"condition_value" => "",
													"action_value" => "'<?
\$data[\"\$row_index\"] = \$$tn;
\$data[\"\$row_index\"][\"{$tn}_status\"] = \${$tn}_status;
?>'"
												);
											
											//add group
											$loop_actions[] = array(
												"result_var_name" => "",
												"action_type" => "group",
												"condition_type" => "execute_if_condition",
												"condition_value" => "\\\$_POST[\"{$tn}_delete\"] && \\\${$tn}", // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
												"action_value" => array(
													"group_name" => "",
													"actions" => $group_actions
												)
											);
										}
										
										if ($entered_in_multiple_update || $entered_in_multiple_delete) {
											//prepare update and delete actions
											$update_delete_actions[] = array(
												"result_var_name" => "",
												"action_type" => "loop",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => array(
													"records_variable_name" => "\$_POST[\"multiple_selection\"][\"$tn\"]",
													"records_start_index" => "",
													"records_end_index" => "",
													"array_item_key_variable_name" => "row_index",
													"array_item_value_variable_name" => "",
													"actions" => $loop_actions
												)
											);
											
											//prepare table status var
											$cond = $entered_in_multiple_update ? "(!\\\$_POST[\"{$tn}" . ($is_multiple_insert_update ? "_add" : "") . "_save\"] || \\\$update_status)" : "";
											$cond .= $entered_in_multiple_delete ? ($cond ? " && " : "") . "(!\\\$_POST[\"{$tn}_delete\"] || \\\$delete_status)" : "";
											if ($cond) {
												$update_delete_actions[] = array(
													"result_var_name" => $tn . "_status",
													"action_type" => "string",
													"condition_type" => "execute_if_condition",
													"condition_value" => $cond,
													"action_value" => "1"
												);
												$update_delete_actions[] = array(
													"result_var_name" => $tn . "_status",
													"action_type" => "string",
													"condition_type" => "execute_if_not_condition",
													"condition_value" => $cond,
													"action_value" => "0"
												);
											}
											else
												$update_delete_actions[] = array(
													"result_var_name" => $tn . "_status",
													"action_type" => "string",
													"condition_type" => "execute_always",
													"condition_value" => "",
													"action_value" => "0"
												);
											
											$cond = "(";
											if ($write_permissions)
												$cond .= "(\\\$has_write_permission && \\\$_POST[\"{$tn}" . ($is_multiple_insert_update ? "_add" : "") . "_save\"])";
											else
												$cond .= "\\\$_POST[\"{$tn}" . ($is_multiple_insert_update ? "_add" : "") . "_save\"]";
											if ($delete_permissions)
												$cond .= " || (\\\$has_delete_permission && \\\$_POST[\"{$tn}_delete\"])";
											else
												$cond .= " || \\\$_POST[\"{$tn}_delete\"]";
											
											$cond .= ") && \\\$_POST[\"multiple_selection\"][\"$tn\"]";
											
											$insert_update_delete_actions[] = array(
												"result_var_name" => "",
												"action_type" => "group",
												"condition_type" => "execute_if_condition",
												"condition_value" => $cond,
												"action_value" => array(
													"group_name" => "",
													"actions" => $update_delete_actions
												)
											);
										}
									}
									
									//prepare status message
									if (!$is_ajax) {
										if ($entered_in_multiple_insert && $is_multiple_insert && !$is_multiple_insert_update) {
											$action_props = isset($actions_props["multiple_insert"]) ? $actions_props["multiple_insert"] : null;
											
											//Preparing correspondent messages
											$action_type = !empty($action_props["ok_msg_type"]) ? $action_props["ok_msg_type"] : "alert";
											$action_type .= $action_type == "alert" ? "_msg" : "_ok_msg";
											
											if (!empty($action_props["ok_msg_redirect_url"]))
												$action_type .= "_and_redirect";
											
											$insert_update_delete_actions[] = array(
												"result_var_name" => "",
												"action_type" => $action_type,
												"condition_type" => "execute_if_condition",
												"condition_value" => "\\\$_POST[\"{$tn}_add\"] && \\\$insert_status" . ($write_permissions ? " && \\\$has_write_permission" : ""), // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
												"action_value" => array(
													"message" => !empty($action_props["ok_msg_message"]) ? $action_props["ok_msg_message"] : "New $tn_label inserted successfully.",
													"redirect_url" => isset($action_props["ok_msg_redirect_url"]) ? $action_props["ok_msg_redirect_url"] : null
												)
											);
											
											$action_type = !empty($action_props["error_msg_type"]) ? $action_props["error_msg_type"] : "alert";
											$action_type .= $action_type == "alert" ? "_msg" : "_error_msg";
											
											if (!empty($action_props["error_msg_redirect_url"]))
												$action_type .= "_and_redirect";
											
											if ($write_permissions)
												$insert_update_delete_actions[] = array(
													"result_var_name" => "",
													"action_type" => $action_type,
													"condition_type" => "execute_if_condition",
													"condition_value" => "\\\$_POST[\"{$tn}_add\"] && !\\\$has_write_permission", // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
													"action_value" => array(
														"message" => "Error: You do NOT have permission to insert $tn_label items!",
														"redirect_url" => isset($action_props["error_msg_redirect_url"]) ? $action_props["error_msg_redirect_url"] : null
													)
												);
											
											$insert_update_delete_actions[] = array(
												"result_var_name" => "",
												"action_type" => $action_type,
												"condition_type" => "execute_if_condition",
												"condition_value" => "\\\$_POST[\"{$tn}_add\"] && !\\\$insert_status" . ($write_permissions ? " && \\\$has_write_permission" : ""), // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
												"action_value" => array(
													"message" => !empty($action_props["error_msg_message"]) ? $action_props["error_msg_message"] : "Error: $tn_label was not inserted!",
													"redirect_url" => isset($action_props["error_msg_redirect_url"]) ? $action_props["error_msg_redirect_url"] : null
												)
											);
										}
										else if ($entered_in_multiple_update && $is_multiple_update && !$is_multiple_insert_update) {
											$action_props = isset($actions_props["multiple_update"]) ?  $actions_props["multiple_update"] : null;
											
											//Preparing correspondent messages
											$action_type = !empty($action_props["ok_msg_type"]) ? $action_props["ok_msg_type"] : "alert";
											$action_type .= $action_type == "alert" ? "_msg" : "_ok_msg";
											
											if (!empty($action_props["ok_msg_redirect_url"]))
												$action_type .= "_and_redirect";
											
											$insert_update_delete_actions[] = array(
												"result_var_name" => "",
												"action_type" => $action_type,
												"condition_type" => "execute_if_condition",
												"condition_value" => "\\\$_POST[\"{$tn}_save\"] && \\\$update_status" . ($write_permissions ? " && \\\$has_write_permission" : ""), // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
												"action_value" => array(
													"message" => !empty($action_props["ok_msg_message"]) ? $action_props["ok_msg_message"] : "$tn_label saved successfully.",
													"redirect_url" => isset($action_props["ok_msg_redirect_url"]) ? $action_props["ok_msg_redirect_url"] : null
												)
											);
											
											$action_type = !empty($action_props["error_msg_type"]) ? $action_props["error_msg_type"] : "alert";
											$action_type .= $action_type == "alert" ? "_msg" : "_error_msg";
											
											if (!empty($action_props["error_msg_redirect_url"]))
												$action_type .= "_and_redirect";
											
											if ($write_permissions)
												$insert_update_delete_actions[] = array(
													"result_var_name" => "",
													"action_type" => $action_type,
													"condition_type" => "execute_if_condition",
													"condition_value" => "\\\$_POST[\"{$tn}_save\"] && !\\\$has_write_permission", // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
													"action_value" => array(
														"message" => "Error: You do NOT have permission to save $tn_label items!",
														"redirect_url" => isset($action_props["error_msg_redirect_url"]) ? $action_props["error_msg_redirect_url"] : null
													)
												);
											
											$insert_update_delete_actions[] = array(
												"result_var_name" => "",
												"action_type" => $action_type,
												"condition_type" => "execute_if_condition",
												"condition_value" => "\\\$_POST[\"{$tn}_save\"] && !\\\$update_status" . ($write_permissions ? " && \\\$has_write_permission" : ""), // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
												"action_value" => array(
													"message" => !empty($action_props["error_msg_message"]) ? $action_props["error_msg_message"] : "Error: $tn_label was not saved!",
													"redirect_url" => isset($action_props["error_msg_redirect_url"]) ? $action_props["error_msg_redirect_url"] : null
												)
											);
										}
										else if ($entered_in_multiple_update && $is_multiple_insert_update) {
											$multiple_insert_update = isset($actions_props["multiple_insert_update"]) ? $actions_props["multiple_insert_update"] : null;
											$multiple_update = isset($actions_props["multiple_update"]) ? $actions_props["multiple_update"] : null;
											$action_props = $is_multiple_insert_update ? $multiple_insert_update : $multiple_update;
											
											//Preparing correspondent messages
											$action_type = !empty($action_props["ok_msg_type"]) ? $action_props["ok_msg_type"] : "alert";
											$action_type .= $action_type == "alert" ? "_msg" : "_ok_msg";
											
											if (!empty($action_props["ok_msg_redirect_url"]))
												$action_type .= "_and_redirect";
											
											$insert_update_delete_actions[] = array(
												"result_var_name" => "",
												"action_type" => $action_type,
												"condition_type" => "execute_if_condition",
												"condition_value" => "\\\$_POST[\"{$tn}_add_save\"] && (\\\$_POST[\"multiple_selection\"][\"$tn\"] || \\\$_POST[\"multiple_insert_selection\"][\"$tn\"]) && (!\\\$_POST[\"multiple_selection\"][\"$tn\"] || \\\$update_status) && (!\\\$_POST[\"multiple_insert_selection\"][\"$tn\"] || \\\$insert_status)" . ($write_permissions ? " && \\\$has_write_permission" : ""), // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
												"action_value" => array(
													"message" => !empty($action_props["ok_msg_message"]) ? $action_props["ok_msg_message"] : "$tn_label saved successfully.",
													"redirect_url" => isset($action_props["ok_msg_redirect_url"]) ? $action_props["ok_msg_redirect_url"] : null
												)
											);
											
											$action_type = !empty($action_props["error_msg_type"]) ? $action_props["error_msg_type"] : "alert";
											$action_type .= $action_type == "alert" ? "_msg" : "_error_msg";
											
											if (!empty($action_props["error_msg_redirect_url"]))
												$action_type .= "_and_redirect";
											
											if ($write_permissions)
												$insert_update_delete_actions[] = array(
													"result_var_name" => "",
													"action_type" => $action_type,
													"condition_type" => "execute_if_condition",
													"condition_value" => "\\\$_POST[\"{$tn}_add_save\"] && !\\\$has_write_permission", // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
													"action_value" => array(
														"message" => "Error: You do NOT have permission to save $tn_label items!",
														"redirect_url" => isset($action_props["error_msg_redirect_url"]) ? $action_props["error_msg_redirect_url"] : null
													)
												);
											
											$insert_update_delete_actions[] = array(
												"result_var_name" => "",
												"action_type" => $action_type,
												"condition_type" => "execute_if_condition",
												"condition_value" => "\\\$_POST[\"{$tn}_add_save\"] && (\\\$_POST[\"multiple_selection\"][\"$tn\"] || \\\$_POST[\"multiple_insert_selection\"][\"$tn\"]) && (!\\\$_POST[\"multiple_selection\"][\"$tn\"] || !\\\$update_status) && (!\\\$_POST[\"multiple_insert_selection\"][\"$tn\"] || !\\\$insert_status)" . ($write_permissions ? " && \\\$has_write_permission" : ""), // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
												"action_value" => array(
													"message" => !empty($action_props["error_msg_message"]) ? $action_props["error_msg_message"] : "Error: $tn_label was not saved!",
													"redirect_url" => isset($action_props["error_msg_redirect_url"]) ? $action_props["error_msg_redirect_url"] : null
												)
											);
										}
										
										if ($entered_in_multiple_delete && $is_multiple_delete) {
											$action_props = isset($actions_props["multiple_delete"]) ? $actions_props["multiple_delete"] : null;
											
											//Preparing correspondent messages
											$action_type = !empty($action_props["ok_msg_type"]) ? $action_props["ok_msg_type"] : "alert";
											$action_type .= $action_type == "alert" ? "_msg" : "_ok_msg";
											
											if (!empty($action_props["ok_msg_redirect_url"]))
												$action_type .= "_and_redirect";
											
											$insert_update_delete_actions[] = array(
												"result_var_name" => "",
												"action_type" => $action_type,
												"condition_type" => "execute_if_condition",
												"condition_value" => "\\\$_POST[\"$tn" . "_delete\"] && \\\$_POST[\"multiple_selection\"][\"$tn\"] && \\\$delete_status" . ($delete_permissions ? " && \\\$has_delete_permission" : ""), // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
												"action_value" => array(
													"message" => !empty($action_props["ok_msg_message"]) ? $action_props["ok_msg_message"] : "$tn_label deleted successfully.",
													"redirect_url" => isset($action_props["ok_msg_redirect_url"]) ? $action_props["ok_msg_redirect_url"] : null
												)
											);
											
											$action_type = !empty($action_props["error_msg_type"]) ? $action_props["error_msg_type"] : "alert";
											$action_type .= $action_type == "alert" ? "_msg" : "_error_msg";
											
											if (!empty($action_props["error_msg_redirect_url"]))
												$action_type .= "_and_redirect";
											
											if ($delete_permissions)
												$insert_update_delete_actions[] = array(
													"result_var_name" => "",
													"action_type" => $action_type,
													"condition_type" => "execute_if_condition",
													"condition_value" => "\\\$_POST[\"{$tn}_delete\"] && !\\\$has_delete_permission", // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
													"action_value" => array(
														"message" => "Error: You do NOT have permission to delete $tn_label items!",
														"redirect_url" => isset($action_props["error_msg_redirect_url"]) ? $action_props["error_msg_redirect_url"] : null
													)
												);
											
											$insert_update_delete_actions[] = array(
												"result_var_name" => "",
												"action_type" => $action_type,
												"condition_type" => "execute_if_condition",
												"condition_value" => "\\\$_POST[\"$tn" . "_delete\"] && \\\$_POST[\"multiple_selection\"][\"$tn\"] && !\\\$delete_status" . ($delete_permissions ? " && \\\$has_delete_permission" : ""), // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
												"action_value" => array(
													"message" => !empty($action_props["error_msg_message"]) ? $action_props["error_msg_message"] : "Error: $tn_label was not deleted!",
													"redirect_url" => isset($action_props["error_msg_redirect_url"]) ? $action_props["error_msg_redirect_url"] : null
												)
											);
										}
										
										//prepare error message when no elements are selected
										if ($is_multiple_insert || $is_multiple_update || $is_multiple_insert_update || $is_multiple_delete) {
											$multiple_insert_update = isset($actions_props["multiple_insert_update"]) ? $actions_props["multiple_insert_update"] : null;
											$multiple_update = isset($actions_props["multiple_update"]) ? $actions_props["multiple_update"] : null;
											$multiple_insert = isset($actions_props["multiple_insert"]) ? $actions_props["multiple_update"] : null;
											$iu_action_props = $is_multiple_insert_update ? $multiple_insert_update : ($is_multiple_update ? $multiple_update : $multiple_insert);
											$d_action_props = isset($actions_props["multiple_delete"]) ? $actions_props["multiple_delete"] : null;
											$action_type = isset($iu_action_props["ok_msg_type"]) ? $iu_action_props["ok_msg_type"] : null;
											
											if (!$action_type) {
												if (!empty($iu_action_props["error_msg_type"]))
													$action_type = $iu_action_props["error_msg_type"];
												else if (!empty($d_action_props["ok_msg_type"]))
													$action_type = $d_action_props["ok_msg_type"];
												else if (!empty($d_action_props["error_msg_type"]))
													$action_type = $d_action_props["error_msg_type"];
											}
											
											$action_type = !empty($action_props["error_msg_type"]) ? $action_props["error_msg_type"] : "alert";
											$action_type .= $action_type == "alert" ? "_msg" : "_error_msg";
											
											$cond = "";
											if ($is_multiple_insert_update)
												$cond = "(\\\$_POST[\"{$tn}_add_save\"] && !\\\$_POST[\"multiple_selection\"][\"$tn\"] && !\\\$_POST[\"multiple_insert_selection\"][\"$tn\"])";
											else if ($is_multiple_update)
												$cond = "(\\\$_POST[\"{$tn}_save\"] && !\\\$_POST[\"multiple_selection\"][\"$tn\"])";
											else if ($is_multiple_insert)
												$cond = "(\\\$_POST[\"{$tn}_add\"] && !\\\$_POST[\"multiple_insert_selection\"][\"$tn\"])";
											
											if ($is_multiple_delete)
												$cond .= ($cond ? " || " : "") . "(\\\$_POST[\"$tn" . "_delete\"] && !\\\$_POST[\"multiple_selection\"][\"$tn\"])";
											
											$insert_update_delete_actions[] = array(
												"result_var_name" => "",
												"action_type" => $action_type,
												"condition_type" => "execute_if_condition",
												"condition_value" => $cond,
												"action_value" => array(
													"message" => "Please select some items first!",
												)
											);
										}
									}
									
									//only added actions if any of them real exist
									if ($entered_in_multiple_insert || $entered_in_multiple_update || $entered_in_multiple_delete)
										$table_actions_settings[] = array(
											"result_var_name" => "",
											"action_type" => "group",
											"condition_type" => "execute_if_condition",
											"condition_value" => "\\\$_POST[\"$tn\"]", // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
											"action_value" => array(
												"group_name" => "",
												"actions" => $insert_update_delete_actions
											)
										);
								}
								
								//prepare get_all
								if ($get_all || $has_data_access_layer) {
									//Preparing brokers if not exist
									if ($is_panel_list && (!$get_all || empty($get_all["brokers_layer_type"]))) {
										$attrs = array();
										foreach ($attributes as $attr)
											$attrs[] = array("column" => $attr);
										
										$sql = DB::buildDefaultTableFindRelationshipSQL($table_name, array(
											"keys" => self::getTablesInnerJoinKeys($tables, $table_name, $table_parent),
											"attributes" => $attrs,
											"conditions" => $conditions,
										));
										self::prepareGlobalVarsInArray($sql);
										
										$get_all = array(
											"brokers_layer_type" => "select",
											"dal_broker" => $dal_broker,
											"db_driver" => $db_driver,
											"db_type" => $type,
											"sql" => $sql,
										);
										
										if ($is_pagination_active) {
											$get_all["options_type"] = "array";
											$get_all["options"] = array(
												"start" => "#" . $tn_plural . "_parameters[options][start]#",
												"limit" => "#" . $tn_plural . "_parameters[options][limit]#",
											);
										}
										//echo "<pre>";print_r($get_all);die();
									}
									else if ($get_all) {
										//echo "<pre>";print_r($get_all);die();
										if ($is_pagination_active) {
											$p = $pagination;
											$p["start_row"] = "#" . $tn_plural . "_parameters[options][start]#";
											$p["rows_per_page"] = "#" . $tn_plural . "_parameters[options][limit]#";
											
											self::prepareBrokerParametersPagination($tn_plural, $get_all, $p);
										}
										
										if ($conditions) 
											self::addConditionsToBrokerSettings($get_all, $conditions, $table_parent);
									}
									
									if ($get_all) {
										//Preparing code to be passed to the form module
										self::prepareBrokerSettings($get_all, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $tables);
										
										if ($is_pagination_active) {
											$pagination_used = true;
											
											if (!$count || empty($count["brokers_layer_type"])) {
												$sql = DB::buildDefaultTableCountRelationshipSQL($table_name, array(
													"keys" => self::getTablesInnerJoinKeys($tables, $table_name, $table_parent),
													"conditions" => $conditions,
												));
												self::prepareGlobalVarsInArray($sql);
												
												$count = array(
													"brokers_layer_type" => "select",
													"dal_broker" => $dal_broker,
													"db_driver" => $db_driver,
													"db_type" => $type,
													"sql" => $sql,
												);
											}
											else {
												if ($conditions) 
													self::addConditionsToBrokerSettings($count, $conditions, $table_parent);
											}
											
											//Preparing code to be passed to the form module
											self::prepareBrokerSettings($count, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $tables);
											
											//Preparing pagination
											$table_actions_settings[] = array(
												"result_var_name" => $tn_plural . "_current_page",
												"action_type" => "variable",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => '$_GET["' . $tn_plural . '_current_page"]'
											);
											
											$table_actions_settings[] = array(
												"result_var_name" => $tn_plural . "_pagination_start",
												"action_type" => "callobjectmethod",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_description" => "",
												"action_value" => array(
													"method_obj" => "PaginationHandler",
													"method_name" => "getStartValue",
													"method_static" => 1,
													"method_args" => array('$_GET["' . $tn_plural . '_current_page"]', $rows_per_page)
												)
											);
											
											$table_actions_settings[] = array(
												"result_var_name" => $tn_plural . "_parameters",
												"action_type" => "array",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => array(
													"options" => array(
														"limit" => $rows_per_page,
														"start" => "#" . $tn_plural . "_pagination_start#"
													)
												)
											);
											
											$table_actions_settings[] = array(
												"result_var_name" => $tn_plural . "_rows_per_page",
												"action_type" => "variable",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => "#" . $tn_plural . "_parameters[options][limit]#"
											);
											
											//Preparing count objects
											$table_actions_settings[] = array(
												"result_var_name" => $tn_plural . "_count",
												"action_type" => isset($count["brokers_layer_type"]) ? $count["brokers_layer_type"] : null,
												"condition_type" => "execute_always",
												"condition_value" => '',
												"action_value" => $count
											);
											
											$count_brokers_layer_type = isset($count["brokers_layer_type"]) ? $count["brokers_layer_type"] : null;
											
											if ($count_brokers_layer_type == "select" || 
												$count_brokers_layer_type == "callibatisquery" || 
												$count_brokers_layer_type == "getquerydata" || 
												(
													$count_brokers_layer_type == "callhibernatemethod" &&
													isset($count["service_method"]) && 
													(
														(
															$count["service_method"] == "callQuery" && 
															isset($count["sma_query_type"]) && 
															$count["sma_query_type"] == "select"
														) || 
														$count["service_method"] == "callSelect"
													)
												)
											)
												$table_actions_settings[] = array(
													"result_var_name" => $tn_plural . "_count",
													"action_type" => "variable",
													"condition_type" => "execute_if_var",
													"condition_value" => $tn_plural . "_count",
													"action_value" => "#" . $tn_plural . "_count[0][total]#"
												);
										}
										
										//Preparing get objects
										$table_actions_settings[] = array(
											"result_var_name" => $tn_plural,
											"action_type" => isset($get_all["brokers_layer_type"]) ? $get_all["brokers_layer_type"] : null,
											"condition_type" => "execute_always",
											"condition_value" => '',
											"action_value" => $get_all
										);
										
										//if broker is hibernate get code to parse hibernate results
										if (isset($get_all["brokers_layer_type"]) && $get_all["brokers_layer_type"] == "callhibernatemethod" && $get_all["service_method"] == "find") {
											$code = self::getHibernateGetAllActionNextCode($tn_plural);
											
											$table_actions_settings[] = array(
												"result_var_name" => "",
												"action_type" => "code",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => $code
											);
										}
										
										//Preparing get objects next code - preparing the get data to be shown
										$code = self::getSelectItemsActionNextCode($tables, $table_name, $tn_plural, $attributes);
										if ($code)
											$table_actions_settings[] = array(
												"result_var_name" => "",
												"action_type" => "code",
												"condition_type" => "execute_if_var",
												"condition_value" => $tn_plural,
												"action_value" => $code
											);
									}
								}
								
								//prepare list table and list form
								if (!$is_ajax && $is_panel_list) {
									$table_class = "list-items" . ($panel_class ? " $panel_class" : "");
									
									//Preparing html
									if ($panel_type == "list_table") {
										if ($form_type == "ptl")
											$table_actions_settings[] = array(
												"result_var_name" => $output_var_name ? $output_var_name . "[]" : ($is_ajax ? $tn_plural . "_list_html" : ""),
												"action_type" => "html",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => array(
													"ptl" => array(
														"code" => self::getTablePTLCode($tables, $table_name, $tn, $tn_label, $tn_plural, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, $generic_javascript, $pagination, $table_class, $panel_id),
														/*"input_data_var_name" => "",
														"idx_var_name" => "",
														"external_vars" => array()*/
													)
												)
											);
										else
											$table_actions_settings[] = array(
												"result_var_name" => $output_var_name ? $output_var_name . "[]" : ($is_ajax ? $tn_plural . "_list_html" : ""),
												"action_type" => "html",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => self::getTableFormSettings($tables, $table_name, $tn, $tn_label, $tn_plural, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, $generic_javascript, $pagination, $table_class, $panel_id)
											);
									}
									else { //$panel_type == "list_form"
										if ($form_type == "ptl")
											$table_actions_settings[] = array(
												"result_var_name" => $output_var_name ? $output_var_name . "[]" : ($is_ajax ? $tn_plural . "_list_html" : ""),
												"action_type" => "html",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => array(
													"ptl" => array(
														"code" => self::getTreePTLCode($tables, $table_name, $tn, $tn_label, $tn_plural, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, $generic_javascript, $pagination, $table_class, $panel_id),
														/*"input_data_var_name" => "",
														"idx_var_name" => "",
														"external_vars" => array()*/
													)
												)
											);
										else
											$table_actions_settings[] = array(
												"result_var_name" => $output_var_name ? $output_var_name . "[]" : ($is_ajax ? $tn_plural . "_list_html" : ""),
												"action_type" => "html",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => self::getTreeFormSettings($tables, $table_name, $tn, $tn_label, $tn_plural, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, $generic_javascript, $pagination, $table_class, $panel_id)
											);
									}
									
									//Preparing empty items message
									$table_actions_settings[] = array(
										"result_var_name" => $output_var_name ? $output_var_name . "[]" : ($is_ajax ? $tn_plural . "_empty_message" : ""),
										"action_type" => "code",
										"condition_type" => "execute_if_not_var",
										"condition_value" => $tn_plural,
										"action_value" => '"<div class=\"error\">There are no ' . self::getName($tn_plural) . '...</div>"'
									);
								}
							}
							
							//prepare forms: single and multiple form
							if ($panel_type == "multiple_form" || $panel_type == "single_form" || $is_ajax) {
								$has_single_insert = ($insert || (!empty($actions_props["single_insert"]) && $has_data_access_layer)) && empty($actions_props["single_insert"]["action_type"]) && empty($actions_props["multiple_insert"]) && empty($actions_props["multiple_insert_update"]);
								$has_single_update = ($update || (!empty($actions_props["single_update"]) && $has_data_access_layer)) && empty($actions_props["single_update"]["action_type"]) && empty($actions_props["multiple_update"]) && empty($actions_props["multiple_insert_update"]);
								$has_single_delete = ($delete || (!empty($actions_props["single_delete"]) && $has_data_access_layer)) && empty($actions_props["single_delete"]["action_type"]) && (empty($actions_props["multiple_delete"]) || !$is_ajax);
								
								if ($has_single_insert || $has_single_update || $has_single_delete) {
									$table_actions_settings[] = array(
										"result_var_name" => $tn,
										"action_type" => "variable",
										"condition_type" => "execute_always",
										"condition_value" => "",
										"action_value" => '#_POST[' . $tn . ']#'
									);
									
									//create generic data var
									if ($is_ajax)
										$table_actions_settings[] = array(
											"result_var_name" => "data",
											"action_type" => "variable",
											"condition_type" => "execute_always",
											"condition_value" => "",
											"action_value" => "#$tn#"
										);
									
									if ($write_permissions && ($has_single_insert || $has_single_update))
										$table_actions_settings[] = array(
											"result_var_name" => "has_write_permission",
											"action_type" => "check_logged_user_permissions",
											"condition_type" => "execute_if_condition",
											"condition_value" => "\\\$_POST", // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
											"action_value" => array(
												"all_permissions_checked" => 0,
												"entity_path" => "\"\" . \$entity_path . \"\"",
												"logged_user_id" => "\"\" . (isset(\$GLOBALS[\"logged_user_id\"]) ? \$GLOBALS[\"logged_user_id\"] : null) . \"\"",
												"users_perms" => $write_permissions
											)
										);
									
									if ($delete_permissions && $has_single_delete)
										$table_actions_settings[] = array(
											"result_var_name" => "has_delete_permission",
											"action_type" => "check_logged_user_permissions",
											"condition_type" => "execute_if_condition", // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
											"condition_value" => "\\\$_POST",
											"action_value" => array(
												"all_permissions_checked" => 0,
												"entity_path" => "\"\" . \$entity_path . \"\"",
												"logged_user_id" => "\"\" . (isset(\$GLOBALS[\"logged_user_id\"]) ? \$GLOBALS[\"logged_user_id\"] : null) . \"\"",
												"users_perms" => $delete_permissions
											)
										);
									
									//Preparing insert action code
									if ($has_single_insert) {
										$action_props = isset($actions_props["single_insert"]) ? $actions_props["single_insert"] : null;
										$group_actions = array();
										
										//Preparing broker if not exist
										if (!$insert || empty($insert["brokers_layer_type"])) {
											$attrs = array();
											foreach ($attributes as $attr) 
												if (!in_array($attr, $pks_auto_increment))
													$attrs[] = array("column" => $attr, "value" => '#' . $tn . '[' . $attr . ']#');
											
											$insert = array(
												"brokers_layer_type" => "insert",
												"dal_broker" => $dal_broker,
												"db_driver" => $db_driver,
												"db_type" => $type,
												"table" => $table_name,
												"attributes" => $attrs
											);
										}
										else //transform all "$_POST[" vars to #_POST[...]#
											self::prepareGlobalVarsInArrayWithSingleTableVariable($table_name, $tn, $insert, '$_POST');
										
										//Preparing code to be passed to the form module
										self::prepareBrokerSettings($insert, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $tables);
										
										//Preparing insert previous code - preparing the post attributes
										$code = self::getInsertActionPreviousCode($tables, $table_name, $tn, $attributes, $insert, $WorkFlowTaskHandler);
										if ($code)
											$group_actions[] = array(
												"result_var_name" => "",
												"action_type" => "code",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => $code
											);
										
										//Preparing action
										$group_actions[] = array(
											"result_var_name" => $tn . "_status",
											"action_type" => isset($insert["brokers_layer_type"]) ? $insert["brokers_layer_type"] : null,
											"condition_type" => "execute_always",
											"condition_value" => "",
											"action_value" => $insert
										);
										
										//Preparing insert next code - getting the inserted id
										$broker_external_var_name = null;
										$code = self::getInsertActionNextCode($tn, $insert, $pks_auto_increment, $WorkFlowTaskHandler, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $broker_external_var_name, true);
										
										if ($code) {
											if ($broker_external_var_name) //adding external var that the broker uses
												$group_actions[] = array(
													"result_var_name" => $broker_external_var_name,
													"action_type" => "variable",
													"condition_type" => "execute_always",
													"condition_value" => "",
													"action_value" => '$' . $broker_external_var_name
												);
											
											$group_actions[] = array(
												"result_var_name" => "",
												"action_type" => "code",
												"condition_type" => "execute_if_var",
												"condition_value" => $tn . "_status",
												"action_value" => $code
											);
										}
										
										if (!$is_ajax) {
											//Preparing correspondent messages
											$action_type = !empty($action_props["ok_msg_type"]) ? $action_props["ok_msg_type"] : "alert";
											$action_type .= $action_type == "alert" ? "_msg" : "_ok_msg";
											
											if (!empty($action_props["ok_msg_redirect_url"]))
												$action_type .= "_and_redirect";
											
											$group_actions[] = array(
												"result_var_name" => "",
												"action_type" => $action_type,
												"condition_type" => "execute_if_var",
												"condition_value" => $tn . "_status",
												"action_value" => array(
													"message" => !empty($action_props["ok_msg_message"]) ? $action_props["ok_msg_message"] : "New $tn_label inserted successfully.",
													"redirect_url" => self::getInsertActionRedirectUrlWithQueryString(isset($action_props["ok_msg_redirect_url"]) ? $action_props["ok_msg_redirect_url"] : null, $tn, $pks, $pks_auto_increment, $insert)
												)
											);
											
											$action_type = !empty($action_props["error_msg_type"]) ? $action_props["error_msg_type"] : "alert";
											$action_type .= $action_type == "alert" ? "_msg" : "_error_msg";
											
											if (!empty($action_props["error_msg_redirect_url"]))
												$action_type .= "_and_redirect";
											
											$insert_error_action_type = $action_type;
											$insert_error_msg_redirect_url =  self::getInsertActionRedirectUrlWithQueryString(isset($action_props["error_msg_redirect_url"]) ? $action_props["error_msg_redirect_url"] : null, $tn, $pks, $pks_auto_increment, $insert);
											
											$group_actions[] = array(
												"result_var_name" => "",
												"action_type" => $insert_error_action_type,
												"condition_type" => "execute_if_not_var",
												"condition_value" => $tn . "_status",
												"action_value" => array(
													"message" => !empty($action_props["error_msg_message"]) ? $action_props["error_msg_message"] : "Error: $tn_label was not inserted!",
													"redirect_url" => $insert_error_msg_redirect_url
												)
											);
										}
										else //if ($is_ajax) //prepare data variable
											$group_actions[] = array(
												"result_var_name" => "",
												"action_type" => "code",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => "'<?
\$data = \${$tn};
\$data[\"{$tn}_status\"] = \${$tn}_status;
?>'"
											);
										
										//add group
										$table_actions_settings[] = array(
											"result_var_name" => "",
											"action_type" => "group",
											"condition_type" => "execute_if_condition",
											"condition_value" => "\\\$_POST[\"{$tn}_add\"]" . ($write_permissions ? " && \\\$has_write_permission" : ""), // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
											"action_value" => array(
												"group_name" => "",
												"actions" => $group_actions
											)
										);
										
										if (!$is_ajax && $write_permissions)
											$table_actions_settings[] = array(
												"result_var_name" => "",
												"action_type" => isset($insert_error_action_type) ? $insert_error_action_type : null,
												"condition_type" => "execute_if_condition",
												"condition_value" => "\\\$_POST[\"{$tn}_add\"] && !\\\$has_write_permission", // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
												"action_value" => array(
													"message" => "Error: You do NOT have permission to insert this $tn_label!",
													"redirect_url" => isset($insert_error_msg_redirect_url) ? $insert_error_msg_redirect_url : null
												)
											);
									}
									
									//Preparing update action code
									if ($has_single_update) {
										$action_props = isset($actions_props["single_update"]) ? $actions_props["single_update"] : null;
										$group_actions = array();
										
										//Preparing broker if not exist
										if (!$update || empty($update["brokers_layer_type"])) {
											$attrs = array();
											foreach ($attributes as $attr) 
												if (!in_array($attr, $pks_auto_increment)) //auto incremented pks are hidden
													$attrs[] = array("column" => $attr, "value" => '#' . $tn . '[' . $attr . ']#');
											
											$conds = array();
											foreach ($pks as $pk)
												$conds[] = array("column" => $pk, "value" => '#' . $tn . '[' . $pk . ']#');
											
											$update = array(
												"brokers_layer_type" => "update",
												"dal_broker" => $dal_broker,
												"db_driver" => $db_driver,
												"db_type" => $type,
												"table" => $table_name,
												"attributes" => $attrs,
												"conditions" => $conds
											);
										}
										else //transform all "$_POST[" vars to #_POST[...]#
											self::prepareGlobalVarsInArrayWithSingleTableVariable($table_name, $tn, $update, '$_POST');
										
										//Preparing broker pks if not exist
										if (!$update_pks || empty($update_pks["brokers_layer_type"])) {
											$attrs = array();
											foreach ($pks as $pk) 
												$attrs[] = array("column" => $pk, "value" => '#' . $tn . '[new_' . $pk . ']#');
											
											$conds = array();
											foreach ($pks as $pk)
												$conds[] = array("column" => $pk, "value" => '#' . $tn . '[old_' . $pk . ']#');
											
											$update_pks = array(
												"brokers_layer_type" => "update",
												"dal_broker" => $dal_broker,
												"db_driver" => $db_driver,
												"db_type" => $type,
												"table" => $table_name,
												"attributes" => $attrs,
												"conditions" => $conds
											);
										}
										else //transform all "$_POST[" vars to #_POST[...]#
											self::prepareGlobalVarsInArrayWithSingleTableVariable($table_name, $tn, $update_pks, '$_POST');
										
										//Preparing code to be passed to the form module
										self::prepareBrokerSettings($update, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $tables);
										self::prepareBrokerSettings($update_pks, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $tables);
										
										//Preparing update previous code - preparing the post attributes
										$code = self::getUpdateActionPreviousCode($tables, $table_name, $tn, $attributes, $pks, $update, $WorkFlowTaskHandler);
										if ($code)
											$group_actions[] = array(
												"result_var_name" => "",
												"action_type" => "code",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => $code
											);
										
										//Preparing update pks
										$condition_to_validate = '\\$_POST["' . $tn . '_save_pks"]';
										foreach ($pks as $pk)
											$condition_to_validate .= ' && \\$' . $tn . '["old_' . $pk . '"] && \\$' . $tn . '["new_' . $pk . '"]';
										
										$group_actions[] = array(
											"result_var_name" => $tn . "_status",
											"action_type" => isset($update_pks["brokers_layer_type"]) ? $update_pks["brokers_layer_type"] : null,
											"condition_type" => "execute_if_condition",
											"condition_value" => $condition_to_validate,
											"action_value" => $update_pks
										);
										
										//Preparing update
										$condition_to_validate = '(!\\$_POST["' . $tn . '_save_pks"] || \\$' . $tn . '_status)'; //$_POST["save_pks"] is set in the getUpdateActionPreviousCode
										foreach ($pks as $pk)
											$condition_to_validate .= ' && \\$' . $tn . '["' . $pk . '"]';
										
										$group_actions[] = array(
											"result_var_name" => $tn . "_status",
											"action_type" => isset($update["brokers_layer_type"]) ? $update["brokers_layer_type"] : null,
											"condition_type" => "execute_if_condition",
											"condition_value" => $condition_to_validate,
											"action_value" => $update
										);
										
										if (!$is_ajax) {
											//Preparing correspondent messages
											$action_type = !empty($action_props["ok_msg_type"]) ? $action_props["ok_msg_type"] : "alert";
											$action_type .= $action_type == "alert" ? "_msg" : "_ok_msg";
											
											if (!empty($action_props["ok_msg_redirect_url"]))
												$action_type .= "_and_redirect";
											
											$group_actions[] = array(
												"result_var_name" => "",
												"action_type" => $action_type,
												"condition_type" => "execute_if_var",
												"condition_value" => $tn . "_status",
												"action_value" => array(
													"message" => !empty($action_props["ok_msg_message"]) ? $action_props["ok_msg_message"] : "$tn_label saved successfully.",
													"redirect_url" => self::getUpdateActionRedirectUrlWithQueryString(isset($action_props["ok_msg_redirect_url"]) ? $action_props["ok_msg_redirect_url"] : null, $tn, $pks, true)
												)
											);
											
											$action_type = !empty($action_props["error_msg_type"]) ? $action_props["error_msg_type"] : "alert";
											$action_type .= $action_type == "alert" ? "_msg" : "_error_msg";
											
											if (!empty($action_props["error_msg_redirect_url"]))
												$action_type .= "_and_redirect";
											
											$update_error_action_type = $action_type;
											$update_error_msg_redirect_url =  self::getUpdateActionRedirectUrlWithQueryString(isset($action_props["error_msg_redirect_url"]) ? $action_props["error_msg_redirect_url"] : null, $tn, $pks, false);
											
											$group_actions[] = array(
												"result_var_name" => "",
												"action_type" => $update_error_action_type,
												"condition_type" => "execute_if_not_var",
												"condition_value" => $tn . "_status",
												"action_value" => array(
													"message" => !empty($action_props["error_msg_message"]) ? $action_props["error_msg_message"] : "Error: $tn_label was not saved!",
													"redirect_url" => $update_error_msg_redirect_url
												)
											);
										}
										else //if ($is_ajax) //prepare data variable
											$group_actions[] = array(
												"result_var_name" => "",
												"action_type" => "code",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => "'<?
\$data = \${$tn};
\$data[\"{$tn}_status\"] = \${$tn}_status;
?>'"
											);
										
										//add group
										$table_actions_settings[] = array(
											"result_var_name" => "",
											"action_type" => "group",
											"condition_type" => "execute_if_condition",
											"condition_value" => "\\\$_POST[\"{$tn}_save\"]" . ($write_permissions ? " && \\\$has_write_permission" : ""), // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
											"action_value" => array(
												"group_name" => "",
												"actions" => $group_actions
											)
										);
										
										if (!$is_ajax && $write_permissions)
											$table_actions_settings[] = array(
												"result_var_name" => "",
												"action_type" => isset($update_error_action_type) ? $update_error_action_type : null,
												"condition_type" => "execute_if_condition",
												"condition_value" => "\\\$_POST[\"{$tn}_save\"] && !\\\$has_write_permission", // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
												"action_value" => array(
													"message" => "Error: You do NOT have permission to update this $tn_label!",
													"redirect_url" => isset($update_error_msg_redirect_url) ? $update_error_msg_redirect_url : null
												)
											);
									}
									
									//Preparing delete action code
									if ($has_single_delete) {
										$action_props = isset($actions_props["single_delete"]) ? $actions_props["single_delete"] : null;
										$group_actions = array();
										
										//Preparing broker if not exist
										if (!$delete || empty($delete["brokers_layer_type"])) {
											$conds = array();
											foreach ($pks as $pk)
												$conds[] = array("column" => $pk, "value" => '#' . $tn . '[' . $pk . ']#');
											
											$delete = array(
												"brokers_layer_type" => "delete",
												"dal_broker" => $dal_broker,
												"db_driver" => $db_driver,
												"db_type" => $type,
												"table" => $table_name,
												"conditions" => $conds
											);
										}
										else //transform all "$_POST[" vars to #_POST[...]#
											self::prepareGlobalVarsInArrayWithSingleTableVariable($table_name, $tn, $delete, '$_POST');
										
										//Preparing code to be passed to the form module
										self::prepareBrokerSettings($delete, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $tables);
										
										//Preparing update previous code - preparing the post attributes
										$code = self::getDeleteActionPreviousCode($tn, $pks);
										if ($code)
											$group_actions[] = array(
												"result_var_name" => "",
												"action_type" => "code",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => $code
											);
										
										//Preparing actions
										$condition_to_validate = '';
										foreach ($pks as $pk)
											$condition_to_validate .= ($condition_to_validate ? ' && ' : '') . '\\$' . $tn . '["' . $pk . '"]';
										
										$group_actions[] = array(
											"result_var_name" => $tn . "_status",
											"action_type" => isset($delete["brokers_layer_type"]) ? $delete["brokers_layer_type"] : null,
											"condition_type" => "execute_if_condition",
											"condition_value" => $condition_to_validate,
											"action_value" => $delete
										);
										
										//Preparing update next code - only on single delete (default type)
										$code = self::getDeleteActionNextCode($tn, $pks);
										if ($code)
											$group_actions[] = array(
												"result_var_name" => "",
												"action_type" => "code",
												"condition_type" => "execute_if_var",
												"condition_value" => $tn . "_status",
												"action_value" => $code
											);
										
										if (!$is_ajax) {
											//Preparing correspondent messages
											$action_type = !empty($action_props["ok_msg_type"]) ? $action_props["ok_msg_type"] : "alert";
											$action_type .= $action_type == "alert" ? "_msg" : "_ok_msg";
											
											if (!empty($action_props["ok_msg_redirect_url"]))
												$action_type .= "_and_redirect";
											
											$group_actions[] = array(
												"result_var_name" => "",
												"action_type" => $action_type,
												"condition_type" => "execute_if_var",
												"condition_value" => $tn . "_status",
												"action_value" => array(
													"message" => !empty($action_props["ok_msg_message"]) ? $action_props["ok_msg_message"] : "$tn_label deleted successfully.",
													"redirect_url" => self::getDeleteActionRedirectUrlWithQueryString(isset($action_props["ok_msg_redirect_url"]) ? $action_props["ok_msg_redirect_url"] : null, $tn, $pks)
												)
											);
											
											$action_type = !empty($action_props["error_msg_type"]) ? $action_props["error_msg_type"] : "alert";
											$action_type .= $action_type == "alert" ? "_msg" : "_error_msg";
											
											if (!empty($action_props["error_msg_redirect_url"]))
												$action_type .= "_and_redirect";
											
											$delete_error_action_type = $action_type;
											$delete_error_msg_redirect_url = self::getDeleteActionRedirectUrlWithQueryString(isset($action_props["error_msg_redirect_url"]) ? $action_props["error_msg_redirect_url"] : null, $tn, $pks);
											
											$group_actions[] = array(
												"result_var_name" => "",
												"action_type" => $delete_error_action_type,
												"condition_type" => "execute_if_not_var",
												"condition_value" => $tn . "_status",
												"action_value" => array(
													"message" => !empty($action_props["error_msg_message"]) ? $action_props["error_msg_message"] : "Error: $tn_label was not deleted!",
													"redirect_url" => $delete_error_msg_redirect_url
												)
											);
										}
										else //if ($is_ajax) //prepare data variable
											$group_actions[] = array(
												"result_var_name" => "",
												"action_type" => "code",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => "'<?
\$data[\"{$tn}_status\"] = \${$tn}_status;
?>'"
											);
										
										//add group
										$table_actions_settings[] = array(
											"result_var_name" => "",
											"action_type" => "group",
											"condition_type" => "execute_if_condition",
											"condition_value" => "\\\$_POST[\"{$tn}_delete\"]" . ($delete_permissions ? " && \\\$has_delete_permission" : ""), // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
											"action_value" => array(
												"group_name" => "",
												"actions" => $group_actions
											)
										);
										
										if (!$is_ajax && $delete_permissions)
											$table_actions_settings[] = array(
												"result_var_name" => "",
												"action_type" => isset($delete_error_action_type) ? $delete_error_action_type : null,
												"condition_type" => "execute_if_condition",
												"condition_value" => "\\\$_POST[\"{$tn}_delete\"] && !\\\$has_delete_permission", // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there could be previous code that changes these variables dynamically.
												"action_value" => array(
													"message" => "Error: You do NOT have permission to delete this $tn_label!",
													"redirect_url" => isset($delete_error_msg_redirect_url) ? $delete_error_msg_redirect_url : null
												)
											);
									}
								}
								
								$default_page_attr_value = 0;
								
								if ($panel_type == "multiple_form") {
									if ($get_all || $has_data_access_layer) {
										$page_attr_name = $tn_plural . "_record_index";
										
										//Preparing broker if not exist
										if (!$get_all || empty($get_all["brokers_layer_type"])) {
											$attrs = array();
											foreach ($attributes as $attr)
												$attrs[] = array("column" => $attr);
											
											$sql = DB::buildDefaultTableFindRelationshipSQL($table_name, array(
												"keys" => self::getTablesInnerJoinKeys($tables, $table_name, $table_parent),
												"attributes" => $attrs,
												"conditions" => $conditions,
											));
											self::prepareGlobalVarsInArray($sql);
											
											$get_all = array(
												"brokers_layer_type" => "select",
												"dal_broker" => $dal_broker,
												"db_driver" => $db_driver,
												"db_type" => $type,
												"sql" => $sql,
												"options_type" => "array",
												"options" => array(
													"start" => "#$page_attr_name#",
													"limit" => "1",
												),
											);
										}
										else {
											$p = $pagination;
											$p["start_row"] = '$_GET["' . $page_attr_name . '"]';
											$p["rows_per_page"] = 1;
											
											self::prepareBrokerParametersPagination($tn_plural, $get_all, $p);
											
											if ($conditions) 
												self::addConditionsToBrokerSettings($get_all, $conditions, $table_parent);
										}
										
										//Preparing page_attr_name
										$table_actions_settings[] = array(
											"result_var_name" => $page_attr_name,
											"action_type" => "variable",
											"condition_type" => "execute_always",
											"condition_value" => '',
											"action_value" => "\"\" . (\$_GET[\"$page_attr_name\"] > $default_page_attr_value ? \$_GET[\"$page_attr_name\"] : $default_page_attr_value) . \"\""
										);
										
										//Preparing code to be passed to the form module
										self::prepareBrokerSettings($get_all, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $tables);
										
										//Preparing actions
										$table_actions_settings[] = array(
											"result_var_name" => $tn_plural,
											"action_type" => isset($get_all["brokers_layer_type"]) ? $get_all["brokers_layer_type"] : null,
											"condition_type" => "execute_always",
											"condition_value" => '',
											"action_value" => $get_all
										);
										
										//if broker is hibernate get code to parse hibernate results
										if (isset($get_all["brokers_layer_type"]) && $get_all["brokers_layer_type"] == "callhibernatemethod" && $get_all["service_method"] == "find") {
											$code = self::getHibernateGetAllActionNextCode($tn_plural);
											
											$table_actions_settings[] = array(
												"result_var_name" => "",
												"action_type" => "code",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => $code
											);
										}
										
										$table_actions_settings[] = array(
											"result_var_name" => $tn,
											"action_type" => "return_specific_record",
											"condition_type" => "execute_always",
											"condition_value" => "",
											"action_value" => array(
												"records_variable_name" => $tn_plural,
												"index_variable_name" => 0
											)
										);
										
										//Preparing select item next code - preparing the get data to be shown
										$code = self::getSelectItemActionNextCode($tables, $table_name, $tn, $attributes);
										if ($code)
											$table_actions_settings[] = array(
												"result_var_name" => "",
												"action_type" => "code",
												"condition_type" => "execute_if_var",
												"condition_value" => $tn,
												"action_value" => $code
											);
										
										//preparing count - count will be used in the pagination
										if (!$count || empty($count["brokers_layer_type"])) {
											$sql = DB::buildDefaultTableCountRelationshipSQL($table_name, array(
												"keys" => self::getTablesInnerJoinKeys($tables, $table_name, $table_parent),
												"conditions" => $conditions,
											));
											self::prepareGlobalVarsInArray($sql);
											
											$count = array(
												"brokers_layer_type" => "select",
												"dal_broker" => $dal_broker,
												"db_driver" => $db_driver,
												"db_type" => $type,
												"sql" => $sql,
											);
										}
										else {
											if ($conditions) 
												self::addConditionsToBrokerSettings($count, $conditions, $table_parent);
										}
										
										//Preparing code to be passed to the form module
										self::prepareBrokerSettings($count, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $tables);
										
										//Preparing count objects
										$table_actions_settings[] = array(
											"result_var_name" => $tn_plural . "_count",
											"action_type" => isset($count["brokers_layer_type"]) ? $count["brokers_layer_type"] : null,
											"condition_type" => "execute_always",
											"condition_value" => '',
											"action_value" => $count
										);
										
										$count_brokers_layer_type = isset($count["brokers_layer_type"]) ? $count["brokers_layer_type"] : null;
										
										if ($count_brokers_layer_type == "select" || 
											$count_brokers_layer_type == "callibatisquery" || 
											$count_brokers_layer_type == "getquerydata" || 
											(
												$count_brokers_layer_type == "callhibernatemethod" && 
												isset($count["service_method"]) && 
												(
													(
														$count["service_method"] == "callQuery" && 
														isset($count["sma_query_type"]) && 
														$count["sma_query_type"] == "select"
													) || 
													$count["service_method"] == "callSelect"
												)
											)
										)
											$table_actions_settings[] = array(
												"result_var_name" => $tn_plural . "_count",
												"action_type" => "variable",
												"condition_type" => "execute_if_var",
												"condition_value" => $tn_plural . "_count",
												"action_value" => "#" . $tn_plural . "_count[0][total]#"
											);
									}
								}
								else if ($get || $has_data_access_layer) {
									$init_get = (!$get || empty($get["brokers_layer_type"])) && $panel_type == "single_form" && (($update || $delete) || !empty($actions_props["get"])); //This covers the case where we simply want a form to add a new object without any get. Which means that the get will only be created, if there is an update or delete... To force the init get please be sure that the $actions_props["get"] exists. The $actions_props["get"] is being used in the create_presentation_uis_diagram_files.php
									
									//Preparing broker if not exist
									if ($init_get) {
										$attrs = array();
										foreach ($attributes as $attr) 
											$attrs[] = array("column" => $attr);
										
										$conds = $conditions ? $conditions : array();
										foreach ($pks as $pk) {
											$exists = false;
											
											//check if there are is a condition which matches with the $pk, and if yes, replace it by the correspondent value. 
											//2020-11-06: This is very important bc I may want to have a static value for a specific PK, this is, I may want to only show the user with id 10, so I need to use this settings instead of #_GET[$pk]#.
											if ($conditions)
												foreach ($conditions as $idx => $cond) {
													$col = isset($cond["column"]) ? $cond["column"] : null;
													$col = !empty($cond["table"]) ? preg_replace("/^" . $cond["table"] . "\./", "", $col) : $col;
													
													if (strtolower($col) == strtolower($pk)) {
														$conds[$idx] = array(
															"column" => $pk, 
															"value" => isset($cond["value"]) ? $cond["value"] : null
														);
														$exists = true;
														break;
													}
												}
											
											if (!$exists)
												$conds[] = array("column" => $pk, "value" => '#_GET[' . $pk . ']#');
										}
										
										$get = array(
											"brokers_layer_type" => "select",
											"dal_broker" => $dal_broker,
											"db_driver" => $db_driver,
											"db_type" => $type,
											"table" => $table_name,
											"attributes" => $attrs,
											"conditions" => $conds
										);
									}
									else if ($get) { //transform all "$_POST[" vars to #_POST[...]#
										self::prepareGlobalVarsInArray($get);
										
										//check if there are is a condition which matches with the $pk, and if yes, replace it by the correspondent value. 
										//2020-11-06: This is very important bc I may want to have a static value for a specific PK, this is, I may want to only show the user with id 10, so I need to merge the conditions with the previous settings of this broker.
										if ($conditions) 
											self::addConditionsToGetBrokerSettings($get, $conditions);
									}
									
									if ($get) {
										//Preparing code to be passed to the form module
										self::prepareBrokerSettings($get, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $tables);
										
										//Preparing actions
										$table_actions_settings[] = array(
											"result_var_name" => $tn,
											"action_type" => isset($get["brokers_layer_type"]) ? $get["brokers_layer_type"] : null,
											"condition_type" => "execute_if_condition",
											"condition_value" => "\\\$_GET[\"" . implode("\"] && \\\$_GET[\"", $pks) . "\"]", // \\\$ is very important, otherwise this will get converted to an invalid php string. Addiciotnally we want the system to execute this only when it gets to this condition, since there is previous code that changes the '$_GET[pk]', like the getDeleteActionNextCode(..) method.
											"action_value" => $get
										);
										
										$get_brokers_layer_type = isset($get["brokers_layer_type"]) ? $get["brokers_layer_type"] : null;
										
										//if broker is hibernate get code to parse hibernate result
										if (
											$get_brokers_layer_type == "callhibernatemethod" && 
											isset($get["service_method"]) &&
											(
												$get["service_method"] == "findById" || 
												(
													$get["service_method"] == "callQuery" && 
													isset($get["sma_query_type"]) && 
													$get["sma_query_type"] == "select"
												) || 
												$get["service_method"] == "callSelect"
											)
										) {
											$code = self::getHibernateGetActionNextCode($tn);
											
											$table_actions_settings[] = array(
												"result_var_name" => "",
												"action_type" => "code",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => $code
											);
										}
										else if ($get_brokers_layer_type == "select" || $get_brokers_layer_type == "callibatisquery" || $get_brokers_layer_type == "getquerydata")
											$table_actions_settings[] = array(
												"result_var_name" => $tn,
												"action_type" => "return_specific_record",
												"condition_type" => "execute_always",
												"condition_value" => "",
												"action_value" => array(
													"records_variable_name" => $tn,
													"index_variable_name" => 0
												)
											);
								
										//Preparing select item next code - preparing the get data to be shown
										$code = self::getSelectItemActionNextCode($tables, $table_name, $tn, $attributes);
										if ($code)
											$table_actions_settings[] = array(
												"result_var_name" => "",
												"action_type" => "code",
												"condition_type" => "execute_if_var",
												"condition_value" => $tn,
												"action_value" => $code
											);
									}
									//if is only insert action and not $get yet, this is if is only a form with the insert action and no values. In this case we set some default values in the form from $_GET. This covers the case where we simply want a form to add a new object. Normally this form appears empty with no default values, since is a new object to be inserted, but we may wish to add some default values from _GET. This is very usefull bc we may want to add a new Child object where the Parent id already appears filled.
									else if ((!$get || empty($get["brokers_layer_type"])) && $panel_type == "single_form" && $insert && !$update && !$delete && empty($actions_props["get"])) {
										$code = self::getInsertItemActionGetDefaultValues($tables, $table_name, $attributes);
										
										if ($code)
											$table_actions_settings[] = array(
												"result_var_name" => $tn,
												"action_type" => "code",
												"condition_type" => "execute_if_not_var",
												"condition_value" => $tn,
												"action_value" => $code
											);
									}
								}
								
								//Preparing form html
								if (!$is_ajax) {
									if ($panel_type == "multiple_form") {
										$form_class = $update || !empty($actions_props["single_update"]) || $delete || !empty($actions_props["single_delete"]) ? "edit-item" : "view-item";
										
										if ($form_type == "ptl") {
											$code = self::getMultipleFormAddButtonPTLCode($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, $generic_javascript, $insert, $update, $delete, $form_class, $panel_class, $panel_id);
											
											if ($code) 
												$table_actions_settings[] = array(
													"result_var_name" => $output_var_name ? $output_var_name . "[]" : "",
													"action_type" => "html",
													"condition_type" => "execute_always",
													"condition_value" => "",
													"action_value" => array(
														"ptl" => array(
															"code" => $code,
														)
													)
												);
										}
										else {
											$code = self::getMultipleFormAddButtonSettings($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, $generic_javascript, $insert, $update, $delete, $form_class, $panel_class, $panel_id);
											
											if ($code)
												$table_actions_settings[] = array(
													"result_var_name" => $output_var_name ? $output_var_name . "[]" : "",
													"action_type" => "html",
													"condition_type" => "execute_always",
													"condition_value" => "",
													"action_value" => $code
												);
										}
										
										$table_actions_settings[] = array(
											"result_var_name" => $output_var_name ? $output_var_name . "[]" : "",
											"action_type" => "code",
											"condition_type" => "execute_if_not_var",
											"condition_value" => $tn,
											"action_value" => '"<div class=\"error\">' . $tn_label . ' not found!</div>"'
										);
										
										if ($form_type == "ptl") 
											$table_actions_settings[] = array(
												"result_var_name" => $output_var_name ? $output_var_name . "[]" : "",
												"action_type" => "html",
												"condition_type" => "execute_if_var",
												"condition_value" => $tn,
												"action_value" => array(
													"ptl" => array(
														"code" => self::getMultipleFormPTLCode($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, $insert, $update, $delete, $form_class, $panel_class, $panel_id),
														/*"input_data_var_name" => "",
														"idx_var_name" => "",
														"external_vars" => array()*/
													)
												)
											);
										else 
											$table_actions_settings[] = array(
												"result_var_name" => $output_var_name ? $output_var_name . "[]" : "",
												"action_type" => "html",
												"condition_type" => "execute_if_var",
												"condition_value" => $tn,
												"action_value" => self::getMultipleFormFormSettings($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, $insert, $update, $delete, $form_class, $panel_class, $panel_id)
											);
										
										//Preparing previous and next buttons if form list type
										$on_click_js_func = isset($pagination["on_click_js_func"]) ? $pagination["on_click_js_func"] : null;
										
										if ($on_click_js_func) {
											$prev_on_click = "return $on_click_js_func('{$tn_plural}_record_index', '<ptl:echo @\\\$input[\"{$tn_plural}_record_index\"] &gt; $default_page_attr_value ? \\\$input[\"{$tn_plural}_record_index\"] - 1 : $default_page_attr_value/>', 'prev', '$panel_id', this);";
											$next_on_click = "return $on_click_js_func('{$tn_plural}_record_index', '<ptl:echo (@\\\$input[\"{$tn_plural}_record_index\"] + 1)/>', 'next', '$panel_id', this);";
										}
										else {
											$prev_on_click = "return loadPageWithNewNavigation('{$tn_plural}_record_index', '<ptl:echo @\\\$input[\"{$tn_plural}_record_index\"] &gt; $default_page_attr_value ? \\\$input[\"{$tn_plural}_record_index\"] - 1 : $default_page_attr_value/>');";
											$next_on_click = "return loadPageWithNewNavigation('{$tn_plural}_record_index', '<ptl:echo (@\\\$input[\"{$tn_plural}_record_index\"] + 1)/>');";
										}
										
										$table_actions_settings[] = array(
											"result_var_name" => $output_var_name ? $output_var_name . "[]" : "",
											"action_type" => "html",
											"condition_type" => "execute_always",
											"condition_value" => "",
											"action_value" => array(
												"ptl" => array(
													"code" => "
<div class=\"buttons pagination-buttons\">
	<div class=\"button\">
		<input type=\"button\" <ptl:echo @\\\$input[\"{$tn_plural}_record_index\"] &gt; $default_page_attr_value ? '' : 'class=\"button-hidden\"'/> value=\"Go to Previous Record\" onClick=\"$prev_on_click\" />
	</div>
	<div class=\"button\">
		<input type=\"button\" <ptl:echo @\\\$input[\"{$tn_plural}_record_index\"] + 1 &gt;= \\\$input['{$tn_plural}_count'] ? 'class=\"button-hidden\"' : '' /> value=\"Go to Next Record\" onClick=\"$next_on_click\" />
	</div>
</div>", 
													/*"input_data_var_name" => "", 
													"idx_var_name" => "",
													"external_vars" => array()*/
												)
											)
										);
									}
									else if ($panel_type == "single_form") {
										$has_insert = $insert || !empty($actions_props["single_insert"]); //local insert exists or insert via ajax
										$form_class = $update || !empty($actions_props["single_update"]) || $delete || !empty($actions_props["single_delete"]) ? "edit-item" : ($has_insert ? "add-item" : "view-item");
										$form_class = trim("$form_class $panel_class");
										
										if (!$has_insert)
											$table_actions_settings[] = array(
												"result_var_name" => $output_var_name ? $output_var_name . "[]" : "",
												"action_type" => "code",
												"condition_type" => "execute_if_not_var",
												"condition_value" => $tn,
												"action_value" => '"<div class=\"error\">' . $tn_label . ' not found!</div>"'
											);
										
										if ($form_type == "ptl") 
											$table_actions_settings[] = array(
												"result_var_name" => $output_var_name ? $output_var_name . "[]" : "",
												"action_type" => "html",
												"condition_type" => $has_insert ? "execute_always" : "execute_if_var",
												"condition_value" => $has_insert ? "" : $tn,
												"action_value" => array(
													"ptl" => array(
														"code" => self::getFormPTLCode($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, $insert, $update, $delete, $form_class, $panel_id),
														/*"input_data_var_name" => "",
														"idx_var_name" => "",
														"external_vars" => array()*/
													)
												)
											);
										else 
											$table_actions_settings[] = array(
												"result_var_name" => $output_var_name ? $output_var_name . "[]" : "",
												"action_type" => "html",
												"condition_type" => $has_insert ? "execute_always" : "execute_if_var",
												"condition_value" => $has_insert ? "" : $tn,
												"action_value" => self::getFormFormSettings($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, $insert, $update, $delete, $form_class, $panel_id)
											);
									}
								}
							}
							
							//add previous html;
							if ($panel_previous_html) {
								$previous_html_action_settings = array(
									"result_var_name" => $output_var_name ? $output_var_name . "[]" : "",
									"action_type" => "html",
									"condition_type" => "execute_always",
									"condition_value" => "",
									"action_value" => array(
										"ptl" => array(
											"code" => $panel_previous_html
										)
									)
								);
								array_unshift($table_actions_settings, $previous_html_action_settings);
							}
							
							//add next html;
							if ($panel_next_html)
								$table_actions_settings[] = array(
									"result_var_name" => $output_var_name ? $output_var_name . "[]" : "",
									"action_type" => "html",
									"condition_type" => "execute_always",
									"condition_value" => "",
									"action_value" => array(
										"ptl" => array(
											"code" => $panel_next_html
										)
									)
								);
							
							//include PaginationHandler file
							if ($pagination_used) {
								$include_action_settings = array(
									"result_var_name" => "",
									"action_type" => "include_file", //cannot be code bc this must be executed directly in the php
									"condition_type" => "execute_always",
									"condition_value" => "",
									"action_value" => array(
										"path" => 'LIB_PATH . "org/phpframework/util/web/html/pagination/PaginationHandler.php"',
										"once" => 1,
									),
								);
								array_unshift($table_actions_settings, $include_action_settings);
							}
							
							//prepare the vars to be outputed for the ajax request
							if ($is_ajax)
								$ajax_vars_to_output[$table_name] = self::getActionsSettingsVars($table_actions_settings, $tn);
							
							//concat table_actions_settings with actions_settings
							$actions_settings = array_merge($actions_settings, $table_actions_settings);
						}
					}
					
					//output the $variables for the ajax request if any...
					if ($ajax_vars_to_output) {
						$code = "'<?\n\$res = array(\n"; 
						
						foreach ($ajax_vars_to_output as $table_name => $vars) {
							$table_alias = isset($tables_alias[$table_name]) ? $tables_alias[$table_name] : null;
							$tn = self::getParsedTableName($table_alias ? $table_alias : $table_name);
							
							$code .= "\t\"$tn\" => " . self::printActionsSettingsVars($vars, "\t\t");
						}
						
						$code .= ");\necho json_encode(\$res);\n?>'";
						
						$actions_settings[] = array(
							"result_var_name" => "",
							"action_type" => "code",
							"condition_type" => "execute_always",
							"condition_value" => "",
							"action_value" => $code
						);
					}
					
					//prepare form settings
					$form_settings = array(
						"actions" => $actions_settings,
						"css" => $is_ajax ? "" : ".field-hidden, .button-hidden {display:none;}",
						"js" => $generic_javascript,
					);
				}
				
				$PHPVariablesFileHandler->endUserGlobalVariables();
			}
		}
		
		return $form_settings;
	}
	
	/* ATTRIBUTES SETTINGS METHODS */
	
	//the keys from $attributes_settings are in lower case but the attributes may be upper case or have upper case letters, so we must replace the keys with the real attributes names.
	public static function prepareAttributesSettingsWithRealAttributeNames(&$attributes_settings, $tables, $table_name) {
		if ($attributes_settings) {
			$attributes = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
			
			if ($attributes) {
				$new_attributes_settings = array();
				
				foreach ($attributes_settings as $attribute_name => $as) {
					$ran = null;
					
					if (!isset($attributes[$attribute_name])) {
						$lan = strtolower($attribute_name);
						
						foreach ($attributes as $attr_name => $attr)
							if (strtolower($attr_name) == $lan) {
								$ran = $attr_name;
								break;
							}
					}
					
					if ($ran)
						$new_attributes_settings[$ran] = $as;
					else
						$new_attributes_settings[$attribute_name] = $as;
				}
				
				$attributes_settings = $new_attributes_settings;
			}
		}
	}
	
	//prepare $actions_props["attributes_settings"], this is, verify if exists available values for each attribute, by checking if exists a related db table. Then create correspondent actions and add the variable name with all table's records to $actions_props["attributes_settings"][attribute_name]["available_values"].
	public static function prepareAttributeAvailableValues(&$table_actions_settings, &$attributes_settings, $has_data_access_layer, $settings, $tables, $table_name, $broker_settings, $dal_broker, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $type) {
		if ($attributes_settings) {
			self::prepareAttributesSettingsWithRealAttributeNames($attributes_settings, $tables, $table_name);
			//print_r($attributes_settings);
			
			foreach ($attributes_settings as $attribute_name => $as) {
				//prepare link
				$as["link"] = isset($as["link"]) ? trim($as["link"]) : "";
				
				if (strlen($as["link"])) {
					$as["href"] = $as["link"];
					$as["type"] = "link";
					unset($as["link"]);
					
					$attributes_settings[$attribute_name] = $as;
				}
				
				//prepare external table attribute
				$attribute_actions = array();
				$var_name = "";
				
				if (isset($as["list_type"]) && $as["list_type"] == "from_db" && !empty($as["db_table"]) && !empty($as["db_attribute_label"])) {
					$db_table = $as["db_table"];
					$db_attribute_label = $as["db_attribute_label"];
					$db_attribute_fk = isset($as["db_attribute_fk"]) ? $as["db_attribute_fk"] : null;
					$db_attribute_db_driver = array_key_exists("db_driver", $as) ? $as["db_driver"] : $db_driver;
					$db_attribute_include_db_driver = array_key_exists("include_db_driver", $as) ? $as["include_db_driver"] : ($db_attribute_db_driver && $db_attribute_db_driver != $db_driver ? true : $include_db_driver); //if db driver is different than main db driver, hard-code db driver, this is, set include_db_driver to true.
					$db_attribute_db_type = array_key_exists("db_type", $as) ? $as["db_type"] : $type;
					
					if (!$db_attribute_fk)
						$db_attribute_fk = self::findTableAttributeForeignKey($tables, $table_name, $attribute_name, $db_table);
					
					if ($db_attribute_fk) {
						//prepare query string if link exists and is not a javascript, this is, add the attribute to the query string
						$href = isset($as["href"]) ? trim($as["href"]) : "";
						
						if (strlen($href) && stripos($href, "javascript:") !== 0) {
							$query_string = "$db_attribute_fk=#$attribute_name#";
							$exists = stripos($href, "&$query_string") !== false || stripos($href, "?$query_string") !== false;
							
							if (!$exists) {
								$href .= (strpos($href, "?") !== false ? "&" : "?") . $query_string;
								$attributes_settings[$attribute_name]["href"] = $href;
							}
						}
						
						//prepare brokers
						$table_brokers = isset($settings[$db_table]["brokers"]) ? $settings[$db_table]["brokers"] : null;
						
						if (empty($table_brokers["get_all"]))
							$table_brokers = $settings[$table_name]["brokers"]["other"][$db_table];
						
						$get_all = isset($table_brokers["get_all"]) ? $table_brokers["get_all"] : null;
						
						//prepare get_all
						if ($get_all || $has_data_access_layer) {
							//Preparing brokers if not exist
							if (!$get_all) {
								//$sql = DB::buildDefaultTableFindSQL($db_table, array($db_attribute_fk => $db_attribute_fk, $db_attribute_label => $db_attribute_label));
								$get_all = array(
									"brokers_layer_type" => "select",
									"dal_broker" => $dal_broker,
									"db_driver" => $db_attribute_db_driver,
									"db_type" => $db_attribute_db_type,
									//"sql" => $sql,
									"table" => $db_table,
									"attributes" => array(
										array("column" => $db_attribute_fk),
										array("column" => $db_attribute_label),
									),
								);
							}
							
							self::prepareBrokerSettings($get_all, $db_broker, $include_db_broker, $db_attribute_db_driver, $db_attribute_include_db_driver, $tables);
							
							//Preparing actions and attribute_settings
							$var_name = str_replace(array(" ", "-"), "_", self::getParsedTableName( strtolower($db_table . "_" . $db_attribute_fk . "_" . $db_attribute_label . "_available_values") )); //$table_name name can have schema
							
							$attribute_actions[] = array(
								"result_var_name" => $var_name,
								"action_type" => isset($get_all["brokers_layer_type"]) ? $get_all["brokers_layer_type"] : null,
								"condition_type" => "execute_always",
								"condition_value" => "",
								"action_value" => $get_all
							);
							
							$attribute_actions[] = array(
								"result_var_name" => $var_name,
								"action_type" => "code",
								"condition_type" => "execute_always",
								"condition_value" => "",
								"action_value" => "'<?
\$avs = array();
if (\${$var_name})
    foreach (\${$var_name} as \$av)
        \$avs[ \$av[\"{$db_attribute_fk}\"] ] = \$av[\"{$db_attribute_label}\"];

return \$avs;
?>'"
							);
						}
					}
				}
				else if (isset($as["list_type"]) && $as["list_type"] == "manual" && !empty($as["manual_list"])) {
					$var_name = str_replace(array(" ", "-"), "_", self::getParsedTableName( strtolower($table_name . "_" . $attribute_name . "_manual_available_values") )); //$table_name name can have schema
					
					$manual_list = $as["manual_list"];
					if (!empty($manual_list["value"]) || !empty($manual_list["label"]))
						$manual_list = array($manual_list);
					
					$code = '';
					foreach ($manual_list as $item) {
						$value = isset($item["value"]) ? $item["value"] : null;
						$value_type = PHPUICodeExpressionHandler::getValueType($value, array("empty_string_type" => "string", "non_set_type" => "string"));
						$value = PHPUICodeExpressionHandler::getArgumentCode($value, $value_type);
						
						$label = isset($item["label"]) ? $item["label"] : null;
						$label_type = PHPUICodeExpressionHandler::getValueType($label, array("empty_string_type" => "string", "non_set_type" => "string"));
						$label = PHPUICodeExpressionHandler::getArgumentCode($label, $label_type);
						
						$code .= ($code ? ', ' : '') . $value . ' => ' . $label;
					}
					
					$code = $code ? "array($code);" : "";
					
					if ($code) 
						$attribute_actions[] = array(
							"result_var_name" => $var_name,
							"action_type" => "code",
							"condition_type" => "execute_always",
							"condition_value" => "",
							"action_value" => "'<?
return $code;
?>'"
						);
				}
				
				if ($attribute_actions && $var_name) {
					$attributes_settings[$attribute_name]["available_values"] = $var_name;
					$attributes_settings[$attribute_name]["options"] = $var_name;
					$attributes_settings[$attribute_name]["options_javascript_variable_name"] = $var_name;
					
					$attribute_actions[] = array(
						"result_var_name" => "",
						"action_type" => "html",
						"condition_type" => "execute_always",
						"condition_value" => "",
						"action_value" => array(
							"ptl" => array(
								"code" => "<script>
var $var_name = <ptl:echo isset(\\\$$var_name) ? json_encode(\\\$$var_name) : 'null' />;
</script>", 
							)
						)
					);
					
					$table_actions_settings[] = array(
						"result_var_name" => "",
						"action_type" => "group",
						"condition_type" => "execute_always",
						"condition_value" => "",
						"action_value" => array(
							"group_name" => "",
							"actions" => $attribute_actions
						)
					);
				}
			}
		}
	}
	
	private static function findTableAttributeForeignKey($tables, $table_name, $attribute_name, $fk_table_name) {
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		$attr = isset($attrs[$attribute_name]) ? $attrs[$attribute_name] : null;
		$fk_attribute = null;
		$lfktn = strtolower($fk_table_name);
		
		//check if foreign tables are dependent childs
		if (!empty($attr["fk"]))
			foreach ($attr["fk"] as $fk) {
				$attr_fk_table = isset($fk["table"]) ? $fk["table"] : null;
				
				if (strtolower($attr_fk_table) == $lfktn) {
					$fk_attribute = isset($fk["attribute"]) ? $fk["attribute"] : null;
					break;
				}
			}
		
		$fk_attrs = WorkFlowDBHandler::getTableFromTables($tables, $fk_table_name);
		
		if (!$fk_attribute && !empty($fk_attrs[$attribute_name]))
			$fk_attribute = $attribute_name;
		
		return $fk_attribute;
	}
	
	private static function getTablesInnerJoinKeys($tables, $table_name, $table_parent) {
		$keys = array();
		
		if ($table_parent) {
			$ltn = strtolower($table_name);
			$ltp = strtolower($table_parent);
			
			$table_props = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
			$parent_table_props = WorkFlowDBHandler::getTableFromTables($tables, $table_parent);
			
			if ($parent_table_props)
				foreach ($parent_table_props as $attr_name => $attr)
					if (!empty($attr["fk"]))
						foreach ($attr["fk"] as $fk) {
							$attr_fk_table = isset($fk["table"]) ? $fk["table"] : null;
							
							if (strtolower($attr_fk_table) == $ltn)
								$keys[] = array(
									"ptable" => $table_name,
									"pcolumn" => isset($fk["attribute"]) ? $fk["attribute"] : null,
									"ftable" => $table_parent,
									"fcolumn" => $attr_name,
								);
						}
			
			//may be flipped, this is, if task is aa inner task with a parent task, we set the parent_table with the parent task table. However it may not be a real parent table, but a child instead. So we must cover this case, by checking if there is no $inner_join_on, it means the tables are flipped.
			if (empty($keys) && $table_props)
				foreach ($table_props as $attr_name => $attr)
					if (!empty($attr["fk"]))
						foreach ($attr["fk"] as $fk) {
							$attr_fk_table = isset($fk["table"]) ? $fk["table"] : null;
							
							if (strtolower($attr_fk_table) == $ltp)
								$keys[] = array(
									"ptable" => $table_name,
									"pcolumn" => $attr_name,
									"ftable" => $table_parent,
									"fcolumn" => isset($fk["attribute"]) ? $fk["attribute"] : null,
								);
						}
			
			//if there isn't $parent_table_props, we should continue returning the keys to be added to the sql. Yes I know that it will show a sql error if there isn't a sql iner join "ON" condition, but if this happens it means that this class was hacked and that it was not created by the proper channels, so we need to generate an incomplete sql on purpose, in order to track this issue easily.
		}
		
		return $keys;
	}
	
	/* CONVERSION METHODS */
	
	public static function convertFormSettingsToJavascriptSettings($form_settings) {
		if ($form_settings)
			foreach ($form_settings as $k => $settings) 
				if ($k == "actions" && is_array($settings)) 
					foreach ($settings as $idx => $action_settings) {
						$action_type = isset($action_settings["action_type"]) ? $action_settings["action_type"] : null;
						$action_value = isset($action_settings["action_value"]) ? $action_settings["action_value"] : null;
						
						if ($action_type == "array" && is_array($action_value))
							$form_settings[$k][$idx]["action_value"] = self::convertFormSettingsArrayToJavascriptSettings($action_value);
						else if ($action_type == "html")
							$form_settings[$k][$idx]["action_value"] = array(
								"form_settings_data_type" => "array",
								"form_settings_data" => self::convertFormSettingsArrayToJavascriptSettings($action_value),
							);
						else if ($action_type == "code") {
							//checks if is a string sorrounded by double quotes
							if (substr($action_value, 0, 1) == '"' && substr($action_value, -1) == '"' && !preg_match('/^"(.*)([^\\\\])"(.*)"$/', str_replace("\n", "", $action_value))) {
								$action_value = substr($action_value, 1, -1);
								$action_value = stripcslashes($action_value); //remove slashes from quotes that are not escaped and other slashes
								$form_settings[$k][$idx]["action_value"] = $action_value;
							}
							
							//checks if is a string sorrounded by single quotes
							if (substr($action_value, 0, 1) == "'" && substr($action_value, -1) == "'" && !preg_match("/^'(.*)([^\\\\])'(.*)'$/", str_replace("\n", "", $action_value))) {
								$action_value = substr($action_value, 1, -1);
								$action_value = TextSanitizer::replaceIfNotEscaped('$', '\\$', $action_value);
								$action_value = TextSanitizer::stripCSlashes($action_value, "'");
								$form_settings[$k][$idx]["action_value"] = $action_value;
							}
						}
						else if (($action_type == "group" || $action_type == "loop") && !empty($action_value["actions"]))
							$form_settings[$k][$idx]["action_value"] = self::convertFormSettingsToJavascriptSettings($action_value);
						else if ($action_type == "callfunction" && !empty($action_value["func_args"]))
							$form_settings[$k][$idx]["action_value"]["func_args"] = self::convertFormSettingsArrayToJavascriptSettings($action_value["func_args"]);
						else if ($action_type == "callobjectmethod" && !empty($action_value["method_args"]))
							$form_settings[$k][$idx]["action_value"]["method_args"] = self::convertFormSettingsArrayToJavascriptSettings($action_value["method_args"]);
						else if ($action_type == "select" && !empty($action_value["options"]))
							$form_settings[$k][$idx]["action_value"]["options"] = self::convertFormSettingsArrayToJavascriptSettings($action_value["options"]);
					}
		
		return $form_settings;
	}
	
	//used here and in SequentialLogicalActivityResourceCreator::getSLAResourceActions
	public static function convertFormSettingsArrayToJavascriptSettings($arr) {
		$arr_settings = array();
		
		if (is_array($arr))
			foreach ($arr as $k => $v)
				if (is_array($v))
					$arr_settings[] = array(
						"key" => $k,
						"key_type" => is_numeric($k) ? "" : "string",
						"items" => self::convertFormSettingsArrayToJavascriptSettings($v)
					);
				else {
					$is_code_type = is_numeric($v) || (substr($v, 0, 1) == '"' && substr($v, -1) == '"' && preg_match('/^"(.*)([^\\\\])"(.*)"$/', str_replace("\n", "", $v)));
					$is_code_type = $is_code_type || (substr($v, 0, 1) == "'" && substr($v, -1) == "'" && preg_match("/^'(.*)([^\\\\])'(.*)'$/", str_replace("\n", "", $v)));
					
					$arr_settings[] = array(
						"key" => $k,
						"key_type" => is_numeric($k) ? "" : "string",
						"value" => $v,
						"value_type" => $is_code_type ? "" : (substr($v, 0, 1) == '$' || substr($v, 0, 2) == '@$' ? "variable" : "string"),
					);
				}
		
		return $arr_settings;
	}
	
	/* REDIRECT URLS METHODS */
	
	private static function getInsertActionRedirectUrlWithQueryString($redirect_url, $tn, $pks, $pks_auto_increment, $insert) {
		$qs = '';
		
		if (isset($insert["brokers_layer_type"]) && $insert["brokers_layer_type"] == "callhibernatemethod") {	
			$ids_var_name = isset($insert["sma_ids"]) ? $insert["sma_ids"] : null;
			
			if ($ids_var_name)
				foreach ($pks as $pk)
					$qs .= ($qs ? '&' : '') . $pk . '=' . '#' . $ids_var_name . '[' . $pk . ']#';
		}
		else
			foreach ($pks as $pk) {
				$is_pk_auto_increment = in_array($pk, $pks_auto_increment);
				$qs .= ($qs ? '&' : '') . $pk . '=' . ($is_pk_auto_increment ? '#' . $tn . '_status#' : '#' . $tn . '[' . $pk . ']#');
			}
		
		if ($qs)
			$redirect_url .= (strpos($redirect_url, "?") !== false ? "&" : "?") . $qs;
		
		return $redirect_url;
	}
	
	private static function getUpdateActionRedirectUrlWithQueryString($redirect_url, $tn, $pks, $is_pk_changed) {
		$qs = '';
		
		foreach ($pks as $pk)
			$qs .= ($qs ? '&' : '') . $pk . '=' . '#' . $tn . '[' . ($is_pk_changed ? '' : 'orig_') . $pk . ']#';
		
		if ($qs)
			$redirect_url .= (strpos($redirect_url, "?") !== false ? "&" : "?") . $qs;
		
		return $redirect_url;
	}
	
	private static function getDeleteActionRedirectUrlWithQueryString($redirect_url, $tn, $pks) {
		return self::getUpdateActionRedirectUrlWithQueryString($redirect_url, $tn, $pks, true);
	}
	
	/* INSERT/UPDATE/GET ACTIONS PREVIOUS/NEXT CODE METHODS */
	
	//if insert/update/delete actions exists, check if attributes have the right pks, otherwise add them but as hidden fields. If this is not done, then we will have an interface without PK and with actions, which will give errors when we execute the insert/update/delete actions.
	private static function prepareAttributesWithPKs(&$attributes, $pks, &$actions_props) {
		if ($pks)
			foreach ($pks as $pk) 
				if (!in_array($pk, $attributes)) {
					//add pk to attributes
					$attributes[] = $pk;
					
					//add attribute props with input hidden
					if (!isset($actions_props["attributes_settings"][$pk]))
						$actions_props["attributes_settings"][$pk]["type"] = "hidden";
				}
	}
	
	//This method is called inside of the getUpdateActionPreviousCode too
	//If you change this method, please make the same changes inside the method SequentialLogicalActivityResourceCreator::getInsertActionPreviousCode
	private static function getInsertActionPreviousCode($tables, $table_name, $tn, $attributes, $insert, $WorkFlowTaskHandler) {
		$code = "";
		
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		$brokers_layer_type = isset($insert["brokers_layer_type"]) ? $insert["brokers_layer_type"] : null;
		$is_insert = self::isInsertBroker($insert);
		$is_db_primitive_action = self::isDBPrimitiveBroker($insert); //used when insert and update action
		$is_ibatis = self::isIbatisBroker($insert);
		
		$var_prefix = '$' . $tn;
		
		foreach ($attributes as $attr_name) {
			$attr = isset($attrs[$attr_name]) ? $attrs[$attr_name] : null;
			$type = isset($attr["type"]) ? $attr["type"] : null;
			$allow_null = !isset($attr["null"]) || $attr["null"];
			$is_numeric_type = ObjTypeHandler::isDBTypeNumeric($type) || ObjTypeHandler::isPHPTypeNumeric($type);
			
			$is_logged_user_id_attribute = (ObjTypeHandler::isDBAttributeNameACreatedUserId($attr_name) || ObjTypeHandler::isDBAttributeNameAModifiedUserId($attr_name)) && $is_numeric_type;
			
			//check if field is checkbox/boolean and if yes the default should be replaced by 0, bc it means the user set the checkbox to unchcekd which makes the browser to not include this attribute in the requests...
			self::prepareFormInputParameters($attr, $input_type);
			if (isset($attr["default"]) && strlen($attr["default"]) && ($input_type == "checkbox" || $input_type == "radio") && $is_numeric_type)
				$attr["default"] = 0;
			
			//prepare code
			if ($is_insert && !empty($attr["primary_key"]) && WorkFlowDataAccessHandler::isAutoIncrementedAttribute($attr)) {
				$code .= self::getInsertActionPreviousCodeIfBrokerSettingsContainsAutoIncrementPrimaryKeys($table_name, $tn, $attr_name, $attr, $insert, $WorkFlowTaskHandler);
			}
			else if ($allow_null && ($is_numeric_type || ObjTypeHandler::isDBTypeDate($type))) {
				if ($is_db_primitive_action) {
					$code .= 'if (isset(' . $var_prefix . '["' . $attr_name . '"]) && is_numeric(' . $var_prefix . '["' . $attr_name . '"]) && is_string(' . $var_prefix . '["' . $attr_name . '"])) ' . $var_prefix . '["' . $attr_name . '"] += 0;' . "\n"; //convert string to real numeric value. This is very important, bc in the insert and update primitive actions of the DBSQLConverter, the sql must be created with numeric values and without quotes, otherwise the DB server gives a sql error.
					
					$default = isset($attr["default"]) && strlen($attr["default"]) ? (is_numeric($attr["default"]) ? $attr["default"] : '"' . $attr["default"] . '"') : '"DEFAULT"';
					
					if ((ObjTypeHandler::isDBAttributeNameACreatedDate($attr_name) || ObjTypeHandler::isDBAttributeNameAModifiedDate($attr_name)) && ObjTypeHandler::isDBTypeDate($type))
						$default = $type == "date" ? 'date("Y-m-d")' : 'date("Y-m-d H:i:s")';
					else if ($is_logged_user_id_attribute)
						$default .= '/* REPLACE THIS BY THE LOGGED USER ID */';
					else if (ObjTypeHandler::isDBAttributeValueACurrentTimestamp($default))
						$default = 'date("Y-m-d H:i:s")';
					else if ( (!isset($attr["default"]) || !strlen($attr["default"])) && ObjTypeHandler::isDBTypeDate($type))
						$default = $is_ibatis ? '"null"' : 'null';
					
					$code .= 'else if (!isset(' . $var_prefix . '["' . $attr_name . '"]) || !strlen(trim(' . $var_prefix . '["' . $attr_name . '"]))) ' . $var_prefix . '["' . $attr_name . '"] = ' . $default . ';' . "\n\n";
				}
				else {
					$default = 'null';
					
					if ($is_logged_user_id_attribute)
						$default .= '/* REPLACE THIS BY THE LOGGED USER ID */';
					
					$code .= 'if (!isset(' . $var_prefix . '["' . $attr_name . '"]) || !strlen(trim(' . $var_prefix . '["' . $attr_name . '"]))) ' . $var_prefix . '["' . $attr_name . '"] = ' . $default . ';' . "\n";
				}
			}
			else if ($is_db_primitive_action && $is_numeric_type) { //for the cases with a checkbox where the value doesn't exist and is numeric
				$code .= 'if (isset(' . $var_prefix . '["' . $attr_name . '"]) && is_numeric(' . $var_prefix . '["' . $attr_name . '"]) && is_string(' . $var_prefix . '["' . $attr_name . '"])) ' . $var_prefix . '["' . $attr_name . '"] += 0;' . "\n"; //convert string to real numeric value. This is very important, bc in the insert and update primitive actions of the DBSQLConverter, the sql must be created with numeric values and without quotes, otherwise the DB server gives a sql error.
				
				if (!empty($attr["primary_key"]))
					$code .= 'else if (!isset(' . $var_prefix . '["' . $attr_name . '"]) || !is_numeric(' . $var_prefix . '["' . $attr_name . '"])) ' . $var_prefix . '["' . $attr_name . '"] = "null";' . "\n"; //This is on purpose so it can return empty records or don't do nothing in the DB, bc if the user wrote a pk with a non numeric value, it means is trying to do some hack.
				else {
					$default = isset($attr["default"]) && strlen($attr["default"]) ? (is_numeric($attr["default"]) ? $attr["default"] : '"' . $attr["default"] . '"') : '"DEFAULT"';
					
					if ($is_logged_user_id_attribute)
						$default .= "/* REPLACE THIS BY THE LOGGED USER ID */";
					
					$code .= 'else if (!isset(' . $var_prefix . '["' . $attr_name . '"]) || !strlen(trim(' . $var_prefix . '["' . $attr_name . '"]))) ' . $var_prefix . '["' . $attr_name . '"] = ' . $default . ';' . "\n";
				}
				
				$code .= "\n";
			}
			else if ($brokers_layer_type == "callbusinesslogic" && ($is_numeric_type || ObjTypeHandler::isDBTypeBoolean($type))) {
				$default = isset($attr["default"]) && strlen($attr["default"]) ? (is_numeric($attr["default"]) ? $attr["default"] : '"' . $attr["default"] . '"') : 'null';
				
				if ($is_logged_user_id_attribute)
					$default .= "/* REPLACE THIS BY THE LOGGED USER ID */";
				
				$code .= 'if (array_key_exists("' . $attr_name . '", ' . $var_prefix . ') && !is_numeric(' . $var_prefix . '["' . $attr_name . '"])) ' . $var_prefix . '["' . $attr_name . '"] = ' . $default . ';' . "\n";
			}
		}
		
		return $code ? "'<?\n" . $code . "?>'" : null;
	}
	
	//If you change this method, please make the same changes inside the method SequentialLogicalActivityResourceCreator::getInsertActionPreviousCodeIfBrokerSettingsContainsAutoIncrementPrimaryKeys
	private static function getInsertActionPreviousCodeIfBrokerSettingsContainsAutoIncrementPrimaryKeys($table_name, $tn, $attr_name, $attr, $insert, $WorkFlowTaskHandler) {
		$brokers_layer_type = isset($insert["brokers_layer_type"]) ? $insert["brokers_layer_type"] : null;
		$data = null;
		//print_r($insert);die();
		
		//checks if auto increment pk exists in attributes
		if ($brokers_layer_type == "callibatisquery" && isset($insert["service_type"]) && $insert["service_type"] == "insert") 
			$data = isset($insert["parameters"]) ? $insert["parameters"] : null; //[$i]["value"] => #item[title]#
		else if ($brokers_layer_type == "getquerydata" || $brokers_layer_type == "setquerydata") 
			$data = isset($insert["sql"]) ? $insert["sql"] : null;
		else if ($brokers_layer_type == "callhibernatemethod" && isset($insert["service_method"]) && ($insert["service_method"] == "getData" || $insert["service_method"] == "setData")) 
			$data = isset($insert["sma_sql"]) ? $insert["sma_sql"] : null;
		else if ($brokers_layer_type == "insert")
			$data = isset($insert["attributes"]) ? $insert["attributes"] : null; //[$i]["column"]
		else if ($brokers_layer_type == "callhibernatemethod" && isset($insert["service_method"]) && ($insert["service_method"] == "insert" || $insert["service_method"] == "callInsert")) 
			$data = isset($insert["sma_data"]) ? $insert["sma_data"] : null; //[$i]["value"] => #item[title]#
		else if ($brokers_layer_type == "callhibernatemethod" && isset($insert["service_method"]) && $insert["service_method"] == "callQuery" && isset($insert["sma_query_type"]) && $insert["sma_query_type"] == "insert")
			$data = isset($insert["sma_data"]) ? $insert["sma_data"] : null; //[$i]["value"] => #item[title]#
		
		$exists = false;
		
		if ($data) {
			if (is_array($data)) {
				foreach ($data as $item) {
					if (array_key_exists("column", $item) && $item["column"] == $attr_name) {
						$exists = true;
						break;
					}
					else if (array_key_exists("value", $item) && isset($item["value_type"]) && $item["value_type"] == "string" && $item["value"] == "#{$tn}[$attr_name]#") {
						$exists = true;
						break;
					}
				}
			}
			else { //parse sql
				$sql_data = DB::convertDefaultSQLToObject($data);
				
				if (isset($sql_data["type"]) && $sql_data["type"] == "insert" && !empty($sql_data["attributes"]))
					foreach ($sql_data["attributes"] as $attr) {
						$column = isset($attr["column"]) ? $attr["column"] : null;
						
						if ($column == $attr_name) {
							$exists = true;
							break;
						}
					}
			}
		}
		
		//sets max value from DB
		if ($exists) {
			$broker_code = null;
			
			if ($brokers_layer_type == "insert")
				$broker_code = '$EVC->getBroker(' . (!empty($insert["dal_broker"]) ? '"' . $insert["dal_broker"] . '"' : '') . ');';
			else if (isset($insert["method_obj"]) && trim($insert["method_obj"])) {
				$method_obj = trim($insert["method_obj"]);
				$broker_code = substr($method_obj, 0, 1) == '$' || substr($method_obj, 0, 2) == '@$' ? $method_obj : '$' . $method_obj;
			}
			
			if ($broker_code) {
				$var_prefix = '$' . $tn;
				$options = self::getBrokerSettingsOptionsCode($WorkFlowTaskHandler, $insert);
				$options = $options ? str_replace("\n", "\n\t", $options) : "null";
				
				return 'if (!isset(' . $var_prefix . '["' . $attr_name . '"]) || !strlen(trim(' . $var_prefix . '["' . $attr_name . '"]))) 
	' . $var_prefix . '["' . $attr_name . '"] = ' . $broker_code . '->findObjectsColumnMax("' . $table_name . '", "' . $attr_name . '", ' . addcslashes($options, "'") . ');' . "\n";
			}
		}
		
		return "";
	}
	
	private static function isInsertBroker($broker_settings) {
		$brokers_layer_type = isset($broker_settings["brokers_layer_type"]) ? $broker_settings["brokers_layer_type"] : null;
		
		if ($brokers_layer_type == "callibatisquery" && isset($broker_settings["service_type"]) && $broker_settings["service_type"] == "insert") 
			return true;
		else if ($brokers_layer_type == "insert")
			return true;
		else if ($brokers_layer_type == "getquerydata" || $brokers_layer_type == "setquerydata") 
			return isset($broker_settings["sql"]) && preg_match("/^insert\s+/i", trim($broker_settings["sql"]));
		else if ($brokers_layer_type == "callhibernatemethod" && isset($broker_settings["service_method"]) && ($broker_settings["service_method"] == "getData" || $broker_settings["service_method"] == "setData")) 
			return isset($broker_settings["sma_sql"]) && preg_match("/^insert\s+/i", trim($broker_settings["sma_sql"]));
		else if ($brokers_layer_type == "callhibernatemethod" && isset($broker_settings["service_method"]) && ($broker_settings["service_method"] == "insert" || $broker_settings["service_method"] == "callInsert")) 
			return true;
		else if ($brokers_layer_type == "callhibernatemethod" && isset($broker_settings["service_method"]) && $broker_settings["service_method"] == "callQuery" && isset($broker_settings["sma_query_type"]) && $broker_settings["sma_query_type"] == "insert")
			return true;
		
		return false;
	}
	
	private static function isDBPrimitiveBroker($broker_settings) {
		return isset($broker_settings["brokers_layer_type"]) && in_array($broker_settings["brokers_layer_type"], array("callibatisquery", "callhibernatemethod", "getquerydata", "setquerydata", "insert", "update", "delete", "select", "procedure"));
	}
	
	private static function isIbatisBroker($broker_settings) {
		return isset($broker_settings["brokers_layer_type"]) && $broker_settings["brokers_layer_type"] == "callibatisquery";
	}
	
	//If you change this method, please make the same changes inside the method SequentialLogicalActivityResourceCreator::getUpdateActionPreviousCode
	private static function getUpdateActionPreviousCode($tables, $table_name, $tn, $attributes, $pks, $update, $WorkFlowTaskHandler) {
		//preparing code for some variables
		$code = self::getInsertActionPreviousCode($tables, $table_name, $tn, $attributes, $update, $WorkFlowTaskHandler);
		
		if ($pks) {
			$var_prefix = '$' . $tn;
		
			$code = $code ? substr($code, 0, -3) . "\n" : "'<?\n"; //if code exists: removing php end tag and single quote at the end of code; otherwise add single quote and php open tag
			
			//Preparing code for the update_pks and update
			//for each pk, checks if exists orig_pk and pk and if they different. If yes, sets save_pks = true and new_pk and old_pk variables, so they can be used in the update_pks action
			foreach ($pks as $pk)
				$code .= 'if (isset(' . $var_prefix . '["orig_' . $pk . '"]) && isset(' . $var_prefix . '["' . $pk . '"]) && ' . $var_prefix . '["orig_' . $pk . '"] != ' . $var_prefix . '["' . $pk . '"]) $_POST["' . $tn . '_save_pks"] = true;' . "\n";
			
			$code .= "\n";
			
			//In case $_POST["save_pks"] be false or if $tn[pk] is not set!
			foreach ($pks as $pk)
				$code .= 'if (isset(' . $var_prefix . '["orig_' . $pk . '"]) && !isset(' . $var_prefix . '["' . $pk . '"])) ' . $var_prefix . '["' . $pk . '"] = ' . $var_prefix . '["orig_' . $pk . '"];' . "\n";
				
			$code .= "\n" . 'if (!empty($_POST["' . $tn . '_save_pks"])) {' . "\n";
			
			//sets new_pk and old_pk variables
			foreach ($pks as $pk)
				$code .=  "\t" . $var_prefix . '["old_' . $pk . '"] = isset(' . $var_prefix . '["orig_' . $pk . '"]) ? ' . $var_prefix . '["orig_' . $pk . '"] : ' . $var_prefix . '["' . $pk . '"];' . "\n" . 
						"\t" . $var_prefix . '["new_' . $pk . '"] = ' . $var_prefix . '["' . $pk . '"];' . "\n";
			
			$code .= "}\n?>'";
		}
		
		return $code;
	}
	
	//For each PK, checks if exists $_POST[$tn][orig_$pk] and if it does sets: $_POST[$tn][$pk] = $_POST[$tn][orig_$pk]
	private static function getDeleteActionPreviousCode($tn, $pks) {
		$code = "";
		$var_prefix = '$' . $tn;
		
		foreach ($pks as $pk)
			$code .= 'if (isset(' . $var_prefix . '["orig_' . $pk . '"])) ' . $var_prefix . '["' . $pk . '"] = ' . $var_prefix . '["orig_' . $pk . '"];' . "\n";
		
		return $code ? "'<?\n" . $code . "?>'" : null;
	}
	
	//For each PK, unset $_GET[pk name]
	private static function getDeleteActionNextCode($tn, $pks) {
		$code = 'unset($' . $tn . ');' . "\n";
		
		foreach ($pks as $pk) 
			$code .= 'unset($_GET["' . $pk . '"]);' . "\n";
		
		return $code ? "'<?\n" . $code . "?>'" : null;
	}
	
	//If you change this method, please make the same changes inside the method SequentialLogicalActivityResourceCreator::getInsertActionNextCode
	private static function getInsertActionNextCode($tn, $insert, $pks_auto_increment, $WorkFlowTaskHandler, $db_broker, $include_db_broker, $db_driver, $include_db_driver, &$broker_external_var_name = null, $is_single_insert = false) {
		$code = "";
		
		if ($pks_auto_increment) {
			$var_prefix = '$' . $tn;
			$brokers_layer_type = isset($insert["brokers_layer_type"]) ? $insert["brokers_layer_type"] : null;
			
			if ($brokers_layer_type == "callhibernatemethod" && isset($insert["service_method"]) && $insert["service_method"] == "insert") {
				$sma_ids = isset($insert["sma_ids"]) ? $insert["sma_ids"] : null;
				
				foreach ($pks_auto_increment as $pk) 
					$code .= "\n" . $var_prefix . '["' . $pk . '"] = $' . $sma_ids . '["' . $pk . '"];';
				$code .= "\n";
				
				//sets the new pks into $_GET so the rest of the code could execute correctly, this is, if we want to show the new record details after an insert, we need to set the $_GET[pk_name] with the new inserted id
				if ($is_single_insert) {
					foreach ($pks_auto_increment as $pk) 
						$code .= "\n" . '$_GET["' . $pk . '"] = ' . $var_prefix . '["' . $pk . '"];';
					$code .= "\n";
				}
			}
			else {
				if ($brokers_layer_type == "insert") {
					$extra_code = "";
					$insert_db_broker = $include_db_broker ? $db_broker : null;
					$insert_db_driver = $include_db_driver ? (!empty($insert["db_driver"]) ? $insert["db_driver"] : $db_driver) : null;
					
					if ($insert_db_broker || $insert_db_driver) {
						$extra_code = "array(";
						$extra_code .= $insert_db_driver ? '"db_driver" => "' . $insert_db_driver . '"' : '';
						$extra_code .= $insert_db_broker ? ($insert_db_driver ? ', ' : '') . '"db_broker" => "' . $insert_db_broker . '"' : '';
						$extra_code .= ")";
					}
					
					$code .= '$' . $tn . '_status = $EVC->getBroker(' . (!empty($insert["dal_broker"]) ? '"' . $insert["dal_broker"] . '"' : '') . ')->getInsertedId(' . $extra_code . ');' . "\n";
				}
				else if (self::isInsertBroker($insert)) {
					$method_obj = isset($insert["method_obj"]) ? trim($insert["method_obj"]) : "";
					
					if ($method_obj) {
						$method_obj = substr($method_obj, 0, 1) == '$' || substr($method_obj, 0, 2) == '@$' ? $method_obj : '$' . $method_obj;
						$options = self::getBrokerSettingsOptionsCode($WorkFlowTaskHandler, $insert);
						
						$code .= '$' . $tn . '_status = ' . $method_obj . '->getInsertedId(' . addcslashes($options, "'") . ');' . "\n";
						
						if (substr($method_obj, 0, 6) != '$EVC->')
							$broker_external_var_name = substr($method_obj, 1); //removing $
					}
				}
				
				foreach ($pks_auto_increment as $pk) 
					$code .= "\n" . $var_prefix . '["' . $pk . '"] = $' . $tn . '_status;';
				$code .= "\n";
				
				//sets the new pks into $_GET so the rest of the code could execute correctly, this is, if we want to show the new record details after an insert, we need to set the $_GET[pk_name] with the new inserted id
				if ($is_single_insert) {
					foreach ($pks_auto_increment as $pk) 
						$code .= "\n" . '$_GET["' . $pk . '"] = $' . $tn . '_status;';
					$code .= "\n";
				}
			}
		}
		
		return $code ? "'<?\n" . $code . "?>'" : null;
	}
	
	private static function getInsertItemActionGetDefaultValues($tables, $table_name, $attributes) {
		$code = "return array(";
		
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		
		foreach ($attributes as $attr_name)
			$code .= "\n\t" . '"' . $attr_name . '" => $_GET["' . $attr_name . '"],';
		
		$code .= "\n);\n";
		
		return $code ? "'<?\n" . $code . "?>'" : null;
	}
	
	//If you change this method, please make the same changes inside the method SequentialLogicalActivityResourceCreator::getSelectItemActionNextCode
	private static function getSelectItemActionNextCode($tables, $table_name, $tn, $attributes) {
		$code = "";
		
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		
		foreach ($attributes as $attr_name) {
			$attr = isset($attrs[$attr_name]) ? $attrs[$attr_name] : null;
			
			if (isset($attr["type"]) && ObjTypeHandler::isDBTypeDate($attr["type"]))
				$code .= 'if (isset($' . $tn . '["' . $attr_name . '"]) && ($' . $tn . '["' . $attr_name . '"] == "0000-00-00 00:00:00" || $' . $tn . '["' . $attr_name . '"] == "0000-00-00")) $' . $tn . '["' . $attr_name . '"] = "";' . "\n";
		}
		
		return $code ? "'<?\n" . $code . "?>'" : null;
	}
	
	//If you change this method, please make the same changes inside the method SequentialLogicalActivityResourceCreator::getSelectItemsActionNextCode
	private static function getSelectItemsActionNextCode($tables, $table_name, $tn_plural, $attributes) {
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		$db_date_attrs = array();
		
		foreach ($attributes as $attr_name) {
			$attr = isset($attrs[$attr_name]) ? $attrs[$attr_name] : null;
			
			if (isset($attr["type"]) && ObjTypeHandler::isDBTypeDate($attr["type"]))
				$db_date_attrs[] = $attr_name;
		}
		
		if ($db_date_attrs) {
			$code = "'<?\n" . 
			'if (is_array($' . $tn_plural . '))' . "\n" . 
			'	foreach ($' . $tn_plural . ' as $k => &$v) {' . "\n";
			
			foreach ($db_date_attrs as $attr_name)
				$code .= '		if (isset($v["' . $attr_name . '"]) && ($v["' . $attr_name . '"] == "0000-00-00 00:00:00" || $v["' . $attr_name . '"] == "0000-00-00")) $v["' . $attr_name . '"] = "";' . "\n";
			
			$code .= "	}\n?>'";
			
			return $code;
		}
		
		return null;
	}
	
	//If you change this method, please make the same changes inside the method SequentialLogicalActivityResourceCreator::getHibernateGetAllActionNextCode
	private static function getHibernateGetAllActionNextCode($tn_plural) {
		return ""; //this function is deprecated bc the hibernate returns the same result array than ibatis
		
		return "'<?\n" . 
		'if (!empty($' . $tn_plural . ')) {' . "\n" . 
		'	$hbn_object_name = array_keys($' . $tn_plural . '[0]);' . "\n" . 
		'	$hbn_object_name = $hbn_object_name[0];' . "\n" . 
		"	\n" . 
		'	$items = array();' . "\n" . 
		'	$t = count($' . $tn_plural . ')' . "\n" . 
		"	\n" . 
		'	for ($i = 0; $i < $t; $i++)' . "\n" . 
		'		$items[] = isset($' . $tn_plural . '[$i][$hbn_object_name]) ? $' . $tn_plural . '[$i][$hbn_object_name] : null;' . "\n" . 
		"	\n" . 
		'	$' . $tn_plural . ' = $items;' . "\n" . 
		"}\n" . 
		"\n?>'";
	}
	
	//If you change this method, please make the same changes inside the method SequentialLogicalActivityResourceCreator::getHibernateGetActionNextCode
	private static function getHibernateGetActionNextCode($tn) {
		return ""; //this function is deprecated bc the hibernate returns the same result array than ibatis
		
		return "'<?\n" . 
		'if (!empty($' . $tn . ')) {' . "\n" . 
		'	$hbn_object_name = array_keys($' . $tn . ');' . "\n" . 
		'	$hbn_object_name = $hbn_object_name[0];' . "\n" . 
		'	$' . $tn . ' = isset($' . $tn . '[$hbn_object_name]) ? $' . $tn . '[$hbn_object_name] : null;' . "\n" . 
		"}\n" . 
		"\n?>'";
	}
	
	/* MULTIPE FORM METHODS */
	
	private static function getMultipleFormFormSettings($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, $insert, $update, $delete, $form_class = "", $panel_class = "", $panel_id = "") {
		$fc = trim("$form_class $panel_class");
		
		$after_actions_props = $actions_props;
		unset($after_actions_props["single_insert"]);
		
		$form_settings = self::getFormFormSettings($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $after_actions_props, null, $update, $delete, $fc, $panel_id);
		
		return $form_settings;
	}
	
	private static function getMultipleFormPTLCode($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, $insert, $update, $delete, $form_class = "", $panel_class = "", $panel_id = "") {
		$fc = trim("$form_class $panel_class");
		
		$after_actions_props = $actions_props;
		unset($after_actions_props["single_insert"]);
		
		$code = self::getFormPTLCode($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $after_actions_props, null, $update, $delete, $fc, $panel_id);
		
		return $code;
	}
	
	private static function getMultipleFormAddButtonSettings($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, &$generic_javascript, $insert, $update, $delete, $form_class = "", $panel_class = "", $panel_id = "") {
		if ($insert || !empty($actions_props["single_insert"])) {
			$on_click = 'addMultipleFormNewItem(this)';
			$ea_button_type = "insert";
			$ea_button_name = $tn . "_add";
			$ea_button_label = "Add";
			$ea_action_props = isset($actions_props["single_insert"]) ? $actions_props["single_insert"] : null;
			$ea_html = self::getFormFieldButtonExtraAttributesHtml($ea_button_type, $ea_button_name, $ea_button_label, $ea_action_props, $tn, $tn_label, $panel_id, $on_click);
			
			$insert_htmls = self::getMultipleFormInsertFieldsSettings($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, $insert, $update, $delete, $form_class, $panel_class, $panel_id);
			$panel_id_hash = HashCode::getHashCodePositive($panel_id);
			
			$generic_javascript .= "\n" . self::convertHtmlToJavascriptVariable("{$tn}_multiple_form_{$panel_id_hash}_before_insert_html", $insert_htmls[0]);
			$generic_javascript .= "\n" . self::convertHtmlToJavascriptVariable("{$tn}_multiple_form_{$panel_id_hash}_after_insert_html", $insert_htmls[1]);
			$generic_javascript .= "\n";
			
			return array(
				"form_containers" => array(
					array(
						"container" => array(
							"class" => "buttons add-button",
							"previous_html" => '<a href="javascript:void(0);"' . $ea_html . '><span class="icon add"></span> Add ' . $tn_label . '</a>'
						)
					)
				)
			);
		}
		
		return null;
	}
	
	private static function getMultipleFormAddButtonPTLCode($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, &$generic_javascript, $insert, $update, $delete, $form_class = "", $panel_class = "", $panel_id = "") {
		if ($insert || !empty($actions_props["single_insert"])) {
			$form_settings = self::getMultipleFormAddButtonSettings($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, $generic_javascript, $insert, $update, $delete, $form_class, $panel_class, $panel_id);
			$form_settings["parse_values"] = false;
			
			$html = HtmlFormHandler::createHtmlForm($form_settings);
			
			return $html;
		}
		
		return null;
	}
	
	private static function getMultipleFormInsertFieldsSettings($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, $insert, $update, $delete, $form_class, $panel_class, $panel_id) {
		$before_actions_props = $actions_props;
		unset($before_actions_props["links"]);
		unset($before_actions_props["single_update"]);
		unset($before_actions_props["single_delete"]);
		
		$after_actions_props = $actions_props;
		unset($after_actions_props["single_insert"]);
		
		$before_insert_fields = self::getFormFormSettings($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $before_actions_props, $insert, null, null, trim("add-item multiple-form $panel_class"), $panel_id);
		$after_insert_fields = self::getFormFormSettings($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $after_actions_props, null, $update, $delete, trim("$form_class $panel_class"), $panel_id);
		
		$before_insert_html = '';
		$after_insert_html = '';
		
		$before_insert_fields["parse_values"] = $after_insert_fields["parse_values"] = false;
		$before_insert_html = HtmlFormHandler::createHtmlForm($before_insert_fields);
		$after_insert_html = HtmlFormHandler::createHtmlForm($after_insert_fields);
		
		//changing onInsert handler to //onMultipleFormSingleInsert
		$before_insert_html = str_replace('onClick="return executeSingleAction(this, onInsert);"', 'onClick="return executeSingleAction(this, onMultipleFormSingleInsert);"', $before_insert_html);
		
		//Removing default values
		foreach ($attributes as $attr_name) {
			$before_insert_html = str_replace("#{$tn}[$attr_name]#", '', $before_insert_html);
			$after_insert_html = str_replace("#{$tn}[$attr_name]#", "#$attr_name#", str_replace("=\"#{$tn}[$attr_name]#\"", '=""', $after_insert_html)); //#[idx][$attr_name]# replacements is for the links
		}
		
		return array($before_insert_html, $after_insert_html);
	}
	
	/* FORM METHODS */
	
	private static function getFormFormSettings($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, $insert, $update, $delete, $panel_class = "", $panel_id = "") {
		$insertable = $insert || !empty($actions_props["single_insert"]);
		$updatable = $update || !empty($actions_props["single_update"]);
		$deletable = $delete || !empty($actions_props["single_delete"]);
		$fields = self::getFieldsSettings($tables, $table_name, $tn, $attributes, $pks, $pks_auto_increment, $actions_props, $insertable, $updatable, $deletable);
		$has_action = $insertable || $updatable || $deletable;
		
		$buttons = array();
		
		if ($insert || !empty($actions_props["single_insert"]))
			$buttons[] = self::getFormFieldButtonSettings("insert", $tn . "_add", "Add", $actions_props["single_insert"], $tn, $tn_label, $panel_id);
		
		if ($update || !empty($actions_props["single_update"]))
			$buttons[] = self::getFormFieldButtonSettings("update", $tn . "_save", "Save", $actions_props["single_update"], $tn, $tn_label, $panel_id);
		
		if ($delete || !empty($actions_props["single_delete"])) {
			$action_props = isset($actions_props["single_delete"]) ? $actions_props["single_delete"] : null;
			
			if (!isset($action_props["confirmation_message"])) {
				if ($child_tables) {
					$action_props["confirmation_message"] .= "You are about to delete this $tn_label.\nNote that this $tn_label may have some dependencies/children in the following table(s):";
					
					for ($i = 0; $i < count($child_tables); $i++)
						$action_props["confirmation_message"] .= "\n- " + self::getName($child_tables[$i]);
					
					$action_props["confirmation_message"] .= "\n\nYou should delete the correspondent dependencies/children first, and only then delete this $tn_label.\n\nAre you sure that you wish to proceed with this $tn_label deletion (before delete it's dependencies)?";
				}
				else
					$action_props["confirmation_message"] = "Do you wish to delete this $tn_label?";
			}
			
			$buttons[] = self::getFormFieldButtonSettings("delete", $tn . "_delete", "Delete", $action_props, $tn, $tn_label, $panel_id);
		}
		
		$links = array();
		
		if (!empty($actions_props["links"]))
			foreach ($actions_props["links"] as $link) {
				$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
				
				//I may want a link without a "a" html element, like a separator for the following links
				self::prepareLink($link, $tn, $attrs, $pks, false, false, false, false);
				
				$links[] = array(
					"field" => array(
						"class" => "link link-$tn " . (isset($link["class"]) ? $link["class"] : ""),
						"input" => array(
							"type" => "link",
							"value" => !empty($link["value"]) ? $link["value"] : (isset($link["title"]) ? $link["title"] : null),
							"href" => isset($link["url"]) ? $link["url"] : null,
							"target" => isset($link["target"]) ? $link["target"] : null,
							"title" => isset($link["title"]) ? $link["title"] : null,
							"extra_attributes" => isset($link["extra_attributes"]) ? $link["extra_attributes"] : null,
							"previous_html" => isset($link["previous_html"]) ? $link["previous_html"] : null,
							"next_html" => isset($link["next_html"]) ? $link["next_html"] : null
						)
					)
				);
			}
			
		$containers = array();
		
		if ($fields)
			$containers[] = array(
				"container" => array(
					"class" => "fields-container",
					"previous_html" => "",
					"next_html" => "",
					"elements" => $fields
				)
			);
		
		if ($buttons)
			$containers[] = array(
				"container" => array(
					"class" => "buttons",
					"elements" => $buttons
				)
			);
		
		if ($links)
			$containers[] = array(
				"container" => array(
					"class" => "links",
					"elements" => $links
				)
			);
		
		$form_id = "form_" . rand(0, 10000);
		
		return array(
			"with_form" => $has_action,
			"form_id" => $form_id,
			"form_method" => "post",
			"form_class" => "",
			"form_on_submit" => "",
			"form_action" => "",
			"form_type" => "horizontal",
			"form_containers" => array(
				array(
					"container" => array(
						"id" => $panel_id,
						"class" => 'form-' . str_replace(array("_", " "), "-", self::getParsedTableName($tn)) . ' ' . $panel_class, //$tn name can have schema
						"previous_html" => "",
						"next_html" => "",
						"elements" => $containers
					)
				)
			),
			"form_css" => "",
			"form_js" => $has_action ? 'if (typeof MyJSLib != "undefined") { MyJSLib.FormHandler.initForm( $("#' . $form_id . '")[0] ) }' : ''
		);
	}
	
	private static function getFormPTLCode($tables, $table_name, $tn, $tn_label, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, $insert, $update, $delete, $panel_class = "", $panel_id = "") {
		$code = '';
		$has_action = $insert || $update || $delete || !empty($actions_props["single_insert"]) || !empty($actions_props["single_update"]) || !empty($actions_props["single_delete"]);
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		$form_id = null;
		
		if ($has_action) {
			$form_id = "form_" . rand(0, 10000);
			$code .= '<form id="' . $form_id . '" method="post" onSubmit="return (typeof MyJSLib == \'undefined\' || MyJSLib.FormHandler.formCheck(this));" enctype="multipart/form-data">';
		}
		
		$code .= '<div class="form-' . str_replace(array("_", " "), "-", self::getParsedTableName($tn)) . ' ' . $panel_class . '"' . ($panel_id ? ' id="' . $panel_id . '"' : '') . '>
		<div class="fields-container">'; //$tn name can have schema
		
		$insertable = $insert || !empty($actions_props["single_insert"]);
		$updatable = $update || !empty($actions_props["single_update"]);
		$deletable = $delete || !empty($actions_props["single_delete"]);
		$editable = $insertable || $updatable;
		
		foreach ($attributes as $attr_name) {
			$attr_props = isset($actions_props["attributes_settings"][$attr_name]) ? $actions_props["attributes_settings"][$attr_name] : null;
			$is_pk = in_array($attr_name, $pks);
			$is_pk_auto_increment = in_array($attr_name, $pks_auto_increment);
			
			//if only insert and attr is a pk auto-incremented, doesn't show attribute
			if ($insertable && !$updatable && !$deletable && $is_pk_auto_increment)
				continue 1;
			
			$single_update_action_type = isset($actions_props["single_update"]["action_type"]) ? $actions_props["single_update"]["action_type"] : null;
			$code .= self::getFormFieldGroupHtml($tn, $attr_name, $attrs, $attr_props, $pks, $is_pk, $is_pk_auto_increment, $editable, $deletable, $single_update_action_type == "ajax_on_blur");
		}
		
		$code .= '</div>';
		
		//Preparing insert/update/delete html
		if ($has_action) {
			$code .= '<div class="buttons">';
			
			if ($insert || !empty($actions_props["single_insert"]))
				$code .= self::getFormFieldButtonHtml("insert", $tn . "_add", "Add", $actions_props["single_insert"], $tn, $tn_label, $panel_id);
			
			if ($update || !empty($actions_props["single_update"]))
				$code .= self::getFormFieldButtonHtml("update", $tn . "_save", "Save", $actions_props["single_update"], $tn, $tn_label, $panel_id);
			
			if ($delete || !empty($actions_props["single_delete"])) {
				$action_props = isset($actions_props["single_delete"]) ? $actions_props["single_delete"] : null;
				
				if (!isset($action_props["confirmation_message"]))  {
					if ($child_tables) {
						$action_props["confirmation_message"] .= "You are about to delete this $tn_label.\nNote that this $tn_label may have some dependencies/children in the following table(s):";
						
						for ($i = 0; $i < count($child_tables); $i++)
							$action_props["confirmation_message"] .= "\n- " + self::getName($child_tables[$i]);
						
						$action_props["confirmation_message"] .= "\n\nYou should delete the correspondent dependencies/children first, and only then delete this $tn_label.\n\nAre you sure that you wish to proceed with this $tn_label deletion (before delete it's dependencies)?";
					}
					else
						$action_props["confirmation_message"] = "Do you wish to delete this $tn_label?";
				}
				
				$code .= self::getFormFieldButtonHtml("delete", $tn . "_delete", "Delete", $action_props, $tn, $tn_label, $panel_id);
			}
			
			$code .= '</div>';
		}
		
		if (!empty($actions_props["links"]))
			$code .= '<div class="links">' . self::getFormFieldLinksHtml($actions_props["links"], $tn, $attrs, $pks) . '</div>';
		
		$code .= '</div>';
		
		if ($has_action)
			$code .= '</form>
			<script>if (typeof MyJSLib != "undefined") { MyJSLib.FormHandler.initForm( $("#' . $form_id . '")[0] ) }</script>';
		
		return $code;
	}
	
	private static function getFormFieldButtonSettings($button_type, $button_name, $button_label, $action_props, $tn, $tn_label, $panel_id, $is_multiple = false) {
		$action_type = isset($action_props["action_type"]) ? $action_props["action_type"] : null;
		$confirmation_message = isset($action_props["confirmation_message"]) ? $action_props["confirmation_message"] : null;
		$is_ajax = substr($action_type, 0, 5) == "ajax_";
		$btn_class = str_replace(array(" ", "_"), "-", $button_type);
		
		if ($is_ajax) {
			if ($is_multiple) {
				$callback = $button_type == "insert_update" ? ', onMultipleInsertUpdate' : ($button_type == "insert" ? ', onMultipleInsert' : ($button_type == "update" ? ', onMultipleUpdate' : ($button_type == "delete" ? ', onMultipleDelete' : '')));
				$on_click = 'return executeMultipleActions(this' . $callback . ');';
			}
			else {
				$callback = $button_type == "insert" ? ', onInsert' : ($button_type == "update" ? ', onUpdate' : ($button_type == "delete" ? ', onDelete' : ''));
				$on_click = 'return executeSingleAction(this' . $callback . ');';
			}
			
			$extra_attributes = self::getFormFieldButtonExtraAttributesSettings($button_type, $button_name, $button_label, $action_props, $tn, $tn_label, $panel_id, $on_click);
			unset($extra_attributes["data-confirmation"]);
			unset($extra_attributes["data-confirmation-message"]);
			
			return array(
				"field" => array(
					"class" => "button button-" . $btn_class . " button-" . $btn_class . "-$tn",
					"input" => array(
						"type" => "button",
						"name" => $button_name,
						"value" => $button_label,
						"confirmation" => !empty($confirmation_message),
						"confirmation_message" => $confirmation_message,
						"extra_attributes" => $extra_attributes,
					)
				)
			);
		}
		
		return array(
			"field" => array(
				"class" => "button button-" . $btn_class . " button-" . $btn_class . "-$tn",
				"input" => array(
					"type" => "submit",
					"name" => $button_name,
					"value" => $button_label,
					"confirmation" => !empty($confirmation_message),
					"confirmation_message" => $confirmation_message,
				)
			)
		);
	}
	
	private static function getFormFieldButtonHtml($button_type, $button_name, $button_label, $action_props, $tn, $tn_label, $panel_id, $is_multiple = false) {
		$button_settings = self::getFormFieldButtonSettings($button_type, $button_name, $button_label, $action_props, $tn, $tn_label, $panel_id, $is_multiple);
		$button_field = isset($button_settings["field"]) ? $button_settings["field"] : null;
		$HtmlFormHandler = new HtmlFormHandler(array("parse_values" => false));
		return $HtmlFormHandler->getFieldInputHtml($button_field);
		/*
		$action_type = isset($action_props["action_type"]) ? $action_props["action_type"] : null;
		$is_ajax = substr($action_type, 0, 5) == "ajax_";
		$confirm_attrs = !empty($action_props["confirmation_message"]) ? ' data-confirmation="1" data-confirmation-message="' . $action_props["confirmation_message"] . '"' : '';
		
		if ($is_ajax) {
			if ($is_multiple) {
				$callback = $button_type == "insert_update" ? ', onMultipleInsertUpdate' : ($button_type == "insert" ? ', onMultipleInsert' : ($button_type == "update" ? ', onMultipleUpdate' : ($button_type == "delete" ? ', onMultipleDelete' : '')));
				$on_click = 'return executeMultipleActions(this' . $callback . ');';
			}
			else {
				$callback = $button_type == "insert" ? ', onInsert' : ($button_type == "update" ? ', onUpdate' : ($button_type == "delete" ? ', onDelete' : ''));
				$on_click = 'return executeSingleAction(this' . $callback . ');';
			}
			
			$extra_attributes = self::getFormFieldButtonExtraAttributesHtml($button_type, $button_name, $button_label, $action_props, $tn, $tn_label, $panel_id, $on_click);
			$confirm_attrs = "";
		}
		
		$btn_class = str_replace(array(" ", "_"), "-", $button_type);
		
		$code = '<div class="button button-' . $btn_class . " button-" . $btn_class . "-$tn" . '">
				<input type="' . ($is_ajax ? "button" : "submit") . '" value="' . $button_label . '" name="' . $button_name . '"' . $confirm_attrs . $extra_attributes . ' />
			</div>';
		
		return $code;*/
	}
	
	private static function getFormFieldButtonExtraAttributesSettings($button_type, $button_name, $button_label, $action_props, $tn, $tn_label, $panel_id, $on_click = false) {
		$extra_attributes = array();
		
		$extra_attributes[] = array("name" => "data-panel-id-hash", "value" => HashCode::getHashCodePositive($panel_id)); //even if no ajax, bc of the addNewItem function
		
		if ($on_click)
			$extra_attributes[] = array("name" => "onClick", "value" => $on_click);
		
		if (!empty($action_props["confirmation_message"])) {
			$extra_attributes[] = array("name" => "data-confirmation", "value" => 1);
			$extra_attributes[] = array("name" => "data-confirmation-message", "value" => $action_props["confirmation_message"]);
		}
		
		$extra_attributes[] = array("name" => "data-table-name", "value" => $tn);
		$extra_attributes[] = array("name" => "data-button-name", "value" => $button_name);
		$extra_attributes[] = array("name" => "data-button-type", "value" => $button_type);
		
		$action_type = isset($action_props["action_type"]) ? $action_props["action_type"] : null;
		$is_ajax = substr($action_type, 0, 5) == "ajax_";
		
		if ($is_ajax) {
			$bl = $button_label . (strtolower($button_label) == "add" ? "e" : "");
			
			if (!empty($action_props["ajax_url"]))
				$extra_attributes[] = array("name" => "data-url", "value" => $action_props["ajax_url"]);
			
			if (empty($action_props["ok_msg_message"]))
				$action_props["ok_msg_message"] = $tn_label . ' ' . strtolower($bl) . 'd successfully!';
			
			if (empty($action_props["error_msg_message"]))
				$action_props["error_msg_message"] = 'Error: ' . $tn_label . ' ' . strtolower($bl) . 'd unsuccessfully!';
			
			$extra_attributes[] = array("name" => "data-ok-msg-message", "value" => $action_props["ok_msg_message"]);
			$extra_attributes[] = array("name" => "data-error-msg-message", "value" => $action_props["error_msg_message"]);
			
			if (!empty($action_props["ok_msg_redirect_url"]))
				$extra_attributes[] = array("name" => "data-ok-msg-redirect-url", "value" => $action_props["ok_msg_redirect_url"]);
			
			if (!empty($action_props["error_msg_redirect_url"]))
				$extra_attributes[] = array("name" => "data-error-msg-redirect-url", "value" => $action_props["error_msg_redirect_url"]);
		}
		
		return $extra_attributes;
	}
	
	private static function getFormFieldButtonExtraAttributesHtml($button_type, $button_name, $button_label, $action_props, $tn, $tn_label, $panel_id, $on_click = false) {
		$extra_attributes = self::getFormFieldButtonExtraAttributesSettings($button_type, $button_name, $button_label, $action_props, $tn, $tn_label, $panel_id, $on_click);
		
		$code = '';
		foreach ($extra_attributes as $extra_attribute)
			if (!empty($extra_attribute["name"])) {
				$value = isset($extra_attribute["value"]) ? $extra_attribute["value"] : null;
				$code .= ' ' . $extra_attribute["name"] . '="' . addcslashes($value, '"') . '"';
			}
		
		return $code;
	}
	
	private static function getFormFieldGroupHtml($tn, $attr_name, $attrs, $attr_props, $pks, $is_pk, $is_pk_auto_increment, $editable, $deletable, $ajax_on_blur = false) {
		$db_attr_props = isset($attrs[$attr_name]) ? $attrs[$attr_name] : null;
		$input_html = self::getFormFieldInputHtml($tn, $attr_name, $attrs, $attr_props, $pks, $is_pk, $is_pk_auto_increment, $editable, $deletable, $input_type, $ajax_on_blur);
		$db_attr_type = isset($db_attr_props["type"]) ? $db_attr_props["type"] : null;
		
		return '<div class="field field-' . strtolower(str_replace(" ", "-", $db_attr_type)) . " " . $attr_name . ($is_pk ? " is-pk" : "") . ($is_pk_auto_increment ? " is-pk-auto-increment" : "") . " field-" . $input_type . (!empty($attr_props["class"]) ? " " . self::getFormFieldAttrPropHtml($attr_props["class"]) : "") . '">
			' . self::getFormFieldLabelHtml($attr_name, $attr_props) . '
			' . $input_html . '
			</div>';
	}
	
	private static function getFormFieldLabelHtml($attr_name, $attr_props) {
		$label_previous_html = isset($attr_props["label_previous_html"]) ? $attr_props["label_previous_html"] : null;
		$label_next_html = isset($attr_props["label_next_html"]) ? $attr_props["label_next_html"] : null;
		
		return self::getFormFieldAttrPropHtml($label_previous_html) . 
			'<label' . (!empty($attr_props["label_class"]) ? ' class="' . self::getFormFieldAttrPropHtml($attr_props["label_class"]) . '"' : '') . '>' . 
			(isset($attr_props["label_value"]) && strlen($attr_props["label_value"]) ? self::getFormFieldAttrPropHtml($attr_props["label_value"]) : self::getName($attr_name)) . 
			': </label>' . 
			self::getFormFieldAttrPropHtml($label_next_html);
	}
	
	private static function getFormFieldAttrPropHtml($attr_prop) {
		if ($attr_prop) {
			$attr_prop_type = PHPUICodeExpressionHandler::getValueType($attr_prop, array("empty_string_type" => "string", "non_set_type" => "string"));
			
			if (empty($attr_prop_type)) //if php code like a $_GET var
				return '<ptl:echo @' . $attr_prop . ' />';
		}
		return $attr_prop;
	}
		
	private static function getFormFieldInputHtml($tn, $attr_name, $attrs, $attr_props, $pks, $is_pk, $is_pk_auto_increment, $editable, $deletable, &$input_type = null, $ajax_on_blur = false) {
		$db_attr_props = isset($attrs[$attr_name]) ? $attrs[$attr_name] : null;
		$input_attrs = self::getFormFieldInputAttrs($tn, $db_attr_props, $input_type, array("ajax_on_blur" => $ajax_on_blur));
		
		if (!empty($attr_props["type"]))
			$input_type = $attr_props["type"];
		
		$input_previous_html = isset($attr_props["input_previous_html"]) ? $attr_props["input_previous_html"] : null;
		$code = self::getFormFieldAttrPropHtml($input_previous_html);
		
		$input_class = isset($attr_props["input_class"]) ? $attr_props["input_class"] : null;
		
		$field_value = isset($attr_props["input_value"]) && strlen($attr_props["input_value"]) ? self::getFormFieldAttrPropHtml($attr_props["input_value"]) : '<ptl:echo str_replace(\'"\', \'&quot;\', (@\\$input[' . $tn . '][' . $attr_name . '] )) />';
		$field_name = $tn . '[' . $attr_name . ']';
		$field_class = $input_class ? ' class="' . self::getFormFieldAttrPropHtml($input_class) . '"' : '';
		
		$orig_field_value = $field_value;
		
		if (!empty($attr_props["available_values"]))  {
			$field_value = '
			<ptl:var:av_exists false/>
			<ptl:var:var_aux @\\$input[' . $tn . '][' . $attr_name . '] />';
			
			if (is_array($attr_props["available_values"]))
				$field_value .= '<ptl:var:avs ' . str_replace(" => ", " =&gt; ", str_replace("\n", "", var_export($attr_props["available_values"], true))) . ' />';
			else //must be a string
				$field_value .= '<ptl:var:avs @\\$input[' . $attr_props["available_values"] . ']/>';
			
			$field_value .= '
			<ptl:if is_array(\\$avs) />
				<ptl:foreach \\$avs k v>
					<ptl:if \\$k == \\$var_aux>
						<ptl:echo \\$v/>
						<ptl:var:av_exists true/>
						<ptl:break/>
					</ptl:if>
				</ptl:foreach>
			</ptl:if>
			<ptl:if \\$av_exists>
				<ptl:echo \\$var_aux/>
			</ptl:if>';
		}
		
		if ($input_type == "hidden") //this is used when the user does include the pks in $attributes, and the system adds them automatically by as hidden fields...
			$code .= '<input' . $field_class . ' type="hidden" name="' . $field_name . '" value="' . $orig_field_value . '" />';
		else if (!$editable) {
			if ($input_type == "link") {
				$href = isset($attr_props["href"]) ? $attr_props["href"] : null;
				$target = isset($attr_props["target"]) ? $attr_props["target"] : null;
				
				$code .= '<a class="field-value' . ($input_class ? ' ' . self::getFormFieldAttrPropHtml($input_class) : '') . '" href="' . self::getHrefWithPks($href, $tn, $attrs, $pks, false, false, true) . '"' . ($target ? ' target="' . self::getFormFieldAttrPropHtml($target) . '"' : "") . '>' . $field_value . '</a>';
			}
			else {
				if (empty($attr_props["type"]))
					$input_type = "label";
				
				//$code .= '<span class="field-value' . ($input_class ? ' ' . self::getFormFieldAttrPropHtml($input_class) : '') . '">' . $field_value . '</span>';
				//THIS IS NOT TESTED
				$HtmlFormHandler = new HtmlFormHandler(array("parse_values" => false));
				$code .= $HtmlFormHandler->getFieldInputHtml(array(
					"input" => array(
						"type" => $input_type,
						"class" => self::prepareFieldSettingExtraStringToConcatenateWithAttrProp($input_class, "field-value", true) . " " . $input_class,
						"value" => $field_value,
					)
				));
			}
			
			if ($deletable && $is_pk)
				$code .= '<input' . $field_class . ' type="hidden" name="' . $field_name . '" value="' . $orig_field_value . '" />';
		}
		else if ($is_pk_auto_increment) {
			$input_type = "label";
			
			$code .= '<span class="field-value' . ($input_class ? ' ' . self::getFormFieldAttrPropHtml($input_class) : '') . '">' . $orig_field_value . '</span>
			<input' . $field_class . ' type="hidden" name="' . $field_name . '" value="' . $orig_field_value . '" />';
		}
		else {
			if ($is_pk && ($input_type != "hidden" || !empty($attr_props["options"])))
				$code .= '<input' . $field_class . ' type="hidden" name="' . $tn . '[orig_' . $attr_name . ']" value="' . $orig_field_value . '" />';
			
			if (!empty($attr_props["options"]) && empty($attr_props["type"]))
				$input_type = "select"; //only change this if $attr_props["type"] is empty.
			
			if ($input_type == "textarea")
				$code .= '<textarea' . $field_class . ' name="' . $field_name . '"' . $input_attrs . '>' . $orig_field_value . '</textarea>';
			else if ($input_type == "select") {
				$code .= '<select' . $field_class . ' name="' . $field_name . '"' . $input_attrs;
				
				if (!empty($attr_props["options"])) {
					if (!empty($attr_props["options_javascript_variable_name"]))
						$code .= ' data-options-javascript-variable-name="' . $attr_props["options_javascript_variable_name"] . '"';
					
					$code .= '>';
					
					if (is_array($attr_props["options"]))
						foreach($attr_props["options"] as $k => $v)
							$code .= '<option value="' . $k . '"<ptl:echo ("' . $k . '" == @\\$input[' . $tn . '][' . $attr_name . '] ? " selected" : "")/>>' . $v . '</option>';
					else {
						$var_name = $attr_props["options"];
						
						$code .= '<ptl:if is_array(@\\$input[' . $var_name . '])>
									<ptl:foreach \\$input[' . $var_name . '] k v>
										<option value="<ptl:echo str_replace(\'"\', \'&quot;\', \\$k) />"<ptl:echo (\\$k == @\\$input[' . $tn . '][' . $attr_name . '] ? " selected" : "")/>><ptl:echo \\$v/></option>
									</ptl:foreach>
								</ptl:if>';
					}
				}
				else 
					$code .= '>';
				
				$code .= '</select>';
			}
			else if ($input_type == "checkbox" || $input_type == "radio") {
				$options = isset($attr_props["options"]) ? $attr_props["options"] : null;
				
				if (is_array($options)) {
					$t = count($options);
					
					foreach($options as $k => $v) {
						if ($t > 1)
							$code .= '<div' . $field_class . '><label>' . $v . '</label><input type="' . $input_type . '" name="' . $field_name . '" value="' . $k . '" <ptl:echo (@\\$input[' . $tn . '][' . $attr_name . '] == "' . $k . '" ? "checked" : "")' . $input_attrs . ' /></div>';
						else
							$code .= '<input' . $field_class . ' type="' . $input_type . '" name="' . $field_name . '" value="' . $k . '" <ptl:echo (@\\$input[' . $tn . '][' . $attr_name . '] == "' . $k . '" ? "checked" : "") />' . $input_attrs . ' />';
					}
				}
				else if ($options) {
					$var_name = $options;
					
					$code .= '<ptl:if is_array(@\\$input[' . $var_name . '])>
								<ptl:var:t count(\\$input[' . $var_name . ']) />
								
								<ptl:foreach \\$input[' . $var_name . '] k v>
									<ptl:if \\$t &gt; 1>
										<div' . $field_class . '>
											<label><ptl:echo \\$v/></label>
											<input type="' . $input_type . '" name="' . $field_name . '" value="<ptl:echo str_replace(\'"\', \'&quot;\', \\$k) />" <ptl:echo (\\$k == @\\$input[' . $tn . '][' . $attr_name . '] ? "checked" : "")/>' . $input_attrs . ' />
										</div>
									<ptl:else>
										<input' . $field_class . ' type="' . $input_type . '" name="' . $field_name . '" value="<ptl:echo str_replace(\'"\', \'&quot;\', \\$k) />" <ptl:echo (\\$k == @\\$input[' . $tn . '][' . $attr_name . '] ? "checked" : "")/>' . $input_attrs . ' />
									</ptl:if>
								</ptl:foreach>
							</ptl:if>';
				}
				else
					$code .= '<input' . $field_class . ' type="' . $input_type . '" name="' . $field_name . '" value="1" <ptl:echo (@\\$input[' . $tn . '][' . $attr_name . '] == 1 ? "checked" : "") />' . $input_attrs . ' />';
			}
			else if (!in_array($input_type, self::$editable_input_types)) {
				$code .= '<input type="hidden" name="' . $field_name . '" value="' . $orig_field_value . '" />';
				
				//THIS IS NOT TESTED
				$HtmlFormHandler = new HtmlFormHandler(array("parse_values" => false));
				$code .= $HtmlFormHandler->getFieldInputHtml(array(
					"input" => array(
						"type" => $input_type,
						"class" => self::prepareFieldSettingExtraStringToConcatenateWithAttrProp($input_class, "field-value", true) . " " . $field_class,
						"value" => $field_value,
					)
				));
			}
			else
				$code .= '<input' . $field_class . ' type="' . $input_type . '" name="' . $field_name . '" value="' . $orig_field_value . '"' . ($input_type != "hidden" ? $input_attrs : '') . ' />';
		}
		
		$input_next_html = isset($attr_props["input_next_html"]) ? $attr_props["input_next_html"] : null;
		$code .= self::getFormFieldAttrPropHtml($input_next_html);
		
		return $code;
	}
	
	private static function getFormFieldLinksHtml($links, $tn, $attrs, $pks) {
		$code = '';
		
		if ($links) {
			foreach ($links as $link) {
				//I may want a link without a "a" html element, like a separator for the following links
				self::prepareLink($link, $tn, $attrs, $pks, false, false, true, false);
				
				$href = !empty($link["url"]) ? ' href="' . self::getFormFieldAttrPropHtml($link["url"]) . '"' : '';
				$title = !empty($link["title"]) ? ' title="' . self::getFormFieldAttrPropHtml($link["title"]) . '"' : '';
				$target = !empty($link["target"]) ? ' target="' . self::getFormFieldAttrPropHtml($link["target"]) . '"' : '';
				
				$extra_attributes = "";
				
				if (!empty($link["extra_attributes"])) {
					if(is_array($link["extra_attributes"]))
						foreach ($link["extra_attributes"] as $f)
							if (!empty($f["name"])) {
								$value = isset($f["value"]) ? $f["value"] : null;
								$extra_attributes .= ' ' . self::getFormFieldAttrPropHtml($f["name"]) . '="' . self::getFormFieldAttrPropHtml($value) . '"';
							}
					else
						$extra_attributes .= ' ' . self::getFormFieldAttrPropHtml($link["extra_attributes"]);
				}
				
				$class = isset($link["class"]) ? $link["class"] : '';
				$previous_html = isset($link["previous_html"]) ? $link["previous_html"] : '';
				$value = isset($link["value"]) ? $link["value"] : '';
				$next_html = isset($link["next_html"]) ? $link["next_html"] : '';
				
				$code .= '<div class="link link-' . $tn . ' ' . self::getFormFieldAttrPropHtml($class) . '">' . 
					self::getFormFieldAttrPropHtml($previous_html) . 
					'<a' . $href . $title . $target . $extra_attributes . '>' . 
						self::getFormFieldAttrPropHtml($value ? $value : (isset($link["title"]) ? $link["title"] : null)) . 
					'</a>' . 
					self::getFormFieldAttrPropHtml($next_html) . 
				'</div>';
			}
		}
		
		return $code;
	}
	
	/* TABLE METHODS */
	
	private static function getTableFormSettings($tables, $table_name, $tn, $tn_label, $tn_plural, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, &$generic_javascript, $pagination = false, $panel_class = "", $panel_id = "") {
		$updatable = !empty($actions_props["single_update"]) || !empty($actions_props["multiple_update"]) || !empty($actions_props["multiple_insert_update"]);
		$deletable = !empty($actions_props["single_delete"]) || !empty($actions_props["multiple_delete"]);
		
		$has_form = !empty($actions_props["multiple_insert_update"]) || !empty($actions_props["multiple_insert"]) || !empty($actions_props["multiple_update"]) || !empty($actions_props["multiple_delete"]);
		
		$has_form_default_action = (
			(isset($actions_props["multiple_insert_update"]["action_type"]) && substr($actions_props["multiple_insert_update"]["action_type"], 0, 5) != "ajax_") || 
			(isset($actions_props["multiple_insert"]["action_type"]) && substr($actions_props["multiple_insert"]["action_type"], 0, 5) != "ajax_") || 
			(isset($actions_props["multiple_update"]["action_type"]) && substr($actions_props["multiple_update"]["action_type"], 0, 5) != "ajax_") || 
			(isset($actions_props["multiple_delete"]["action_type"]) && substr($actions_props["multiple_delete"]["action_type"], 0, 5) != "ajax_")
		);
		
		$fields = self::getFieldsSettings($tables, $table_name, $tn, $attributes, $pks, $pks_auto_increment, $actions_props, false, $updatable, $deletable, true);
		
		if (!empty($actions_props["multiple_insert_update"]) || 
			!empty($actions_props["multiple_insert"]) || 
			!empty($actions_props["single_insert"]) || 
			!empty($actions_props["single_update"]) || 
			!empty($actions_props["single_delete"])
		) {
			$field = array(
				"field" => array(
					"class" => 'actions',
				)
			);
			
			if (!empty($actions_props["multiple_insert_update"]) || 
				!empty($actions_props["multiple_insert"]) || 
				!empty($actions_props["single_insert"])
			) {
				$on_click = 'addNewItem(this)';
				$ea_button_type = !empty($actions_props["multiple_insert_update"]) ? "insert_update" : "insert";
				$ea_button_name = !empty($actions_props["multiple_insert_update"]) ? $tn . "_add_save" : $tn . "_add";
				$ea_button_label = !empty($actions_props["multiple_insert_update"]) ? "Save" : "Add";
				$ea_action_props = !empty($actions_props["multiple_insert_update"]) ? $actions_props["multiple_insert_update"] : (
					!empty($actions_props["multiple_insert"]) ? $actions_props["multiple_insert"] : (
						!empty($actions_props["single_insert"]) ? $actions_props["single_insert"] : null
					)
				);
				$ea_html = self::getFormFieldButtonExtraAttributesHtml($ea_button_type, $ea_button_name, $ea_button_label, $ea_action_props, $tn, $tn_label, $panel_id, $on_click);
				
				$field["field"]["label"] = array(
					"previous_html" => '<span class="icon add"' . $ea_html . '></span>',
				);
			}
			
			$single_delete_action_props = isset($actions_props["single_delete"]) ? $actions_props["single_delete"] : null;
			
			if ($single_delete_action_props && !isset($single_delete_action_props["confirmation_message"])) {
				if ($child_tables) {
					$single_delete_action_props["confirmation_message"] .= "You are about to delete this $tn_label.\nNote that this $tn_label may have some dependencies/children in the following table(s):";
					
					for ($i = 0; $i < count($child_tables); $i++)
						$single_delete_action_props["confirmation_message"] .= "\n- " + self::getName($child_tables[$i]);
					
					$single_delete_action_props["confirmation_message"] .= "\n\nYou should delete the correspondent dependencies/children first, and only then delete this $tn_label.\n\nAre you sure that you wish to proceed with this $tn_label deletion (before delete it's dependencies)?";
				}
				else
					$single_delete_action_props["confirmation_message"] = "Do you wish to delete this $tn_label?";
			}
			
			if (!empty($actions_props["single_update"])) {
				$extra_attributes = self::getFormFieldButtonExtraAttributesSettings("update", $tn . "_save", "Save", $actions_props["single_update"], $tn, $tn_label, $panel_id, 'executeSingleAction(this, onUpdate)');
				$delete_eas_html = self::getFormFieldButtonExtraAttributesHtml("delete", $tn . "_delete", "Delete", $single_delete_action_props, $tn, $tn_label, $panel_id, 'executeSingleAction(this, onDelete)');
				
				$field["field"]["input"] = array(
					"type" => "label",
					"class" => "icon save",
					"extra_attributes" => $extra_attributes,
					"previous_html" => (!empty($actions_props["single_delete"]) ? '<span class="icon delete"' . $delete_eas_html . '></span>' : null),
				);
			}
			else if (!empty($actions_props["single_delete"])) {
				$extra_attributes = self::getFormFieldButtonExtraAttributesSettings("delete", $tn . "_delete", "Delete", $single_delete_action_props, $tn, $tn_label, $panel_id, 'executeSingleAction(this, onDelete)');
				
				$field["field"]["input"] = array(
					"type" => "label",
					"class" => "icon delete",
					"extra_attributes" => $extra_attributes,
				);
			}
			
			$fields[] = $field;
		}
		
		if (!empty($actions_props["links"]))
			foreach ($actions_props["links"] as $link) {
				$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
				
				//I may want a link without a "a" html element, like a separator for the following links
				self::prepareLink($link, $tn, $attrs, $pks, true, false, false, false);
				
				$fields[] = array(
					"field" => array(
						"class" => "link link-$tn " . (isset($link["class"]) ? $link["class"] : ""),
						"input" => array(
							"type" => "link",
							"value" => !empty($link["value"]) ? $link["value"] : (isset($link["title"]) ? $link["title"] : null),
							"href" => isset($link["url"]) ? $link["url"] : null,
							"target" => isset($link["target"]) ? $link["target"] : null,
							"title" => isset($link["title"]) ? $link["title"] : null,
							"extra_attributes" => isset($link["extra_attributes"]) ? $link["extra_attributes"] : null,
							"previous_html" => isset($link["previous_html"]) ? $link["previous_html"] : null,
							"next_html" => isset($link["next_html"]) ? $link["next_html"] : null,
						)
					)
				);
			}
		
		if (!empty($actions_props["single_insert"]) || !empty($actions_props["multiple_insert"]) || !empty($actions_props["multiple_insert_update"])) {
			$insert_htmls = self::getTableInsertFieldsSettings($tables, $table_name, $tn, $tn_label, $panel_id, $attributes, $pks, $pks_auto_increment, $actions_props, $fields);
			$panel_id_hash = HashCode::getHashCodePositive($panel_id);
			
			$generic_javascript .= "\n" . self::convertHtmlToJavascriptVariable("{$tn}_table_{$panel_id_hash}_before_insert_html", $insert_htmls[0]);
			$generic_javascript .= "\n" . self::convertHtmlToJavascriptVariable("{$tn}_table_{$panel_id_hash}_after_insert_html", $insert_htmls[1]);
			$generic_javascript .= "\n";
		}
		
		$buttons = array();
		
		//only add insert button if update action does not exists, otherwise the insert actions will be done by the SAVE button
		if (!empty($actions_props["multiple_insert_update"]))
			$buttons[] = self::getFormFieldButtonSettings("insert_update", $tn . "_add_save", "Save", $actions_props["multiple_insert_update"], $tn, $tn_label, $panel_id, true);
		else if (!empty($actions_props["multiple_insert"]))
			$buttons[] = self::getFormFieldButtonSettings("insert", $tn . "_add", "Add", $actions_props["multiple_insert"], $tn, $tn_label, $panel_id, true);
		else if (!empty($actions_props["multiple_update"]))
			$buttons[] = self::getFormFieldButtonSettings("update", $tn . "_save", "Save", $actions_props["multiple_update"], $tn, $tn_label, $panel_id, true);
		
		if (!empty($actions_props["multiple_delete"])) {
			$action_props = $actions_props["multiple_delete"];
			
			if (!isset($action_props["confirmation_message"])) {
				$tn_plural_label = self::getName($tn_plural);
				
				if ($child_tables) {
					$action_props["confirmation_message"] .= "You are about to delete multiples $tn_plural_label.\nNote that these $tn_plural_label may have some dependencies/children in the following table(s):";
					
					for ($i = 0; $i < count($child_tables); $i++)
						$action_props["confirmation_message"] .= "\n- " + self::getName($child_tables[$i]);
					
					$action_props["confirmation_message"] .= "\n\nYou should delete the correspondent dependencies/children first, and only then delete these $tn_plural_label.\n\nAre you sure that you wish to proceed with this $tn_plural_label deletion (before delete it's dependencies)?";
				}
				else
					$action_props["confirmation_message"] = "Do you wish to delete multiples $tn_plural_label?";
			}
			
			$buttons[] = self::getFormFieldButtonSettings("delete", $tn . "_delete", "Delete", $action_props, $tn, $tn_label, $panel_id, true);
		}
		
		$is_pagination_active = !$pagination || !empty($pagination["active"]);
		
		if ($is_pagination_active) {
			$top_pagination = array(
				"container" => array(
					"class" => "top-pagination",
					"elements" => array(
						array(
							"pagination" => array(
								"pagination_template" => "bootstrap1",
								"rows_per_page" => "#" . $tn_plural . "_rows_per_page#",
								"page_number" => "#" . $tn_plural . "_current_page#",
								"max_num_of_shown_pages" => "20",
								"total_rows" => "#" . $tn_plural . "_count#",
								"page_attr_name" => $tn_plural . "_current_page",
								"on_click_js_func" => $pagination && !empty($pagination["on_click_js_func"]) ? $pagination["on_click_js_func"] : null
							)
						)
					)
				)
			);
			
			$bottom_pagination = $top_pagination;
			$bottom_pagination["container"]["class"] = "bottom-pagination";
		}
		
		$containers = array();
		
		if ($is_pagination_active)
			$containers[] = $top_pagination;
		
		$containers[] = array(
			"container" => array(
				"class" => "list-container",
				"previous_html" => "",
				"next_html" => "",
				"elements" => array(
					0 => array(
						"table" => array(
							"table_class" => "list-table",
							"default_input_data" => "#" . $tn_plural . "#",
							"elements" => $fields
						)
					)
				)
			)
		);
		
		if ($is_pagination_active)
			$containers[] = $bottom_pagination;
		
		if ($buttons)
			$containers[] = array(
				"container" => array(
					"class" => "buttons",
					"elements" => $buttons
				)
			);
		
		$form_id = "form_" . rand(0, 10000);
		
		return array(
			"with_form" => $has_form,
			"form_id" => $form_id,
			"form_method" => "post",
			"form_class" => "",
			"form_on_submit" => $has_form_default_action ? "" : "return false",
			"form_action" => "",
			"form_type" => "",
			"form_containers" => array(
				array(
					"container" => array(
						"id" => $panel_id,
						"class" => 'list-' . str_replace(array("_", " "), "-", self::getParsedTableName($tn_plural)) . ' ' . $panel_class, //$tn_plural name can have schema
						"previous_html" => "",
						"next_html" => "",
						"elements" => $containers
					)
				)
			),
			"form_css" => "",
			"form_js" => $has_form ? 'if (typeof MyJSLib != "undefined") { MyJSLib.FormHandler.initForm( $("#' . $form_id . '")[0] ) }' : ''
		);
	}
	
	private static function getTablePTLCode($tables, $table_name, $tn, $tn_label, $tn_plural, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, &$generic_javascript, $pagination = false, $panel_class = "", $panel_id = "") {
		$has_action = !empty($actions_props["multiple_insert_update"]) || 
			!empty($actions_props["multiple_insert"]) || 
			!empty($actions_props["single_insert"]) || 
			!empty($actions_props["single_update"]) || 
			!empty($actions_props["single_delete"]);
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		
		$editable = !empty($actions_props["single_update"]) || 
			!empty($actions_props["multiple_update"]) || 
			!empty($actions_props["multiple_insert_update"]);
		$deletable = !empty($actions_props["single_delete"]) || !empty($actions_props["multiple_delete"]);
		$is_multiple = !empty($actions_props["multiple_insert_update"]) || 
			!empty($actions_props["multiple_insert"]) || 
			!empty($actions_props["multiple_update"]) || 
			!empty($actions_props["multiple_delete"]);
		$has_form = $is_multiple;
		$has_form_default_action = (
			(isset($actions_props["multiple_insert_update"]["action_type"]) && substr($actions_props["multiple_insert_update"]["action_type"], 0, 5) != "ajax_") || 
			(isset($actions_props["multiple_insert"]["action_type"]) && substr($actions_props["multiple_insert"]["action_type"], 0, 5) != "ajax_") || 
			(isset($actions_props["multiple_update"]["action_type"]) && substr($actions_props["multiple_update"]["action_type"], 0, 5) != "ajax_") || 
			(isset($actions_props["multiple_delete"]["action_type"]) && substr($actions_props["multiple_delete"]["action_type"], 0, 5) != "ajax_")
		);
		
		$form_id = null;
		
		//Preparing form html
		$is_pagination_active = !$pagination || !empty($pagination["active"]);
		
		if ($is_pagination_active)
			$pagination_code = '
		<ptl:var:PaginationLayout new PaginationLayout(@\$input["' . $tn_plural . '_count"], @\$input["' . $tn_plural . '_rows_per_page"], array("' . $tn_plural . '_current_page" =&gt; @\$input["' . $tn_plural . '_current_page"]), "' . $tn_plural . '_current_page"' . ($pagination && !empty($pagination["on_click_js_func"]) ? ', "' . $pagination["on_click_js_func"] . '"' : '') . ') />
		<ptl:var:PaginationLayout-&gt;show_x_pages_at_once 20 />
		<ptl:var:pagination_data \$PaginationLayout-&gt;data />
		<ptl:var:pagination_data["style"] "bootstrap1" />
		<ptl:echo \$PaginationLayout-&gt;designWithStyle(1, \$pagination_data) />';
		
		$code = '';
		
		if ($has_form) {
			$form_id = "form_" . rand(0, 10000);
			$aux = '(typeof MyJSLib == \'undefined\' || MyJSLib.FormHandler.formCheck(this))';
			$code .= '<form id="' . $form_id . '" method="post" class="form-horizontal" onSubmit="' . ($has_form_default_action ? 'return ' . $aux : $aux . ';return false') . ';' . '" enctype="multipart/form-data">';
		}
		
		
		$code .= '
<div class="list-' . str_replace(array("_", " "), "-", self::getParsedTableName($tn_plural)) . ' ' . $panel_class . '"' . ($panel_id ? ' id="' . $panel_id . '"' : '') . '>
	' . ($is_pagination_active ? '<div class="top-pagination">' . $pagination_code . '</div>' : '') . '
	<div class="list-container">
		<table class="list-table">
			<thead>
				<tr>'; //$tn_plural name can have schema
		
		if ($is_multiple)
			$code .= '<th class="multiple-selection"></th>';
		
		foreach ($attributes as $attr_name) {
			$attr_props = isset($actions_props["attributes_settings"][$attr_name]) ? $actions_props["attributes_settings"][$attr_name] : null;
			$label_previous_html = isset($attr_props["label_previous_html"]) ? $attr_props["label_previous_html"] : null;
			$label_next_html = isset($attr_props["label_next_html"]) ? $attr_props["label_next_html"] : null;
			
			$code .= '<th class="' . $attr_name . (!empty($attr_props["label_class"]) ? ' ' . self::getFormFieldAttrPropHtml($attr_props["label_class"]) : '') . '">' . 
				self::getFormFieldAttrPropHtml($label_previous_html) . 
				(isset($attr_props["label_value"]) && strlen($attr_props["label_value"]) ? self::getFormFieldAttrPropHtml($attr_props["label_value"]) : self::getName($attr_name)) . 
				self::getFormFieldAttrPropHtml($label_next_html) . 
			'</th>';
		}
		
		if ($has_action) {
			$code .= '<th class="actions">';
			
			if (!empty($actions_props["single_insert"]) || !empty($actions_props["multiple_insert"]) || !empty($actions_props["multiple_insert_update"])) {
				$on_click = 'addNewItem(this)';
				$ea_button_type = !empty($actions_props["multiple_insert_update"]) ? "insert_update" : "insert";
				$ea_button_name = !empty($actions_props["multiple_insert_update"]) ? $tn . "_add_save" : $tn . "_add";
				$ea_button_label = !empty($actions_props["multiple_insert_update"]) ? "Save" : "Add";
				$ea_action_props = !empty($actions_props["multiple_insert_update"]) ? $actions_props["multiple_insert_update"] : (
					!empty($actions_props["multiple_insert"]) ? $actions_props["multiple_insert"] : (
						!empty($actions_props["single_insert"]) ? $actions_props["single_insert"] : null
					)
				);
				$ea_html = self::getFormFieldButtonExtraAttributesHtml($ea_button_type, $ea_button_name, $ea_button_label, $ea_action_props, $tn, $tn_label, $panel_id, $on_click);
				$code .= '<span class="icon add"' . $ea_html . '></span>';
				
				$insert_htmls = self::getTableInsertFieldsPTLCode($tables, $table_name, $tn, $tn_label, $tn_plural, $panel_id, $attributes, $pks, $pks_auto_increment, $actions_props);
				$panel_id_hash = HashCode::getHashCodePositive($panel_id);
				
				$generic_javascript .= "\n" . self::convertHtmlToJavascriptVariable("{$tn}_table_{$panel_id_hash}_before_insert_html", $insert_htmls[0], true);
				$generic_javascript .= "\n" . self::convertHtmlToJavascriptVariable("{$tn}_table_{$panel_id_hash}_after_insert_html", $insert_htmls[1], true);
				$generic_javascript .= "\n";
			}
			
			$code .= '</th>';
		}
		
		if (!empty($actions_props["links"]))
			$code .= '<th class="links"></th>';
		
		$code .= '</tr>
			</thead>
			<tbody>
				<ptl:if is_array(@\\$input[' . $tn_plural . '])>
					<ptl:foreach \\$input[' . $tn_plural . '] i item>
						<tr>';
		
		$fields_code = '';
		
		if ($is_multiple)
			$fields_code .= '<td class="multiple-selection"><input type="checkbox" name="multiple_selection[' . $tn . '][<ptl:echo \$i/>]" value="1"/></td>';
		
		foreach ($attributes as $attr_name) {
			$attr_props = isset($actions_props["attributes_settings"][$attr_name]) ? $actions_props["attributes_settings"][$attr_name] : null;
			$attr = isset($attrs[$attr_name]) ? $attrs[$attr_name] : null;
			$is_pk = in_array($attr_name, $pks);
			$is_pk_auto_increment = in_array($attr_name, $pks_auto_increment);
			
			$input_html = self::getTableFieldInputHtml($tn, $tn_plural, $attr_name, $attrs, $attr_props, $pks, $is_pk, $is_pk_auto_increment, $editable, $deletable, $input_type, 
				isset($actions_props["single_update"]["action_type"]) && $actions_props["single_update"]["action_type"] == "ajax_on_blur", 
				false, 
				!empty($actions_props["multiple_update"]) || !empty($actions_props["multiple_insert_update"]) || !empty($actions_props["single_update"])
			);
			
			$fields_code .= '		<td class="field field-' . (isset($attr["type"]) ? strtolower(str_replace(" ", "-", $attr["type"])) : "") . " " . $attr_name . ($is_pk ? " is-pk" : "") . ($is_pk_auto_increment ? " is-pk-auto-increment" : "") . " field-" . $input_type . (!empty($attr_props["class"]) ? " " . self::getFormFieldAttrPropHtml($attr_props["class"]) : "") . '">
								' . $input_html . '
							</td>';
		}
		
		if ($has_action) {
			$fields_code .= '		<td class="actions">';
				
			if (!empty($actions_props["single_update"])) {
				$ea_html = self::getFormFieldButtonExtraAttributesHtml("update", $tn . "_save", "Save", $actions_props["single_update"], $tn, $tn_label, $panel_id, 'executeSingleAction(this, onUpdate)');
				$fields_code .= '		<span class="icon save"' . $ea_html . '></span>';
			}
			
			if (!empty($actions_props["single_delete"])) {
				$action_props = isset($actions_props["single_delete"]) ? $actions_props["single_delete"] : null;
				
				if (!isset($action_props["confirmation_message"])) {
					if ($child_tables) {
						$action_props["confirmation_message"] .= "You are about to delete this $tn_label.\nNote that this $tn_label may have some dependencies/children in the following table(s):";
						
						for ($i = 0; $i < count($child_tables); $i++)
							$action_props["confirmation_message"] .= "\n- " + self::getName($child_tables[$i]);
						
						$action_props["confirmation_message"] .= "\n\nYou should delete the correspondent dependencies/children first, and only then delete this $tn_label.\n\nAre you sure that you wish to proceed with this $tn_label deletion (before delete it's dependencies)?";
					}
					else
						$action_props["confirmation_message"] = "Do you wish to delete this $tn_label?";
				}
				
				$ea_html = self::getFormFieldButtonExtraAttributesHtml("delete", $tn . "_delete", "Delete", $action_props, $tn, $tn_label, $panel_id, 'executeSingleAction(this, onDelete)');
				$fields_code .= '		<span class="icon delete"' . $ea_html . '></span>';
			}
		
			$fields_code .= '		</td>';
		}
			
		if (!empty($actions_props["links"]))
			$fields_code .= '<td class="links">' . self::getTableFieldLinksHtml($actions_props["links"], $tn, $tn_plural, $attrs, $pks) . '</td>';
		
		$code .= $fields_code;
		$code .= '		</tr>
					</ptl:foreach>
				</ptl:if>
			</tbody>
		</table>
	</div>
	' . ($is_pagination_active ? '<div class="bottom-pagination">' . $pagination_code . '</div>' : '');
		
		//prepare multiple buttons
		if ($is_multiple) {
			$code .= '<div class="buttons">';
			
			//only add insert button if update action does not exists, otherwise the insert actions will be done by the SAVE button
			if (!empty($actions_props["multiple_insert_update"]))
				$code .= self::getFormFieldButtonHtml("insert_update", $tn . "_add_save", "Save", $actions_props["multiple_insert_update"], $tn, $tn_label, $panel_id, true);
			else if (!empty($actions_props["multiple_insert"]))
				$code .= self::getFormFieldButtonHtml("insert", $tn . "_add", "Add", $actions_props["multiple_insert"], $tn, $tn_label, $panel_id, true);
			else if (!empty($actions_props["multiple_update"]))
				$code .= self::getFormFieldButtonHtml("update", $tn . "_save", "Save", $actions_props["multiple_update"], $tn, $tn_label, $panel_id, true);
			
			if (!empty($actions_props["multiple_delete"])) {
				$action_props = $actions_props["multiple_delete"];
				
				if (!isset($action_props["confirmation_message"]))  {
					$tn_plural_label = self::getName($tn_plural);
					
					if ($child_tables) {
						$action_props["confirmation_message"] .= "You are about to delete multiples $tn_plural_label.\nNote that these $tn_plural_label may have some dependencies/children in the following table(s):";
						
						for ($i = 0; $i < count($child_tables); $i++)
							$action_props["confirmation_message"] .= "\n- " + self::getName($child_tables[$i]);
						
						$action_props["confirmation_message"] .= "\n\nYou should delete the correspondent dependencies/children first, and only then delete these $tn_plural_label.\n\nAre you sure that you wish to proceed with this $tn_plural_label deletion (before delete it's dependencies)?";
					}
					else
						$action_props["confirmation_message"] = "Do you wish to delete multiples $tn_plural_label?";
				}
				
				$code .= self::getFormFieldButtonHtml("delete", $tn . "_delete", "Delete", $action_props, $tn, $tn_label, $panel_id, true);
			}
			
			$code .= '</div>';
		}
		
		$code .= '</div>';
		
		if ($has_form) 
			$code .= '</form>
			<script>if (typeof MyJSLib != "undefined") { MyJSLib.FormHandler.initForm( $("#' . $form_id . '")[0] ) }</script>';
		
		return $code;
	}
	
	private static function getTableInsertFieldsSettings($tables, $table_name, $tn, $tn_label, $panel_id, $attributes, $pks, $pks_auto_increment, $actions_props, $after_insert_fields) {
		//Preparing before-insert fields
		$before_insert_fields = array();
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		
		if (!empty($actions_props["multiple_insert_update"]) || !empty($actions_props["multiple_insert"]) || !empty($actions_props["multiple_update"]) || !empty($actions_props["multiple_delete"]))
			$before_insert_fields[] = array(
				"field" => array(
					"class" => 'multiple-selection',
					"input" => array(
						"type" => "checkbox",
						"name" => "multiple_insert_selection[$tn][]",
						"value" => "1",
						"extra_attributes" => array(
							array("name" => "style", "value" => "display:none;")
						),
					)
				)
			);
		
		foreach ($attributes as $attr_name)
			$before_insert_fields[] = self::getFieldSettings($attr_name, $attrs, $tn, $attributes, $pks, $pks_auto_increment, $actions_props, true, true, true, true);
		
		if ((!empty($actions_props["multiple_insert_update"]) || !empty($actions_props["multiple_insert"])) && empty($actions_props["single_insert"]))
			$before_insert_fields[] = array(
				"field" => array(
					"class" => "actions",
					"input" => array(
						"type" => "label",
						"class" => "icon delete",
						"extra_attributes" => array(
							array('name' => 'onClick', 'value' => '$(this).parent().closest(\'tr\').remove()')
						),
					)
				)
			);
		else { //if ($actions_props["single_insert"])
			$extra_attributes = self::getFormFieldButtonExtraAttributesSettings("insert", $tn . "_add", "Add", isset($actions_props["single_insert"]) ? $actions_props["single_insert"] : null, $tn, $tn_label, $panel_id, 'executeSingleAction(this, onSingleInsert)');
			
			$before_insert_fields[] = array(
				"field" => array(
					"class" => 'actions',
					"input" => array(
						"type" => "label",
						"class" => "icon insert",
						"extra_attributes" => $extra_attributes,
						"previous_html" => '<span class="icon delete" onClick="$(this).parent().closest(\'tr\').remove()"></span>',
					)
				)
			);
		}
		
		if (!empty($actions_props["links"]))
			foreach ($actions_props["links"] as $link) {
				$before_insert_fields[] = array(
					"field" => array(
						"class" => "link link-$tn " . (isset($link["class"]) ? $link["class"] : "")
					)
				);
			}
		
		//Preparing html
		$before_insert_html = '<tr data-is-insert-record="1">';
		$after_insert_html = '<tr>';
		$HtmlFormHandler = new HtmlFormHandler(array("parse_values" => false));
		$t = count($before_insert_fields);
		
		for ($i = 0; $i < $t; $i++) {
			$before_insert_field = $before_insert_fields[$i];
			
			$bif = isset($before_insert_field["field"]) ? $before_insert_field["field"] : null;
			$aif = isset($after_insert_fields[$i]["field"]) ? $after_insert_fields[$i]["field"] : null;
			$class = !empty($aif["class"]) ? ' class="' . $aif["class"] . '"' : '';
			
			$before_insert_html .= '<td' . $class . '>' . $HtmlFormHandler->getFieldInputHtml($bif)  . '</td>';
			$after_insert_html .= '<td' . $class . '>' . $HtmlFormHandler->getFieldInputHtml($aif)  . '</td>';
		}
		
		$before_insert_html .= '</tr>';
		$after_insert_html .= '</tr>';
		
		//Removing default values
		foreach ($attributes as $attr_name) {
			$before_insert_html = str_replace("=\"{$tn}[#idx#][orig_$attr_name]\"", "=\"{$tn}[][orig_$attr_name]\"", str_replace("=\"{$tn}[#idx#][$attr_name]\"", "=\"{$tn}[][$attr_name]\"", str_replace("=\"#[idx][$attr_name]#\"", '=""', $before_insert_html)));
			$after_insert_html = str_replace("#[idx][$attr_name]#", "#$attr_name#", str_replace("=\"{$tn}[#idx#][orig_$attr_name]\"", "=\"{$tn}[][orig_$attr_name]\"", str_replace("=\"{$tn}[#idx#][$attr_name]\"", "=\"{$tn}[][$attr_name]\"", str_replace("=\"#[idx][$attr_name]#\"", '=""', $after_insert_html)))); //#[idx][$attr_name]# replacements is for the links
		}
		$after_insert_html = str_replace("name=\"multiple_selection[$tn][#idx#]\"", "name=\"multiple_selection[$tn][]\"", $after_insert_html);
		
		return array($before_insert_html, $after_insert_html);
	}
	
	private static function getTableInsertFieldsPTLCode($tables, $table_name, $tn, $tn_label, $tn_plural, $panel_id, $attributes, $pks, $pks_auto_increment, $actions_props) {
		$before_insert_html = '';
		$after_insert_html = '';
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		
		$editable = !empty($actions_props["single_update"]);
		$deletable = !empty($actions_props["single_delete"]);
		
		if (!empty($actions_props["multiple_insert_update"]) || 
			!empty($actions_props["multiple_insert"]) || 
			!empty($actions_props["multiple_update"]) || 
			!empty($actions_props["multiple_delete"])
		) {
			$before_insert_html .= '<td class="multiple-selection"><input type="checkbox" name="multiple_insert_selection[' . $tn . '][]" value="1" checked style="display:none;"/></td>';
			$after_insert_html .= '<td class="multiple-selection"><input type="checkbox" name="multiple_selection[' . $tn . '][]" value="1"/></td>';
		}
		
		foreach ($attributes as $attr_name) {
			$attr_props = isset($actions_props["attributes_settings"][$attr_name]) ? $actions_props["attributes_settings"][$attr_name] : null;
			$db_attr_props = isset($attrs[$attr_name]) ? $attrs[$attr_name] : null;
			$is_pk = in_array($attr_name, $pks);
			$is_pk_auto_increment = in_array($attr_name, $pks_auto_increment);
			$input_type = null;
			
			$aux_html = self::getTableFieldInputHtml($tn, '', $attr_name, $attrs, $attr_props, $pks, $is_pk, $is_pk_auto_increment, $editable, $deletable, $input_type, 
				isset($actions_props["single_update"]["action_type"]) && $actions_props["single_update"]["action_type"] == "ajax_on_blur", 
				true, 
				!empty($actions_props["multiple_update"]) || !empty($actions_props["multiple_insert_update"]) || !empty($actions_props["single_update"])
			);
			
			$td_html = '<td class="field field-' . (isset($db_attr_props["type"]) ? strtolower(str_replace(" ", "-", $db_attr_props["type"])) : "") . " " . $attr_name . ($is_pk ? " is-pk" : "") . ($is_pk_auto_increment ? " is-pk-auto-increment" : "") . " field-" . $input_type . (!empty($attr_props["class"]) ? " " . self::getFormFieldAttrPropHtml($attr_props["class"]) : "") . '">';
			
			$before_insert_html .= $td_html . self::getTableFieldInputHtml($tn, '', $attr_name, $attrs, $attr_props, $pks, $is_pk, $is_pk_auto_increment, true, $deletable, $aux = null, false, true, true) . '</td>';
			
			$after_insert_html .= $td_html . $aux_html . '</td>';
		}
		
		$before_insert_html .= '<td class="actions">
			<span class="icon delete" onClick="$(this).parent().closest(\'tr\').remove()"></span>';
		
		if (!empty($actions_props["single_insert"])) {
			$insert_eas_html = self::getFormFieldButtonExtraAttributesHtml("insert", $tn . "_add", "Add", $actions_props["single_insert"], $tn, $tn_label, $panel_id, 'executeSingleAction(this, onSingleInsert)');
			
			$before_insert_html .= '<span class="icon insert"' . $insert_eas_html . '></span>';
		}
			
		$before_insert_html .= '</td>
		' . (!empty($actions_props["links"]) ? '<td class="links"></td>' : '');
		
		$update_eas_html = self::getFormFieldButtonExtraAttributesHtml("update", $tn . "_save", "Save", isset($actions_props["single_update"]) ? $actions_props["single_update"] : null, $tn, $tn_label, $panel_id, 'executeSingleAction(this, onUpdate)');
		$delete_eas_html = self::getFormFieldButtonExtraAttributesHtml("delete", $tn . "_delete", "Delete", isset($actions_props["single_delete"]) ? $actions_props["single_delete"] : null, $tn, $tn_label, $panel_id, 'executeSingleAction(this, onDelete)');
		
		$after_insert_html .= '
			<td class="actions">
				' . (!empty($actions_props["single_update"]) ? '<span class="icon save"' . $update_eas_html . '></span>' : '') . '
				' . (!empty($actions_props["single_delete"]) ? '<span class="icon delete"' . $delete_eas_html . '></span>' : '') . '
			</td>
			' . (!empty($actions_props["links"]) ? '<td class="links">' . self::getTableFieldLinksHtml($actions_props["links"], $tn, $tn_plural, $attrs, $pks, true) . '</td>' : '');
		
		$before_insert_html = '<tr data-is-insert-record="1">' . $before_insert_html . '</tr>';
		$after_insert_html = '<tr>' . $after_insert_html . '</tr>';
		
		//preparing default values
		foreach ($attributes as $attr_name) {
			$after_insert_html = str_replace("#[idx][$attr_name]#", "#$attr_name#", $after_insert_html); //#[idx][$attr_name]# replacements is for the links
		}
		
		return array($before_insert_html, $after_insert_html);
	}
	
	private static function getTreeInsertFieldsSettings($tables, $table_name, $tn, $tn_label, $panel_id, $attributes, $pks, $pks_auto_increment, $actions_props, $after_inner_containers) {
		//Preparing before-insert fields
		$before_insert_fields = array();
		$before_buttons = array();
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		
		//preparing fields
		if (!empty($actions_props["multiple_insert_update"]) || 
			!empty($actions_props["multiple_insert"]) || 
			!empty($actions_props["multiple_update"]) || 
			!empty($actions_props["multiple_delete"])
		)
			$before_insert_fields[] = array(
				"field" => array(
					"class" => 'multiple-selection',
					"input" => array(
						"type" => "checkbox",
						"name" => "multiple_insert_selection[$tn][]",
						"value" => "1",
						"extra_attributes" => array(
							array("name" => "style", "value" => "display:none;")
						),
					)
				)
			);
		
		foreach ($attributes as $attr_name) {
			//if only insert and attr is a pk auto-incremented, doesn't show attribute
			if (in_array($attr_name, $pks_auto_increment))
				continue 1;
			
			$before_insert_fields[] = self::getFieldSettings($attr_name, $attrs, $tn, $attributes, $pks, $pks_auto_increment, $actions_props, true, true, true, true);
		}
		
		//preparing buttons
		if (!empty($actions_props["single_insert"])) {
			$extra_attributes = self::getFormFieldButtonExtraAttributesSettings("insert", $tn . "_add", "Add", $actions_props["single_insert"], $tn, $tn_label, $panel_id, 'executeSingleAction(this, onSingleInsert)');
			
			$before_buttons[] = array(
				"field" => array(
					"class" => "button button-insert button-insert-$tn",
					"input" => array(
						"type" => "button",
						"name" => $tn . "_add",
						"value" => "Add",
						"extra_attributes" => $extra_attributes,
						"next_html" => '<span class="icon delete" onClick="$(this).parent().closest(\'li\').remove()"></span>',
					)
				)
			);
		}
		else
			$before_buttons[] = array(
				"field" => array(
					"class" => "button button-insert button-insert-$tn",
					"input" => array(
						"type" => "label",
						"class" => "icon delete",
						"extra_attributes" => array(
							array('name' => 'onClick', 'value' => '$(this).parent().closest(\'li\').remove()')
						),
					)
				)
			);
		
		//Preparing html
		$before_inner_containers = array();
		
		$before_inner_containers[] = array(
			"container" => array(
				"class" => "fields-container",
				"previous_html" => "",
				"next_html" => "",
				"elements" => $before_insert_fields
			)
		);
		
		$before_inner_containers[] = array(
			"container" => array(
				"class" => "buttons single-buttons",
				"elements" => $before_buttons
			)
		);
		
		//Preparing html
		$HtmlFormHandler = new HtmlFormHandler(array("parse_values" => false));
		$before_insert_html = '<li data-is-insert-record="1">' . $HtmlFormHandler->createElements($before_inner_containers) . '</li>';
		$after_insert_html = '<li>' . $HtmlFormHandler->createElements($after_inner_containers) . '</li>';
		
		//Removing default values
		foreach ($attributes as $attr_name) {
			$before_insert_html = str_replace("=\"{$tn}[#idx#][orig_$attr_name]\"", "=\"{$tn}[][orig_$attr_name]\"", str_replace("=\"{$tn}[#idx#][$attr_name]\"", "=\"{$tn}[][$attr_name]\"", str_replace("=\"#[idx][$attr_name]#\"", '=""', $before_insert_html)));
			$after_insert_html = str_replace("#[idx][$attr_name]#", "#$attr_name#", str_replace("=\"{$tn}[#idx#][orig_$attr_name]\"", "=\"{$tn}[][orig_$attr_name]\"", str_replace("=\"{$tn}[#idx#][$attr_name]\"", "=\"{$tn}[][$attr_name]\"", str_replace("=\"#[idx][$attr_name]#\"", '=""', $after_insert_html)))); //#[idx][$attr_name]# replacements is for the links
		}
		$after_insert_html = str_replace("name=\"multiple_selection[$tn][#idx#]\"", "name=\"multiple_selection[$tn][]\"", $after_insert_html);
		
		return array($before_insert_html, $after_insert_html);
	}
	
	private static function getTreeInsertFieldsPTLCode($tables, $table_name, $tn, $tn_label, $tn_plural, $panel_id, $attributes, $pks, $pks_auto_increment, $actions_props) {
		$before_insert_html = '<div class="fields-container">';
		$after_insert_html = '<div class="fields-container">';
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		
		$editable = !empty($actions_props["single_update"]);
		$deletable = !empty($actions_props["single_delete"]);
		
		if (!empty($actions_props["multiple_insert_update"]) || 
			!empty($actions_props["multiple_insert"]) || 
			!empty($actions_props["multiple_update"]) || 
			!empty($actions_props["multiple_delete"])
		) {
			$before_insert_html .= '<div class="multiple-selection" style="display:none;"><input type="checkbox" name="multiple_insert_selection[' . $tn . '][]" value="1" checked/></div>';
			$after_insert_html .= '<div class="multiple-selection"><label>Select to perform action</label><input type="checkbox" name="multiple_selection[' . $tn . '][]" value="1"/></div>';
		}
		
		foreach ($attributes as $attr_name) {
			$attr_props = isset($actions_props["attributes_settings"][$attr_name]) ? $actions_props["attributes_settings"][$attr_name] : null;
			$db_attr_props = isset($attrs[$attr_name]) ? $attrs[$attr_name] : null;
			$is_pk = in_array($attr_name, $pks);
			$is_pk_auto_increment = in_array($attr_name, $pks_auto_increment);
			$input_type = null;
			
			$aux_html = self::getTableFieldInputHtml($tn, '', $attr_name, $attrs, $attr_props, $pks, $is_pk, $is_pk_auto_increment, $editable, $deletable, $input_type, 
				isset($actions_props["single_update"]["action_type"]) && $actions_props["single_update"]["action_type"] == "ajax_on_blur", 
				true, 
				!empty($actions_props["multiple_update"]) || !empty($actions_props["multiple_insert_update"]) || !empty($actions_props["single_update"])
			);
			
			
			$div_html = '<div class="field field-' . (isset($db_attr_props["type"]) ? strtolower(str_replace(" ", "-", $db_attr_props["type"])) : "") . " " . $attr_name . ($is_pk ? " is-pk" : "") . ($is_pk_auto_increment ? " is-pk-auto-increment" : "") . " field-" . $input_type . (!empty($attr_props["class"]) ? " " . self::getFormFieldAttrPropHtml($attr_props["class"]) : "") . '">
				' . self::getFormFieldLabelHtml($attr_name, $attr_props);
			
			//if only insert and attr is a pk auto-incremented, doesn't show attribute
			if (!$is_pk_auto_increment) 
				$before_insert_html .= $div_html . self::getTableFieldInputHtml($tn, '', $attr_name, $attrs, $attr_props, $pks, $is_pk, $is_pk_auto_increment, true, $deletable, $aux = null, false, true, true) . '</div>';
			
			$after_insert_html .= $div_html . $aux_html . '</div>';
		}
		
		$before_insert_html .= '</div>
				<div class="buttons single-buttons">
					<div class="button button-insert button-insert-' . $tn . '">';
		
		if (!empty($actions_props["single_insert"])) {
			$eas_html = self::getFormFieldButtonExtraAttributesHtml("insert", $tn . "_add", "Add", $actions_props["single_insert"], $tn, $tn_label, $panel_id, 'executeSingleAction(this, onSingleInsert)');
			
			$before_insert_html .= '<input type="button" value="Add" name="' . $tn . '_add"' . $eas_html . ' />';
		}
			
		$before_insert_html .= '
						<span class="icon delete" onClick="$(this).parent().closest(\'li\').remove()"></span>
					</div>
				</div>';
		
		$after_insert_html .= '</div>';
		
		if (!empty($actions_props["single_update"]) || !empty($actions_props["single_delete"])) {
			$update_eas_html = self::getFormFieldButtonExtraAttributesHtml("update", $tn . "_save", "Save", isset($actions_props["single_update"]) ? $actions_props["single_update"] : null, $tn, $tn_label, $panel_id, 'executeSingleAction(this, onUpdate)');
			$delete_eas_html = self::getFormFieldButtonExtraAttributesHtml("delete", $tn . "_delete", "Delete", isset($actions_props["single_delete"]) ? $actions_props["single_delete"] : null, $tn, $tn_label, $panel_id, 'executeSingleAction(this, onDelete)');
			
			$after_insert_html .= '
				<div class="buttons single-buttons">
					' . (!empty($actions_props["single_update"]) ? '
						<div class="button button-update button-update-' . $tn . '">
							<input type="button" value="Save" name="' . $tn . '_save"' . $update_eas_html . ' />
						</div>' : '') . '
					' . (!empty($actions_props["single_delete"]) ? '
						<div class="button button-delete button-delete-' . $tn . '">
							<input type="button" value="Delete" name="' . $tn . '_delete"' . $delete_eas_html . ' />
						</div>' : '') . '
				</div>
				' . (!empty($actions_props["links"]) ? '<div class="links">' . self::getTableFieldLinksHtml($actions_props["links"], $tn, $tn_plural, $attrs, $pks, true) . '</div>' : '');
		}
		
		$before_insert_html = '<li data-is-insert-record="1">' . $before_insert_html . '</li>';
		$after_insert_html = '<li>' . $after_insert_html . '</li>';
		
		//preparing default values
		foreach ($attributes as $attr_name) {
			$after_insert_html = str_replace("#[idx][$attr_name]#", "#$attr_name#", $after_insert_html); //#[idx][$attr_name]# replacements is for the links
		}
		
		return array($before_insert_html, $after_insert_html);
	}
	
	private static function getTableFieldInputHtml($tn, $tn_plural, $attr_name, $attrs, $attr_props, $pks, $is_pk, $is_pk_auto_increment, $editable, $deletable, &$input_type = null, $ajax_on_blur = false, $is_table_insert = false, $multiple_key_press = false) {
		$db_attr_props = isset($attrs[$attr_name]) ? $attrs[$attr_name] : null;
		$input_attrs = self::getFormFieldInputAttrs($tn, $db_attr_props, $input_type, array("ajax_on_blur" => $ajax_on_blur, "multiple_key_press" => $multiple_key_press));
		
		if (!empty($attr_props["type"]))
			$input_type = $attr_props["type"];
		
		$input_previous_html = isset($attr_props["input_previous_html"]) ? $attr_props["input_previous_html"] : null;
		$code = self::getFormFieldAttrPropHtml($input_previous_html);
		
		$is_empty_value = empty($tn_plural);
		$field_value = !$is_empty_value ? (isset($attr_props["input_value"]) && strlen($attr_props["input_value"]) ? self::getFormFieldAttrPropHtml($attr_props["input_value"]) : '<ptl:echo str_replace(\'"\', \'&quot;\', (@\\$input[' . $tn_plural . '][\\$i][' . $attr_name . '] )) />') : '';
		$field_name = $tn . ($is_table_insert ? '[]' : '[<ptl:echo \$i/>]') . '[' . $attr_name . ']';
		$field_class = !empty($attr_props["input_class"]) ? ' class="' . self::getFormFieldAttrPropHtml($attr_props["input_class"]) . '"' : '';
		$attr_type = isset($attr_props["type"]) ? $attr_props["type"] : null;
		
		$orig_field_value = $field_value;
		
		if (!$is_empty_value && !empty($attr_props["available_values"]))  {
			$field_value = '
				<ptl:var:av_exists false/>
				<ptl:var:var_aux @\\$input[' . $tn_plural . '][\\$i][' . $attr_name . '] />';
				
			if (is_array($attr_props["available_values"]))
				$field_value .= '<ptl:var:avs ' . str_replace(" => ", " =&gt; ", str_replace("\n", "", var_export($attr_props["available_values"], true))) . ' />';
			else //must be a string
				$field_value .= '<ptl:var:avs @\\$input[' . $attr_props["available_values"] . ']/>';
				
			$field_value .= '
				<ptl:if is_array(\\$avs) />
					<ptl:foreach \\$avs k v>
						<ptl:if \\$k == \\$var_aux>
							<ptl:echo \\$v/>
							<ptl:var:av_exists true/>
							<ptl:break/>
						</ptl:if>
					</ptl:foreach>
				</ptl:if>
				<ptl:if !\\$av_exists>
					<ptl:echo \\$var_aux/>
				</ptl:if>';
		}
		
		if ($input_type == "hidden") //this is used when the user does include the pks in $attributes, and the system adds them automatically by as hidden fields...
			$code .= '<input' . $field_class . ' type="hidden" name="' . $field_name . '" value="' . $orig_field_value . '" />';
		else if (!$editable) {
			if ($attr_type == "link") {
				$input_type = "link";
				$href = isset($attr_props["href"]) ? $attr_props["href"] : null;
				
				$code .= '<a class="field-value' . (!empty($attr_props["input_class"]) ? " " . self::getFormFieldAttrPropHtml($attr_props["input_class"]) : "") . '"' . $field_class . ' href="' . self::getHrefWithPks($href, $tn_plural, $attrs, $pks, true, $is_empty_value, true) . '"' . (!empty($attr_props["target"]) ? ' target="' . self::getFormFieldAttrPropHtml($attr_props["target"]) . '"' : "") . '>' . $field_value . '</a>';
			}
			else {
				if (!$attr_type)
					$input_type = "label";
				
				$input_class = isset($attr_props["input_class"]) ? $attr_props["input_class"] : null;
				//$code .= '<span class="field-value' . ($input_class ? " " . self::getFormFieldAttrPropHtml($input_class) : "") . '">' . $field_value . '</span>';
				//THIS IS NOT TESTED
				$HtmlFormHandler = new HtmlFormHandler(array("parse_values" => false));
				$code .= $HtmlFormHandler->getFieldInputHtml(array(
					"input" => array(
						"type" => $input_type,
						"class" => self::prepareFieldSettingExtraStringToConcatenateWithAttrProp($input_class, "field-value", true) . " " . $input_class,
						"value" => $field_value,
					)
				));
			}
			
			if ($deletable && $is_pk)
				$code .= '<input' . $field_class . ' type="hidden" name="' . $field_name . '" value="' . $orig_field_value . '" />';
		}
		else if ($is_pk_auto_increment) {
			$input_type = "label";
			
			$code .= '<span class="field-value' . (!empty($attr_props["input_class"]) ? " " . self::getFormFieldAttrPropHtml($attr_props["input_class"]) : "") . '">' . $orig_field_value . '</span>
			<input' . $field_class . ' type="hidden" name="' . $field_name . '" value="' . $orig_field_value . '" />';
		}
		else {
			$options = isset($attr_props["options"]) ? $attr_props["options"] : null;
			
			if ($is_pk && ($input_type != "hidden" || $options))
				$code .= '<input' . $field_class . ' type="hidden" name="' . $tn . ($is_table_insert ? '[]' : '[<ptl:echo \$i/>]') . '[orig_' . $attr_name . ']" value="' . $orig_field_value . '" />';
			
			if ($options && !$attr_type)
				$input_type = "select"; //only change this if $attr_type is empty.
			
			if ($input_type == "textarea")
				$code .= '<textarea' . $field_class . ' name="' . $field_name . '"' . $input_attrs . '>' . $orig_field_value . '</textarea>';
			else if ($options) {
				
			}
			else if ($input_type == "select") {
				$code .= '<select' . $field_class . ' name="' . $field_name . '"' . $input_attrs;
				
				if ($options) {
					if (!empty($attr_props["options_javascript_variable_name"]))
						$code .= ' data-options-javascript-variable-name="' . $attr_props["options_javascript_variable_name"] . '"';
					
					$code .= '>';
					
					if (is_array($options))
						foreach($options as $k => $v) {
							$selected = !$is_empty_value ? '<ptl:echo ("' . $k . '" == @\\$input[' . $tn_plural . '][\\$i][' . $attr_name . '] ? " selected" : "")/>' : '';
							$code .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
						}
					else {
						$var_name = $options;
						
						$selected = !$is_empty_value ? '<ptl:echo (\\$k == @\\$input[' . $tn_plural . '][\\$i][' . $attr_name . '] ? " selected" : "")/>' : '';
						$code .= '<ptl:if is_array(@\\$input[' . $var_name . '])>
									<ptl:foreach \\$input[' . $var_name . '] k v>
										<option value="<ptl:echo str_replace(\'"\', \'&quot;\', \\$k) />"' . $selected . '><ptl:echo \\$v/></option>
									</ptl:foreach>
								</ptl:if>';
					}
				}
				else 
					$code .= '>';
				
				$code .= '</select>';
			}
			else if ($input_type == "checkbox" || $input_type == "radio") {
				if (is_array($options)) {
					$t = count($options);
					
					foreach($options as $k => $v) {
						$checked = !$is_empty_value ? '<ptl:echo ("' . $k . '" == @\\$input[' . $tn_plural . '][\\$i][' . $attr_name . '] ? " checked" : "")/>' : '';
						
						if ($t > 1)
							$code .= '<div' . $field_class . '><label>' . $v . '</label><input' . $field_class . ' type="' . $input_type . '" name="' . $field_name . '" value="' . $k . '"' . $checked . $input_attrs . ' /></div>';
						else
							$code .= '<input' . $field_class . ' type="' . $input_type . '" name="' . $field_name . '" value="' . $k . '"' . $checked . $input_attrs . ' />';
					}
				}
				else if ($options) {
					$var_name = $options;
					
					$checked = !$is_empty_value ? '<ptl:echo (\\$k == @\\$input[' . $tn_plural . '][\\$i][' . $attr_name . '] ? " checked" : "")/>' : '';
					$code .= '<ptl:if is_array(@\\$input[' . $var_name . '])>
								<ptl:var:t count(\\$input[' . $var_name . ']) />
								
								<ptl:foreach \\$input[' . $var_name . '] k v>
									<ptl:if \\$t &gt; 1>
										<div' . $field_class . '>
											<label><ptl:echo \\$v/></label>
											<input' . $field_class . ' type="' . $input_type . '" name="' . $field_name . '" value="<ptl:echo str_replace(\'"\', \'&quot;\', \\$k) />"' . $checked . $input_attrs . ' />
										</div>
									<ptl:else>
										<input' . $field_class . ' type="' . $input_type . '" name="' . $field_name . '" value="<ptl:echo str_replace(\'"\', \'&quot;\', \\$k) />"' . $checked . $input_attrs . ' />
									</ptl:if>
								</ptl:foreach>
							</ptl:if>';
				}
				else {
					$checked = !$is_empty_value ? ' <ptl:echo (@\\$input[' . $tn_plural . '][\\$i][' . $attr_name . '] == 1 ? "checked" : "") />' : '';
					$code .= '<input' . $field_class . ' type="' . $input_type . '" name="' . $field_name . '" value="1"' . $checked . $input_attrs . ' />';
				}
			}
			else if (!in_array($input_type, self::$editable_input_types)) {
				$code .= '<input type="hidden" name="' . $field_name . '" value="' . $orig_field_value . '" />';
				
				//THIS IS NOT TESTED
				$HtmlFormHandler = new HtmlFormHandler(array("parse_values" => false));
				$code .= $HtmlFormHandler->getFieldInputHtml(array(
					"input" => array(
						"type" => $input_type,
						"class" => self::prepareFieldSettingExtraStringToConcatenateWithAttrProp($attr_props["input_class"], "field-value", true) . " " . $field_class,
						"value" => $field_value,
					)
				));
			}
			else
				$code .= '<input' . $field_class . ' type="' . $input_type . '" name="' . $field_name . '" value="' . $orig_field_value . '"' . ($input_type != "hidden" ? $input_attrs : '') . ' />';
		}
		
		$input_next_html = isset($attr_props["input_next_html"]) ? $attr_props["input_next_html"] : null;
		$code .= self::getFormFieldAttrPropHtml($input_next_html);
		
		return $code;
	}
	
	private static function getTableFieldLinksHtml($links, $tn, $tn_plural, $attrs, $pks, $simple_value = false) {
		$code = '';
		
		if ($links) {
			foreach ($links as $link) {
				//I may want a link without a "a" html element, like a separator for the following links
				self::prepareLink($link, $tn_plural, $attrs, $pks, true, false, true, $simple_value);
				
				$href = !empty($link["url"]) ? ' href="' . self::getFormFieldAttrPropHtml($link["url"]) . '"' : '';
				$title = !empty($link["title"]) ? ' title="' . self::getFormFieldAttrPropHtml($link["title"]) . '"' : '';
				$target = !empty($link["target"]) ? ' target="' . self::getFormFieldAttrPropHtml($link["target"]) . '"' : '';
				$extra_attributes = "";
				
				if (!empty($link["extra_attributes"])) {
					if(is_array($link["extra_attributes"]))
						foreach ($link["extra_attributes"] as $f)
							if (!empty($f["name"])) {
								$value = isset($f["value"]) ? $f["value"] : null;
								$extra_attributes .= ' ' . self::getFormFieldAttrPropHtml($f["name"]) . '="' . self::getFormFieldAttrPropHtml($value) . '"';
							}
					else
						$extra_attributes .= ' ' . self::getFormFieldAttrPropHtml($link["extra_attributes"]);
				}
				
				$class = isset($link["class"]) ? $link["class"] : null;
				$previous_html = isset($link["previous_html"]) ? $link["previous_html"] : null;
				$value = isset($link["value"]) ? $link["value"] : null;
				$next_html = isset($link["next_html"]) ? $link["next_html"] : null;
				
				$code .= '<div class="link link-' . $tn . ' ' . self::getFormFieldAttrPropHtml($class) . '">' . 
					self::getFormFieldAttrPropHtml($previous_html) . 
					'<a' . $href . $title . $target . $extra_attributes . '>' . 
						self::getFormFieldAttrPropHtml($value ? $value : (isset($link["title"]) ? $link["title"] : null)) . 
					'</a>' . 
					self::getFormFieldAttrPropHtml($next_html) . 
				'</div>' . "\n";
			}
		}
		
		return $code;
	}
	
	/* TREE METHODS */
	
	private static function getTreeFormSettings($tables, $table_name, $tn, $tn_label, $tn_plural, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, &$generic_javascript, $pagination = false, $panel_class = "", $panel_id = "") {
		$insertable = !empty($actions_props["single_insert"]) || !empty($actions_props["multiple_insert"]) || !empty($actions_props["multiple_insert_update"]);
		$updatable = !empty($actions_props["single_update"]) || !empty($actions_props["multiple_update"]) || !empty($actions_props["multiple_insert_update"]);
		$deletable = !empty($actions_props["single_delete"]) || !empty($actions_props["multiple_delete"]);
		
		$has_form = !empty($actions_props["multiple_insert_update"]) || !empty($actions_props["multiple_insert"]) || !empty($actions_props["multiple_update"]) || !empty($actions_props["multiple_delete"]);
		$has_form_default_action = (
			(isset($actions_props["multiple_insert_update"]["action_type"]) && substr($actions_props["multiple_insert_update"]["action_type"], 0, 5) != "ajax_") || 
			(isset($actions_props["multiple_insert"]["action_type"]) && substr($actions_props["multiple_insert"]["action_type"], 0, 5) != "ajax_") || 
			(isset($actions_props["multiple_update"]["action_type"]) && substr($actions_props["multiple_update"]["action_type"], 0, 5) != "ajax_") || 
			(isset($actions_props["multiple_delete"]["action_type"]) && substr($actions_props["multiple_delete"]["action_type"], 0, 5) != "ajax_")
		);
		
		//preparing fields
		$fields = self::getFieldsSettings($tables, $table_name, $tn, $attributes, $pks, $pks_auto_increment, $actions_props, $insertable, $updatable, $deletable, "tree");
		
		//preparing single buttons
		$single_buttons = array();
		
		//2020-01-22: does not make sense the insert button for the existent items. Only the new added items will have the insert button
		//if ($actions_props["single_insert"])
		//	$single_buttons[] = self::getFormFieldButtonSettings("insert", $tn . "_add", "Add", $actions_props["single_insert"], $tn, $tn_label, $panel_id);
		
		if (!empty($actions_props["single_update"]))
			$single_buttons[] = self::getFormFieldButtonSettings("update", $tn . "_save", "Save", $actions_props["single_update"], $tn, $tn_label, $panel_id);
		
		if (!empty($actions_props["single_delete"])) {
			$action_props = $actions_props["single_delete"];
			
			if (!isset($action_props["confirmation_message"])) {
				if ($child_tables) {
					$action_props["confirmation_message"] .= "You are about to delete this $tn_label.\nNote that this $tn_label may have some dependencies/children in the following table(s):";
					
					for ($i = 0; $i < count($child_tables); $i++)
						$action_props["confirmation_message"] .= "\n- " + self::getName($child_tables[$i]);
					
					$action_props["confirmation_message"] .= "\n\nYou should delete the correspondent dependencies/children first, and only then delete this $tn_label.\n\nAre you sure that you wish to proceed with this $tn_label deletion (before delete it's dependencies)?";
				}
				else
					$action_props["confirmation_message"] = "Do you wish to delete this $tn_label?";
			}
			
			$single_buttons[] = self::getFormFieldButtonSettings("delete", $tn . "_delete", "Delete", $actions_props["single_delete"], $tn, $tn_label, $panel_id);
		}
		
		//preparing links
		$links = array();
		
		if (!empty($actions_props["links"]))
			foreach ($actions_props["links"] as $link) {
				$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
				
				//I may want a link without a "a" html element, like a separator for the following links
				self::prepareLink($link, $tn, $attrs, $pks, true, false, false, false);
				
				$links[] = array(
					"field" => array(
						"class" => "link link-$tn " . (isset($link["class"]) ? $link["class"] : ""),
						"input" => array(
							"type" => "link",
							"value" => !empty($link["value"]) ? $link["value"] : (isset($link["title"]) ? $link["title"] : null),
							"href" => isset($link["url"]) ? $link["url"] : null,
							"target" => isset($link["target"]) ? $link["target"] : null,
							"title" => isset($link["title"]) ? $link["title"] : null,
							"extra_attributes" => isset($link["extra_attributes"]) ? $link["extra_attributes"] : null,
							"previous_html" => isset($link["previous_html"]) ? $link["previous_html"] : null,
							"next_html" => isset($link["next_html"]) ? $link["next_html"] : null,
						)
					)
				);
			}
		
		//preparing inner containers
		$inner_containers = array();
		
		if ($fields)
			$inner_containers[] = array(
				"container" => array(
					"class" => "fields-container",
					"previous_html" => "",
					"next_html" => "",
					"elements" => $fields
				)
			);
		
		if ($single_buttons)
			$inner_containers[] = array(
				"container" => array(
					"class" => "buttons single-buttons",
					"elements" => $single_buttons
				)
			);
		
		if ($links)
			$inner_containers[] = array(
				"container" => array(
					"class" => "links",
					"elements" => $links
				)
			);
		
		//preparing multiple buttons
		$multiple_buttons = array();
		
		if (!empty($actions_props["single_insert"]) || !empty($actions_props["multiple_insert_update"]) || !empty($actions_props["multiple_insert"])) {
			$insert_htmls = self::getTreeInsertFieldsSettings($tables, $table_name, $tn, $tn_label, $panel_id, $attributes, $pks, $pks_auto_increment, $actions_props, $inner_containers);
			$panel_id_hash = HashCode::getHashCodePositive($panel_id);
			
			$generic_javascript .= "\n" . self::convertHtmlToJavascriptVariable("{$tn}_tree_{$panel_id_hash}_before_insert_html", $insert_htmls[0]);
			$generic_javascript .= "\n" . self::convertHtmlToJavascriptVariable("{$tn}_tree_{$panel_id_hash}_after_insert_html", $insert_htmls[1]);
			$generic_javascript .= "\n";
		}
		
		//only add insert button if update action does not exists, otherwise the insert actions will be done by the SAVE button
		if (!empty($actions_props["multiple_insert_update"]))
			$multiple_buttons[] = self::getFormFieldButtonSettings("insert_update", $tn . "_add_save", "Save", $actions_props["multiple_insert_update"], $tn, $tn_label, $panel_id, true);
		else if (!empty($actions_props["multiple_insert"]))
			$multiple_buttons[] = self::getFormFieldButtonSettings("insert", $tn . "_add", "Add", $actions_props["multiple_insert"], $tn, $tn_label, $panel_id, true);
		else if (!empty($actions_props["multiple_update"]))
			$multiple_buttons[] = self::getFormFieldButtonSettings("update", $tn . "_save", "Save", $actions_props["multiple_update"], $tn, $tn_label, $panel_id, true);
		
		if (!empty($actions_props["multiple_delete"])) {
			$action_props = $actions_props["multiple_delete"];
			
			if (!isset($action_props["confirmation_message"])) {
				$tn_plural_label = self::getName($tn_plural);
				
				if ($child_tables) {
					$action_props["confirmation_message"] .= "You are about to delete multiples $tn_plural_label.\nNote that these $tn_plural_label may have some dependencies/children in the following table(s):";
					
					for ($i = 0; $i < count($child_tables); $i++)
						$action_props["confirmation_message"] .= "\n- " + self::getName($child_tables[$i]);
					
					$action_props["confirmation_message"] .= "\n\nYou should delete the correspondent dependencies/children first, and only then delete these $tn_plural_label.\n\nAre you sure that you wish to proceed with this $tn_plural_label deletion (before delete it's dependencies)?";
				}
				else
					$action_props["confirmation_message"] = "Do you wish to delete multiples $tn_plural_label?";
			}
			
			$multiple_buttons[] = self::getFormFieldButtonSettings("delete", $tn . "_delete", "Delete", $action_props, $tn, $tn_label, $panel_id, true);
		}
			
		//preparing pagination
		$is_pagination_active = !$pagination || !empty($pagination["active"]);
		
		if ($is_pagination_active) {
			$top_pagination = array(
				"container" => array(
					"class" => "top-pagination",
					"elements" => array(
						array(
							"pagination" => array(
								"pagination_template" => "bootstrap1",
								"rows_per_page" => "#" . $tn_plural . "_rows_per_page#",
								"page_number" => "#" . $tn_plural . "_current_page#",
								"max_num_of_shown_pages" => "20",
								"total_rows" => "#" . $tn_plural . "_count#",
								"page_attr_name" => $tn_plural . "_current_page",
								"on_click_js_func" => $pagination && isset($pagination["on_click_js_func"]) ? $pagination["on_click_js_func"] : null
							)
						)
					)
				)
			);
			
			$bottom_pagination = $top_pagination;
			$bottom_pagination["container"]["class"] = "bottom-pagination";
		}
		
		//preparing containers
		$containers = array();
		
		if (!empty($actions_props["single_insert"]) || !empty($actions_props["multiple_insert"]) || !empty($actions_props["multiple_insert_update"])) {
			$on_click = 'addNewItem(this)';
			$ea_button_type = !empty($actions_props["multiple_insert_update"]) ? "insert_update" : "insert";
			$ea_button_name = !empty($actions_props["multiple_insert_update"]) ? $tn . "_add_save" : $tn . "_add";
			$ea_button_label = !empty($actions_props["multiple_insert_update"]) ? "Save" : "Add";
			$ea_action_props = !empty($actions_props["multiple_insert_update"]) ? $actions_props["multiple_insert_update"] : (
				!empty($actions_props["multiple_insert"]) ? $actions_props["multiple_insert"] : (
					!empty($actions_props["single_insert"]) ? $actions_props["single_insert"] : null
				)
			);
			$ea_html = self::getFormFieldButtonExtraAttributesHtml($ea_button_type, $ea_button_name, $ea_button_label, $ea_action_props, $tn, $tn_label, $panel_id, $on_click);
			
			$containers[] = array(
				"container" => array(
					"class" => "buttons multiple-buttons add-button",
					"previous_html" => '<a href="javascript:void(0);"' . $ea_html . '><span class="icon add"></span> Add ' . $tn_label . '</a>'
				)
			);
		}
		
		if ($is_pagination_active)
			$containers[] = $top_pagination;
		
		$containers[] = array(
				"container" => array(
					"class" => "list-container",
					"previous_html" => "",
					"next_html" => "",
					"elements" => array(
						0 => array(
							"tree" => array(
								"tree_class" => "list-tree",
								"default_input_data" => "#" . $tn_plural . "#",
								"elements" => $inner_containers
							)
						)
					)
				)
			);
		
		if ($multiple_buttons)
			$containers[] = array(
				"container" => array(
					"class" => "buttons multiple-buttons",
					"elements" => $multiple_buttons
				)
			);
		
		if ($is_pagination_active)
			$containers[] = $bottom_pagination;
		
		$form_id = "form_" . rand(0, 10000);
		
		return array(
			"with_form" => $has_form,
			"form_id" => $form_id,
			"form_method" => "post",
			"form_class" => "",
			"form_on_submit" => $has_form_default_action ? "" : "return false",
			"form_action" => "",
			"form_type" => "horizontal",
			"form_containers" => array(
				array(
					"container" => array(
						"id" => $panel_id,
						"class" => 'list-' . str_replace(array("_", " "), "-", self::getParsedTableName($tn_plural)) . ' ' . $panel_class, //$tn_plural name can have schema
						"previous_html" => "",
						"next_html" => "",
						"elements" => $containers
					)
				)
			),
			"form_css" => "",
			"form_js" => $has_form ? 'if (typeof MyJSLib != "undefined") { MyJSLib.FormHandler.initForm( $("#' . $form_id . '")[0] ) }' : ''
		);
	}
	
	private static function getTreePTLCode($tables, $table_name, $tn, $tn_label, $tn_plural, $attributes, $pks, $pks_auto_increment, $child_tables, $actions_props, &$generic_javascript, $pagination = false, $panel_class = "", $panel_id = "") {
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		
		$insertable = !empty($actions_props["single_insert"]) || !empty($actions_props["multiple_insert"]) || !empty($actions_props["multiple_insert_update"]);
		$editable = !empty($actions_props["single_update"]) || !empty($actions_props["multiple_update"]) || !empty($actions_props["multiple_insert_update"]);
		$deletable = !empty($actions_props["single_delete"]) || !empty($actions_props["multiple_delete"]);
		
		$is_multiple = !empty($actions_props["multiple_insert_update"]) || !empty($actions_props["multiple_insert"]) || !empty($actions_props["multiple_update"]) || !empty($actions_props["multiple_delete"]);
		
		$has_form = $is_multiple;
		$has_form_default_action = (
			(isset($actions_props["multiple_insert_update"]["action_type"]) && substr($actions_props["multiple_insert_update"]["action_type"], 0, 5) != "ajax_") || 
			(isset($actions_props["multiple_insert"]["action_type"]) && substr($actions_props["multiple_insert"]["action_type"], 0, 5) != "ajax_") || 
			(isset($actions_props["multiple_update"]["action_type"]) && substr($actions_props["multiple_update"]["action_type"], 0, 5) != "ajax_") || 
			(isset($actions_props["multiple_delete"]["action_type"]) && substr($actions_props["multiple_delete"]["action_type"], 0, 5) != "ajax_")
		);
		
		$form_id = null;
		
		//Preparing form html
		$is_pagination_active = !$pagination || !empty($pagination["active"]);
		
		if ($is_pagination_active)
			$pagination_code = '
		<ptl:var:PaginationLayout new PaginationLayout(@\$input["' . $tn_plural . '_count"], @\$input["' . $tn_plural . '_rows_per_page"], array("' . $tn_plural . '_current_page" =&gt; @\$input["' . $tn_plural . '_current_page"]), "' . $tn_plural . '_current_page"' . ($pagination && !empty($pagination["on_click_js_func"]) ? ', "' . $pagination["on_click_js_func"] . '"' : '') . ') />
		<ptl:var:PaginationLayout-&gt;show_x_pages_at_once 20 />
		<ptl:var:pagination_data \$PaginationLayout-&gt;data />
		<ptl:var:pagination_data["style"] "bootstrap1" />
		<ptl:echo \$PaginationLayout-&gt;designWithStyle(1, \$pagination_data) />';
		
		$code = '';
		
		if ($has_form) {
			$form_id = "form_" . rand(0, 10000);
			$aux = '(typeof MyJSLib == \'undefined\' || MyJSLib.FormHandler.formCheck(this))';
			$code .= '<form id="' . $form_id . '" method="post" class="form-horizontal" onSubmit="' . ($has_form_default_action ? 'return ' . $aux : $aux . ';return false') . ';' . '" enctype="multipart/form-data">';
		}
		
		$code .= '
<div class="list-' . str_replace(array("_", " "), "-", self::getParsedTableName($tn_plural)) . ' ' . $panel_class . '"' . ($panel_id ? ' id="' . $panel_id . '"' : '') . '>'; //$tn_plural name can have schema
		
		if (!empty($actions_props["single_insert"]) || !empty($actions_props["multiple_insert"]) || !empty($actions_props["multiple_insert_update"])) {
			$on_click = 'addNewItem(this)';
			$ea_button_type = !empty($actions_props["multiple_insert_update"]) ? "insert_update" : "insert";
			$ea_button_name = !empty($actions_props["multiple_insert_update"]) ? $tn . "_add_save" : $tn . "_add";
			$ea_button_label = !empty($actions_props["multiple_insert_update"]) ? "Save" : "Add";
			$ea_action_props = !empty($actions_props["multiple_insert_update"]) ? $actions_props["multiple_insert_update"] : (
				!empty($actions_props["multiple_insert"]) ? $actions_props["multiple_insert"] : (
					!empty($actions_props["single_insert"]) ? $actions_props["single_insert"] : null
				)
			);
			$ea_html = self::getFormFieldButtonExtraAttributesHtml($ea_button_type, $ea_button_name, $ea_button_label, $ea_action_props, $tn, $tn_label, $panel_id, $on_click);
			
			$code .= '<div class="buttons multiple-buttons add-button">
				<a href="javascript:void(0);"' . $ea_html . '><span class="icon add"></span> Add ' . $tn_label . '</a>
			</div>';
		}
		
		$code .= ($is_pagination_active ? '<div class="top-pagination">' . $pagination_code . '</div>' : '') . '
	<div class="list-container">
		<ul class="list-tree">
			<ptl:if is_array(@\\$input[' . $tn_plural . '])>
				<ptl:foreach \\$input[' . $tn_plural . '] i item>
					<li>
						<div class="fields-container">';
		
		if ($is_multiple)
			$code .= '<div class="multiple-selection"><label>Select to perform action</label><input type="checkbox" name="multiple_selection[' . $tn . '][<ptl:echo \$i/>]" value="1"/></div>';
		
		foreach ($attributes as $attr_name) {
			$attr_props = isset($actions_props["attributes_settings"][$attr_name]) ? $actions_props["attributes_settings"][$attr_name] : null;
			$attr = isset($attrs[$attr_name]) ? $attrs[$attr_name] : null;
			$is_pk = in_array($attr_name, $pks);
			$is_pk_auto_increment = in_array($attr_name, $pks_auto_increment);
			
			//if only insert and attr is a pk auto-incremented, doesn't show attribute
			if ($insertable && !$editable && !$deletable && $is_pk_auto_increment)
				continue 1;
			
			$input_html = self::getTableFieldInputHtml($tn, $tn_plural, $attr_name, $attrs, $attr_props, $pks, $is_pk, $is_pk_auto_increment, $editable, $deletable, $input_type, 
				isset($actions_props["single_update"]["action_type"]) && $actions_props["single_update"]["action_type"] == "ajax_on_blur", 
				false, 
				!empty($actions_props["multiple_update"]) || !empty($actions_props["multiple_insert_update"]) || !empty($actions_props["single_update"])
			);
			
			$code .= '		<div class="field field-' . (isset($attr["type"]) ? strtolower(str_replace(" ", "-", $attr["type"])) : "") . " " . $attr_name . ($is_pk ? " is-pk" : "") . ($is_pk_auto_increment ? " is-pk-auto-increment" : "") . " field-" . $input_type . (!empty($attr_props["class"]) ? " " . self::getFormFieldAttrPropHtml($attr_props["class"]) : "") . '">
								' . self::getFormFieldLabelHtml($attr_name, $attr_props) . '
								' . $input_html . '
							</div>';
		}
		
		$code .= '		</div>';
		
		if (!empty($actions_props["single_insert"]) || !empty($actions_props["single_update"]) || !empty($actions_props["single_delete"])) {
			$code .= '	<div class="buttons single-buttons">';
			
			//2020-01-22: does not make sense the insert button for the existent items. Only the new added items will have the insert button
			//if ($actions_props["single_insert"])
			//	$code .= self::getFormFieldButtonHtml("insert", $tn . "_add", "Add", $actions_props["single_insert"], $tn, $tn_label, $panel_id);
			
			if (!empty($actions_props["single_update"]))
				$code .= self::getFormFieldButtonHtml("update", $tn . "_save", "Save", $actions_props["single_update"], $tn, $tn_label, $panel_id);
			
			if (!empty($actions_props["single_delete"])) {
				$action_props = $actions_props["single_delete"];
				
				if (!isset($action_props["confirmation_message"]))  {
					if ($child_tables) {
						$action_props["confirmation_message"] .= "You are about to delete this $tn_label.\nNote that this $tn_label may have some dependencies/children in the following table(s):";
						
						for ($i = 0; $i < count($child_tables); $i++)
							$action_props["confirmation_message"] .= "\n- " + self::getName($child_tables[$i]);
						
						$action_props["confirmation_message"] .= "\n\nYou should delete the correspondent dependencies/children first, and only then delete this $tn_label.\n\nAre you sure that you wish to proceed with this $tn_label deletion (before delete it's dependencies)?";
					}
					else
						$action_props["confirmation_message"] = "Do you wish to delete this $tn_label?";
				}
				
				$code .= self::getFormFieldButtonHtml("delete", $tn . "_delete", "Delete", $action_props, $tn, $tn_label, $panel_id);
			}
			
			$code .= '	</div>';
		}
		
		if (!empty($actions_props["links"]))
			$code .= '	<div class="links">' . self::getTableFieldLinksHtml($actions_props["links"], $tn, $tn_plural, $attrs, $pks) . '</div>';
		
		$code .= '	</li>
				</ptl:foreach>
			</ptl:if>
		</ul>
	</div>
	' . ($is_pagination_active ? '<div class="bottom-pagination">' . $pagination_code . '</div>' : '');
		
		//prepare multiple buttons
		if (!empty($actions_props["single_insert"]) || !empty($actions_props["multiple_insert_update"]) || !empty($actions_props["multiple_insert"])) {
			$insert_htmls = self::getTreeInsertFieldsPTLCode($tables, $table_name, $tn, $tn_label, $tn_plural, $panel_id, $attributes, $pks, $pks_auto_increment, $actions_props);
			$panel_id_hash = HashCode::getHashCodePositive($panel_id);
			
			$generic_javascript .= "\n" . self::convertHtmlToJavascriptVariable("{$tn}_tree_{$panel_id_hash}_before_insert_html", $insert_htmls[0], true);
			$generic_javascript .= "\n" . self::convertHtmlToJavascriptVariable("{$tn}_tree_{$panel_id_hash}_after_insert_html", $insert_htmls[1], true);
			$generic_javascript .= "\n";
		}
		
		if ($is_multiple) {
			$code .= '<div class="buttons multiple-buttons">';
			
			//only add insert button if update action does not exists, otherwise the insert actions will be done by the SAVE button
			if (!empty($actions_props["multiple_insert_update"]))
				$code .= self::getFormFieldButtonHtml("insert_update", $tn . "_add_save", "Save", $actions_props["multiple_insert_update"], $tn, $tn_label, $panel_id, true);
			else if (!empty($actions_props["multiple_insert"]))
				$code .= self::getFormFieldButtonHtml("insert", $tn . "_add", "Add", $actions_props["multiple_insert"], $tn, $tn_label, $panel_id, true);
			else if (!empty($actions_props["multiple_update"]))
				$code .= self::getFormFieldButtonHtml("update", $tn . "_save", "Save", $actions_props["multiple_update"], $tn, $tn_label, $panel_id, true);
			
			if (!empty($actions_props["multiple_delete"])) {
				$action_props = $actions_props["multiple_delete"];
				
				if (!isset($action_props["confirmation_message"]))  {
					$tn_plural_label = self::getName($tn_plural);
					
					if ($child_tables) {
						$action_props["confirmation_message"] .= "You are about to delete multiples $tn_plural_label.\nNote that these $tn_plural_label may have some dependencies/children in the following table(s):";
						
						for ($i = 0; $i < count($child_tables); $i++)
							$action_props["confirmation_message"] .= "\n- " + self::getName($child_tables[$i]);
						
						$action_props["confirmation_message"] .= "\n\nYou should delete the correspondent dependencies/children first, and only then delete these $tn_plural_label.\n\nAre you sure that you wish to proceed with this $tn_plural_label deletion (before delete it's dependencies)?";
					}
					else
						$action_props["confirmation_message"] = "Do you wish to delete multiples $tn_plural_label?";
				}
				
				$code .= self::getFormFieldButtonHtml("delete", $tn . "_delete", "Delete", $action_props, $tn, $tn_label, $panel_id, true);
			}
			
			$code .= '</div>';
		}
		
		$code .= '</div>';
		
		if ($has_form) 
			$code .= '</form>
			<script>if (typeof MyJSLib != "undefined") { MyJSLib.FormHandler.initForm( $("#' . $form_id . '")[0] ) }</script>';
		
		return $code;
	}
	
	/* OTHER METHODS  */
	
	public static function getActionsSettingsVars($actions_settings, $ignore_vars = false) {
		$vars = array();
		$ignore_vars = is_array($ignore_vars) ? $ignore_vars : ($ignore_vars ? array($ignore_vars) : array());
		
		foreach ($actions_settings as $action_settings)
			if (!empty($action_settings["result_var_name"])) {
				$result_var_name = $action_settings["result_var_name"];
				
				if (!in_array($result_var_name, $ignore_vars)) {
					if (isset($action_settings["action_value"]) && is_array($action_settings["action_value"]) && !empty($action_settings["action_value"]["actions"])) {
						$sub_vars = self::getActionsSettingsVars($action_settings["action_value"]["actions"], $ignore_vars);
						$prev_vars = $vars[$result_var_name];
						$vars[$result_var_name] = is_array($prev_vars) ? array_merge($prev_vars, $sub_vars) : $sub_vars;
					}
					else if (!isset($vars[$result_var_name]))
						$vars[$result_var_name] = true;
				}
			}
			else if (isset($action_settings["action_value"]) && is_array($action_settings["action_value"]) && !empty($action_settings["action_value"]["actions"])) {
				$sub_vars = self::getActionsSettingsVars($action_settings["action_value"]["actions"], $ignore_vars);
				$vars = array_merge($vars, $sub_vars);
			}
		
		return $vars;
	}
	
	public static function printActionsSettingsVars($vars, $prefix = "") {
		$code = "array(\n";
		
		foreach ($vars as $var_name => $sub_vars) {
			$code .= "$prefix\"$var_name\" => ";
			
			if (is_array($sub_vars))
				$code .= self::printActionsSettingsVars($sub_vars, $prefix . "\t");
			else
				$code .= "\$$var_name";
			
			$code .= ",\n"; 
		}
		
		$code .= substr($prefix, -1) . ")";
		
		return $code;
	}
	
	private static function getFieldsSettings($tables, $table_name, $tn, $attributes, $pks, $pks_auto_increment, $actions_props, $insertable, $updatable, $deletable, $is_list = false, $is_list_before_insert = false) {
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		$fields = array();
		$editable = $insertable || $updatable;
		
		if ($is_list && (
			!empty($actions_props["multiple_insert_update"]) || !empty($actions_props["multiple_insert"]) || !empty($actions_props["multiple_update"]) || !empty($actions_props["multiple_delete"])
		)) {
			$fields[] = array(
				"field" => array(
					"class" => 'multiple-selection',
					"label" => array(
						"value" => $is_list === "tree" ? "Select to perform action" : "",
					),
					"input" => array(
						"type" => "checkbox",
						"name" => "multiple_selection[$tn][#idx#]",
						"options" => array(array("value" => 1)),
					)
				)
			);
		}
		
		foreach ($attributes as $attr_name) {
			//if only insert and attr is a pk auto-incremented, doesn't show attribute
			if (!$is_list && $insertable && !$updatable && !$deletable && in_array($attr_name, $pks_auto_increment))
				continue 1;
			
			$fields[] = self::getFieldSettings($attr_name, $attrs, $tn, $attributes, $pks, $pks_auto_increment, $actions_props, $editable, $deletable, $is_list, $is_list_before_insert);
		}
		
		return $fields;
	}
	
	private static function getFieldSettings($attr_name, $attrs, $tn, $attributes, $pks, $pks_auto_increment, $actions_props, $editable, $deletable, $is_list = false, $is_list_before_insert = false) {
		$attr = isset($attrs[$attr_name]) ? $attrs[$attr_name] :null;
		$attr_props = isset($actions_props["attributes_settings"][$attr_name]) ? $actions_props["attributes_settings"][$attr_name] : null;
		
		$field_value = isset($attr_props["input_value"]) && strlen($attr_props["input_value"]) ? $attr_props["input_value"] : ($is_list ? ($is_list_before_insert ? '' : '#[idx][' . $attr_name . ']#') : '#' . $tn . '[' . $attr_name . ']#');
		$field_name = $tn . ($is_list ? '[#idx#]' : '') . '[' . $attr_name . ']';
		
		$is_pk = in_array($attr_name, $pks);
		$is_pk_auto_increment = in_array($attr_name, $pks_auto_increment);
		$class = "field field-" . (isset($attr["type"]) ? strtolower(str_replace(" ", "-", $attr["type"])) : "") . " " . $attr_name . ($is_pk ? " is-pk" : "") . ($is_pk_auto_increment ? " is-pk-auto-increment" : "");
		
		if (!empty($attr_props["class"]))
			$class = self::prepareFieldSettingExtraStringToConcatenateWithAttrProp($attr_props["class"], $class, true) . " " . $attr_props["class"];
		
		$label = isset($attr_props["label_value"]) && strlen($attr_props["label_value"]) ? $attr_props["label_value"] : self::getName($attr_name);
		
		if (!$is_list)
			$label .= self::prepareFieldSettingExtraStringToConcatenateWithAttrProp(isset($attr_props["label_value"]) ? $attr_props["label_value"] : null, ":");
		
		$is_input_class_php_code = empty(PHPUICodeExpressionHandler::getValueType(isset($attr_props["input_class"]) ? $attr_props["input_class"] : null, array("empty_string_type" => "string", "non_set_type" => "string")));
		$is_input_value_php_code = empty(PHPUICodeExpressionHandler::getValueType(isset($attr_props["input_value"]) ? $attr_props["input_value"] : null, array("empty_string_type" => "string", "non_set_type" => "string")));
		$is_input_previous_html_php_code = empty(PHPUICodeExpressionHandler::getValueType(isset($attr_props["input_previous_html"]) ? $attr_props["input_previous_html"] : null, array("empty_string_type" => "string", "non_set_type" => "string")));
		
		$previous_html = "";
		if (!empty($attr_props["input_previous_html"])) {
			if (!$is_input_previous_html_php_code && ($is_input_class_php_code || $is_input_value_php_code))
				$previous_html = "'" . addcslashes($attr_props["input_previous_html"], "\\'") . "'";
			else
				$previous_html = $attr_props["input_previous_html"];
		}
		
		$eas = $options = null;
		
		$available_values = array();
		if (!empty($attr_props["available_values"])) { //available_values could be an array or a variable
			if (is_array($attr_props["available_values"]))
				$available_values = $attr_props["available_values"];
			else if (is_string($attr_props["available_values"])) //it means it is a variable name
				$available_values = "#" . $attr_props["available_values"] . "#";
		}
		
		$attr_type = isset($attr_props["type"]) ? $attr_props["type"] : null;
		$attr_class = isset($attr_props["class"]) ? $attr_props["class"] : null;
		$attr_input_class = isset($attr_props["input_class"]) ? $attr_props["input_class"] : null;
		
		if ($attr_type == "hidden") //this is used when the user does include the pks in $attributes, and the system adds them automatically by as hidden fields...
			$field = array(
				"field" => array(
					"class" => $class . self::prepareFieldSettingExtraStringToConcatenateWithAttrProp($attr_class, "field-hidden"),
					"input" => array(
						"type" => "hidden",
						"class" => $attr_input_class,
						"name" => $field_name,
						"value" => $field_value,
						"previous_html" => isset($attr_props["input_previous_html"]) ? $attr_props["input_previous_html"] : null,
						"next_html" => isset($attr_props["input_next_html"]) ? $attr_props["input_next_html"] : null,
					),
				)
			);
		else if (!$editable) {
			if ($is_pk && $deletable)
				$previous_html .= self::getFormSettingInputHidden($field_name, $field_value, $attr_input_class, $is_input_class_php_code, $is_input_value_php_code, $is_input_previous_html_php_code, !empty($previous_html));
			
			if ($attr_type == "link") {
				$href = isset($attr_props["href"]) ? $attr_props["href"] : null;
				
				$field = array(
					"field" => array(
						"class" => $class . self::prepareFieldSettingExtraStringToConcatenateWithAttrProp($attr_class, "field-link field-link-" . $tn . " field-link-" . $tn . "-" . $attr_name),
						"label" => array(
							"value" => $label,
							"class" => isset($attr_props["label_class"]) ? $attr_props["label_class"] : null,
							"previous_html" => isset($attr_props["label_previous_html"]) ? $attr_props["label_previous_html"] : null,
							"next_html" => isset($attr_props["label_next_html"]) ? $attr_props["label_next_html"] : null
						),
						"input" => array(
							"type" => "link",
							"class" => self::prepareFieldSettingExtraStringToConcatenateWithAttrProp($attr_input_class, "field-value", true) . " " . $attr_input_class,
							"value" => $field_value,
							"href" => self::getHrefWithPks($href, $tn, $attrs, $pks, $is_list, $is_list_before_insert),
							"target" => isset($attr_props["target"]) ? $attr_props["target"] : null,
							"available_values" => $available_values,
							"previous_html" => $previous_html,
							"next_html" => isset($attr_props["input_next_html"]) ? $attr_props["input_next_html"] : null,
						),
					)
				);
			}
			else
				$field = array(
					"field" => array(
						"class" => $class . self::prepareFieldSettingExtraStringToConcatenateWithAttrProp($attr_class, "field-label"),
						"label" => array(
							"value" => $label,
							"class" => isset($attr_props["label_class"]) ? $attr_props["label_class"] : null,
							"previous_html" => isset($attr_props["label_previous_html"]) ? $attr_props["label_previous_html"] : null,
							"next_html" => isset($attr_props["label_next_html"]) ? $attr_props["label_next_html"] : null,
						),
						"input" => array(
							"type" => $attr_type ? $attr_type : "label",
							"class" => self::prepareFieldSettingExtraStringToConcatenateWithAttrProp($attr_input_class, "field-value", true) . " " . $attr_input_class,
							"value" => $field_value,
							"available_values" => $available_values,
							"previous_html" => $previous_html,
							"next_html" => isset($attr_props["input_next_html"]) ? $attr_props["input_next_html"] : null,
						),
					)
				);
		}
		else if ($is_pk_auto_increment) {
			$previous_html .= self::getFormSettingInputHidden($field_name, $field_value, $attr_input_class, $is_input_class_php_code, $is_input_value_php_code, $is_input_previous_html_php_code, !empty($previous_html));
			
			$field = array(
				"field" => array(
					"class" => $class . " " . self::prepareFieldSettingExtraStringToConcatenateWithAttrProp($attr_class, "field-label"),
					"label" => array(
						"value" => $label,
						"class" => isset($attr_props["label_class"]) ? $attr_props["label_class"] : null,
						"previous_html" => isset($attr_props["label_previous_html"]) ? $attr_props["label_previous_html"] : null,
						"next_html" => isset($attr_props["label_next_html"]) ? $attr_props["label_next_html"] : null,
					),
					"input" => array(
						"type" => "label",
						"class" => self::prepareFieldSettingExtraStringToConcatenateWithAttrProp($attr_input_class, "field-value", true) . " " . $attr_input_class,
						"value" => $field_value,
						"available_values" => $available_values,
						"previous_html" => $previous_html,
						"next_html" => isset($attr_props["input_next_html"]) ? $attr_props["input_next_html"] : null,
					),
				)
			);
		}
		else {
			$allow_null = $validation_type = $validation_message = $place_holder = $extra_attributes = $max_length = $eas = $options = null;
			
			self::prepareFormInputParameters($attr, $input_type, $allow_null, $validation_type, $validation_message, $validation_label, $place_holder, $extra_attributes, $max_length);
			
			//only assign $input_type with $attr_type, if no is_list_before_insert or if $attr_type is editable type.
			if ($attr_type && (!$is_list_before_insert || in_array($attr_type, self::$editable_input_types)))
				$input_type = $attr_type;
			
			$eas = array();
			if ($extra_attributes) 
				foreach ($extra_attributes as $k => $v)
					$eas[] = array(
						"name" => $k,
						"value" => $v
					);
			
			if (isset($actions_props["single_update"]["action_type"]) && $actions_props["single_update"]["action_type"] == "ajax_on_blur" && !$is_list_before_insert)
				$eas[] = array(
					"name" => "onBlur",
					"value" => 'update' . str_replace(" ", "", self::getName($tn)) . '(this, onUpdate)'
				);
			
			if (!empty($actions_props["multiple_update"]) || !empty($actions_props["multiple_insert_update"]) || ($is_list && !empty($actions_props["single_update"])))
				$eas[] = array(
					"name" => $input_type == "select" || $input_type == "checkbox" || $input_type == "radio" ? "onChange" : "onKeyDown",
					"value" => 'onMultipleUpdateKeyDown(this)'
				);
			
			$options = array();
			if (!empty($attr_props["options"])) {
				if (!$attr_type)
					$input_type = "select"; //only change this if $attr_type is empty.
				else if ($is_list_before_insert && $input_type != "select" && $input_type != "checkbox" && $input_type != "radio")
					$input_type = "select";//if before insert, only change this if $attr_type is not a select, checkbox or radio button.
				
				if ($input_type == "select" || $input_type == "checkbox" || $input_type == "radio") {
					$available_values = null; //otherwise the selected values won't be correct bc will try to match after it gets the value from the $available_values list.
					
					if (is_array($attr_props["options"]))
						$options = $attr_props["options"];
					else if (is_string($attr_props["options"]))
						$options = "#" . $attr_props["options"] . "#";
					
					if (!empty($attr_props["options_javascript_variable_name"]))
						$eas[] = array(
							"name" => "data-options-javascript-variable-name",
							"value" => $attr_props["options_javascript_variable_name"]
						);
				}
			}
			
			if ($is_pk && $input_type != "hidden")
				$previous_html .= self::getFormSettingInputHidden($tn . ($is_list ? '[#idx#]' : '') . '[orig_' . $attr_name . ']', $field_value, $attr_input_class, $is_input_class_php_code, $is_input_value_php_code, $is_input_previous_html_php_code, !empty($previous_html));
				
			//add hidden input field if input_type is not an editable field.
			if (!in_array($input_type, self::$editable_input_types))
				$previous_html .= self::getFormSettingInputHidden($tn . ($is_list ? '[#idx#]' : '') . '[' . $attr_name . ']', $field_value, "", false, $is_input_value_php_code, $is_input_previous_html_php_code, !empty($previous_html));
			
			$field = array(
				"field" => array(
					"class" => $class . " " . self::prepareFieldSettingExtraStringToConcatenateWithAttrProp($attr_class, "field-" . $input_type),
					"label" => array(
						"value" => $label,
						"class" => isset($attr_props["label_class"]) ? $attr_props["label_class"] : null,
						"previous_html" => isset($attr_props["label_previous_html"]) ? $attr_props["label_previous_html"] : null,
						"next_html" => isset($attr_props["label_next_html"]) ? $attr_props["label_next_html"] : null,
					),
					"input" => array(
						"type" => $input_type,
						"class" => $attr_input_class,
						"name" => $field_name,
						"value" => $field_value,
						"place_holder" => $place_holder,
						"allow_null" => $allow_null,
						"max_length" => $max_length,
						"validation_label" => $validation_label,
						"validation_message" => $validation_message,
						"validation_type" => $validation_type,
						"extra_attributes" => $eas,
						"available_values" => $available_values,
						"options" => $options,
						"previous_html" => $previous_html,
						"next_html" => isset($attr_props["input_next_html"]) ? $attr_props["input_next_html"] : null,
					),
				)
			);
		}
		
		return $field;
	}
	
	private static function getFormSettingInputHidden($field_name, $field_value, $input_class, $is_input_class_php_code, $is_input_value_php_code, $is_input_previous_html_php_code, $append = true) {
		if ($is_input_class_php_code || $is_input_value_php_code || $is_input_previous_html_php_code) {
			$html = ($append ? ' . ' : '') . '\'<input';
			
			if ($input_class) {
				$html .= ' class="';
				
				if ($is_input_class_php_code)
					$html .= '\' . ' . $input_class . ' . \'';
				else
					$html .= $input_class;
				
				$html .= '"';
			}
			
			$html .= ' type="hidden" name="' . $field_name . '" value="';
			
			if ($is_input_value_php_code)
				$html .= '\' . ' . $field_value . ' . \'';
			else
				$html .= $field_value;
			 
			 $html .= '" />\'';
		}
		else
			$html = '<input' . ($input_class ? ' class="' . $input_class . '"' : '') . ' type="hidden" name="' . $field_name . '" value="' . $field_value . '" />';
		
		return $html;
	}
	
	private static function prepareFieldSettingExtraStringToConcatenateWithAttrProp($attr_prop, $str, $prepend = false) {
		if ($attr_prop) {
			$attr_prop_type = PHPUICodeExpressionHandler::getValueType($attr_prop, array("empty_string_type" => "string", "non_set_type" => "string"));
			
			if (empty($attr_prop_type)) //if php code like a $_GET var
				return (!$prepend ? " . " : "") . "'" . addcslashes($str, "\\'") . "'" . ($prepend ? " . " : "");
		}
		return " " . $str;
	}
	
	/* JAVASCRIPT FUNCTIONS */
	
	private static function getAjaxJavascript($actions_props, $generic_ajax_javascript, $panel_type, $pagination) {
		$code = '';
		$is_single_insert_ajax = isset($actions_props["single_insert"]["action_type"]) && substr($actions_props["single_insert"]["action_type"], 0, 5) == "ajax_";
		$is_single_update_ajax = isset($actions_props["single_update"]["action_type"]) && substr($actions_props["single_update"]["action_type"], 0, 5) == "ajax_";
		$is_single_delete_ajax = isset($actions_props["single_delete"]["action_type"]) && substr($actions_props["single_delete"]["action_type"], 0, 5) == "ajax_";
		$is_multiple_insert_ajax = isset($actions_props["multiple_insert"]["action_type"]) && substr($actions_props["multiple_insert"]["action_type"], 0, 5) == "ajax_";
		$is_multiple_update_ajax = isset($actions_props["multiple_update"]["action_type"]) && substr($actions_props["multiple_update"]["action_type"], 0, 5) == "ajax_";
		$is_multiple_delete_ajax = isset($actions_props["multiple_delete"]["action_type"]) && substr($actions_props["multiple_delete"]["action_type"], 0, 5) == "ajax_";
		$is_multiple_insert_update_ajax = isset($actions_props["multiple_insert_update"]["action_type"]) && substr($actions_props["multiple_insert_update"]["action_type"], 0, 5) == "ajax_";
		
		//only if any if $generic_ajax_javascript
		
		if (strpos($generic_ajax_javascript, "vendor/phpjs/functions/strings/parse_str.js") === false && ($is_single_insert_ajax || $is_single_update_ajax || $is_single_delete_ajax || $is_multiple_insert_update_ajax || $is_multiple_insert_ajax || $is_multiple_update_ajax || $is_multiple_delete_ajax))
			$code .= '
if (typeof parse_str != "function")
	document.write(unescape("%3Cscript src=\'{$project_common_url_prefix}vendor/phpjs/functions/strings/parse_str.js\' type=\'text/javascript\'%3E%3C/script%3E"));
';
		
		if (strpos($generic_ajax_javascript, ".replaceAll = function(") === false && (
			!empty($actions_props["single_insert"]) || 
			!empty($actions_props["single_update"]) || 
			!empty($actions_props["single_delete"]) || 
			!empty($actions_props["multiple_insert"]) || 
			!empty($actions_props["multiple_update"]) || 
			!empty($actions_props["multiple_delete"]) || 
			!empty($actions_props["multiple_insert_update"])
		))
			$code .= '
if (typeof String.prototype.replaceAll != "function")
	String.prototype.replaceAll = function(to_replace, replacement) {
		return this.split(to_replace).join(replacement);
	}
';
		
		if (strpos($generic_ajax_javascript, "function loadPageWithNewNavigation(") === false && $panel_type == "multiple_form" && empty($pagination["on_click_js_func"]))
			$code .= '
if (typeof loadPageWithNewNavigation != "function")
	function loadPageWithNewNavigation(page_attr_name, page_number) {
		var url = document.location.toString();
		eval("url = decodeURI(url).replace(/" + page_attr_name + "=[^&]*/gi, \'\');");
		url += (url.indexOf("?") == -1 ? "?" : "&") + page_attr_name + "=" + page_number;
		url = url.replace(/[&]+/g, "&");
		
		document.location = url;
		return false;
	}
';
		
		if (strpos($generic_ajax_javascript, "function getRedirectUrlWithPks(") === false && ($is_single_insert_ajax || $is_single_update_ajax || $is_single_delete_ajax || $is_multiple_insert_update_ajax || $is_multiple_insert_ajax || $is_multiple_update_ajax || $is_multiple_delete_ajax))
			$code .= '
if (typeof getRedirectUrlWithPks != "function")
	function getRedirectUrlWithPks(url, fields) {
		var qs = "";
		
		$.each(fields, function(idx, field) {
			field = $(field)
			
			if (field.is("input, textarea, select") && field.parent().hasClass("is-pk"))
				qs += (qs ? "&" : "") + field.attr("name") + "=" + field.val();
		});
		
		url += (url.indexOf("?") == -1 ? "?" : "&") + qs;
		
		return url;
	}
';
		
		if (strpos($generic_ajax_javascript, "function getItemRowIndex(") === false && ($panel_type == "list_table" || $panel_type == "list_form" || $panel_type == "multiple_form") && (
			!empty($actions_props["single_insert"]) || 
			!empty($actions_props["multiple_insert_update"]) || 
			!empty($actions_props["multiple_insert"])
		))
			$code .= '
if (typeof getItemRowIndex != "function")
	function getItemRowIndex(item, field_name_prefix) {
		var fields = $(item).find("input, textarea, select");
			
		for (var i = 0; i < fields.length; i++) {
			var name = fields[i].name;
			
			if (name.indexOf(field_name_prefix + "[") === 0) {
				var s = field_name_prefix.length + 1;
				var row_index = parseInt(name.substr(s, name.indexOf("]", s) - s));
				
				if ($.isNumeric(row_index))
					return row_index;
			}
		}
		
		return null;
	}
';
		
		if (strpos($generic_ajax_javascript, "function getNextRowIndex(") === false && ($panel_type == "list_table" || $panel_type == "list_form" || $panel_type == "multiple_form") && (
			!empty($actions_props["single_insert"]) || 
			!empty($actions_props["multiple_insert_update"]) || 
			!empty($actions_props["multiple_insert"])
		))
			$code .= '
if (typeof getNextRowIndex != "function")
	function getNextRowIndex(parent, field_name_prefix) {
		var next_row_index = 0;
		var children = $(parent).children();
		
		for (var i = 0; i < children.length; i++) {
			var row_index = getItemRowIndex(children[i], field_name_prefix);
			next_row_index = row_index && next_row_index < row_index ? row_index : next_row_index;
		};
		next_row_index++;
		
		return next_row_index;
	}
';
		
		if (strpos($generic_ajax_javascript, "function getRowIndexElement(") === false && ($is_multiple_insert_update_ajax || $is_multiple_insert_ajax || $is_multiple_update_ajax || $is_multiple_delete_ajax))
			$code .= '
if (typeof getRowIndexElement != "function")
	function getRowIndexElement(parent, field_name_prefix, row_index) {
		var children = $(parent).find("li, tr");
		
		for (var i = 0; i < children.length; i++) {
			var child = $( children[i] );
			var fields = child.find("input, textarea, select");
			
			for (var j = 0; j < fields.length; j++) {
				var name = fields[j].name;
				
				if (name.indexOf(field_name_prefix + "[") === 0) {
					var s = field_name_prefix.length + 1;
					var field_row_index = parseInt(name.substr(s, name.indexOf("]", s) - s));
					
					if (row_index == field_row_index)
						return child;
					
					break;
				}
			}
		}
		
		return null;
	}
';
		
		if (strpos($generic_ajax_javascript, "function onMultipleUpdateKeyDown(") === false && (
			!empty($actions_props["multiple_insert_update"]) || 
			!empty($actions_props["multiple_insert"]) || 
			!empty($actions_props["multiple_update"]) || 
			( ($panel_type == "list_table" || $panel_type == "list_form") && !empty($actions_props["single_update"]) )
		))
			$code .= '
if (typeof onMultipleUpdateKeyDown != "function")
	function onMultipleUpdateKeyDown(elm) {
		var input = $(elm).parent().closest("li, tr").find(".multiple-selection input");
		
		if (!input.is(":checked"))
			input.attr("checked", "checked").prop("checked", true);
	}
';
		
		if (strpos($generic_ajax_javascript, "function onInsert(") === false && ($is_single_insert_ajax || $is_multiple_insert_update_ajax || $is_multiple_insert_ajax))
			$code .= '
if (typeof onInsert != "function")
	function onInsert(elm, data, status, field_name_prefix) {
		if (status) {
			var fields = $(elm).parent().closest("tr, li, form").find(".field"); //it could be a td or a sub-div inside of li. Do not add .children(".field") bc the .field items inside of the li are not directly children.
			
			if (fields.length > 0) {
				//preparing PKS
				var pks = $.isNumeric(data) ? data : ($.isPlainObject(data) && $.isPlainObject(data[field_name_prefix]) ? data[field_name_prefix]["data"] : null);
				
				if ($.isNumeric(pks))
					$.each(fields.filter(".is-pk-auto-increment").children("input, textarea, select, .field-value"), function (idx, field) {
						field = $(field);
						
						if (field.is("input, textarea, select"))
							field.val(pks);
						else
							field.html(pks);
					});
				else if ($.isPlainObject(pks)) //update all the other fields including the other pks and fields starting by "orig_" or "old_"
					for (var attr_name in pks) {
						var pk_value = pks[attr_name];
						
						$.each(fields.filter("." + attr_name).children("input, textarea, select, .field-value"), function (idx, field) {
							field = $(field);
							
							if (field.is("input, textarea, select")) {
								if (field.is("input[type=checkbox], input[type=radio]")) {
									if (field.attr("value") == pk_value)
										field.attr("checked", "checked").prop("checked", true);
									else
										field.removeAttr("checked").prop("checked", false);
								}
								else
									field.val(pk_value);
							}
							else
								field.html(pk_value);
						});
					}
			}
		}
	}
';
		
		if (strpos($generic_ajax_javascript, "function prepareAfterInsertHtml(") === false && ($is_single_insert_ajax || $is_multiple_insert_update_ajax || $is_multiple_insert_ajax))
			$code .= '
if (typeof prepareAfterInsertHtml != "function") 
	function prepareAfterInsertHtml(elm, data, status, field_name_prefix, after_insert_html) {
		if (status) {
			elm = $(elm);
			var before_insert_parent = elm.parent().closest("tr, li, .multiple-form").first();
			var before_insert_fields = before_insert_parent.find(".field"); //it could be a td or a sub-div inside of li. Do not add .children(".field") bc the .field items inside of the li are not directly children.
			
			var row_index = getItemRowIndex(before_insert_parent, field_name_prefix);
			
			if (!row_index && !before_insert_parent.hasClass("multiple-form"))
				alert("Wrong row index in prepareAfterInsertHtml function. Please check this function!");
			
			var new_item = after_insert_html;
			new_item = new_item.replaceAll(field_name_prefix + "[]", field_name_prefix + "[" + row_index + "]");
			new_item = new_item.replaceAll("&lt;script", "<script").replaceAll("&lt;/script", "</script");
			new_item = new_item.replaceAll("multiple_selection[" + field_name_prefix + "][]", "multiple_selection[" + field_name_prefix + "][" + row_index + "]"); //in case of multiple insert in tables
			new_item = $(new_item);
			
			//preparing select fields with correspondent options
			prepareSelectFieldsWithJavascriptOptions(new_item);
			
			var after_insert_fields = new_item.find(".field"); //it could be a td or a sub-div inside of li. Do not add .children(".field") bc the .field items inside of the li are not directly children.
			
			$.each(before_insert_fields, function (idx, before_insert_field) {
				before_insert_field = $(before_insert_field);
				var after_insert_field = $(after_insert_fields[idx])
				
				//preparing update/view fields with values
				if (!before_insert_field.hasClass("actions") && !before_insert_field.hasClass("link")) {
					var before_fields = before_insert_field.children("input, textarea, select, .field-value");
					var after_fields = after_insert_field.children("input, textarea, select, .field-value");
					
					$.each(before_fields, function(idy, item) {
						item = $(item);
						var name = item.attr("name");
						var value = item.val();
						
						if (after_fields.filter("[name=\'" + name + "\']").length > 0)
							$.each(after_fields.filter("[name=\'" + name + "\']"), function (idw, field) {
								field = $(field);
								
								if (field.is("input[type=checkbox], input[type=radio]")) {
									if (field.attr("value") == value)
										field.attr("checked", "checked").prop("checked", true);
									else
										field.removeAttr("checked").prop("checked", false);
								}
								else
									field.val(value);
							});
						else if (item.hasClass("field-value") && after_fields.filter(".field-value").length > 0)
							$.each(after_fields.filter(".field-value"), function (idw, field) {
								$(field).html(value);
							});
						else
							$.each(after_fields, function (idw, field) {
								field = $(field);
								
								if (field.is("input, textarea, select")) {
									if (field.is("input[type=checkbox], input[type=radio]")) {
										if (field.attr("value") == value)
											field.attr("checked", "checked").prop("checked", true);
										else
											field.removeAttr("checked").prop("checked", false);
									}
									else
										field.val(value);
								}
								else
									field.html(value);
							});
					});
				}
			});
			
			onInsert(after_insert_fields[0], data, status, field_name_prefix);
			
			//preparing links if exist
			new_item.find(".actions, .link").find("a").each(function (idx, a) { //it could be a td or a sub-div inside of li. Do not add .children(".actions, .link") bc the .actions/.link items inside of the li are not directly children.
				var a_attrs_to_check = ["href", "data-query-string"];
				
				for (var k in a_attrs_to_check) {
					var attr_name_to_check = a_attrs_to_check[k];
					
					if (a.hasAttribute(attr_name_to_check)) {
						var attr_value_to_check = a.getAttribute(attr_name_to_check);
						var matches = ("" + attr_value_to_check).match(/#([\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC \-\+\.]+)#/g); //Do not use /#([\p{L}\w \-\+\.]+)#/gu bc it does not work in IE.
						
						if (matches) {
							for (var i = 0; i < matches.length; i++) {
								var m = matches[i];
								var attr_name = m.substr(1, m.length - 2);
								var field = after_insert_fields.filter("." + attr_name).children("input, textarea, select, .field-value").first(); //Note that the PK-Auto-Incremented fields must have hidden inputs, so we can get the inserted values
								var value = "";
								
								if (field[0]) {
									if (field.is("input, textarea, select"))
										value = field.val();
									else
										value = field.html();
								}
								
								attr_value_to_check = attr_value_to_check.replace(m, value);
							}
							
							a.setAttribute(attr_name_to_check, attr_value_to_check);
						}
					}
				}
			});
			
			//adding new element and delete insert element
			new_item.insertAfter(before_insert_parent);
			before_insert_parent.remove();
			
			if (typeof onNewHtml == "function")
				onNewHtml(elm, new_item);
		}
	}
';
		
		if (strpos($generic_ajax_javascript, "function onSingleInsert(") === false && $is_single_insert_ajax && $panel_type != "multiple_form")
			$code .= '
if (typeof onSingleInsert != "function") 
	function onSingleInsert(elm, data, status, field_name_prefix) {
		if (status && data) {
			elm = $(elm);
			var lc = elm.parent().closest(".list-items").children(".list-container");
			var p = lc.children("ul.list-tree, table.list-table");
			
			if (!p[0]) //in case of DataTables runs, it will copy the table to another children
				p = elm.parent().closest("ul.list-tree, table.list-table"); 
			
			var type = p.hasClass("list-tree") ? "tree" : "table";
			var table_name = elm.attr("data-table-name");
			var panel_id_hash = elm.attr("data-panel-id-hash");
			eval("var after_insert_html = " + table_name + "_" + type + "_" + panel_id_hash + "_after_insert_html;");
			
			return prepareAfterInsertHtml(elm, data, status, field_name_prefix, after_insert_html);
		}
	}
';
		
		if (strpos($generic_ajax_javascript, "function onMultipleFormSingleInsert(") === false && $is_single_insert_ajax && $panel_type == "multiple_form")
			$code .= '
if (typeof onMultipleFormSingleInsert != "function") 
	function onMultipleFormSingleInsert(elm, data, status, field_name_prefix) {
		if (status && data) {
			elm = $(elm);
			var type = "multiple_form";
			var table_name = elm.attr("data-table-name");
			var panel_id_hash = elm.attr("data-panel-id-hash");
			eval("var after_insert_html = " + table_name + "_" + type + "_" + panel_id_hash + "_after_insert_html;");
			
			return prepareAfterInsertHtml(elm, data, status, field_name_prefix, after_insert_html);
		}
	}
';
		
		if (strpos($generic_ajax_javascript, "function onMultipleInsert(") === false && $is_multiple_insert_ajax)
			$code .= '
if (typeof onMultipleInsert != "function") 
	function onMultipleInsert(elm, data, status, field_name_prefix) {
		if (status && data)
			if ($.isPlainObject(data) && data[field_name_prefix] && $.isPlainObject(data[field_name_prefix]) && data[field_name_prefix]["data"] && ($.isPlainObject(data[field_name_prefix]["data"]) || $.isArray(data[field_name_prefix]["data"]))) {
				elm = $(elm);
				var lc = elm.parent().closest(".list-items").children(".list-container");
				var p = lc.children("ul.list-tree, table.list-table");
				
				if (!p[0]) //in case of DataTables runs, it will copy the table to another children
					p = elm.parent().closest("ul.list-tree, table.list-table"); 
				
				var type = p.hasClass("list-tree") ? "tree" : "table";
				var table_name = elm.attr("data-table-name");
				var panel_id_hash = elm.attr("data-panel-id-hash");
				eval("var after_insert_html = " + table_name + "_" + type + "_" + panel_id_hash + "_after_insert_html;");
				
				$.each(data[field_name_prefix]["data"], function(row_index, item) {
					if ($.isNumeric(row_index)) {
						var p = elm.parent().closest("form");
						var row = getRowIndexElement(p, field_name_prefix, row_index);
						
						if (row) {
							var item_data = {};
							item_data[field_name_prefix] = {"data": item};
							prepareAfterInsertHtml(row.children().first(), item_data, true, field_name_prefix, after_insert_html);
						}
					}
				});
			}
	}
';
		
		if (strpos($generic_ajax_javascript, "function onUpdate(") === false && ($is_single_update_ajax || $is_multiple_update_ajax || $is_multiple_insert_update_ajax))
			$code .= '
if (typeof onUpdate != "function")
	function onUpdate(elm, data, status, field_name_prefix) {
		if (status) {
			var pks_elms = $(elm).parent().closest("tr, li, form").find(".is-pk");
			
			$.each(pks_elms, function (idx, td) {
				var fields = $(td).find("input, textarea, select");
				var orig_fields = [];
				var pk_value = null;
				
				$.each(fields, function (idx, field) {
					if (field.name.substr(0, 5) == "orig_")
						orig_fields.push(field);
					else
						pk_value = $(field).val();
				});
				
				if (pk_value != null && ("" + pk_value).length > 0)
					$.each(orig_fields, function (idx, field) {
						$(field).val(pk_value);
					});
			});
		}
	}
';
		if (strpos($generic_ajax_javascript, "function onMultipleUpdate(") === false && $is_multiple_update_ajax)
			$code .= '
if (typeof onMultipleUpdate != "function")
	function onMultipleUpdate(elm, data, status, field_name_prefix) {
		if (status && data) {
			elm = $(elm);
			
			if ($.isPlainObject(data) && data[field_name_prefix] && $.isPlainObject(data[field_name_prefix]) && data[field_name_prefix]["data"] && ($.isPlainObject(data[field_name_prefix]["data"]) || $.isArray(data[field_name_prefix]["data"])))
				$.each(data[field_name_prefix]["data"], function(row_index, item) {
					if ($.isNumeric(row_index)) {
						var p = elm.parent().closest("form");
						var row = getRowIndexElement(p, field_name_prefix, row_index);
						
						if (row) {
							var item_data = {};
							item_data[field_name_prefix] = {"data": item};
							onUpdate(row.children().first(), item_data, true, field_name_prefix);
						}
					}
				});
		}
	}
';
		
		if (strpos($generic_ajax_javascript, "function onMultipleInsertUpdate(") === false && $is_multiple_insert_update_ajax)
			$code .= '
if (typeof onMultipleInsertUpdate != "function") 
	function onMultipleInsertUpdate(elm, data, status, field_name_prefix) {
		if (status && data)
			if ($.isPlainObject(data) && data[field_name_prefix] && $.isPlainObject(data[field_name_prefix]) && data[field_name_prefix]["data"] && ($.isPlainObject(data[field_name_prefix]["data"]) || $.isArray(data[field_name_prefix]["data"]))) {
				elm = $(elm);
				var lc = elm.parent().closest(".list-items").children(".list-container");
				var p = lc.children("ul.list-tree, table.list-table");
				
				if (!p[0]) //in case of DataTables runs, it will copy the table to another children
					p = elm.parent().closest("ul.list-tree, table.list-table"); 
				
				var type = p.hasClass("list-tree") ? "tree" : "table";
				var table_name = elm.attr("data-table-name");
				var panel_id_hash = elm.attr("data-panel-id-hash");
				eval("var after_insert_html = " + table_name + "_" + type + "_" + panel_id_hash + "_after_insert_html;");
				
				$.each(data[field_name_prefix]["data"], function(row_index, item) {
					if ($.isNumeric(row_index)) {
						var p = elm.parent().closest("form");
						var row = getRowIndexElement(p, field_name_prefix, row_index);
						
						if (row) {
							var item_data = {};
							item_data[field_name_prefix] = {"data": item};
							
							//call correspondent function according if item is a new record or existent one.
							if (row.attr("data-is-insert-record") == 1)
								prepareAfterInsertHtml(row.children().first(), item_data, true, field_name_prefix, after_insert_html);
							else
								onUpdate(row.children().first(), item_data, true, field_name_prefix);
						}
					}
				});
			}
	}
';
		
		if (strpos($generic_ajax_javascript, "function onDelete(") === false && ($is_single_delete_ajax || $is_multiple_delete_ajax))
			$code .= '
if (typeof onDelete != "function")
	function onDelete(elm, data, status, field_name_prefix) {
		if (status)
			$(elm).parent().closest("tr, li, form").remove();
	}
';
		
		if (strpos($generic_ajax_javascript, "function onMultipleDelete(") === false && $is_multiple_delete_ajax)
			$code .= '
if (typeof onMultipleDelete != "function")
	function onMultipleDelete(elm, data, status, field_name_prefix) {
		if (status && data) {
			elm = $(elm);
			
			if ($.isPlainObject(data) && data[field_name_prefix] && $.isPlainObject(data[field_name_prefix]) && data[field_name_prefix]["data"] && ($.isPlainObject(data[field_name_prefix]["data"]) || $.isArray(data[field_name_prefix]["data"])))
				$.each(data[field_name_prefix]["data"], function(row_index, item) {
					if ($.isNumeric(row_index)) {
						var p = elm.parent().closest("form");
						var row = getRowIndexElement(p, field_name_prefix, row_index);
						
						if (row) {
							var item_data = {};
							item_data[field_name_prefix] = {"data": item};
							onDelete(row.children().first(), item_data, true, field_name_prefix);
						}
					}
				});
		}
	}
';
		
		if (strpos($generic_ajax_javascript, "function addNewItem(") === false && ($panel_type == "list_table" || $panel_type == "list_form") && (
			!empty($actions_props["single_insert"]) || 
			!empty($actions_props["multiple_insert_update"]) || 
			!empty($actions_props["multiple_insert"])
		))
			$code .= '
if (typeof addNewItem != "function") 
	function addNewItem(elm) {
		elm = $(elm);
		var lc = elm.parent().closest(".list-items").children(".list-container");
		var p = lc.children("ul.list-tree, table.list-table");
		
		if (!p[0]) //in case of DataTables runs, it will copy the table to another children
			p = elm.parent().closest("ul.list-tree, table.list-table"); 
		
		var is_tree = p.hasClass("list-tree");
		var tbody_or_ul = is_tree ? p : p.children("tbody");
		
		if (!tbody_or_ul[0]) {
			alert("Error: couldn\'t find parent element to add new item. Please check your javascript function: addNewItem!");
			return;
		}
		
		//preparing fields name with the correct row index
		var table_name = elm.attr("data-table-name");
		var panel_id_hash = elm.attr("data-panel-id-hash");
		var row_index = getNextRowIndex(tbody_or_ul, table_name);
		var type = p.hasClass("list-tree") ? "tree" : "table";
		eval("var new_item = " + table_name + "_" + type + "_" + panel_id_hash + "_before_insert_html;");
		new_item = new_item.replaceAll(table_name + "[]", table_name + "[" + row_index + "]");
		new_item = new_item.replaceAll("&lt;script", "<script").replaceAll("&lt;/script", "</script");
		
		//in case of multiple insert in tables
		new_item = new_item.replaceAll("multiple_selection[" + table_name + "][]", "multiple_selection[" + table_name + "][" + row_index + "]");
		new_item = new_item.replaceAll("multiple_insert_selection[" + table_name + "][]", "multiple_insert_selection[" + table_name + "][" + row_index + "]");
		
		new_item = $(new_item);
		
		//adding new row to table
		tbody_or_ul.append(new_item);
    
		//preparing select fields with correspondent options
		prepareSelectFieldsWithJavascriptOptions(new_item);
		
		//populate the input fields with the values from the query string. When is a new item, populate the fields with the _GET[attr_name]
		prepareNewFieldsWithQueryStringValues(table_name, new_item);
		
		if (typeof onNewHtml == "function")
			onNewHtml(elm, new_item);
	}
';
		
		if (strpos($generic_ajax_javascript, "function addMultipleFormNewItem(") === false && $panel_type == "multiple_form" && !empty($actions_props["single_insert"]))
			$code .= '
if (typeof addMultipleFormNewItem != "function") 
	function addMultipleFormNewItem(elm) {
		elm = $(elm);
		var add_button_container = $(elm).parent().closest(".add-button");
		
		//preparing fields name with the correct row index
		var table_name = elm.attr("data-table-name");
		var panel_id_hash = elm.attr("data-panel-id-hash");
		var type = "multiple_form";
		eval("var new_item = " + table_name + "_" + type + "_" + panel_id_hash + "_before_insert_html;");
		new_item = new_item.replaceAll("&lt;script", "<script").replaceAll("&lt;/script", "</script");
		
		new_item = $(new_item);
		
		var close_button = $(\'<div class="button button-close"><input type="button" value="Close"></div>\');
		close_button.click(function() {
			new_item.remove();
		});
		new_item.find(" > .multiple-form > .buttons").append(close_button);
		
		//adding new row to table
		new_item.insertAfter(add_button_container);
		
		//preparing select fields with correspondent options
		prepareSelectFieldsWithJavascriptOptions(new_item);
		
		//populate the input fields with the values from the query string. When is a new item, populate the fields with the _GET[attr_name]
		prepareNewFieldsWithQueryStringValues(table_name, new_item);
		
		if (typeof onNewHtml == "function")
			onNewHtml(elm, new_item);
	}
';
		
		if (strpos($generic_ajax_javascript, "function prepareNewFieldsWithQueryStringValues(") === false && ($panel_type == "list_table" || $panel_type == "list_form" || $panel_type == "multiple_form") && (
			!empty($actions_props["single_insert"]) || 
			!empty($actions_props["multiple_insert_update"]) || 
			!empty($actions_props["multiple_insert"])
		))
			$code .= '
if (typeof prepareNewFieldsWithQueryStringValues != "function") 
	function prepareNewFieldsWithQueryStringValues(table_name, new_item) {
		//populate the input fields with the values from the query string. When is a new item, populate the fields with the _GET[attr_name]
		var embeded_container = new_item.parent().closest(".embeded-inner-task");
		var default_url = embeded_container.attr("data-url"); //contains the querystring with the default values to be set
		var inputs = new_item.find(\'input, textarea, select\');
		
		$.each(inputs, function(idx, input) {
			input = $(input);
			var name = "" + input.attr("name");
			
			if (name.substr(0, table_name.length + 1) == table_name + "[" && name.substr(name.length - 1) == "]") {
				name = name.substr(table_name.length, name.length - table_name.length); //[attr_name] or [idx][attr_name]
				
				//for forms
				var match = name.match(/^\[([\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC \-\+\.]+)\]$/); //\'\\w\' means all words with \'_\' and \'u\' means with accents and ç too. Do not use /^\[([\p{L}\w \-\+\.]+)\]$/u bc it does not work in IE.
				
				//for lists
				if (!match) 
					match = name.match(/^\[[0-9]+\]\[([\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC \-\+\.]+)\]$/); //\'\\w\' means all words with \'_\' and \'u\' means with accents and ç too. Do not use /^\[([\p{L}\w \-\+\.]+)\]$/u bc it does not work in IE.
				
				var attr_name = match ? match[1] : null;
				
				if (attr_name) {
					var attr_value = getQueryStringParameterByName(attr_name, default_url);
					
					if (attr_value != null) { //if null it means does not exists in the querystring
						if (input.is("input[type=checkbox], input[type=radio]")) {
							if (input.attr("value") == attr_value)
								input.attr("checked", "checked").prop("checked", true);
							else
								input.removeAttr("checked").prop("checked", false);
						}
						else
							input.val(attr_value);
						
						var p = input.parent();
						var fv = p.children(\'.field-value\');
						fv.html(attr_value);
						
						//check if parent has class: "is-pk-auto-increment" and if yes, adds a reset button to remove the pk pre-loaded value, in case the user prefers to use the auto-increment feature, instead of the pre-loaded value.
						if (p.hasClass("is-pk-auto-increment")) {
							var r = p.children(".reset");
							
							if (!r[0]) {
								r = $(\'<span class="icon reset" title="Reset this value to default">&times;</span>\');
								p.append(r);
								
								r.click(function() {
									input.val("");
									fv.html("");
									r.remove();
								});
							}
						}
						else
							p.children(".reset").remove();
					}
				}
			}
		});
	}
';
		
		if (strpos($generic_ajax_javascript, "function getQueryStringParameterByName(") === false && ($panel_type == "list_table" || $panel_type == "list_form" || $panel_type == "multiple_form") && (
			!empty($actions_props["single_insert"]) || 
			!empty($actions_props["multiple_insert_update"]) || 
			!empty($actions_props["multiple_insert"])
		))
			$code .= '
if (typeof getQueryStringParameterByName != "function") 
	function getQueryStringParameterByName(name, url) {
		if (!url) 
			url = window.location.href;
		
		name = name.replace(/[\\[]/, "\\\\[").replace(/[\\]]/, "\\\\]");
		var regex = new RegExp("[\\?&]" + name + "(=([^&#]*)|&|#|$)");
		var results = regex.exec(url);
		
		if (!results)
			return null;
		
		if (!results[2])
			return "";
		
		return decodeURIComponent(results[2].replace(/\\+/g, " "));
	}
';
		
		if (strpos($generic_ajax_javascript, "function prepareSelectFieldsWithJavascriptOptions(") === false && ($panel_type == "list_table" || $panel_type == "list_form" || $panel_type == "multiple_form") && (
			!empty($actions_props["single_insert"]) || 
			!empty($actions_props["multiple_insert_update"]) || 
			!empty($actions_props["multiple_insert"])
		))
			$code .= '
if (typeof prepareSelectFieldsWithJavascriptOptions != "function") 
	function prepareSelectFieldsWithJavascriptOptions(item) {
		//preparing select fields with correspondent options
		var selects = item.find("select");

		$.each(selects, function(idx, select) {
			select = $(select);
			var options = select.attr("data-options-javascript-variable-name");
			
			if (options) {
				try {
					eval("options = " + options + ";");
				} catch(e) {}
				
				if (options) {
					var html = "<option></option>";
					
					if ($.isPlainObject(options) || $.isArray(options))
						$.each(options, function(option_id, option_label) {
							html += \'<option value="\' + option_id + \'">\' + option_label + \'</option>\';
						});
					
					select.html(html);
				}
			}
		});
	}
';
		
		if (strpos($generic_ajax_javascript, "function executeSingleAction(") === false && ($is_single_insert_ajax || $is_single_update_ajax || $is_single_delete_ajax))
			$code .= '
if (typeof executeSingleAction != "function")
	function executeSingleAction(elm, success_func) {
		elm = $(elm);
		var confirmation = elm.attr("data-confirmation");
		var confirmation_message = elm.attr("data-confirmation-message");
		
		if (!confirmation || confirm(confirmation_message)) {
			var url = elm.attr("data-url");
			var form_obj = elm.parent().closest("tr, li, form");
			var fields = form_obj.find("input, textarea, select");
			var status = typeof MyJSLib == \'undefined\' || MyJSLib.FormHandler.formElementsCheck( fields.toArray() );
			
			if (status) {
				if (url) {
					var form_data = {};
					var query_string = fields.serialize();
					parse_str(query_string, form_data);
					
					var table_name = elm.attr("data-table-name");
					var button_type = elm.attr("data-button-type");
					var has_permission_var_name = "has_" + (button_type == "delete" ? "delete" : "write") + "_permission";
					var ok_msg_message = elm.attr("data-ok-msg-message");
					var ok_msg_redirect_url = elm.attr("data-ok-msg-redirect-url");
					var error_msg_message = elm.attr("data-error-msg-message");
					var error_msg_redirect_url = elm.attr("data-error-msg-redirect-url");
					
					//bc the fields name inside of the TR/LI elements contain the row index (this is table_name[index]), we need to remove it and pass directly the selected item data.
					if (form_obj.is("tr, li") && form_data[table_name])
						for (var row_index in form_data[table_name]) {
							form_data[table_name] = form_data[table_name][row_index];
							break;
						}
					
					if (elm.is("input, button"))
						form_data[ elm.attr("name") ] = elm.val();
					else
						form_data[ elm.attr("data-button-name") ] = 1;
					
					//console.log(form_data);
					
					$.ajax({
						type: "POST",
						url: url,
						data: form_data,
						dataType: "json",
						success: function(data) {
							var status = false;
							var status_message = null;
							var has_permission = true;

							if ($.isPlainObject(data) && data) {
								status = data["status"];
								status_message = data["message"];

								if (data.hasOwnProperty(table_name) && data[table_name]) {
									var tn = table_name.toLowerCase();
									
									if (data[table_name].hasOwnProperty(table_name + "_status"))
										status = data[table_name][table_name + "_status"];
									else if ($.isPlainObject(data[table_name]["data"]) && data[table_name]["data"].hasOwnProperty(tn + "_status"))
										status = data[table_name]["data"][table_name + "_status"];
									else if (data[table_name].hasOwnProperty("status"))
										status = data[table_name]["status"];
									
									var msg = data[table_name].hasOwnProperty(table_name + "_status_message") ? data[table_name][table_name + "_status_message"] : data[table_name]["status_message"];
									status_message = msg ? msg : status_message;
									
									if (data[table_name].hasOwnProperty(has_permission_var_name))
										has_permission = data[table_name][has_permission_var_name];
								}
							}
							else if ($.isNumeric(data) && data > 0) 
								status = true;

							if (success_func && typeof success_func == "function")
								success_func(elm[0], data, status, table_name);

							if (status) {
								if (ok_msg_message)
									status_message = ok_msg_message + (status_message ? " " + status_message : "");
								
								if (status_message)
									alert(status_message);
								
								if (ok_msg_redirect_url)
									document.location = getRedirectUrlWithPks(ok_msg_redirect_url, fields, true);
							}
							else {
								if (error_msg_message)
									status_message = error_msg_message + (status_message ? " " + status_message : "");

								if (!has_permission)
									alert("You do not have permission to " + (button_type == "insert_update" ? "update" : button_type) + " this " + table_name + "!");
								else if (status_message)
									alert(status_message);
								
								if (error_msg_redirect_url)
									document.location = getRedirectUrlWithPks(error_msg_redirect_url, fields, false);
							}
						},
						error: function() {
							if (error_msg_message)
								alert(error_msg_message);
							
							if (error_msg_redirect_url)
								document.location = getRedirectUrlWithPks(error_msg_redirect_url, fields, false);
						}
					});
				}
				else
					alert("Error: URL cannot be undefined in the executeSingleAction function!");
			}
		}
	}
';
		
		if (strpos($generic_ajax_javascript, "function executeMultipleActions(") === false && ($is_multiple_insert_ajax || $is_multiple_update_ajax || $is_multiple_delete_ajax))
			$code .= '
if (typeof executeMultipleActions != "function")
	function executeMultipleActions(elm, success_func) {
		elm = $(elm);
		var confirmation = elm.attr("data-confirmation");
		var confirmation_message = elm.attr("data-confirmation-message");
		
		if (!confirmation || confirm(confirmation_message)) {
			var table_name = elm.attr("data-table-name");
			var button_type = elm.attr("data-button-type");
			var has_permission_var_name = "has_" + (button_type == "delete" ? "delete" : "write") + "_permission";
			var ok_msg_message = elm.attr("data-ok-msg-message");
			var ok_msg_redirect_url = elm.attr("data-ok-msg-redirect-url");
			var error_msg_message = elm.attr("data-error-msg-message");
			var error_msg_redirect_url = elm.attr("data-error-msg-redirect-url");
			
			var url = elm.attr("data-url");
			var form_obj = elm.parent().closest("form");
			
			//get the fields that have the multiple-selection checkbox active
			var fields = [];
			var multiple_selection_inputs = form_obj.find(".multiple-selection input");
			$.each(multiple_selection_inputs, function(idx, input) {
				input = $(input);
				var push_to_fields = input.is(":checked");
				
				if (push_to_fields && (button_type == "update" || button_type == "delete"))
					push_to_fields = input.attr("name").indexOf("multiple_selection[") == 0;
				
				if (push_to_fields) {
					var item_fields = input.parent().closest("li, tr").find("input, textarea, select").toArray();

					$.each(item_fields, function(idy, field) {
						if ($.inArray(field, fields) == -1)
							fields.push(field);
					});
				}
			});
			
			if (fields.length == 0) {
				alert("Please select some items first!");
				return;
			}
			
			var status = typeof MyJSLib == \'undefined\' || MyJSLib.FormHandler.formElementsCheck(fields);
			fields = $(fields);
			
			if (status) {
				if (url) {
					var form_data = {};
					var query_string = fields.serialize();
					parse_str(query_string, form_data);
					
					if (elm.is("input, button"))
						form_data[ elm.attr("name") ] = elm.val();
					else
						form_data[ elm.attr("data-button-name") ] = 1;
					
					//console.log(form_data);
					
					$.ajax({
						type: "POST",
						url: url,
						data: form_data,
						dataType: "json",
						success: function(data) {
							var status = false;
							var status_message = null;
							var has_permission = true;

							if ($.isPlainObject(data) && data) {
								status = data["status"];
								status_message = data["message"];
								
								if (data.hasOwnProperty(table_name + "_status_message"))
									status_message = data[table_name + "_status_message"];
								
								if (data.hasOwnProperty(table_name) && data[table_name] && ($.isPlainObject(data[table_name]) || $.isArray(data[table_name]))) {
									if (data[table_name].hasOwnProperty(table_name + "_status") || data[table_name].hasOwnProperty("status")) {
										status = data[table_name].hasOwnProperty(table_name + "_status") ? data[table_name][table_name + "_status"] : data[table_name]["status"];
										
										var msg = data[table_name].hasOwnProperty(table_name + "_status_message") ? data[table_name][table_name + "_status_message"] : data[table_name]["status_message"];
										status_message = msg ? msg : status_message;
										
										if (data[table_name].hasOwnProperty(has_permission_var_name))
											has_permission = data[table_name][has_permission_var_name];
									}
									else if ($.isPlainObject(data[table_name]["data"]) || $.isArray(data[table_name]["data"])) {
										status = true;
										
										$.each(data[table_name]["data"], function(row_index, item) {
											if ($.isNumeric(row_index)) {
												if (!item)
													status = false;
												else if ($.isPlainObject(item) && item.hasOwnProperty(table_name + "_status") && !item[table_name + "_status"])
													status = false;
											}
										});
									}
								}
							}
							else if ($.isNumeric(data) && data == 1) 
								status = true;

							if (success_func && typeof success_func == "function")
								success_func(elm[0], data, status, table_name);

							if (status) {
								if (ok_msg_message)
									status_message = ok_msg_message + (status_message ? " " + status_message : "");

								if (status_message)
									alert(status_message);
									
								if (ok_msg_redirect_url)
									document.location = getRedirectUrlWithPks(ok_msg_redirect_url, fields, true);
							}
							else {
								if (error_msg_message)
									status_message = error_msg_message + (status_message ? " " + status_message : "");
								
								if (!has_permission)
									alert("You do not have permission to " + (button_type == "insert_update" ? "update" : button_type) + " this " + table_name + "!");
								else if (status_message)
									alert(status_message);
								
								if (error_msg_redirect_url)
									document.location = getRedirectUrlWithPks(error_msg_redirect_url, fields, false);
							}
						},
						error: function() {
							if (error_msg_message)
								alert(error_msg_message);
							
							if (error_msg_redirect_url)
								document.location = getRedirectUrlWithPks(error_msg_redirect_url, fields, false);
						}
					});
				}
				else
					alert("Error: URL cannot be undefined in the executeMultipleActions function!");
			}
		}
	}
';
		
		return $code;
	}
	
	//Note that everytime I cahnged this function, I must do the same changes in the __system/layer/presentation/phpframework/webroot/lib/jquerylayoutuieditor/js/LayoutUIEditorWidgetResource.js:getWidgetItemAttributeFieldAttributesHtml method
	private static function getFormFieldInputAttrs($tn, $attr_props, &$input_type, $options = false) {
		self::prepareFormInputParameters($attr_props, $input_type, $allow_null, $validation_type, $validation_message, $validation_label, $place_holder, $extra_attributes, $max_length);
		
		$input_attrs = ' data-allow-null="' . ($allow_null ? 1 : 0) . '"';
		if ($validation_type)
			$input_attrs .= ' data-validation-type="' . $validation_type . '"';
		
		if ($validation_message)
			$input_attrs .= ' data-validation-message="' . addcslashes($validation_message, '"') . '"';
		
		if ($validation_label)
			$input_attrs .= ' data-validation-label="' . addcslashes($validation_label, '"') . '"';
		
		if ($place_holder)
			$input_attrs .= ' placeHolder="' . addcslashes($place_holder, '"') . '"';
		
		if ($max_length)
			$input_attrs .= ' maxLength="' . $max_length . '"';
		
		if ($extra_attributes)
			foreach ($extra_attributes as $k => $v)
				$input_attrs .= ' ' . $k . '="' . addcslashes($v, '"') . '"';
		
		if (!empty($options["ajax_on_blur"]))
			$input_attrs .= ' onBlur="update' . str_replace(" ", "", self::getName($tn)) . '(this, onUpdate)"';
		
		if (!empty($options["multiple_key_press"]))
			$input_attrs .= " " . ($input_type == "select" || $input_type == "checkbox" || $input_type == "radio" ? "onChange" : "onKeyDown") . '="onMultipleUpdateKeyDown(this)"';
		
		return $input_attrs;
	}

	//Note that everytime I cahnged this function, I must do the same changes in the __system/layer/presentation/phpframework/webroot/lib/jquerylayoutuieditor/js/LayoutUIEditorWidgetResource.js:getWidgetItemAttributeFieldParameters method
	//used by the SequentialLogicalActivityResourceCreator::getInsertActionPreviousCode
	public static function prepareFormInputParameters($attr, &$input_type = null, &$allow_null = null, &$validation_type = null, &$validation_message = null, &$validation_label = null, &$place_holder = null, &$extra_attributes = null, &$max_length = null) {
		$input_type = $validation_type = $validation_message = $place_holder = null;
		
		$allow_null = !isset($attr["null"]) || $attr["null"] ? "1" : "0";
		$max_length = self::getFormInputMaxLength($attr);
		
		$attr_name = isset($attr["name"]) ? $attr["name"] : null;
		$attr_type = isset($attr["type"]) ? $attr["type"] : null;
		$attr_length = isset($attr["length"]) ? $attr["length"] : null;
		
		$label = self::getName($attr_name);
		$validation_label = $label;
		
		switch ($attr_type) {
			case "int":
				$validation_type = "int";
				$validation_message = "'$label' field is not a valid integer number.";
				$input_type = "number";
				break;
			case "bigint":
				$validation_type = "bigint";
				$validation_message = "'$label' field is not a valid big integer number.";
				$input_type = "number";
				break;
			case "decimal":
				$validation_type = "decimal";
				$validation_message = "'$label' field is not a valid decimal number.";
				$input_type = "number";
				$extra_attributes = array("step" => "any");
				break;
			case "double":
				$validation_type = "double";
				$validation_message = "'$label' field is not a valid double number.";
				$input_type = "number";
				$extra_attributes = array("step" => "0.00000000000001");
				break;
			case "float":
				$validation_type = "float";
				$validation_message = "'$label' field is not a valid float number.";
				$input_type = "number";
				$extra_attributes = array("step" => "0.0000001");
				break;
			case "smallint":
				if ($attr_length == 1)
					$input_type = "checkbox";
				else {
					$validation_type = "smallint";
					$validation_message = "'$label' field is not a valid small integer number.";
					$input_type = "number";
				}
				break;
				
			case "bit":
			case "boolean":
				$input_type = "checkbox";
				break;
			case "tinyint":
				if ($attr_length == 1)
					$input_type = "checkbox";
				break;
			
			case "mediumtext":
			case "text":
			case "longtext":
			case "blob":
			case "longblob":
				$input_type = "textarea";
				break;
			
			case "date":
				$place_holder = "yyyy-mm-dd";
				$validation_type = "date";
				$validation_message = "'$label' field is not a valid date. Please respect this format: $place_holder";
				$input_type = "date";
				break;
			case "datetime":
			case "datetime2":
			case "datetimeoffset":
			case "smalldatetime":
			case "timestamp":
			case "timestamp without time zone":
				$place_holder = "yyyy-mm-dd hh:ii:ss";
				$validation_type = "datetime";
				$validation_message = "'$label' field is not a valid date. Please respect this format: $place_holder";
				$input_type = "datetime";
				break;
			case "time":
			case "time without time zone":
				$place_holder = "hh:ii:ss";
				$validation_type = "time";
				$validation_message = "'$label' field is not a valid time. Please respect this format: $place_holder";
				$input_type = "time";
				break;
		}
		
		//If there is a form or an editable list with a checkbox with a numeric value, then allows the checkbox to be null, bc the logic code then will add a default value or null, to the attribute correspondent to this checkbox.
		if ($allow_null == "0" && ($input_type == "checkbox" || $input_type == "radio") && (ObjTypeHandler::isDBTypeNumeric($attr_type) || ObjTypeHandler::isPHPTypeNumeric($attr_type)))
			$allow_null = "1";
		
		if (!$input_type && stripos($attr_type, "char") !== false && $attr_length > 255)
			$input_type = "textarea";
		
		if (ObjTypeHandler::isDBTypeNumeric($attr_type) || ObjTypeHandler::isPHPTypeNumeric($attr_type)) {
			$validation_type = $validation_type ? $validation_type : "number";
			$validation_message = "'$label' field is not a valid number.";
			$input_type = $input_type ? $input_type : "number";
		}
		else if (stripos($attr_name, "email") !== false) {
			$place_holder = "example@email.here";
			$validation_type = $validation_type ? $validation_type : "email";
			$validation_message = "'$label' field is not a valid email.";
			$input_type = $input_type ? $input_type : "email";
		}
		else if (stripos($attr_name, "date") !== false) {
			$place_holder = $place_holder ? $place_holder : "yyyy-mm-dd hh:ii:ss";
			$validation_type = $validation_type ? $validation_type : "datetime";
			$validation_message = "'$label' field is not a valid date. Please respect this format: yyyy-mm-dd hh:ii:ss";
			$input_type = $input_type ? $input_type : "datetime";
		}
		else if (stripos($attr_name, "url") !== false) {
			$input_type = $input_type ? $input_type : "url";
		}
		else if (stripos($attr_name, "phone") !== false) {
			$validation_type = $validation_type ? $validation_type : "phone";
			$validation_message = "'$label' field is not a valid phone.";
			$input_type = $input_type ? $input_type : "tel";
		}
		
		if (!$input_type)
			$input_type = "text";
	}
	
	//Note that everytime I cahnged this function, I must do the same changes in the __system/layer/presentation/phpframework/webroot/lib/jquerylayoutuieditor/js/LayoutUIEditorWidgetResource.js:getWidgetItemAttributeFieldMaxLength method
	private static function getFormInputMaxLength($attr) {
		$type = isset($attr["type"]) ? $attr["type"] : null;
		$length = isset($attr["length"]) ? $attr["length"] : null;
		
		if ($length) {
			$other_text_types = array("date", "datetime", "datetime2", "datetimeoffset", "smalldatetime", "timestamp", "timestamp without time zone", "time", "time without time zone");
			
			if (strpos($type, "char") !== false || strpos($type, "text") !== false || strpos($type, "blob") !== false || in_array($type, $other_text_types))
				return $length;
		}
		
		return null;
	}

	public static function getName($name) {
		return ucwords(strtolower( (str_replace(array("_", "-", "."), " ", trim($name)) )) ); //$table name can have schema
	}
	
	public static function getPlural($name) {
		$last = strtolower(substr($name, -1)); 
		
		return $last == "y" ? substr($name, 0, -1) . "ies" : ($last != "s" ? $name . "s" : $name);
	}
	
	public static function getParsedTableName($name) { //$table name can have schema
		return str_replace(".", "_", $name);
	}
	
	private static function getForeignChildTables($tables, $table_name, $tables_alias, $pks, $with_labels = false) {
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		$childs = array();
		$pks = array_flip($pks);
		$ltn = strtolower($table_name);
		
		foreach ($tables as $t_name => $attrs) 
			if ($ltn != strtolower($t_name)) {
				$pks_aux = $pks;
				
				//check if foreign tables are dependent childs
				foreach ($attrs as $attr_name => $attr)
					if (!empty($attr["fk"]))
						foreach ($attr["fk"] as $fk) {
							$fk_table = isset($fk["table"]) ? $fk["table"] : null;
							$fk_attribute = isset($fk["attribute"]) ? $fk["attribute"] : null;
							
							if (strtolower($fk_table) == $ltn && !empty($pks[$fk_attribute])) {
								unset($pks_aux[$fk_attribute]);
								
								if (!$pks_aux)
									break;
							}
						}
				
				if (!$pks_aux)
					$childs[] = !empty($tables_alias[$t_name]) ? $tables_alias[$t_name] : $t_name;
			}
		
		return $childs;
	}
	
	//If you change this method, please make the same changes inside the method SequentialLogicalActivityResourceCreator::getBrokerSettingsOptionsCode
	private static function getBrokerSettingsOptionsCode($WorkFlowTaskHandler, $broker_settings) {
		if (isset($broker_settings["brokers_layer_type"]))
			switch ($broker_settings["brokers_layer_type"]) {
				case "callbusinesslogic":
				case "callibatisquery":
					if (!empty($broker_settings["options"])) {
						$broker_settings["parameters"] = null;
						$broker_settings["parameters_type"] = "";
					
						$code = self::prepareBrokerCode($WorkFlowTaskHandler, $broker_settings);
						if ($code) {
							$pos = strpos($code, '", null, ');
							$options = substr($code, $pos + 9, -1);
						}
					}
					break;
				case "callhibernatemethod":
					if (!empty($broker_settings["sma_options"])) {
						$code_1 = self::prepareBrokerCode($WorkFlowTaskHandler, $broker_settings);
						
						$broker_settings["sma_options"] = null;
						$broker_settings["sma_options_type"] = "";
						$code_2 = self::prepareBrokerCode($WorkFlowTaskHandler, $broker_settings);
						
						if ($code_1 && $code_2) {
							$code_2 = substr(trim($code_2), 0, -1);//removes last ')'
							$code_1 = substr(trim($code_1), 0, -1);//removes last ')'
							
							$code_1 = trim( str_replace($code_2, "", $code_1) );
							$code_1 = substr($code_1, 0, 1) == "," ? substr($code_1, 1) : $code_1;//removes comma if exists.
							$options = trim($code_1);
						}
					}
					break;
				case "getquerydata":
				case "setquerydata":
					if (!empty($broker_settings["options"])) {
						$broker_settings["sql"] = "test";
						$broker_settings["sql_type"] = "variable";
						
						$code = self::prepareBrokerCode($WorkFlowTaskHandler, $broker_settings);
						if ($code) {
							$pos = strpos($code, '($test, ');
							$options = substr($code, $pos + 8, -1);
						}
					}
					break;
			}
		
		return isset($options) && $options != "null" ? $options : "";
	}
	
	//If you change this method, please make the same changes inside the method SequentialLogicalActivityResourceCreator::prepareBrokerCode
	private static function prepareBrokerCode($WorkFlowTaskHandler, $broker_settings) {
		$brokers_layer_type = isset($broker_settings["brokers_layer_type"]) ? $broker_settings["brokers_layer_type"] : null;
		
		$task = $WorkFlowTaskHandler->getTasksByTag($brokers_layer_type);
		$task = isset($task[0]) ? $task[0] : null;
		
		if ($task) {
			$task["properties"] = $broker_settings;
			$task["obj"]->data = $task;
			$code = trim( $task["obj"]->printCode(null, null) );
			$code = substr($code, -1) == ";" ? substr($code, 0, -1) : $code;
			return $code;
		}
		return "";
	}
	
	//used for the get_all action
	private static function prepareBrokerParametersPagination($tn_plural, &$broker_settings, $pagination) {
		$start_row = !empty($pagination["start_row"]) ? $pagination["start_row"] : null;
		$rows_per_page = !empty($pagination["rows_per_page"]) ? $pagination["rows_per_page"] : 100;
		
		$options_type = isset($broker_settings["brokers_layer_type"]) && $broker_settings["brokers_layer_type"] == "callhibernatemethod" ? "sma_options" : "options";
		$options = isset($broker_settings[$options_type]) ? $broker_settings[$options_type] : null;
		
		//error_log("\nCMSPresentationForSettingsUIHandler::prepareBrokerParametersPagination:\nparameters:".print_r($parameters, true) . "\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		/*{
			$parameters = isset($broker_settings["parameters"]) && is_array($broker_settings["parameters"]) ? $broker_settings["parameters"] : array();
			$options_idx = null;
			$next_idx = 0;
			$options_type = isset($broker_settings["brokers_layer_type"]) && $broker_settings["brokers_layer_type"] == "callhibernatemethod" ? "sma_options" : "options";
			
			if (is_array($parameters)) {
				if (isset($parameters["key"]))
					$parameters = array($parameters);
				
				foreach ($parameters as $i => $v) {
					$next_idx = $next_idx > $i ? $next_idx : $i;
					$key = isset($v["key"]) ? $v["key"] : null;
					
					if ($key == $options_type && isset($v["items"]) && is_array($v["items"])) {
						$options = $v["items"];
						$options_idx = $i;
						break;
					}
				}
			}
		}*/
		
		$options = $options ? $options : array();
		
		if (is_array($options)) {
			$options[] = array(
				"key" => "start",
				"key_type" => "string",
				"value" => $start_row,
				"value_type" => $start_row && !is_numeric($start_row) && substr($start_row, 0, 1) != '$' && substr($start_row, 0, 2) != '@$' ? "string" : ""
			);
			
			$options[] = array(
				"key" => "limit",
				"key_type" => "string",
				"value" => $rows_per_page,
				"value_type" => $rows_per_page && !is_numeric($rows_per_page) && substr($rows_per_page, 0, 1) != '$' && substr($rows_per_page, 0, 2) != '@$' ? "string" : ""
			);
			
			$broker_settings[$options_type] = $options;
			/*{
				$options_idx = is_numeric($options_idx) ? $options_idx : $next_idx + 1; //index should start with 1
				$parameters[$options_idx] = array(
					"key" => $options_type,
					"key_type" => "string",
					"items" => $options
				);
				
				$broker_settings["parameters"] = $parameters;
			}*/
		}
		//error_log(print_r($broker_settings, true) . "\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
	}
	
	public static function prepareBrokerSettings(&$broker_settings, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $tables) {
		if ($broker_settings && isset($broker_settings["brokers_layer_type"]))
			switch ($broker_settings["brokers_layer_type"]) {
				case "insert":
				case "update":
				case "delete":
				case "select":
					if (!$include_db_driver) {
						if (isset($broker_settings["db_driver"]))
							$broker_settings["db_driver"] = "";
					}
					else if ($db_driver)
						$broker_settings["db_driver"] = $db_driver;
					
					if (!empty($broker_settings["sql"]))
						//convert getquerydata and setquerydata to insert/update/delete/select task groups
						self::convertQueryDataTaskToSimpleTask($broker_settings, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $tables);
					
					break;
				
				case "callbusinesslogic":
				case "callibatisquery":
				case "callhibernatemethod":
				case "getquerydata":
				case "setquerydata":
					$dal_broker_exists = $db_broker_exists = $db_driver_exists = $return_type_exists = false;
					$sql = isset($broker_settings["sql"]) ? $broker_settings["sql"] : null;
					$check_return_type = $broker_settings["brokers_layer_type"] == "getquerydata" && stripos(trim($sql), "select ") === 0;
					$check_db_broker = $broker_settings["brokers_layer_type"] != "getquerydata" && $broker_settings["brokers_layer_type"] != "setquerydata";
					
					foreach ($broker_settings as $key => $v) {
						switch ($key) {
							case "method_obj":
								if ($v) {
									$static_pos = strpos($v, "::") || ($broker_settings["brokers_layer_type"] == "callobjectmethod" && isset($broker_settings["method_static"]) && $broker_settings["method_static"] == 1);
									$non_static_pos = strpos($v, "->");
									$v = substr($v, 0, 1) != '$' && substr($v, 0, 2) != '@$' && (!$static_pos || ($non_static_pos && $static_pos > $non_static_pos)) ? '$' . $v : $v;
									$v = substr($v, 0, 1) == '$' || substr($v, 0, 2) == '@$' ? $v : '"' . $v . '"';
									
									$broker_settings[$key] = $v;
									$broker_settings[$key . "_type"] = "method";
								}
								break;
							
							case "sma_ids": //very important otherwise it will convert the sma_ids value to a variable by default and we want to have a string with the variable name to be created after it executes the hibernate insert action!
								if ($v && substr($v, 0, 1) != '$' && substr($v, 0, 2) != '@$')
									$broker_settings[$key . "_type"] = "string"; //set this to string instead of variable which is the default value
								break;
							
							case "options": //remove db_driver if not to be included
								if (is_array($v)) {
									foreach ($v as $idx => $vv) {
										$vv_key = isset($vv["key"]) ? $vv["key"] : null;
										$vv_key_type = isset($vv["key_type"]) ? $vv["key_type"] : null;
										
										if ($vv_key == "db_driver" && $vv_key_type == "string") {
											$db_driver_exists = true;
											
											if (!$include_db_driver)
												unset($broker_settings[$key][$idx]);
											else if ($db_driver) {
												$broker_settings[$key][$idx]["value"] = $db_driver;
												$broker_settings[$key][$idx]["value_type"] = "string";
											}
											
											break;
										}
										else if ($check_db_broker && $vv_key == "db_broker" && $vv_key_type == "string") {
											$db_broker_exists = true;
											
											if (!$include_db_broker)
												unset($broker_settings[$key][$idx]);
											else if ($db_broker) {
												$broker_settings[$key][$idx]["value"] = $db_broker;
												$broker_settings[$key][$idx]["value_type"] = "string";
											}
											
											break;
										}
										else if ($check_return_type && $vv_key == "return_type" && $vv_key_type == "string") {
											$return_type_exists = true;
											
											//force return_type to result
											$broker_settings[$key][$idx]["value"] = "result";
											$broker_settings[$key][$idx]["value_type"] = "string";
										}
									}
								}
								break;
						}
					}
					
					if (empty($broker_settings["options"]))
						$broker_settings["options"] = array();
					
					if (is_array($broker_settings["options"])) {
						if ($include_db_driver && $db_driver && !$db_driver_exists)
							$broker_settings["options"][] = array(
								"key" => "db_driver",
								"key_type" => "string",
								"value" => $db_driver,
								"value_type" => "string",
							);
						
						if ($check_db_broker && $include_db_broker && $db_broker && !$db_broker_exists)
							$broker_settings["options"][] = array(
								"key" => "db_broker",
								"key_type" => "string",
								"value" => $db_broker,
								"value_type" => "string",
							);
					}
					
					if ($check_return_type && !$return_type_exists) {
						$options_type = $broker_settings["brokers_layer_type"] == "callhibernatemethod" && (
							(isset($broker_settings["service_method"]) && $broker_settings["service_method"] == "callQuery" && isset($broker_settings["sma_query_type"]) && $broker_settings["sma_query_type"] == "select") || 
							$broker_settings["service_method"] == "callSelect"
						) ? "sma_option" : "options";
						
						if (empty($broker_settings[$options_type]))
							$broker_settings[$options_type] = array();
						
						if (is_array($broker_settings[$options_type]))
							$broker_settings[$options_type][] = array(
								"key" => "return_type",
								"key_type" => "string",
								"value" => "result",
								"value_type" => "string",
							);
					}
					
					//convert getquerydata and setquerydata to insert/update/delete/select task groups
					self::convertQueryDataTaskToSimpleTask($broker_settings, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $tables);
					break;
				
				/*case "callfunction":
				case "callobjectmethod":
					foreach ($broker_settings as $key => $v) {
						switch ($key) {
							case "func_args":
							case "method_args":
								if (is_array($v) && isset($v["childs"])) {
									$args = array();
									
									foreach ($v as $vv)
										if (isset($vv["childs"]["value"][0]["value"])) {
											$vv_value = $vv["childs"]["value"][0]["value"];
											$vv_type = $vv["childs"]["type"][0]["value"];
										
											$args[] = array("value" => $vv_value, "type" => $vv_type);
										}
									
									$broker_settings[$key] = $args;
									$broker_settings[$key . "_type"] = "array";
								}
								break;
						}
					}
					break;*/
			}
	}
	
	//If you change this method, please make the same changes inside the method SequentialLogicalActivityResourceCreator::convertQueryDataTaskToSimpleTask
	//convert getquerydata and setquerydata to insert/update/delete/select task groups
	private static function convertQueryDataTaskToSimpleTask(&$broker_settings, $db_broker, $include_db_broker, $db_driver, $include_db_driver, $tables) {
		//error_log("broker_settings:".print_r($broker_settings, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		$brokers_layer_type = isset($broker_settings["brokers_layer_type"]) ? $broker_settings["brokers_layer_type"] : null;
		$is_get_or_set_query_data = $brokers_layer_type == "getquerydata" || $brokers_layer_type == "setquerydata";
		$is_simple_task = $brokers_layer_type == "insert" || $brokers_layer_type == "update" || $brokers_layer_type == "delete" || $brokers_layer_type == "select";
		
		if ($is_get_or_set_query_data || $is_simple_task) {
			$data = null;
			$sql_type = "select"; //if no sql, show "select" task group with empty sql.
			
			if (!empty($broker_settings["sql"])) {
				self::prepareGlobalVarsInArray($broker_settings["sql"]); //replace all $_GET[id] by #id#
			
				$data = DB::convertDefaultSQLToObject($broker_settings["sql"]);
				$sql_type = $data && isset($data["type"]) ? $data["type"] : null;
			}
		
			$sql_type_valid = $sql_type == "insert" || $sql_type == "update" || $sql_type == "delete" || $sql_type == "select";
			//error_log("broker_settings:".print_r($broker_settings, 1)."\nold_data:".print_r($data, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
			
			//only convert to insert/update/delete/select task group, if sql_type is valid. This is, if sql is a procedure, leave broker_settings with getquerydata or setquerydata
			if ($sql_type_valid) {
				//if sql exists and data is valid
				if ($data) {
					$data_table_name = isset($data["table"]) ? $data["table"] : null;
					$data["main_table"] = $data_table_name;
					$old_sql = DB::convertObjectToDefaultSQL($data); //get old sql through DB
					
					$new_data = array(
						"type" => isset($data["type"]) ? $data["type"] : null,
						"main_table" => isset($data["main_table"]) ? $data["main_table"] : null,
						"attributes" => isset($data["attributes"]) ? $data["attributes"] : null,
						"conditions" => isset($data["conditions"]) ? $data["conditions"] : null,
						"limit" => isset($data["limit"]) ? $data["limit"] : null,
						"start" => isset($data["start"]) ? $data["start"] : null,
					);
					$new_sql = DB::convertObjectToDefaultSQL($new_data); //get new sql through DB
					//error_log("broker_settings:".print_r($broker_settings, 1)."\nold_sql:$old_sql\nnew_sql:$new_sql\nold_data:".print_r($data, 1)."\nnew_data:".print_r($new_data, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
					
					//check if is a simple sql and can be converted to insert/update/delete/select task group
					$is_simple_sql = $new_sql == $old_sql && (empty($data["limit"]) || empty($broker_settings["options"]) || is_array($broker_settings["options"])); //if limit exists and options are an array or null. Note that $broker_settings["options"] could be a variable.
					
					if ($is_simple_sql) {
						//check if all $data["attributes"] are in $tables[table], bc it could be a select with a count(*)
						if (isset($data["type"]) && $data["type"] == "select" && !empty($data["attributes"]) && !empty($tables[$data_table_name]))
							foreach ($data["attributes"] as $attr) {
								$attr_column = isset($attr["column"]) ? $attr["column"] : null;
								
								if (empty($tables[$data_table_name][$attr_column])) {
									$is_simple_sql = false;
									break;
								}
							}
						
						//start converting to insert/update/delete/select task group
						if ($is_simple_sql) {
							//remove sql attribute bc is now a new task group.
							unset($broker_settings["sql"]);
							unset($broker_settings["sql_type"]);
							
							//on insert action, remove primary key auto_increment if exists
							if (isset($data["type"]) && $data["type"] == "insert" && !empty($data["attributes"]) && !empty($tables[$data_table_name]))
								foreach ($data["attributes"] as $idx => $attr) {
									$attr_column = isset($attr["column"]) ? $attr["column"] : null;
									$attr = isset($tables[$data_table_name][$attr_column]) ? $tables[$data_table_name][$attr_column] : null;
									
									if ($attr && !empty($attr["primary_key"]) && WorkFlowDataAccessHandler::isAutoIncrementedAttribute($attr))
										unset($data["attributes"][$idx]);
								}
							
							//add new settings
							$broker_settings["table"] = $data_table_name;
							$broker_settings["attributes"] = isset($data["attributes"]) ? $data["attributes"] : null;
							$broker_settings["conditions"] = isset($data["conditions"]) ? $data["conditions"] : null;
						}
					}
					
					//prepare limit and start in options, if exists
					if (!empty($data["limit"]) && (empty($broker_settings["options"]) || is_array($broker_settings["options"]))) { //if limit exists options must be an array or null
						$limit = $data["limit"];
						$start = isset($data["start"]) ? $data["start"] : null;
						$broker_settings["options_type"] = "array"; //set to array.
						$broker_settings["options"] = isset($broker_settings["options"]) && is_array($broker_settings["options"]) ? $broker_settings["options"] : array(); //if null, set it to an array
						$exists_limit = $exists_start = false;
						
						//if sql exists, remove limit and start from sql, bc it will be added in the options
						if (!empty($broker_settings["sql"])) {
							$other_data = $data;
							unset($other_data["limit"]);
							unset($other_data["start"]);
							$broker_settings["sql"] = DB::convertObjectToDefaultSQL($other_data);
						}
						
						//replace existent limit and start with right values
						foreach ($broker_settings["options"] as $idx => $v) {
							$v_key = isset($v["key"]) ? $v["key"] : null;
							$v_key_type = isset($v["key_type"]) ? $v["key_type"] : null;
							
							if ($v_key == "limit" && $v_key_type == "string") { //Overwrite limit in options
								$broker_settings["options"][$idx]["value"] = $limit; //note that limit can be #xxx#. It doesn't need a numeric value
								$broker_settings["options"][$idx]["value_type"] = "string";
								$exists_limit = true;
							}
							else if (strlen("$start") && $v_key == "start" && $v_key_type == "string") { //Overwrite start in options
								$broker_settings["options"][$idx]["value"] = $start; //note that limit can be #xxx#. It doesn't need a numeric value. If start is 0, discard $start.
								$broker_settings["options"][$idx]["value_type"] = "string";
								$exists_start = true;
							}
						}
						
						//add limit to options
						if (!$exists_limit)
							$broker_settings["options"][] = array(
								"key" => "limit",
								"key_type" => "string",
								"value" => $limit,
								"value_type" => "string",
							);
						
						//add start to options
						if (!$exists_start && strlen("$start"))
							$broker_settings["options"][] = array(
								"key" => "start",
								"key_type" => "string",
								"value" => $start,
								"value_type" => "string",
							);
					}
				}
				
				//convert getquerydata or setquerydata to insert/update/delete/select task group
				if ($is_get_or_set_query_data) {
					if (isset($broker_settings["options"]) && is_array($broker_settings["options"])) {
						//get the selected db_broker and db_driver, if exists. Otherwise continues with default.
						foreach ($broker_settings["options"] as $idx => $v) {
							$v_key = isset($v["key"]) ? $v["key"] : null;
							$v_key_type = isset($v["key_type"]) ? $v["key_type"] : null;
							
							if ($v_key == "db_broker" && $v_key_type == "string" && isset($v["value_type"]) && $v["value_type"] == "string") {
								$db_broker = isset($v["value"]) ? $v["value"] : null;
								unset($broker_settings["options"][$idx]); //remove this bc it will be added bellow
							}
							else if ($v_key == "db_driver" && $v_key_type == "string" && isset($v["value_type"]) && $v["value_type"] == "string") {
								$db_driver = isset($v["value"]) ? $v["value"] : null;
								unset($broker_settings["options"][$idx]); //remove this bc it will be added bellow
							}
							else if ($v_key == "return_type" && $v_key_type == "string") 
								unset($broker_settings["options"][$idx]); //remove this, bc is an invalid option.
						}
						
						//concert options into simple array, bc insert/update/delete/select task group have a simple options array
						$broker_settings["options"] = self::convertActionValueOptionsToSimpleArray($broker_settings["options"], "array");
					}
					
					//update settings to convert getquerydata or setquerydata to insert/update/delete/select task group
					$broker_settings["brokers_layer_type"] = $sql_type;
					$broker_settings["dal_broker"] = $db_broker;
					$broker_settings["db_driver"] = $db_driver;
					$broker_settings["db_type"] = "db";
				}
			}
			
			//echo "\n".$broker_settings["brokers_layer_type"].":".print_r($broker_settings, 1)."\n";
		}
	}
	
	private static function convertActionValueOptionsToSimpleArray($options, $options_type) {
		if ($options_type == "array") {
			if (is_array($options)) {
				$new_options = array();
				
				foreach ($options as $idx => $option) {
					$option_key = isset($option["key"]) ? $option["key"] : null;
					$option_key_type = isset($option["key_type"]) ? $option["key_type"] : null;
					
					$key = self::convertActionValueOptionsToSimpleArray($option_key, $option_key_type);
					
					if (!empty($option["items"]))
						$value = self::convertActionValueOptionsToSimpleArray($option["items"], "array");
					else {
						$option_value = isset($option["value"]) ? $option["value"] : null;
						$option_value_type = isset($option["value_type"]) ? $option["value_type"] : null;
						$value = self::convertActionValueOptionsToSimpleArray($option_value, $option_value_type);
					}
					
					$new_options[$key] = $value;
				}
				
				return $new_options;
			}
			else
				return $options;
		}
		else if ($options_type == "variable" && is_string($options) && substr(trim($options), 0, 1) != '$' && substr(trim($options), 0, 2) != '@$')
			return '$' . trim($options);
		
		return $options;
	}
	
	public static function addConditionsToGetBrokerSettings(&$broker_settings, $conditions) {
		if ($broker_settings && isset($broker_settings["brokers_layer_type"]) && $conditions)
			switch ($broker_settings["brokers_layer_type"]) {
				case "callbusinesslogic":
				case "callibatisquery":
				case "callhibernatemethod":
					$key = $broker_settings["brokers_layer_type"] == "callhibernatemethod" ? "sma_data" : "parameters";
					$parameters = isset($broker_settings[$key]) ? $broker_settings[$key] : null;
					
					if (is_array($parameters)) {
						foreach ($parameters as $idx => $parameter) 
							if (isset($parameter["key_type"]) && $parameter["key_type"] == "string") {
								$parameter_key = isset($parameter["key"]) ? strtolower($parameter["key"]) : "";
								
								foreach ($conditions as $idy => $cond) {
									$col = isset($cond["column"]) ? $cond["column"] : null; //may contain the table name too
									$only_col = !empty($cond["table"]) ? preg_replace("/^" . $cond["table"] . "\./", "", $col) : $col;
									
									if (strtolower($col) == $parameter_key || strtolower($only_col) == $parameter_key) {
										$parameter["value"] = isset($cond["value"]) ? $cond["value"] : null;
										$parameter["value_type"] = PHPUICodeExpressionHandler::getValueType($parameter["value"], array("empty_string_type" => "string", "non_set_type" => "string"));
										
										unset($parameter["items"]);
										$parameters[$idx] = $parameter;
										
										unset($conditions[$idy]);
										break;
									}
								}
							}
						
						$broker_settings[$key . "_type"] = "array";
						$broker_settings[$key] = $parameters;
					}
					
					break;
				case "getquerydata":
				case "setquerydata":
					if ($broker_settings["sql_type"] == "string" && isset($broker_settings["sql"]) && preg_match("/\s+where\s+/i", $broker_settings["sql"])) {
						$sql = trim($broker_settings["sql"]);
						
						foreach ($conditions as $idy => $cond) {
							$col = isset($cond["column"]) ? $cond["column"] : null; //may contain the table name too
							$only_col = !empty($cond["table"]) ? preg_replace("/^" . $cond["table"] . "\./", "", $col) : $col;
							
							//delete the columns in the conditions from the sql. The $conditions will be added later on in the addConditionsToBrokerSettings
							$sql = preg_replace("/\s+$col\s*=\s*'([^']*)'/i", "", $sql);
							$sql = preg_replace("/\s+$col\s*=\s*\"([^\"]*)\"/i", "", $sql);
							$sql = preg_replace("/\s+$col\s*=\s*([0-9]+)/i", "", $sql);
							$sql = preg_replace("/\s+$col\s*=\s*#([^#]*)#/i", "", $sql);
							
							$sql = preg_replace("/\s+$only_col\s*=\s*'([^']*)'/i", "", $sql);
							$sql = preg_replace("/\s+$only_col\s*=\s*\"([^\"]*)\"/i", "", $sql);
							$sql = preg_replace("/\s+$only_col\s*=\s*([0-9]+)/i", "", $sql);
							$sql = preg_replace("/\s+$only_col\s*=\s*#([^#]*)#/i", "", $sql);
						}
						
						$broker_settings["sql"] = $sql;
					}
					break;
			}
		
		self::addConditionsToBrokerSettings($broker_settings, $conditions);
	}
	
	public static function addConditionsToBrokerSettings(&$broker_settings, $conditions, $table_parent = false) {
		if ($broker_settings && isset($broker_settings["brokers_layer_type"]) && $conditions)
			switch ($broker_settings["brokers_layer_type"]) {
				case "callbusinesslogic":
				case "callibatisquery":
				case "callhibernatemethod":
					$key = $broker_settings["brokers_layer_type"] == "callhibernatemethod" ? "sma_data" : "parameters";
					$parameters = isset($broker_settings[$key]) ? $broker_settings[$key] : null;
					$parameters_type = isset($broker_settings[$key . "_type"]) ? $broker_settings[$key . "_type"] : null;
					
					if (!$parameters || is_array($parameters) || $parameters_type == "array") {
						if (!$parameters) 
							$parameters = array();
						
						//prepare conditions item in parameters
						$conditions_index = $idx = null;
						foreach ($parameters as $idx => $parameter) 
							if (isset($parameter["key_type"]) && $parameter["key_type"] == "string" && isset($parameter["key"]) && $parameter["key"] == "conditions") {
								$conditions_index = $idx;
								break;
							}
						
						if (!$conditions_index)
							$conditions_index = $idx + 1;
						
						if (empty($parameters[$conditions_index]) || empty($parameters[$conditions_index]["items"]))
							$parameters[$conditions_index] = array(
								"key" => "conditions",
								"key_type" => "string",
								"items" => array(),
							);
						
						//prepare $conditions inside of $parameters[$conditions_index]
						foreach ($conditions as $condition) {
							$attribute = isset($condition["column"]) ? $condition["column"] : null;
							
							if ($attribute) {
								$value = isset($condition["value"]) ? $condition["value"] : null;
								$value_type = PHPUICodeExpressionHandler::getValueType($value, array("empty_string_type" => "string", "non_set_type" => "string"));
								$exists = false;
								$idx = null;
								
								if (!empty($parameters[$conditions_index]["items"]))
									foreach ($parameters[$conditions_index]["items"] as $idx => $parameter) 
										if (isset($parameter["key_type"] ) && $parameter["key_type"] == "string" && isset($parameter["key"]) && $parameter["key"] == $attribute) {
											$parameters[$conditions_index]["items"][$idx]["value"] = $value;
											$parameters[$conditions_index]["items"][$idx]["value_type"] = $value_type;
											$exists = true;
											break;
										}
								
								if (!$exists) {
									$idx++;
									
									$parameters[$conditions_index]["items"][$idx] = array(
										"key" => $attribute,
										"key_type" => "string",
										"value" => $value,
										"value_type" => $value_type,
									);
								}
							}
						}
						
						$broker_settings[$key . "_type"] = "array";
						$broker_settings[$key] = $parameters;
					}
					
					break;
				case "getquerydata":
				case "setquerydata":
					if (isset($broker_settings["sql_type"]) && $broker_settings["sql_type"] == "string" && isset($broker_settings["sql"]) && trim($broker_settings["sql"])) {
						$broker_settings["sql"] = trim($broker_settings["sql"]);
						
						if (substr($broker_settings["sql"], -1) == ";")
							$broker_settings["sql"] = substr($broker_settings["sql"], 0, -1);
						
						$conditions_str = DB::getSQLRelationshipConditions($conditions, $table_parent);
						
						if ($conditions_str) {
							if (preg_match("/\s+where\s+/i", $broker_settings["sql"]))
								$broker_settings["sql"] = preg_replace("/(\s+where\s+)/i", "\${1}$conditions_str and ", $broker_settings["sql"]);
							else
								$broker_settings["sql"] .= " where " . $conditions_str;
						}
					}
					break;
			}
	}
	
	private static function prepareLink(&$link, $tn, $attrs, $pks, $is_list = false, $is_list_before_insert = false, $is_ptl = false, $simple_value = false) {
		if ($link) {
			$link["url"] = isset($link["url"]) ? trim($link["url"]) : null;
			
			//replace the attributes in url and in javascript code.
			if (strlen($link["url"]))
				$link["url"] = self::getHrefWithPks($link["url"], $tn, $attrs, $pks, $is_list, $is_list_before_insert, $is_ptl, $simple_value);
			
			//prepare data-query-string extra attribute 
			if (!strlen($link["url"]) || stripos($link["url"], "javascript:") === 0) { //if is javascript or ig url is empty, which means that might exist an onClick attribute
				$query_string = self::getHrefWithPks("?", $tn, $attrs, $pks, $is_list, $is_list_before_insert, $is_ptl, $simple_value);
				$query_string = substr($query_string, 1); //remove "?"
				
				if ($query_string) {
					if (!empty($link["extra_attributes"]["name"]) || !empty($link["extra_attributes"]["value"]))
						$link["extra_attributes"] = array($link["extra_attributes"]);
					
					$exists = false;
					if (!empty($link["extra_attributes"]))
						foreach ($link["extra_attributes"] as &$eas)
							if ($eas["name"] == "data-query-string") {
								$eas["value"] .= "&" . $query_string;
								$exists = true;
								break;
							}
					
					if (!$exists)
						$link["extra_attributes"][] = array("name" => "data-query-string", "value" => $query_string);
				}
			}
		}
	}
	
	//add tables and table_name here
	private static function getHrefWithPks($href, $tn, $attrs, $pks, $is_list = false, $is_list_before_insert = false, $is_ptl = false, $simple_value = false) {
		if ($is_list_before_insert || !strlen($href)) //it means that the correspondent DB Record won't have any pk yet, bc this happens before the insert action happens
			return "javascript:void(0)";
		
		if ($attrs) {
			//find #attr_name# and then replace it with the correspondent correct code
			if ($href) {
				preg_match_all("/#([\w \-\+\.]+)#/u", $href, $matches, PREG_PATTERN_ORDER); //'\w' means all words with '_' and '/u' means with accents and ç too.
				
				if ($matches && $matches[1] && !$simple_value) 
					foreach ($matches[1] as $match) 
						if (isset($attrs[$match])) {
							if ($is_ptl)
								$c = '<ptl:echo @\\$input[' . $tn . ']' . ($is_list ? '[\\$i]' : '') . '[' . $match . '] />';
							else
								$c = $is_list ? '#[idx][' . $match . ']#' : '#' . $tn . '[' . $match . ']#';
							
							$href = str_replace("#$match#", $c, $href);
							
							unset($attrs[$match]);
						}
			}
			
			//then add the rest of the pks, but only if not a javascript code
			if ($pks && stripos($href, "javascript:") !== 0) {
				parse_str(parse_url($href, PHP_URL_QUERY), $existent_attrs_name);
				$existent_attrs_name = $existent_attrs_name ? array_keys($existent_attrs_name) : array();
				$query_string = "";
				
				foreach ($attrs as $attr_name => $aux) 
					if (in_array($attr_name, $pks) && !in_array($attr_name, $existent_attrs_name)) { //2021-02-18 JP: very important to only include $attr_name in query_string if not exists yet, bc in the view panel with some available_values the id of the correspondent link is already set with the right fk and if this fk is the same than the $attr_name, this will replace it. So we can only add it if not exists yet.
						$query_string .= ($query_string ? '&' : '') . $attr_name . '=';
						
						if ($simple_value)
							$query_string .= "#$attr_name#";
						else if ($is_ptl)
							$query_string .= '<ptl:echo @\\$input[' . $tn . ']' . ($is_list ? '[\\$i]' : '') . '[' . $attr_name . '] />';
						else
							$query_string .= $is_list ? '#[idx][' . $attr_name . ']#' : '#' . $tn . '[' . $attr_name . ']#';
					}
				
				if ($query_string)
					$href .= (strpos($href, "?") !== false ? (substr($href, -1) == "?" ? "" : "&") : "?") . $query_string;
			}
		}
		
		return $href;
	}		
	
	//replace $_POST[$tn][ by #tn[#
	private static function prepareGlobalVarsInArrayWithSingleTableVariable($table_name, $tn, &$value, $global_vars_type = array('$_POST', '$_GET', '$_REQUEST'), &$exists = null) {
		if (is_array($value)) {
			$keys = array_keys($value);
			for ($i = 0; $i < count($keys); $i++) { //Note that I cannot use foreach here, otherwise it will loose the "value_type" value...
				$k = $keys[$i];
				$v = $value[$k];
				
				$exists = false;
				$value[$k] = self::prepareGlobalVarsInArrayWithSingleTableVariable($table_name, $tn, $v, $global_vars_type, $exists);
				
				//in case of parameters or options, they have the "value_type" attribute which should be string if "value" attribute was replaced by #...#
				if ($exists && isset($value[$k . "_type"]) && (strlen($value[$k . "_type"]) == 0 || $value[$k . "_type"] == "variable"))
					$value[$k . "_type"] = "string";
			}
		}
		else if ($value) {
			$vars = self::getVariablesFromText($value);
			$exists = false;
			$t = count($vars);
			$avs = array(
				'$_POST[' . $tn . '][', '$_POST["' . $tn . '"][', '$_POST[\'' . $tn . '\'][', 
				'$_GET[' . $tn . '][', '$_GET["' . $tn . '"][', '$_GET[\'' . $tn . '\'][', 
				'$_REQUEST[' . $tn . '][', '$_REQUEST["' . $tn . '"][', '$_REQUEST[\'' . $tn . '\'][', 
				
				'$_POST[' . $table_name . '][', '$_POST["' . $table_name . '"][', '$_POST[\'' . $table_name . '\'][', 
				'$_GET[' . $table_name . '][', '$_GET["' . $table_name . '"][', '$_GET[\'' . $table_name . '\'][', 
				'$_REQUEST[' . $table_name . '][', '$_REQUEST["' . $table_name . '"][', '$_REQUEST[\'' . $table_name . '\'][', 
				
				'$_POST[', //very important, bc by default the POST values from insert and update have directly the attributes names, this is, something like: $_POST["user_id"]
			);
			$global_vars_type = is_array($global_vars_type) ? $global_vars_type : ($global_vars_type ? array($global_vars_type) : array());
		
			for ($i = 0; $i < $t; $i++) {
				$var = $vars[$i];
				
				foreach ($avs as $av) {
					$aux = substr($av, 0, strpos($av, "["));
					
					if (in_array($aux, $global_vars_type) && stripos($var, $av) === 0) {
						$replacement_var = substr($var, strlen($av)); 
						$replacement_var = str_replace(array("'", '"'), "", $replacement_var); //remove quotes
						
						$value = str_replace("{" . $var . "}", "#{$tn}[" . $replacement_var . "#", $value); //replace first the variable with {}, this is, '{$var_name}', otherwise we will end up with '{#tn[var_name]#}', which is wrong and if is inside of sql will give a sql error.
						$value = str_replace($var, "#{$tn}[" . $replacement_var . "#", $value);
						$exists = true;
						break;
					}
				}
			}
		}
		
		return $value;
	}
	
	private static function prepareGlobalVarsInArray(&$value, &$exists = null) {
		if (is_array($value)) {
			$keys = array_keys($value);
			for ($i = 0; $i < count($keys); $i++) { //Note that I cannot use foreach here, otherwise it will loose the "value_type" value...
				$k = $keys[$i];
				$v = $value[$k];
				
				$exists = false;
				$value[$k] = self::prepareGlobalVarsInArray($v, $exists);
				
				//in case of parameters or options, they have the "value_type" attribute which should be string if "value" attribute was replaced by #...#
				if ($exists && isset($value[$k . "_type"]) && (strlen($value[$k . "_type"]) == 0 || $value[$k . "_type"] == "variable"))
					$value[$k . "_type"] = "string";
			}
		}
		else if ($value) {
			$vars = self::getVariablesFromText($value);
			$exists = false;
			$t = count($vars);
			$avs = array('$_POST', '$_GET', '$_REQUEST');
			
			for ($i = 0; $i < $t; $i++) {
				$var = $vars[$i];
				
				foreach ($avs as $av) 
					if (stripos($var, $av) === 0) {
						$replacement_var = substr($var, 1);
						$replacement_var = str_replace(array("'", '"'), "", $replacement_var); //remove quotes
						
						$value = str_replace("{" . $var . "}", "#" . $replacement_var . "#", $value); //replace first the variable with {}, this is, '{$var_name}', otherwise we will end up with '{#var_name#}', which is wrong and if is inside of sql will give a sql error.
						$value = str_replace($var, "#" . $replacement_var . "#", $value);
						$exists = true;
						break;
					}
			}
		}
		
		return $value;
	}
	
	/* The code bellow is better than this regex: 
	 * 	preg_match('/^\$\w+(\[("|\')?[\w"\'$]+("|\')?\])*$/i', $value)) //allow php variables like: $aSd, $x["asd"], $x['asd'], $x[0][asd], $["asds'ad"], $x['asd"as'], $x[$y]. However does NOT ALLOW variables like: $x[$y[0]]
	 * Because it gets the real variable
	 * Must be public bc it's used in the module/form/system_settings/create_form_settings_code.php
	 */
	public static function getVariablesFromText($text) {
		$vars = array();
		$l = strlen($text);
		
		preg_match_all('/\$\w+/iu', $text, $matches, PREG_OFFSET_CAPTURE); //'\w' means all words with '_' and '/u' means with accents and ç too.
		$matches = $matches[0];
		
		if ($matches)
			for ($i = 0; $i < count($matches); $i++) {
				$match = $matches[$i];
				$var = $match[0];
				$offset = $match[1];
				$odq = $osq = false;
				$bc = 0;
				
				for ($j = $offset + strlen($var); $j < $l; $j++) {
					$char = $text[$j];

					if ($char == '"' && !$osq)
						$odq = !$odq;
					else if ($char == "'" && !$odq)
						$osq = !$osq;
					else if ($char == "[" && !$osq && !$odq)
						++$bc;
					else if ($char == "]" && !$osq && !$odq)
						--$bc;
					else if (!$osq && !$odq && $bc <= 0)
						break;
				}
				
				$vars[] = substr($text, $offset, $j - $offset);
			}

		return $vars;
	}
	
	private static function hasDataAccessLayer($PEVC) {
		$brokers = $PEVC->getPresentationLayer()->getBrokers();
		
		if ($brokers)
			foreach ($brokers as $broker_name => $broker)
				if (is_a($broker, "IDataAccessBrokerClient")) 
					return true;
		
		return false;
	}
	
	private static function convertHtmlToJavascriptVariable($var_name, $html, $is_ptl = false) {
		$html = str_replace(array("\n", "\r"), "", $html);
		$html = str_replace("<script", "&lt;script", str_replace("</script", "&lt;/script", $html));
		$html = $is_ptl ? TextSanitizer::addCSlashesExcludingPTL($html, "\\'") : addcslashes($html, "\\'");
		
		return "var $var_name = '$html';";
	}
	
	private static function getSettingsPHPCodeList($actions_props, $saved_list = array()) {
		$attributes_settings = isset($actions_props["attributes_settings"]) ? $actions_props["attributes_settings"] : null;
		$list = self::getSettingPHPCodeList($attributes_settings);
		
		//in the future add other settings, if apply...
		
		if ($saved_list)
			$list = array_merge($saved_list, $list);
		
		$list = array_unique($list);
		usort($list, array(self::class, "arraySortBasedInItemLength"));
		
		return $list;
	}
	
	private static function arraySortBasedInItemLength($a, $b) {
		if (is_array($a))
			return 1;
		else if (is_array($b) || strlen($a) > strlen($b))
			return -1;
		else
			return 1;
	}
	
	private static function getSettingPHPCodeList($setting) {
		$list = array();
		
		if (is_array($setting)) {
			foreach ($setting as $k => $v) {
				$sub_list = self::getSettingPHPCodeList($v);
				
				if ($sub_list)
					$list = array_merge($list, $sub_list);
			}
		}
		else if ($setting) { 
			$setting_type = PHPUICodeExpressionHandler::getValueType($setting, array("empty_string_type" => "string", "non_set_type" => "string")); //check if php code
			
			if (empty($setting_type) && !is_numeric($setting)) //if type is code
				$list[] = $setting;
		}
		
		return $list;
	}
}
?>
