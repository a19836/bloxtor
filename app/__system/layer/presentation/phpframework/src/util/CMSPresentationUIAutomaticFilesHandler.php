<?php
include_once get_lib("org.phpframework.phpscript.PHPUICodeExpressionHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("WorkFlowDataAccessHandler");

$common_project_name = $EVC->getCommonProjectName();
$modules_path = $EVC->getModulesPath($common_project_name);
$object_module_path = $modules_path . "object/";
$user_module_path = $modules_path . "user/";

if (file_exists($object_module_path))
	include_once $EVC->getModulePath("object/ObjectUtil", $common_project_name);

if (file_exists($user_module_path))
	include_once $EVC->getModulePath("user/UserUtil", $common_project_name);

class CMSPresentationUIAutomaticFilesHandler {
	
	public static function getTableGroupHtml($table_name, $foreign_keys, $tasks_contents, $allowed_tasks, $with_items_list_ui, $with_view_item_ui, $with_insert_item_form_ui, $with_update_item_form_ui, $with_fks_ui, $selected_tables_alias = false) {
		$table_alias = "";
		$extra = "";
		
		$selected_table_alias = $selected_tables_alias ? WorkFlowDBHandler::getTableFromTables($selected_tables_alias, $table_name) : null;
		
		if ($selected_tables_alias && $selected_table_alias) {
			$extra = " with alias: '" . $selected_table_alias . "'";
			$table_alias = ' table_alias="' . $selected_table_alias . '"';
		}
		
		$html = '<div class="table_group" table_name="' . $table_name . '"' . $table_alias . '>
			<div class="table_header">
				<label>' . ucfirst($table_name) . '\'s Table' . $extra . '</label>
				<span class="icon maximize" onClick="toggleTablePanel(this)" title="Toggle Properties">Toggle</i></span>
				<span class="icon delete" onClick="removeTablePanel(this)" title="Remove">Remove</span>
			</div>
			<div class="table_panel">';
		
		$get_allowed_tasks = $allowed_tasks;
		$set_allowed_tasks = $allowed_tasks;
		
		$idx = array_search("setquerydata", $get_allowed_tasks);
		if ($idx !== false)
			unset($get_allowed_tasks[$idx]);
		
		$idx = array_search("getquerydata", $set_allowed_tasks);
		if ($idx !== false)
			unset($set_allowed_tasks[$idx]);
		
		if ($with_items_list_ui) {
			$html .= self::getTableUIHtml("Search/Get all table's rows", $tasks_contents, $get_allowed_tasks, "get_all");
			$html .= self::getTableUIHtml("Count all table's items", $tasks_contents, $get_allowed_tasks, "count");
		}
		
		if ($with_items_list_ui || $with_view_item_ui || $with_update_item_form_ui)
			$html .= self::getTableUIHtml("Get a specific table's row", $tasks_contents, $get_allowed_tasks, "get");
		
		if ($with_insert_item_form_ui)
			$html .= self::getTableUIHtml("Insert a specific table's row", $tasks_contents, $set_allowed_tasks, "insert");
		
		if ($with_update_item_form_ui) {
			$html .= self::getTableUIHtml("Update a specific table's row", $tasks_contents, $set_allowed_tasks, "update");
			$html .= self::getTableUIHtml("Update a specific table's row primary key", $tasks_contents, $set_allowed_tasks, "update_pks");
			$html .= self::getTableUIHtml("Delete a specific table's row", $tasks_contents, $set_allowed_tasks, "delete");
		}
		else if ($with_items_list_ui) {
			$html .= self::getTableUIHtml("Update a specific table's row", $tasks_contents, $set_allowed_tasks, "update");
			$html .= self::getTableUIHtml("Delete a specific table's row", $tasks_contents, $set_allowed_tasks, "delete");
		}
		
		if ($with_fks_ui) {
			$html .= '
			<div class="table_ui">
				<div class="table_header">
					<label>Foreign Tables</label>
					<span class="icon maximize" onClick="toggleTableUIPanel(this)" title="Toggle Properties">Toggle</span>
					<span class="icon delete" onClick="removeTableUIPanel(this)" title="Remove">Remove</span>
					<span class="icon add" onClick="addForeignTable(this)" title="Add">Add</span>
				</div>
				<div class="table_ui_panel">';
			
			$fks = $foreign_keys ? WorkFlowDBHandler::getTableFromTables($foreign_keys, $table_name) : null;
			$repeated = array();
			
			if ($fks) {
				$t = count($fks);
				for ($i = 0; $i < $t; $i++) {
					$fk = $fks[$i];
					$foreign_table_name = $fk["child_table"] == $table_name ? $fk["parent_table"] : $fk["child_table"];
					
					if (!$repeated[$foreign_table_name])
						$html .= self::getForeignTableRowHtml($table_name, $foreign_table_name, $tasks_contents, $allowed_tasks, $selected_tables_alias);
					
					$repeated[$foreign_table_name] = 1;
				}
			}
			
			$html .= '</div>
			</div>';	
		}

		$html .= '</div>
		</div>';
		
		return $html;
	}

	public static function getForeignTableRowHtml($table_name, $foreign_table_name, $tasks_contents, $allowed_tasks, $selected_tables_alias = false) {
		if ($selected_tables_alias && $selected_tables_alias[$foreign_table_name])
			$extra = " (with table alias: '" . $selected_tables_alias[$foreign_table_name] . "')";
		
		$idx = array_search("setquerydata", $allowed_tasks);
		if ($idx !== false)
			unset($allowed_tasks[$idx]);
		
		$html = self::getTableUIHtml("Get correspondent " . ucfirst($foreign_table_name) . " items$extra", $tasks_contents, $allowed_tasks, "relationships", $foreign_table_name);
		$html .= self::getTableUIHtml("Get correspondent " . ucfirst($foreign_table_name) . " count$extra", $tasks_contents, $allowed_tasks, "relationships_count", $foreign_table_name);
		
		return $html;
	}

	public static function getTableUIHtml($title, $tasks_contents, $allowed_tasks, $type, $relationship_table = false) {
		//array("callbusinesslogic", "callibatisquery", "callhibernatemethod", "getquerydata", "setquerydata")
		
		$html = '
		<div class="table_ui ' . $type . '">
			<div class="table_header">
				<label>' . $title . '</label>
				<span class="icon maximize" onClick="toggleTableUIPanel(this)" title="Toggle Properties">Toggle</span>
				<span class="icon delete" onClick="removeTableUIPanel(this)" title="Remove">Remove</span>
			</div>
			<div class="selected_task_properties table_ui_panel" type="' . $type . '" relationship_table="' . $relationship_table . '">
				<div class="brokers_layer_type">
					<label>Brokers Layer Type:</label>
					<select	name="brokers_layer_type" onChange="onChangeBrokersLayerType(this)">';
		
		if (in_array("callbusinesslogic", $allowed_tasks))
			$html .= '
					<option value="callbusinesslogic">Business Logic Brokers</option>';
		
		if (in_array("callibatisquery", $allowed_tasks))
			$html .= '
						<option value="callibatisquery">Ibatis Brokers</option>';
		
		if (in_array("callhibernatemethod", $allowed_tasks))
			$html .= '
						<option value="callhibernatemethod">Hibernate Brokers</option>';
		
		if (in_array("getquerydata", $allowed_tasks))
			$html .= '
						<option value="getquerydata">Get SQL Brokers</option>';
		
		if (in_array("setquerydata", $allowed_tasks))
			$html .= '
						<option value="setquerydata">Set SQL Brokers</option>';
		
		$html .= '
					</select>
				</div>
				' . $tasks_contents . '
			</div>
		</div>';	
		
		return $html;
	}
	
	public static function getFormSettingsBlockCode($form_settings, $options = null) {
		//print_r($form_settings);
		
		$code = '<?php
	$block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);//must be the same than this file name.

	$block_settings[$block_id] = array(';
		
		if ($form_settings) {
			$form_settings_php_codes_list = $options ? $options["form_settings_php_codes_list"] : null;
			
			if ($form_settings["actions"])
				$code .= '
		"actions" => ' . self::getFormSettingsActionsBlockCode($form_settings["actions"], "\t\t\t") . ',';
			
			if ($form_settings["css"])
				$code .= '
		"css" => ' . self::getCodeAttributeValueConfigured($form_settings["css"]) . ',';
			
			if ($form_settings["js"]) {
				$js = self::getCodeAttributeValueConfigured($form_settings["js"]);
				$js = self::replaceFormSettingsPHPCodesListInStringVariableValue($js, $form_settings_php_codes_list);
				
				$code .= '
		"js" => ' . $js . ',';
			}
		}
		
		$code .= '
	);

	$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("form", $block_id, $block_settings[$block_id]);
	?>';
		
		//echo "code:$code\n";
		return $code;
	}
	
	/*
	 * Replaces wrong hard coded vars (like $_GET vars) or other php code.
	 * This method only needs to be called when it comes from the CMSPresentationUIDiagramFilesHandler::createFile method, bc this calls the CMSPresentationFormSettingsUIHandler::getFormSettings method, which initializes the form settings "js" code with some wrong php code, like:
	 	$form_settings => array(
		 	"js" => "
		 		//some js code here...
		 		var before_insert_html = '...<td class=\"field title\">$_GET[\"title\"]</td>...';
		 		//some other js code here...
		 	",
	 	);
	 *
	 * This js code will give a php error, bc the php will try to execute $_GET[\"title\"] which is wrong. 
	 * To fix this, the settings should be:
	 	$form_settings => array(
		 	"js" => "
		 		//some js code here...
		 		var before_insert_html = '...<td class=\"field title\">" . ($_GET["title"]) . "</td>...';
		 		//some other js code here...
		 	",
	 	);
	 */
	private static function replaceFormSettingsPHPCodesListInStringVariableValue($value, $form_settings_php_codes_list) {
		//if form_settings_php_codes_list exists and if $value is a php code string.
		if ($form_settings_php_codes_list && $value[0] == '"' && substr($value, -1) == '"') {
			//error_log("form_settings_php_codes_list:".print_r($form_settings_php_codes_list, 1)."\n", 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
			
			foreach ($form_settings_php_codes_list as $php_statement) 
				if ($php_statement) { //be sure that php_statement exists
					$to_search = addcslashes($php_statement, "\\'\""); //add back slashes to: \\, ' and ". \\ and ' bc of the CMSPresentationFormSettingsUIHandler::convertHtmlToJavascriptVariable. " bc of self::getCodeAttributeValueConfigured
					$offset = 0;
					//error_log("to_search: $to_search\n", 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
					
					while (preg_match("/" . preg_quote($to_search) . "/", $value, $matches, PREG_OFFSET_CAPTURE, $offset)) {
						$offset = $matches[0][1];
						$start = $offset - 100 > 0 ? $offset - 100 : 0;
						$length = $offset - $start;
						$sub_value = substr($value, $start, $length);
						$new_offset = $offset + strlen($to_search);
						//error_log("offset: $offset\n", 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
						
						if (!preg_match("/<ptl:\w\s+$/", $sub_value)) //if not ptl
							$value = substr($value, 0, $offset) . '" . (' . $php_statement . ') . "' . substr($value, $new_offset);
						
						$offset = $new_offset;
					}
				}
		}
		
		return $value;
	}
	
	private static function getFormSettingsActionsBlockCode($actions, $prefix = "") {
		$code = "array(";
		
		foreach ($actions as $action) {
			switch ($action["action_type"]) {
				case "loop":
				case "group":
					if ($action["action_value"] && $action["action_value"]["actions"]) {
						$action_value = 'array(';
						
						foreach ($action["action_value"] as $k => $v) {
							$action_value .= "\n$prefix\t\t" . (is_numeric($k) ? $k : '"' . $k . '"') . " => ";
							
							if (is_array($v) && $v && $k == "actions")
								$action_value .= self::getFormSettingsActionsBlockCode($v, "$prefix\t\t\t");
							else
								$action_value .= self::getCodeAttributeValueConfigured($v, "$prefix\t\t\t");
							
							$action_value .= ",";
						}
						
						$action_value .= "\n$prefix\t)";
					}
					else
						$action_value = self::getCodeAttributeValueConfigured($action["action_value"], $prefix);
					
					break;
				
				case "callbusinesslogic":
				case "callibatisquery":
				case "callhibernatemethod":
				case "getquerydata":
				case "setquerydata":
				/*case "callfunction":
				case "callobjectmethod":*/
					if (is_array($action["action_value"])) {
						$action_value = 'array(';
						$keys_to_avoid = array("brokers_layer_type", "result_var_type", "parameters_type", "options_type", "sma_options_type");
						
						foreach ($action["action_value"] as $k => $v) 
							if (!in_array($k, $keys_to_avoid)) {
								$action_value .= "\n$prefix\t\t" . (is_numeric($k) ? $k : '"' . $k . '"') . " => ";
								
								if ((is_array($v) || empty($v)) && ($k == "options" || $k == "sma_options" || $k == "parameters")) {
									if (isset($v["key"])) //if only one option or parameter, $v can be the first item, so we need to make it inside of an array.
										$v = array($v);
									
									$action_value .= trim(WorkFlowTask::getArrayString($v, "$prefix\t\t"));
								}
								else if (isset($action["action_value"][$k . "_type"])) {
									$is_array = $action["action_value"][$k . "_type"] == "array";
									
									if ($is_array && is_array($v)) { //when hibernate, the sma_data for insert action and others, contains an array with the table attribtues...
										if (isset($v["key"])) //if only one option or parameter, $v can be the first item, so we need to make it inside of an array.
											$v = array($v);
										
										$action_value .= trim(WorkFlowTask::getArrayString($v, "$prefix\t\t"));
									}
									else
										$action_value .= strlen($v) ? PHPUICodeExpressionHandler::getArgumentCode($v, $action["action_value"][$k . "_type"]) : '""';
										
									$keys_to_avoid[] = $k . "_type";
								}
								else 
									$action_value .= self::getCodeAttributeValueConfigured($v, "$prefix\t\t");
								
								$action_value .= ",";
							}
						
						$action_value .= "\n$prefix\t)";
					}
					else
						$action_value = self::getCodeAttributeValueConfigured($action["action_value"], $prefix);
					
					break;
				
				default:
					$action_value = self::getCodeAttributeValueConfigured($action["action_value"], $prefix);
			}
			
			$code .= '
' . $prefix . 'array(
' . "$prefix\t" . '"result_var_name" => ' . self::getCodeAttributeValueConfigured($action["result_var_name"], $prefix) . ',
' . "$prefix\t" . '"action_type" => ' . self::getCodeAttributeValueConfigured($action["action_type"], $prefix) . ',
' . "$prefix\t" . '"condition_type" => ' . self::getCodeAttributeValueConfigured($action["condition_type"], $prefix) . ',
' . "$prefix\t" . '"condition_value" => ' . self::getCodeAttributeValueConfigured($action["condition_value"], $prefix) . ',
' . "$prefix\t" . '"action_value" => ' . $action_value . '
' . "$prefix" . '),';
		}
		
		$code .= ($code == "array(" ? "" : "\n" . substr($prefix, 0, -1)) . ")";
		
		return $code;
	}

	public static function getCodeAttributeValueConfigured($v, $tab_prefix = "") {
		if (is_array($v)) {
			$code = 'array(';
			
			foreach ($v as $k => $vv)
				$code .= '
	' . $tab_prefix . "\t" . (is_numeric($k) ? $k : '"' . $k . '"') . ' => ' . self::getCodeAttributeValueConfigured($vv, $tab_prefix . "\t") . ',';
			
			$code .= '
	' . $tab_prefix . ')';
			
			return $code;
		}
		
		$type = PHPUICodeExpressionHandler::getValueType($v, array("non_set_type" => "string", "empty_string_type" => "string"));
		
		//if there is '"" . ' the php will return the type "string"
		if (($type == "" || $type == "string") && substr($v, 0, 4) == '"" .' && substr($v, -4) == '. ""') 
			return trim(substr($v, 4, -4));
		
		return PHPUICodeExpressionHandler::getArgumentCode($v, $type);
		/* OLD CODE
		else if (!isset($v))
			return '""'; //Do not return "null", otherwise the form module will convert this to '< ? null ? >'
		else if ($v === false)
			return "false";
		else if ($v === true)
			return "true";
		else if (is_string($v) && strlen($v) == 0)
			return '""'; //Do not return "null", otherwise the form module will convert this to '< ? null ? >'
		else {
			//change this to call PHPUICodeExpressionHandler::getArgumentCode or something similar with token_get_all
			$is_code_type = is_numeric($v) || (substr($v, 0, 1) == '"' && substr($v, -1) == '"') || (substr($v, 0, 1) == "'" && substr($v, -1) == "'");
			
			if ($is_code_type) {
				if (substr($v, 0, 4) == '"" .' && substr($v, -4) == '. ""')
					return trim(substr($v, 4, -4));
				return $v;
			}
			else if (substr($v, 0, 1) == '$')
				return $v;
			else 
				return '"' . addcslashes($v, '"') . '"';
		}*/
	}
	
	public static function saveBlockCode($PEVC, &$block_id, $block_code, $overwrite, &$statuses) {
		if (!$block_id)
			return false;
		
		if (!$overwrite)
			CMSPresentationLayerHandler::configureUniqueFileId($block_id, $PEVC->getBlocksPath(), "." . $PEVC->getPresentationLayer()->getPresentationFileExtension());
		
		return self::saveFileCode($PEVC->getBlockPath($block_id), $block_code, $overwrite, $statuses);
	}

	public static function getEntityCode($entity_settings) {
		$regions_blocks = $entity_settings["regions_blocks"];
		
		if ($regions_blocks) {
			$includes = $entity_settings["includes"];
			$template_params = $entity_settings["template_params"];
			$template = $entity_settings["template"];
			
			$entity_code = '<?php ';
			
			if ($includes) {
				$entity_code .= '
//Includes';
				
				foreach ($includes as $include) {
					$include_path = $include;
					$include_once = false;
					
					if (is_array($include)) {
						$include_path = $include["path"];
						$include_once = $include["once"];
					}
					
					if (trim($include_path))
						$entity_code .= '
include' . ($include_once ? '_once' : '') . ' ' . $include_path . ';';
				}
				
				$entity_code .= '
';
			}
			
			if ($template)
				$entity_code .= '
//Template
$EVC->setTemplate("' . $template . '");
';
			
			if ($template_params) {
				$entity_code .= '
//Template params:';
				
				foreach ($template_params as $tkey => $tp) {
					$name = $value = "";
					
					if (is_array($tp)) {
						$name = $tp["name"];
						$value = $tp["value"];
					}
					else if (!is_numeric($tkey)) {
						$name = $tkey;
						$value = $tp;
					}
					
					if ($name && strlen($value))
						$entity_code .= '
$EVC->getCMSLayer()->getCMSTemplateLayer()->setParam("' . $name . '", "' . $value . '");
';
				}
			}
			
			$entity_code .= '
//Regions-Blocks:';
			
			foreach ($regions_blocks as $rb) {
				$region = "Content";
				$block = $rb;
				$project = null;
				$project_code = "";
				
				if (is_array($rb)) {
					$region = $rb["region"];
					$block = $rb["block"];
					$project = $rb["project"];
					$project_code = $project ? ', "' . $project . '"' : '';
				}
				
				if ($region && $block)
					$entity_code .= '
$block_local_variables = array();
$EVC->getCMSLayer()->getCMSJoinPointLayer()->resetRegionBlockJoinPoints("' . $region . '", "' . $block . '");
$EVC->getCMSLayer()->getCMSTemplateLayer()->addRegionBlock("' . $region . '", "' . $block . '"' . $project_code . ');
include $EVC->getBlockPath("' . $block . '"' . $project_code . ');
';
			}
			
			$entity_code = trim($entity_code) . '
?>';
		}
		
		return $entity_code;
	}
	
	public static function saveEntityCode($PEVC, &$page_id, $entity_code, $overwrite, &$statuses) {
		if (!$page_id)
			return false;
		
		if (!$overwrite)
			CMSPresentationLayerHandler::configureUniqueFileId($page_id, $PEVC->getEntitiesPath(), "." . $PEVC->getPresentationLayer()->getPresentationFileExtension());
		
		return self::saveFileCode($PEVC->getEntityPath($page_id), $entity_code, $overwrite, $statuses);
	}
	
	public static function createAndSaveEntityCode($PEVC, &$page_id, $entity_settings, $overwrite, &$statuses) {
		$entity_code = self::getEntityCode($entity_settings);
		return self::saveEntityCode($PEVC, $page_id, $entity_code, $overwrite, $statuses);
	}

	public static function saveFileCode($file_path, $code, $overwrite, &$statuses) {
		$fp = dirname($file_path);
		if (!is_dir($fp))
			@mkdir($fp, 0755, true);
		
		if (!$overwrite) 
			CMSPresentationLayerHandler::configureUniqueFileId($file_path);
		
		$status = file_put_contents($file_path, $code) !== false && !empty($code);
		
		$statuses[$file_path] = $status;
		return $status;
	}
	
	/* DB FUNCTIONS */ 
	
	public static function isUserModuleInstalled($PEVC) {
		$user_module_installed_and_enabled = $PEVC->getCMSLayer()->getCMSModuleLayer()->existsModule("user/validate_user_activity");
		$installed = false;
		
		if ($user_module_installed_and_enabled && class_exists("UserUtil")) {
			$brokers = $PEVC->getPresentationLayer()->getBrokers();
			
			try {
				//check if the mu_user_type table exists in DB
				UserUtil::getAllUserTypes($brokers);
				$installed = true;
			}
			catch (Exception $e) {
				//do not show any exception, bc it means the mu_user_type table does not exist.
			}
		}
		
		return $installed;
	}
	
	public static function getAvailableUserTypes($PEVC) {
		$user_module_installed_and_enabled = $PEVC->getCMSLayer()->getCMSModuleLayer()->existsModule("user/validate_user_activity");
		$available_items = array();
		
		if ($user_module_installed_and_enabled && class_exists("UserUtil")) {
			$brokers = $PEVC->getPresentationLayer()->getBrokers();
			$items = UserUtil::getAllUserTypes($brokers);
			
			$t = $items ? count($items) : 0;
			for ($i = 0; $i < $t; $i++)
				$available_items[ $items[$i]["user_type_id"] ] = $items[$i]["name"];
		}
		
		return $available_items;
	}
	
	public static function getAvailableActivities($PEVC) {
		$user_module_installed_and_enabled = $PEVC->getCMSLayer()->getCMSModuleLayer()->existsModule("user/validate_user_activity");
		$available_items = array();
		
		if ($user_module_installed_and_enabled && class_exists("UserUtil")) {
			$brokers = $PEVC->getPresentationLayer()->getBrokers();
			$items = UserUtil::getAllActivities($brokers);
			
			$t = $items ? count($items) : 0;
			for ($i = 0; $i < $t; $i++)
				$available_items[ $items[$i]["activity_id"] ] = $items[$i]["name"];
		}
		
		return $available_items;
	}
	
	public static function reinsertReservedActivities($PEVC) {
		$user_module_installed_and_enabled = $PEVC->getCMSLayer()->getCMSModuleLayer()->existsModule("user/validate_user_activity");
		
		if ($user_module_installed_and_enabled && class_exists("UserUtil")) {
			$brokers = $PEVC->getPresentationLayer()->getBrokers();
			return UserUtil::reinsertReservedActivities($brokers);
		}
		
		return false;
	}
	
	public static function getActivityIdByName($PEVC, $name, $force = true) {
		$user_module_installed_and_enabled = $PEVC->getCMSLayer()->getCMSModuleLayer()->existsModule("user/validate_user_activity");
		$activity_id = null;
		
		if ($user_module_installed_and_enabled && class_exists("UserUtil")) {
			$brokers = $PEVC->getPresentationLayer()->getBrokers();
			$name = strtolower($name);
			$attrs = array("name" => $name);
			$activities = UserUtil::getActivitiesByConditions($brokers, $attrs, null);
			
			$activity_id = $activities ? $activities[0]["activity_id"] : null;
			
			if (!$activity_id && $force) {
				$reserved_activities = UserUtil::getReservedActivities();
				
				if (in_array($name, $reserved_activities))
					$activity_id = UserUtil::reinsertReservedActivities($brokers) ? array_search($name, $reserved_activities) : null;
				else
					$activity_id = UserUtil::insertActivity($brokers, $attrs);
			}
		}
		
		return $activity_id;
	}
	
	public static function getObjectTypeIdByName($PEVC, $name, $force = true) {
		$object_module_installed_and_enabled = $PEVC->getCMSLayer()->getCMSModuleLayer()->existsModule("object/edit_object_type");
		$object_type_id = null;
		
		if ($object_module_installed_and_enabled && class_exists("ObjectUtil")) {
			$brokers = $PEVC->getPresentationLayer()->getBrokers();
			$name = strtolower($name);
			$attrs = array("name" => $name);
			$object_types = ObjectUtil::getObjectTypesByConditions($brokers, $attrs, null);
			
			$object_type_id = $object_types ? $object_types[0]["object_type_id"] : null; 
			
			if (!$object_type_id && $force) {
				$reserved_object_types = ObjectUtil::getReservedObjectTypes();
				
				if (in_array($name, $reserved_object_types))
					$object_type_id = ObjectUtil::reinsertReservedObjectTypes($brokers) ? array_search($name, $reserved_object_types) : null;
				else
					$object_type_id = ObjectUtil::insertObjectType($brokers, $attrs);
			}
		}
		
		return $object_type_id;
	}
	
	public static function deleteUserTypeActivityObjects($PEVC, $activity_id, $object_type_id, $object_id) {
		$user_module_installed_and_enabled = $PEVC->getCMSLayer()->getCMSModuleLayer()->existsModule("user/validate_user_activity");
		
		if ($user_module_installed_and_enabled && class_exists("UserUtil")) {
			$brokers = $PEVC->getPresentationLayer()->getBrokers();
			return UserUtil::deleteUserTypeActivityObjectsByActivityIdAndObjectId($brokers, $activity_id, $object_type_id, $object_id);
		}
	}
	
	public static function insertUserTypeActivityObject($PEVC, $user_type_id, $activity_id, $object_type_id, $object_id) {
		$user_module_installed_and_enabled = $PEVC->getCMSLayer()->getCMSModuleLayer()->existsModule("user/validate_user_activity");
		
		if ($user_module_installed_and_enabled && class_exists("UserUtil")) {
			$brokers = $PEVC->getPresentationLayer()->getBrokers();
			$data = array("user_type_id" => $user_type_id, "activity_id" => $activity_id, "object_type_id" => $object_type_id, "object_id" => $object_id);
			return UserUtil::insertUserTypeActivityObject($brokers, $data);
		}
	}
	
	//Used in CMSPresentationUIDiagramFilesHandler.php
	public static function getUserTypeActivityObjectsByObject($PEVC, $object_type_id, $object_id) {
		$user_module_installed_and_enabled = $PEVC->getCMSLayer()->getCMSModuleLayer()->existsModule("user/validate_user_activity");
		
		if ($user_module_installed_and_enabled && class_exists("UserUtil")) {
			$brokers = $PEVC->getPresentationLayer()->getBrokers();
			$conditions = array("object_type_id" => $object_type_id, "object_id" => $object_id);
			return UserUtil::getUserTypeActivityObjectsByConditions($brokers, $conditions, null);
		}
	}
	
	public static function removeAllUserTypeActivitySessionsCache($PEVC) {
		$user_module_installed_and_enabled = $PEVC->getCMSLayer()->getCMSModuleLayer()->existsModule("user/validate_user_activity");
		
		if ($user_module_installed_and_enabled) {
			$EVC = $PEVC; //init EVC bc the module/user/UserSessionActivitiesHandler.php needs it.
			include_once $PEVC->getModulePath("user/UserSessionActivitiesHandler", $PEVC->getCommonProjectName());
			
			$UserSessionActivitiesHandler = new \UserSessionActivitiesHandler($PEVC);
			return $UserSessionActivitiesHandler->removeAllCache();
		}
		
		return false;
	}
}
?>
