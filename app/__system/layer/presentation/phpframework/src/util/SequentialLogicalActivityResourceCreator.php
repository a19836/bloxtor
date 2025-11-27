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

include_once $EVC->getUtilPath("CMSPresentationUIAutomaticFilesHandler");
include_once $EVC->getUtilPath("WorkFlowTasksFileHandler");
include_once $EVC->getUtilPath("SequentialLogicalActivityCodeConverter");
include_once $EVC->getUtilPath("SequentialLogicalActivityBLResourceCreator");

class SequentialLogicalActivityResourceCreator {
	
	private $EVC;
	private $PEVC;
	private $UserAuthenticationHandler;
	private $workflow_paths_id;
	private $webroot_cache_folder_path;
	private $webroot_cache_folder_url;
	private $user_global_variables_file_path;
	private $user_beans_folder_path;
	private $project_url_prefix;
	private $filter_by_layout;
	private $bean_name;
	private $bean_file_name;
	private $path;
	private $db_layer;
	private $db_broker;
	private $db_driver;
	private $db_type;
	private $db_table;
	private $db_table_alias;
	private $is_flush_cache;
	
	private $UserCacheHandler;
	private $PresentationLayer;
	private $include_db_driver;
	private $tables_ui_props;
	private $layer_brokers_settings;
	private $default_broker_path_to_filter;
	private $broker_path_to_filter;
	private $tables;
	private $WorkFlowTaskHandler;
	
	public function __construct($EVC, $PEVC, $UserAuthenticationHandler, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $user_global_variables_file_path, $user_beans_folder_path, $project_url_prefix, $filter_by_layout, $bean_name, $bean_file_name, $path, $db_broker, $db_driver, $db_type, $db_table, $db_table_alias, $no_cache = false) {
		$this->EVC = $EVC;
		$this->PEVC = $PEVC;
		$this->UserAuthenticationHandler = $UserAuthenticationHandler;
		$this->workflow_paths_id = $workflow_paths_id;
		$this->webroot_cache_folder_path = $webroot_cache_folder_path;
		$this->webroot_cache_folder_url = $webroot_cache_folder_url;
		$this->user_global_variables_file_path = $user_global_variables_file_path;
		$this->user_beans_folder_path = $user_beans_folder_path;
		$this->project_url_prefix = $project_url_prefix;
		$this->filter_by_layout = $filter_by_layout;
		$this->bean_name = $bean_name;
		$this->bean_file_name = $bean_file_name;
		$this->path = $path;
		$this->db_broker = $db_broker;
		$this->db_driver = $db_driver;
		$this->db_type = $db_type;
		$this->db_table = $db_table;
		$this->db_table_alias = $db_table_alias;
		$this->is_flush_cache = false;
		
		$this->UserCacheHandler = $EVC->getPresentationLayer()->getPHPFrameWork()->getObject("UserCacheHandler"); //Use EVC instead of PEVC, bc is relative to the __system admin panel
		$this->PresentationLayer = $PEVC->getPresentationLayer();
		$brokers = $this->PresentationLayer->getBrokers();
		$selected_presentation_id = $this->PresentationLayer->getSelectedPresentationId();
		
		$default_db_driver = isset($GLOBALS["default_db_driver"]) ? $GLOBALS["default_db_driver"] : null;
		$this->include_db_driver = $db_driver != $default_db_driver;
		
		//prepare filter_by_layout
		if ($this->filter_by_layout) {
			//only allow filter if really exists
			if (!$this->UserAuthenticationHandler->searchLayoutTypes(array("name" => $this->filter_by_layout, "type_id" => UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID)))
				$this->filter_by_layout = null;
			else
				$this->UserAuthenticationHandler->loadLayoutPermissions($this->filter_by_layout, UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID);
		}
		
		//prepare some default_broker_path for active_brokers
		$this->default_broker_path_to_filter = "resource/" . $this->db_driver . "/";
		
		if ($this->filter_by_layout) {
			$layer_folder_name = WorkFlowBeansFileHandler::getLayerObjFolderName($this->PresentationLayer);
			$this->broker_path_to_filter = substr($this->filter_by_layout, strlen($layer_folder_name) + 1) . "/";
		}
		else if ($selected_presentation_id)
			$this->broker_path_to_filter = $selected_presentation_id . "/";
		
		//prepare tables_ui_props
		$this->initTableUIProps($brokers, $no_cache);
		//echo "<pre>construct tables_ui_props:";print_r($this->tables_ui_props);die();
		
		//prepare layer_brokers_settings
		$this->layer_brokers_settings = WorkFlowBeansFileHandler::getLayerBrokersSettings($user_global_variables_file_path, $user_beans_folder_path, $brokers, '$EVC->getBroker');
		//echo "<pre>";print_r($layer_brokers_settings);die();
		
		//prepare tables
		$this->tables = $this->getTables();
		//print_r($tables);die();
		
		//prepare WorkFlowTaskHandler
		$allowed_tasks = array("callbusinesslogic", "callibatisquery", "callhibernatemethod", "getquerydata", "setquerydata", "dbdaoaction");
		$this->WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
		$this->WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
		$this->WorkFlowTaskHandler->setAllowedTaskTags($allowed_tasks);
		$this->WorkFlowTaskHandler->initWorkFlowTasks();
	}
	
	public function isFlushCache() {
		return $this->is_flush_cache;
	}
	
	public function getUtilFileId() {
		return "resource/" . $this->getUtilClassName();
	}
	
	public function getUtilFilePath() {
		return $this->PEVC->getUtilPath( $this->getUtilFileId() );
	}
	
	public function getUtilClassName() {
		return self::getClassName($this->db_table_alias ? $this->db_table_alias : $this->db_table) . "ResourceUtil";
	}
	
	public function getUtilMethodName($action_type) {
		return self::getMethodName($action_type);
	}
	
	public function getSLAResourceActions($action_type, $resource_name, $resource_data = null, $permissions = null) {
		$class_name = $this->getUtilClassName();
		$method_name = $this->getUtilMethodName($action_type);
		$file_id = $this->getUtilFileId();
		
		$calling_parameters = array();
		$resource_conditions = 'isset(\\$_GET["resource"]) && \\$_GET["resource"] == "' . $resource_name . '"';
		$resource_conditions_bkp = $resource_conditions;
		$resource_description = "";
		$create_unsuccessfully_resource = false;
		$conditions_exists = $resource_data && isset($resource_data["conditions"]) && is_array($resource_data["conditions"]);
		
		switch ($action_type) {
			case "insert": 
				//prepare resource to insert the attributes correspondent to db_table based in:
				//- $_POST[attributes] which are an array with attr_name:value format, this is: {attr_name_1:"...", attr_name_2:"..."}
				$calling_parameters = array(
					array("value" => '$EVC', "type" => ""),
					array("value" => '@$_POST["attributes"]', "type" => "")
				);
				$resource_conditions .= ' && !empty(\\$_POST)';
				$resource_description = "Insert data into table: " . $this->db_table . ".";
				$create_unsuccessfully_resource = true;
				break;
			case "update": 
				//prepare resource to update the attributes correspondent to db_table based in:
				//- $_POST[attributes] which are an array with attr_name:value format, this is: {table_pk_1:3, attr_name_1:"...", attr_name_2:"..."}
				//- $_POST[conditions] which are an array with pk:value format, this is: {table_pk_1:1, table_pk_2:1}
				$calling_parameters = array(
					array("value" => '$EVC', "type" => ""),
					array("value" => '@$_POST["attributes"]', "type" => ""),
					array("value" => '@$_POST["conditions"]', "type" => ""),
				);
				$resource_conditions .= ' && !empty(\\$_POST)';
				$resource_description = "Update data into table: " . $this->db_table . ".";
				$create_unsuccessfully_resource = true;
				break;
			case "save": 
				//prepare resource to save the attributes correspondent to db_table based in:
				//- $_POST[attributes] which are an array with attr_name:value format, this is: {table_pk_1:3, attr_name_1:"...", attr_name_2:"..."}
				//- $_POST[conditions] which are an array with pk:value format, this is: {table_pk_1:1, table_pk_2:1}. $_POST[conditions] are only mandatory if action is update, otherwise insert will be called.
				$calling_parameters = array(
					array("value" => '$EVC', "type" => ""),
					array("value" => '@$_POST["attributes"]', "type" => ""),
					array("value" => '@$_POST["conditions"]', "type" => ""),
				);
				$resource_conditions .= ' && !empty(\\$_POST)';
				$resource_description = "Save data into table: " . $this->db_table . ".";
				$create_unsuccessfully_resource = true;
				break;
			case "multiple_save": 
				//prepare resource to update multiple items correspondent to db_table based in:
				//- $_POST[attributes] which are an array with attr_name:value format, this is: [{table_pk_1:3, attr_name_1:"...", attr_name_2:"..."}, {attr_name_1:"...", attr_name_2: "..."}]
				//- $_POST[conditions] which are an array with pk:value format, this is: [{table_pk_1:1, table_pk_2:1}, {table_pk_1:2, table_pk_2: 2}]
				$calling_parameters = array(
					array("value" => '$EVC', "type" => ""),
					array("value" => '@$_POST["attributes"]', "type" => ""),
					array("value" => '@$_POST["conditions"]', "type" => ""),
				);
				$resource_conditions .= ' && !empty(\\$_POST)';
				$resource_description = "Update multiple records at once into table: " . $this->db_table . ".";
				$create_unsuccessfully_resource = true;
				break;
			case "update_attribute":
				//prepare resource to update a specific attribute correspondent to db_table based in:
				//- $_POST[attributes] which are an array with attr_name:value format, this is: {attr_name_1:"..."}
				//- $_POST[conditions] which are an array with pk:value format, this is: {table_pk_1:1, table_pk_2:1}
				$calling_parameters = array(
					array("value" => '$EVC', "type" => ""),
					array("value" => '@$_POST["attributes"]', "type" => ""),
					array("value" => '@$_POST["conditions"]', "type" => ""),
				);
				$resource_conditions .= ' && !empty(\\$_POST)';
				$resource_description = "Update an attribute from table: " . $this->db_table . ".";
				$create_unsuccessfully_resource = true;
				break;
			case "insert_update_attribute": 
				//prepare resource to insert or update a specific attribute correspondent to db_table based in:
				//- $_POST[attributes] which are an array with attr_name:value format, this is: {attr_name_1:"..."}
				//- $_POST[conditions] which are an array with pk:value format, this is: {table_pk_1:1, table_pk_2:1}
				$calling_parameters = array(
					array("value" => '$EVC', "type" => ""),
					array("value" => '@$_POST["attributes"]', "type" => ""),
					array("value" => '@$_POST["conditions"]', "type" => ""),
				);
				$resource_conditions .= ' && !empty(\\$_POST)';
				$resource_description = "Insert or update an attribute from table: " . $this->db_table . ".";
				$create_unsuccessfully_resource = true;
				break;
			case "insert_delete_attribute": 
				//prepare resource to insert or delete a specific record based if a attribute exists or not, correspondent to db_table based in:
				//- $_POST[attributes] which are an array with attr_name:value format, this is: {attr_name_1:"..."}
				//- $_POST[conditions] which are an array with pk:value format, this is: {table_pk_1:1, table_pk_2:1}
				$calling_parameters = array(
					array("value" => '$EVC', "type" => ""),
					array("value" => '@$_POST["attributes"]', "type" => ""),
					array("value" => '@$_POST["conditions"]', "type" => ""),
				);
				$resource_conditions .= ' && !empty(\\$_POST)';
				$resource_description = "Insert or delete a record based if a value from an attribute, from table: " . $this->db_table . ", exists or not.";
				$create_unsuccessfully_resource = true;
				break;
			case "multiple_insert_delete_attribute": 
				//prepare resource to delete previous records and insert new records based if a attribute exists or not, correspondent to db_table based in:
				//- $_POST[attributes] which are an array with attr_name:value format, this is: {attr_name_1:["..."]}
				//- $_POST[conditions] which are an array with pk:value format, this is: {table_pk_1:1, table_pk_2:1}
				$calling_parameters = array(
					array("value" => '$EVC', "type" => ""),
					array("value" => '@$_POST["attributes"]', "type" => ""),
					array("value" => '@$_POST["conditions"]', "type" => ""),
				);
				$resource_conditions .= ' && !empty(\\$_POST)';
				$resource_description = "Delete all records and insert new ones, based in an attribute, from table: " . $this->db_table . ".";
				$create_unsuccessfully_resource = true;
				break;
			case "delete": 
				//prepare resource to delete the attributes correspondent to db_table based in in:
				//- $_POST[conditions] which are an array with pk:value format, this is: {table_pk_1:1, table_pk_2:1}
				$calling_parameters = array(
					array("value" => '$EVC', "type" => ""),
					array("value" => '@$_POST["conditions"]', "type" => ""),
				);
				$resource_conditions .= ' && !empty(\\$_POST)';
				$resource_description = "Delete record from table: " . $this->db_table . ".";
				$create_unsuccessfully_resource = true;
				break;
			case "multiple_delete": 
				//prepare resource to delete multiple items correspondent to db_table based in:
				//- $_POST[conditions] which are an array with pk:value format, this is: [{table_pk_1:1, table_pk_2:1}, {table_pk_1:2, table_pk_2: 2}]
				$calling_parameters = array(
					array("value" => '$EVC', "type" => ""),
					array("value" => '@$_POST["conditions"]', "type" => ""),
				);
				$resource_conditions .= ' && !empty(\\$_POST)';
				$resource_description = "Delete multiple records at once from table: " . $this->db_table . ".";
				$create_unsuccessfully_resource = true;
				break;
			case "get": 
				//prepare resource to get an item correspondent to db_table based in $_GET[search_attrs] (key:value pair array)
				$calling_parameters = array(
					array("value" => '$EVC', "type" => ""),
					array("value" => $conditions_exists ? '#conditions#' : '@$_GET["search_attrs"]', "type" => $conditions_exists ? "string" : ""),
				);
				$resource_description = "Get a record from table: " . $this->db_table . ".";
				break;
			case "get_all": 
				//prepare resource to get items correspondent to db_table based in $_GET[search_attrs] (key:value pair array)
				$calling_parameters = array(
					array("value" => '$EVC', "type" => ""),
					array("value" => '@$_GET["items_limit_per_page"]', "type" => ""),
					array("value" => '@$_GET["page_items_start"]', "type" => ""),
					array("value" => $conditions_exists ? '#conditions#' : '@$_GET["search_attrs"]', "type" => $conditions_exists ? "string" : ""),
					array("value" => '@$_GET["search_types"]', "type" => ""),
					array("value" => '@$_GET["search_cases"]', "type" => ""),
					array("value" => $conditions_exists ? ($resource_data["conditions_join"] ? '#conditions_join#' : '') : '@$_GET["search_operators"]', "type" => $conditions_exists ? "string" : ""),
					array("value" => '@$_GET["sort_attrs"]', "type" => "")
				);
				$resource_description = "Get records from table: " . $this->db_table . ".";
				break;
			case "count": 
				//prepare resource to count items correspondent to db_table based in $_GET[search_attrs] (key:value pair array)
				$calling_parameters = array(
					array("value" => '$EVC', "type" => ""),
					array("value" => $conditions_exists ? '#conditions#' : '@$_GET["search_attrs"]', "type" => $conditions_exists ? "string" : ""),
					array("value" => '@$_GET["search_types"]', "type" => ""),
					array("value" => '@$_GET["search_cases"]', "type" => ""),
					array("value" => $conditions_exists ? ($resource_data["conditions_join"] ? '#conditions_join#' : '') : '@$_GET["search_operators"]', "type" => $conditions_exists ? "string" : ""),
				);
				$resource_description = "Count records from table: " . $this->db_table . ".";
				break;
			case "get_all_options":
				//prepare resource to get items correspondent to resource_data[table] where the pk (resource_data[attribute]) is the key and the "name|description" attribute is the value of the returned array. Note that the the resource_data[table] is optional, and if not passed we need to get the correspondent pk.
				$calling_parameters = array(
					array("value" => '$EVC', "type" => ""),
					array("value" => '@$_GET["items_limit_per_page"]', "type" => ""),
					array("value" => '@$_GET["page_items_start"]', "type" => ""),
					array("value" => $conditions_exists ? '#conditions#' : '@$_GET["search_attrs"]', "type" => $conditions_exists ? "string" : ""),
					array("value" => '@$_GET["search_types"]', "type" => ""),
					array("value" => '@$_GET["search_cases"]', "type" => ""),
					array("value" => $conditions_exists ? ($resource_data["conditions_join"] ? '#conditions_join#' : '') : '@$_GET["search_operators"]', "type" => $conditions_exists ? "string" : ""),
					array("value" => '@$_GET["sort_attrs"]', "type" => "")
				);
				$resource_description = "Get key-value pair list from table: " . $this->db_table . ", where the key is the table primary key and the value is the table attribute label.";
				break;
		}
		//echo "<pre>code:$code\n\nservice:";print_r($task);die();
		
		//prepare group action
		$group_action = array(
			"result_var_name" => $resource_name . "_group", 
			"action_type" => "group", 
			"condition_type" => $resource_conditions == $resource_conditions_bkp ? "execute_if_get_resource" : "execute_if_condition", 
			"condition_value" => $resource_conditions == $resource_conditions_bkp ? $resource_name : $resource_conditions,
			"action_description" => $resource_description,
			"action_value" => array(
				"group_name" => "",
				"actions" => array()
			)
		);
		
		//prepare permissions
		$permissions_exist = false;
		
		if ($permissions) {
			$access_id = CMSPresentationUIAutomaticFilesHandler::getActivityIdByName($this->PEVC, "access");
			$user_type_ids = $access_id ? self::getPermissionsUserTypeIds($permissions) : null;
			$resource_names = self::getPermissionsResourceNames($permissions);
			
			if ($user_type_ids) {
				$permissions_exist = true;
				$user_perms = array();
				
				foreach ($user_type_ids as $user_type_id)
					$user_perms[] = array(
						"user_type_id" => $user_type_id,
						"activity_id" => $access_id,
					);
				
				$group_action["action_value"]["actions"][] = array(
					"result_var_name" => "allowed", 
					"action_type" => "check_logged_user_permissions", 
					"condition_type" => "execute_always", 
					"condition_value" => "", 
					"action_value" => array(
						"all_permissions_checked" => 0,
						"entity_path" => '$entity_path',
						"logged_user_id" => '"" . (isset($GLOBALS["logged_user_id"]) ? $GLOBALS["logged_user_id"] : "")',
						"users_perms" => $user_perms,
					)
				);
			}
			
			if ($resource_names) {
				$permissions_exist = true;
				
				$group_action["action_value"]["actions"][] = array(
					"result_var_name" => "allowed", 
					"action_type" => "string", 
					"condition_type" => "execute_if_condition",
					"condition_value" => ($user_type_ids ? '\\$allowed && ' : "") . '\\$' . implode(' && \\$', $resource_names) . '',
					"action_value" => '1'
				);
			}
		}
		
		/* Include task is now inside of the callobjectmethod task
		//include util file
		$group_action["action_value"]["actions"][] = array(
			"result_var_name" => null, 
			"action_type" => "include_file", 
			"condition_type" => $permissions_exist ? "execute_if_var" : "execute_always",
			"condition_value" => $permissions_exist ? "allowed" : "",
			"action_value" => array(
				"path" => '$EVC->getUtilPath("' . $file_id . '")',
				"once" => true,
			)
		);*/
		
		//prepare conditions vars
		if ($conditions_exists) {
			$group_action["action_value"]["actions"][] = array(
				"result_var_name" => "conditions", 
				"action_type" => "array", 
				"condition_type" => "execute_always",
				"condition_value" => "",
				"action_value" => CMSPresentationFormSettingsUIHandler::convertFormSettingsArrayToJavascriptSettings($resource_data["conditions"])
			);
			
			if ($resource_data["conditions_join"])
				$group_action["action_value"]["actions"][] = array(
					"result_var_name" => "conditions_join", 
					"action_type" => "string", 
					"condition_type" => "execute_always",
					"condition_value" => "",
					"action_value" => $resource_data["conditions_join"]
				);
		}
		
		//call util method
		$group_action["action_value"]["actions"][] = array(
			"result_var_name" => $resource_name, 
			"action_type" => "callobjectmethod", 
			"condition_type" => $permissions_exist ? "execute_if_var" : "execute_always",
			"condition_value" => $permissions_exist ? "allowed" : "",
			"action_value" => array(
				"method_obj" => $class_name,
				"method_name" => $method_name,
				"method_static" => true,
				"method_args" => $calling_parameters,
				"include_file_path" => '$EVC->getUtilPath("' . $file_id . '")',
				"include_once" => true,
			)
		);
		
		if ($create_unsuccessfully_resource)
			$group_action["action_value"]["actions"][] = array(
				"result_var_name" => $resource_name . "_unsuccessfully", 
				"action_type" => "string", 
				"condition_type" => "execute_if_not_var",
				"condition_value" => $resource_name,
				"action_value" => 1
			);
		
		$actions = array($group_action);
		
		return $actions;
	}
	
	public function createUtilMethod($action_type, $resource_data, &$error_message) {
		$class_name = $this->getUtilClassName();
		$file_path = $this->getUtilFilePath();
		$file_exists = file_exists($file_path) && PHPCodePrintingHandler::getClassFromFile($file_path, $class_name);
		$file_method_exists = false;
		
		if (!$file_exists) 
			$file_exists = PHPCodePrintingHandler::addClassToFile($file_path, array(
				"name" => $class_name
			));
		
		if (!$file_exists)
			$error_message = "Error trying to create file '" . substr($file_path, strlen(LAYER_PATH)) . "'!";
		else {
			//$action_type = "get_all"; //only for testing
			$method_name = $this->getUtilMethodName($action_type);
			$file_method_exists = PHPCodePrintingHandler::getFunctionFromFile($file_path, $method_name, $class_name);
			
			//get available busines logic and ibatis services for db_table
			if (!$file_method_exists)
				switch ($action_type) {
					case "insert": 
						$file_method_exists = $this->createInsertMethod($action_type, $resource_data, $class_name, $file_path, $error_message);
						break;
					case "update": 
						$file_method_exists = $this->createUpdateMethod($action_type, $resource_data, $class_name, $file_path, $error_message);
						break;
					case "save": 
						$file_method_exists = $this->createSaveMethod($action_type, $resource_data, $class_name, $file_path, $error_message);
						break;
					case "multiple_save": 
						$file_method_exists = $this->createMultipleSaveMethod($action_type, $resource_data, $class_name, $file_path, $error_message);
						break;
					case "update_attribute": 
						$file_method_exists = $this->createUpdateAttributeMethod($action_type, $resource_data, $class_name, $file_path, $error_message);
						break;
					case "insert_update_attribute": 
						$file_method_exists = $this->createInsertUpdateAttributeMethod($action_type, $resource_data, $class_name, $file_path, $error_message);
						break;
					case "insert_delete_attribute": 
						$file_method_exists = $this->createInsertDeleteAttributeMethod($action_type, $resource_data, $class_name, $file_path, $error_message);
						break;
					case "multiple_insert_delete_attribute": 
						$file_method_exists = $this->createMultipleInsertDeleteAttributeMethod($action_type, $resource_data, $class_name, $file_path, $error_message);
						break;
					case "delete": 
						$file_method_exists = $this->createDeleteMethod($action_type, $resource_data, $class_name, $file_path, $error_message);
						break;
					case "multiple_delete": 
						$file_method_exists = $this->createMultipleDeleteMethod($action_type, $resource_data, $class_name, $file_path, $error_message);
						break;
					case "get": 
						$file_method_exists = $this->createGetMethod($action_type, $resource_data, $class_name, $file_path, $error_message);
						break;
					case "get_all": 
						$file_method_exists = $this->createGetAllMethod($action_type, $resource_data, $class_name, $file_path, $error_message);
						break;
					case "count": 
						$file_method_exists = $this->createCountMethod($action_type, $resource_data, $class_name, $file_path, $error_message);
						break;
					case "get_all_options":
						$file_method_exists = $this->createGetAllOptionsMethod($action_type, $resource_data, $class_name, $file_path, $error_message);
						break;
				}
		}
		
		return $file_method_exists;
	}
	
	/* PRIVATE FUNCTIONS */
	
	private function createGetLoggedUserIdMethod($class_name, $file_path) {
		$exists_logged_user_id_attribute = $this->containsLoggedUserIdAttribute();
		
		if (!$exists_logged_user_id_attribute) 
			return true;
		else {
			//prepare code
			$code = 'if (!empty($GLOBALS["logged_user_id"]))
	return $GLOBALS["logged_user_id"];

if (empty($GLOBALS["UserSessionActivitiesHandler"])) {
	@include_once $EVC->getUtilPath("user_session_activities_handler", $EVC->getCommonProjectName());
	
	if (function_exists("initUserSessionActivitiesHandler"))
		initUserSessionActivitiesHandler($EVC);
}

if (!empty($GLOBALS["UserSessionActivitiesHandler"])) {
	$user_data = $GLOBALS["UserSessionActivitiesHandler"]->getUserData();
	return $user_data && isset($user_data["user_id"]) ? $user_data["user_id"] : 0;
}

return 0;';
			
			//save resource util task
			$method_args = array('EVC' => null);
			$method_comments = "Get current logged user id.";
			
			return $this->addFunctionToFile($file_path, array(
				"name" => "getLoggedUserId",
				"static" => true,
				"arguments" => $method_args,
				"code" => $code,
				"comments" => $method_comments
			), $class_name);
		}
		
		return false;
	}
	
	private function createInsertMethod($action_type, $resource_data, $class_name, $file_path, &$error_message) {
		$task = $this->loadTask("insert");
		
		if (!$task) 
			$error_message = "Error: Couldn't find any service for $action_type action.";
		else {
			$task = $this->loadTaskParams($task);
			$task = $this->loadTaskParamsWithDefaultValues($task, '$attributes');
			$task = $this->resetTaskOptionNoCache($task);
			$broker_code = $this->getBrokerCode($task);
			$options_code = self::getBrokerSettingsOptionsCode($this->WorkFlowTaskHandler, $action_type, $task);
			if ($options_code) {
				$task["service"]["options"] = "options";
				$task["service"]["options_type"] = "variable";
			}
			else {
				unset($task["service"]["options"]);
				unset($task["service"]["options_type"]);
			}
			$task_code = $this->getTaskCode($action_type, $task, $broker_code, "\t");
			
			$attrs = WorkFlowDBHandler::getTableFromTables($this->tables, $this->db_table);
			
			if ($attrs) {
				$attributes_name = array_keys($attrs);
				$pks_auto_increment = array();
				
				foreach ($attrs as $attr_name => $attr)
					if (!empty($attr["primary_key"]) && WorkFlowDataAccessHandler::isAutoIncrementedAttribute($attr))
						$pks_auto_increment[] = $attr_name;
				
				//Add function to get th logged user id
				if (!self::createGetLoggedUserIdMethod($class_name, $file_path))
					$error_message = "Error: Couldn't create getLoggedUserId method in class: $class_name.";
				
				//prepare code
				$previous_code = self::getInsertActionPreviousCode($this->tables, $this->db_table, $attributes_name, $task, $this->WorkFlowTaskHandler, $broker_code, '$attributes');
				$previous_code = str_replace("\n", "\n\t", $previous_code);
				
				$next_code = self::getInsertActionNextCode($pks_auto_increment, $task, $this->WorkFlowTaskHandler, $broker_code, '$result');
				$next_code = str_replace("\n", "\n\t", $next_code);
				
				$code = 'if ($attributes) {
	' . $previous_code . '
	';
				
				if ($options_code) {
					$options_code = str_replace("\n", "\n\t", $options_code);
					$options_code = '$options = ' . $options_code . ';
	';
					$code .= $options_code;
				}
				
				$code .= '$result = ' . $task_code . ';
	' . $next_code . '
	
	return $result;
}';
				
				//prepare task in business logic layer
				if (isset($task["item_type"]) && $task["item_type"] == "businesslogic") {
					//echo "<pre>createInsertMethod \nbroker_code:$broker_code\ntask:";print_r($task);die();
					$bl_method_comments = "Insert parsed resource data into table: " . $this->db_table . ".";
					$bl_parameters = array('attributes');
					$this->prepareTaskBusinessLogicResourceService($action_type, $task, $broker_code, $options_code, $code, $bl_method_comments, $bl_parameters, "insert", $error_message);
				}
				
				//save resource util task
				$method_args = array('EVC' => null, 'attributes' => null, 'no_cache' => 'true');
				$method_comments = "Insert data into table: " . $this->db_table . ".";
				
				return $this->addFunctionToFile($file_path, array(
					"name" => "insert",
					"static" => true,
					"arguments" => $method_args,
					"code" => $code,
					"comments" => $method_comments
				), $class_name);
			}
		}
		
		return false;
	}

	private function createUpdateMethod($action_type, $resource_data, $class_name, $file_path, &$error_message) {
		$update_task = $this->loadTask("update");
		$update_pks_task = $this->loadTask("update_pks");
		$get_task = $this->loadTask("get");
		$common_options_code = null;
		
		if (!$update_task) 
			$error_message = "Error: Couldn't find any service for update action.";
		else if (!$update_pks_task) 
			$error_message = "Error: Couldn't find any service for update_pks action.";
		else if (!$get_task) 
			$error_message = "Error: Couldn't find any service for get action.";
		else {
			$update_task = $this->loadTaskParams($update_task);
			$update_task = $this->loadTaskParamsWithDefaultValues($update_task, '$data');
			$update_task = $this->resetTaskOptionNoCache($update_task);
			$update_broker_code = $this->getBrokerCode($update_task);
			
			$update_pks_task = $this->loadTaskParams($update_pks_task);
			$update_pks_task = $this->loadTaskParamsWithDefaultValues($update_pks_task, '$filtered_pks');
			$update_pks_task = $this->resetTaskOptionNoCache($update_pks_task);
			$update_pks_broker_code = $this->getBrokerCode($update_pks_task);
			
			$get_task = $this->loadTaskParams($get_task);
			$get_task = $this->loadTaskParamsWithDefaultValues($get_task, '$filtered_attributes');
			$get_task = $this->resetTaskOptionNoCache($get_task);
			$get_broker_code = $this->getBrokerCode($get_task);
			
			$attrs = WorkFlowDBHandler::getTableFromTables($this->tables, $this->db_table);
			
			if ($attrs) {
				//prepare options
				$common_options = $this->prepareBrokerSettingsCommonOptions($update_task, $update_pks_task, $get_task);
				//echo "<pre>";print_r($common_options);print_r($get_task);print_r($update_task);die();
				
				if ($common_options) {
					$dummy_task = $update_task;
					$dummy_task["service"]["options"] = $common_options;
					$dummy_task["service"]["options_type"] = "array";
					$common_options_code = self::getBrokerSettingsOptionsCode($this->WorkFlowTaskHandler, "update", $dummy_task);
				}
				
				$update_options_code = self::getBrokerSettingsOptionsCode($this->WorkFlowTaskHandler, "update", $update_task);
				if ($update_options_code || $common_options_code) {
					$update_task["service"]["options"] = $update_options_code ? "update_options" : "options";
					$update_task["service"]["options_type"] = "variable";
				}
				else {
					unset($update_task["service"]["options"]);
					unset($update_task["service"]["options_type"]);
				}
				$update_task_code = $this->getTaskCode($action_type, $update_task, $update_broker_code, "\t\t\t\t");
				
				$update_pks_options_code = self::getBrokerSettingsOptionsCode($this->WorkFlowTaskHandler, "update_pks", $update_pks_task);
				if ($update_pks_options_code || $common_options_code) {
					$update_pks_task["service"]["options"] = $update_pks_options_code ? "update_pks_options" : "options";
					$update_pks_task["service"]["options_type"] = "variable";
				}
				else {
					unset($update_pks_task["service"]["options"]);
					unset($update_pks_task["service"]["options_type"]);
				}
				$update_pks_task_code = $this->getTaskCode($action_type, $update_pks_task, $update_pks_broker_code, "\t\t\t\t");
				
				$get_options_code = self::getBrokerSettingsOptionsCode($this->WorkFlowTaskHandler, "get", $get_task);
				if ($get_options_code || $common_options_code) {
					$get_task["service"]["options"] = $get_options_code ? "get_options" : "options";
					$get_task["service"]["options_type"] = "variable";
				}
				else {
					unset($get_task["service"]["options"]);
					unset($get_task["service"]["options_type"]);
				}
				$get_task_code = $this->getTaskCode($action_type, $get_task, $get_broker_code, "\t\t\t\t");
				
				//prepare pks_name
				$attributes_name = array_keys($attrs);
				$pks_name = array();
				
				foreach ($attrs as $attr_name => $attr)
					if (!empty($attr["primary_key"]))
						$pks_name[] = $attr_name;
				
				$no_pks = empty($pks_name);
				
				//Add function to get th logged user id
				if (!self::createGetLoggedUserIdMethod($class_name, $file_path))
					$error_message = "Error: Couldn't create getLoggedUserId method in class: $class_name.";
				
				$attributes_previous_code = self::getUpdateActionPreviousCode($this->tables, $this->db_table, $attributes_name, $update_task, $this->WorkFlowTaskHandler, $update_broker_code, '$attributes');
				$attributes_previous_code = str_replace("\n", "\n\t", $attributes_previous_code);
				
				$pks_previous_code = self::getUpdateActionPreviousCode($this->tables, $this->db_table, $pks_name, $update_task, $this->WorkFlowTaskHandler, $update_broker_code, '$pks');
				$pks_previous_code = str_replace("\n", "\n\t", $pks_previous_code);
				
				//prepare options code
				if ($common_options_code) {
					$common_options_code = str_replace("\n", "\n\t\t\t\t", $common_options_code);
					$common_options_code = '$options = ' . $common_options_code . ';
				';
				}
				
				if ($get_options_code) {
					$get_options_code = str_replace("\n", "\n\t\t\t\t", $get_options_code);
					$get_options_code = '$get_options = ' . $get_options_code . ';
				';
					
					if ($common_options_code)
						$get_options_code .= '$get_options = array_merge($options, $get_options);
				';
				}
				
				if ($update_options_code) {
					$update_options_code = str_replace("\n", "\n\t\t\t\t", $update_options_code);
					$update_options_code = '$update_options = ' . $update_options_code . ';
				';
					
					if ($common_options_code)
						$update_options_code .= '$update_options = array_merge($options, $update_options);
				';
				}
				
				if ($update_pks_options_code) {
					$update_pks_options_code = str_replace("\n", "\n\t\t\t\t", $update_pks_options_code);
					$update_pks_options_code = '$update_pks_options = ' . $update_pks_options_code . ';
				';
						
					if ($common_options_code)
						$update_pks_options_code .= '$update_pks_options = array_merge($options, $update_pks_options);
				';
				}
				
				//prepare code
				if ($no_pks) { //if table has no pks
					$code = 'if ($attributes && $pks) {
	$status = true;
	
	' . $attributes_previous_code . '
	' . $pks_previous_code . '
	
	$filtered_attributes = $pks;
	
	if ($status) {
		//get the record from DB bc the $attributes may only have a few attributes, so we need to populate the other ones in order to call the broker->update method.
		';
						
					//prepare get task options
					$code .= str_replace("\n\t\t", "\n", $common_options_code);
					$code .= str_replace("\n\t\t", "\n", $get_options_code);
					
					//prepare get task code
					$code .= '$data = ' . $get_task_code . ';';
				
					if (isset($get_task["item_type"]) && ($get_task["item_type"] == "ibatis" || $get_task["item_type"] == "db"))
						$code .= '
		$data = $data ? $data[0] : null;';
					
					//prepare data code
					$code .= '
		
		if (!$data || !is_array($data))
			return false;
		
		foreach ($attributes as $attr_name => $attr_value)
			$data[$attr_name] = $attr_value;
		
		foreach ($pks as $pk_name => $pk_value) {
			$data["old_" . $pk_name] = $pk_value;
			$data["new_" . $pk_name] = $attributes[$pk_name];
			unset($data[$pk_name]);
		}
		
		' . str_replace("\n\t\t", "\n", $update_options_code) . '
		$status = ' . $update_task_code . ';
	}
	
	return $status;
}';
				}
				else {
					//prepare code for empty files
					$code_for_empty_files = '';
					
					foreach ($attrs as $attr_name => $attr)
						if (empty($attr["primary_key"]) && isset($attr["type"]) && ObjTypeHandler::isDBTypeBlob($attr["type"]))
							$code_for_empty_files .= '
				if (isset($filtered_attributes["' . $attr_name . '"]) && isset($data["' . $attr_name . '"]) && empty($_FILES["' . $attr_name . '"]["tmp_name"])) $filtered_attributes["' . $attr_name . '"] = $data["' . $attr_name . '"];';
					
					$code = 'if ($attributes && $pks) {
	$status = true;
	
	' . $attributes_previous_code . '
	' . $pks_previous_code . '
	
	if ($status) {
		//get new pks from $attributes and get $attributes without pks
		$update_pks = false;
		$filtered_pks = array();
		$filtered_attributes = array();
		
		foreach ($attributes as $attribute_name => $attribute_value) {
			if (array_key_exists($attribute_name, $pks)) {
				$filtered_pks["new_" . $attribute_name] = $attribute_value;
				
				if ($attribute_value != $pks[$attribute_name])
					$update_pks = true;
			}
			else
				$filtered_attributes[$attribute_name] = $attribute_value;
		}
		
		$status = $update_pks || $filtered_attributes;
		
		if ($status) {
			foreach ($pks as $pk_name => $pk_value) {
				if ($update_pks)
					$filtered_pks["old_" . $pk_name] = $pk_value;
				
				if ($filtered_attributes)
					$filtered_attributes[$pk_name] = $pk_value;
			}
			
			if ($filtered_attributes) {
				//get the record from DB bc the $attributes may only have a few attributes, so we need to populate the other ones in order to call the broker->update method.
				';
					
					//prepare get task options
					$code .= $common_options_code;
					$code .= $get_options_code;
					
					//prepare get task code
					$code .= '$data = ' . $get_task_code . ';';
				
					if (isset($get_task["item_type"]) && ($get_task["item_type"] == "ibatis" || $get_task["item_type"] == "db"))
						$code .= '
				$data = $data ? $data[0] : null;';
					
					//prepare data code
					$code .= '
				
				if (!$data || !is_array($data))
					return false;
				' . $code_for_empty_files . '
				
				foreach ($filtered_attributes as $attr_name => $attr_value)
					$data[$attr_name] = $attr_value;
				
				' . $update_options_code . '
				$status = ' . $update_task_code . ';
			}
			
			if ($status && $update_pks) {
				' . $update_pks_options_code . '
				$status = ' . $update_pks_task_code . ';
			}
		}
	}
	
	return $status;
}';
				}
				
				//prepare task in business logic layer
				$update_task["item_type"] = isset($update_task["item_type"]) ? $update_task["item_type"] : null;
				$update_pks_task["item_type"] = isset($update_pks_task["item_type"]) ? $update_pks_task["item_type"] : null;
				$get_task["item_type"] = isset($get_task["item_type"]) ? $get_task["item_type"] : null;
				
				if (($update_task["item_type"] == "businesslogic" || $update_pks_task["item_type"] == "businesslogic" || $get_task["item_type"] == "businesslogic") && $update_broker_code == $update_pks_broker_code && $update_pks_broker_code == $get_broker_code) {
					//echo "<pre>createUpdateMethod \nbroker_code:$broker_code\ntask:";print_r($task);die();
					$bl_method_comments = "Update parsed resource data into table: " . $this->db_table . ".";
					$bl_parameters = array('attributes', 'pks');
					$bl_code = $code;
					
					if ($update_task["item_type"] == "businesslogic")
						$bl_code = str_replace($update_broker_code, '$this->getBusinessLogicLayer()', $bl_code);
					
					if ($update_pks_task["item_type"] == "businesslogic")
						$bl_code = str_replace($update_pks_broker_code, '$this->getBusinessLogicLayer()', $bl_code);
					
					if ($get_task["item_type"] == "businesslogic")
						$bl_code = str_replace($get_broker_code, '$this->getBusinessLogicLayer()', $bl_code);
					
					$status = false;
					
					if ($update_task["item_type"] == "businesslogic")
						$status = $this->prepareTaskBusinessLogicResourceService($action_type, $update_task, $update_broker_code, $common_options_code, $bl_code, $bl_method_comments, $bl_parameters, "update", $error_message);
					else if ($update_pks_task["item_type"] == "businesslogic")
						$status = $this->prepareTaskBusinessLogicResourceService($action_type, $update_pks_task, $update_pks_broker_code, $common_options_code, $bl_code, $bl_method_comments, $bl_parameters, "update", $error_message);
					else if ($get_task["item_type"] == "businesslogic")
						$status = $this->prepareTaskBusinessLogicResourceService($action_type, $get_task, $get_broker_code, $common_options_code, $bl_code, $bl_method_comments, $bl_parameters, "update", $error_message);
					
					if ($status)
						$code = $bl_code;
				}
				
				//save resource util task
				$method_args = array('EVC' => null, 'attributes' => null, 'pks' => null, 'no_cache' => 'true');
				$method_comments = "Update data into table: " . $this->db_table . ".";
				
				return $this->addFunctionToFile($file_path, array(
					"name" => "update",
					"static" => true,
					"arguments" => $method_args,
					"code" => $code,
					"comments" => $method_comments
				), $class_name);
			}
		}
		
		return false;
	}
	
	private function createSaveMethod($action_type, $resource_data, $class_name, $file_path, &$error_message) {
		$insert_method_exists = PHPCodePrintingHandler::getFunctionFromFile($file_path, "insert", $class_name);
		$update_method_exists = PHPCodePrintingHandler::getFunctionFromFile($file_path, "update", $class_name);
		
		if (!$insert_method_exists)
			$insert_method_exists = $this->createInsertMethod("insert", $resource_data, $class_name, $file_path, $error_message);
		
		if (!$update_method_exists)
			$update_method_exists = $this->createUpdateMethod("update", $resource_data, $class_name, $file_path, $error_message);
		
		if (!$insert_method_exists)
			$error_message = "Error: Couldn't find any service for insert action.";
		else if (!$update_method_exists)
			$error_message = "Error: Couldn't find any service for update action.";
		else {
			//prepare code
			$code = '$status = empty($pks) ? self::insert($EVC, $attributes, $no_cache) : self::update($EVC, $attributes, $pks, $no_cache);

return $status;';
			
			//prepare task in business logic layer
			$task = $this->loadTask("update");
			
			if ($task && isset($task["item_type"]) && $task["item_type"] == "businesslogic") {
				//echo "<pre>createSaveMethod \nbroker_code:$broker_code\ntask:";print_r($task);die();
				$bl_method_name = $this->getTaskBusinessLogicResourceServiceClassMethodName($task);
				$bl_method_name = $bl_method_name ? $bl_method_name : "save";
				$bl_method_comments = "Save a record into table: " . $this->db_table . ".";
				$bl_code = '$pks = isset($data["pks"]) ? $data["pks"] : null;

$status = empty($pks) ? $this->insert($data) : $this->' . $bl_method_name . '($data);

return $status;';
				$bl_task = $task;
				$bl_task["service"]["service_id"] = isset($bl_task["service"]["service_id"]) ? $bl_task["service"]["service_id"] : null;
				$bl_task["service"]["service_id"] = strstr($bl_task["service"]["service_id"], ".", true) . ".save";
				$bl_task["service"]["method"] = "save";
				
				if ($this->createTaskBusinessLogicResourceService($bl_task, $bl_code, $bl_method_comments, $error_message)) {
					$bl_task = $this->convertTaskToBusinessLogicResourceService($bl_task);
					$bl_broker_code = $this->getBrokerCode($bl_task);
					$bl_task_code = $this->getTaskCode($action_type, $bl_task, $bl_broker_code);
					
					$exists_logged_user_id_attribute = $this->containsLoggedUserIdAttribute();
					
					$code = '$options = array(
	"no_cache" => $no_cache
);
$data = array(
	"attributes" => $attributes,
	"pks" => $pks,';
					
					if ($exists_logged_user_id_attribute)
						$code .= '
	"logged_user_id" => self::getLoggedUserId($EVC)';
					
					$code .= '
);
$result = ' . $bl_task_code . ';
return $result;';
				}
			}
			
			//save resource util task
			$method_args = array('EVC' => null, 'attributes' => null, 'pks' => null, 'no_cache' => 'true');
			$method_comments = "Save a record into table: " . $this->db_table . ".";
			
			return $this->addFunctionToFile($file_path, array(
				"name" => "save",
				"static" => true,
				"arguments" => $method_args,
				"code" => $code,
				"comments" => $method_comments
			), $class_name);
		}
		
		return false;
	}
	
	private function createMultipleSaveMethod($action_type, $resource_data, $class_name, $file_path, &$error_message) {
		$insert_method_exists = PHPCodePrintingHandler::getFunctionFromFile($file_path, "insert", $class_name);
		$update_method_exists = PHPCodePrintingHandler::getFunctionFromFile($file_path, "update", $class_name);
		
		if (!$insert_method_exists)
			$insert_method_exists = $this->createInsertMethod("insert", $resource_data, $class_name, $file_path, $error_message);
		
		if (!$update_method_exists)
			$update_method_exists = $this->createUpdateMethod("update", $resource_data, $class_name, $file_path, $error_message);
		
		if (!$insert_method_exists)
			$error_message = "Error: Couldn't find any service for insert action.";
		else if (!$update_method_exists)
			$error_message = "Error: Couldn't find any service for update action.";
		else {
			//prepare code
			$code = '$status = true;

if ($attributes)
	for ($i = 0, $t = count($attributes); $i < $t; $i++) {
		$item_attributes = $attributes[$i];
		$item_pks = isset($pks[$i]) ? $pks[$i] : null;
		$is_insert = empty($item_pks);
		
		if ($is_insert && !self::insert($EVC, $item_attributes, $no_cache))
			$status = false;
		else if (!$is_insert && !self::update($EVC, $item_attributes, $item_pks, $no_cache))
			$status = false;
	}

return $status;';
			
			//prepare task in business logic layer
			$task = $this->loadTask("update");
			
			if ($task && isset($task["item_type"]) && $task["item_type"] == "businesslogic") {
				//echo "<pre>createMultipleSaveMethod \nbroker_code:$broker_code\ntask:";print_r($task);die();
				$bl_method_name = $this->getTaskBusinessLogicResourceServiceClassMethodName($task);
				$bl_method_name = $bl_method_name ? $bl_method_name : "update";
				$bl_method_comments = "Save multiple records at once parsed resource record into table: " . $this->db_table . ".";
				$bl_code = '$status = true;

$pks = isset($data["pks"]) ? $data["pks"] : null;
$attributes = isset($data["attributes"]) ? $data["attributes"] : null;

if ($attributes)
	for ($i = 0, $t = count($attributes); $i < $t; $i++) {
		$data["attributes"] = $attributes[$i];
		$data["pks"] = isset($pks[$i]) ? $pks[$i] : null;
		$is_insert = empty($pks[$i]);
		
		if ($is_insert && !$this->insert($data))
			$status = false;
		else if (!$is_insert && !$this->' . $bl_method_name . '($data))
			$status = false;
	}

return $status;';
				$bl_task = $task;
				$bl_task["service"]["service_id"] = isset($bl_task["service"]["service_id"]) ? $bl_task["service"]["service_id"] : null;
				$bl_task["service"]["service_id"] = strstr($bl_task["service"]["service_id"], ".", true) . ".multipleSave";
				$bl_task["service"]["method"] = "multipleSave";
				
				if ($this->createTaskBusinessLogicResourceService($bl_task, $bl_code, $bl_method_comments, $error_message)) {
					$bl_task = $this->convertTaskToBusinessLogicResourceService($bl_task);
					$bl_broker_code = $this->getBrokerCode($bl_task);
					$bl_task_code = $this->getTaskCode($action_type, $bl_task, $bl_broker_code);
					
					$exists_logged_user_id_attribute = $this->containsLoggedUserIdAttribute();
					
					$code = '$options = array(
	"no_cache" => $no_cache
);
$data = array(
	"attributes" => $attributes,
	"pks" => $pks,';
					
					if ($exists_logged_user_id_attribute)
						$code .= '
	"logged_user_id" => self::getLoggedUserId($EVC)';
					
					$code .= '
);
$result = ' . $bl_task_code . ';
return $result;';
				}
			}
			
			//save resource util task
			$method_args = array('EVC' => null, 'attributes' => null, 'pks' => null, 'no_cache' => 'true');
			$method_comments = "Save multiple records at once into table: " . $this->db_table . ".";
			
			return $this->addFunctionToFile($file_path, array(
				"name" => "multipleSave",
				"static" => true,
				"arguments" => $method_args,
				"code" => $code,
				"comments" => $method_comments
			), $class_name);
		}
		
		return false;
	}

	private function createUpdateAttributeMethod($action_type, $resource_data, $class_name, $file_path, &$error_message) {
		$get_task = $this->loadTask("get");
		$update_task = $this->loadTask("update");
		
		if (!$get_task) 
			$error_message = "Error: Couldn't find any service for get action.";
		else if (!$update_task) 
			$error_message = "Error: Couldn't find any service for update action.";
		else {
			$get_task = $this->loadTaskParams($get_task);
			$get_task = $this->loadTaskParamsWithDefaultValues($get_task, '$pks');
			$get_task = $this->resetTaskOptionNoCache($get_task);
			$get_broker_code = $this->getBrokerCode($get_task);
			
			$update_task = $this->loadTaskParams($update_task);
			$update_task = $this->loadTaskParamsWithDefaultValues($update_task, '$data');
			$update_task = $this->resetTaskOptionNoCache($update_task);
			$update_broker_code = $this->getBrokerCode($update_task);
			
			$attrs = WorkFlowDBHandler::getTableFromTables($this->tables, $this->db_table);
			
			if ($attrs) {
				//prepare options
				//echo "<pre>";print_r($update_task);die();
				$common_options = $this->prepareBrokerSettingsCommonOptions($get_task, $update_task);
				//echo "<pre>";print_r($common_options);print_r($get_task);print_r($update_task);die();
				$common_options_code = null;
				
				if ($common_options) {
					$dummy_task = $update_task;
					$dummy_task["service"]["options"] = $common_options;
					$dummy_task["service"]["options_type"] = "array";
					$common_options_code = self::getBrokerSettingsOptionsCode($this->WorkFlowTaskHandler, "update", $dummy_task);
				}
				
				$get_options_code = self::getBrokerSettingsOptionsCode($this->WorkFlowTaskHandler, "get", $get_task);
				if ($get_options_code || $common_options_code) {
					$get_task["service"]["options"] = $get_options_code ? "get_options" : "options";
					$get_task["service"]["options_type"] = "variable";
				}
				else {
					unset($get_task["service"]["options"]);
					unset($get_task["service"]["options_type"]);
				}
				$get_task_code = $this->getTaskCode($action_type, $get_task, $get_broker_code, "\t\t");
				
				$update_options_code = self::getBrokerSettingsOptionsCode($this->WorkFlowTaskHandler, "update", $update_task);
				if ($update_options_code || $common_options_code) {
					$update_task["service"]["options"] = $update_options_code ? "update_options" : "options";
					$update_task["service"]["options_type"] = "variable";
				}
				else {
					unset($update_task["service"]["options"]);
					unset($update_task["service"]["options_type"]);
				}
				$update_task_code = $this->getTaskCode($action_type, $update_task, $update_broker_code, "\t\t\t");
				
				//prepare pks_name
				$attributes_name = array_keys($attrs);
				$pks_name = array();
				
				foreach ($attrs as $attr_name => $attr)
					if (!empty($attr["primary_key"]))
						$pks_name[] = $attr_name;
				
				$no_pks = empty($pks_name);
				
				//Add function to get th logged user id
				if (!self::createGetLoggedUserIdMethod($class_name, $file_path))
					$error_message = "Error: Couldn't create getLoggedUserId method in class: $class_name.";
				
				//prepare code
				$attributes_previous_code = self::getUpdateActionPreviousCode($this->tables, $this->db_table, $attributes_name, $update_task, $this->WorkFlowTaskHandler, $update_broker_code, '$attributes', true);
				$attributes_previous_code = str_replace("\n", "\n\t", $attributes_previous_code);
				
				$pks_previous_code = self::getUpdateActionPreviousCode($this->tables, $this->db_table, $pks_name, $update_task, $this->WorkFlowTaskHandler, $update_broker_code, '$pks', true);
				$pks_previous_code = str_replace("\n", "\n\t", $pks_previous_code);
				
				//to be shown after we get the Data from the DB. Is very important to show this before we execute the update task, bc we need to check the attributes that come from the DB, since some of them may contain wrong values, this is, if a numeric attribute returns null from the DB, but then the update task is IBATIS and contains a sql query with a numeric hashtag without quotes, then we will replace that hashtag by an empty string, which gives a sql error.
				if (isset($update_task["item_type"]) && $update_task["item_type"] == "ibatis") { 
					$attributes_next_code = self::getUpdateActionPreviousCode($this->tables, $this->db_table, $attributes_name, $update_task, $this->WorkFlowTaskHandler, $update_broker_code, '$data', false);
					$attributes_next_code = str_replace("\n", "\n\t\t", $attributes_next_code);
				}
				else
					$attributes_next_code = "";
				
				$code = 'if ($attributes && $pks) {
	$status = true;
	
	' . $attributes_previous_code . '
	' . $pks_previous_code . '
	
	if ($status) {
		';
				
				if ($common_options_code) {
					$common_options_code = str_replace("\n", "\n\t\t", $common_options_code);
					$common_options_code = '$options = ' . $common_options_code . ';
		';
					$code .= $common_options_code;
				}
				
				if ($get_options_code) {
					$get_options_code = str_replace("\n", "\n\t\t", $get_options_code);
					$get_options_code = '$get_options = ' . $get_options_code . ';
		';
					
					if ($common_options_code)
						$get_options_code .= '$get_options = array_merge($options, $get_options);
		';
					
					$code .= $get_options_code;
				}
				
				//prepare code for empty files
				$code_for_empty_files = '';
				
				foreach ($attrs as $attr_name => $attr)
					if (empty($attr["primary_key"]) && isset($attr["type"]) && ObjTypeHandler::isDBTypeBlob($attr["type"]))
						$code_for_empty_files .= '
		if (isset($attributes["' . $attr_name . '"]) && isset($data["' . $attr_name . '"]) && empty($_FILES["' . $attr_name . '"]["tmp_name"])) $attributes["' . $attr_name . '"] = $data["' . $attr_name . '"];';
				
				
				//prepare get data code
				$code .= '$data = ' . $get_task_code . ';';
			
				if (isset($get_task["item_type"]) && ($get_task["item_type"] == "ibatis" || $get_task["item_type"] == "db"))
					$code .= '
		$data = $data ? $data[0] : null;';
				
				//prepare data code
				$code .= '
	
		if (!$data || !is_array($data))
			return false;
		' . $code_for_empty_files . '
		
		foreach ($attributes as $attribute_name => $attribute_value)
			$data[$attribute_name] = $attribute_value;
	
		' . $attributes_next_code . '
		
		if ($status) {
			';
				
				if ($no_pks)
					$code .= 'foreach ($pks as $pk_name => $pk_value) {
				$data["old_" . $pk_name] = $pk_value;
				$data["new_" . $pk_name] = isset($attributes[$pk_name]) ? $attributes[$pk_name] : null;
				unset($data[$pk_name]);
			}
			
			';
				
				if ($update_options_code) {
					$update_options_code = str_replace("\n", "\n\t\t", $update_options_code);
					$update_options_code = '$update_options = ' . $update_options_code . ';
			';
					
					if ($common_options_code)
						$update_options_code .= '$update_options = array_merge($options, $update_options);
			';
					
					$code .= $update_options_code;
				}
				
				$code .= '$status = ' . $update_task_code . ';
		}
	}
	
	return $status;
}';
				
				//prepare task in business logic layer
				$get_task["item_type"] = isset($get_task["item_type"]) ? $get_task["item_type"] : null;
				$update_task["item_type"] = isset($update_task["item_type"]) ? $update_task["item_type"] : null;
				
				if (($get_task["item_type"] == "businesslogic" || $update_task["item_type"] == "businesslogic") && $get_broker_code == $update_broker_code) {
					//echo "<pre>createUpdateAttributeMethod \nbroker_code:$broker_code\ntask:";print_r($task);die();
					$bl_method_comments = "Update a parsed resource attribute from table: " . $this->db_table . ".";
					$bl_parameters = array('attributes', 'pks');
					$bl_code = $code;
					
					if ($get_task["item_type"] == "businesslogic")
						$bl_code = str_replace($get_broker_code, '$this->getBusinessLogicLayer()', $bl_code);
					
					if ($update_task["item_type"] == "businesslogic")
						$bl_code = str_replace($update_broker_code, '$this->getBusinessLogicLayer()', $bl_code);
					
					$status = false;
					
					if ($get_task["item_type"] == "businesslogic")
						$status = $this->prepareTaskBusinessLogicResourceService($action_type, $get_task, $get_broker_code, $common_options_code, $bl_code, $bl_method_comments, $bl_parameters, "updateAttribute", $error_message);
					else if ($update_task["item_type"] == "businesslogic")
						$status = $this->prepareTaskBusinessLogicResourceService($action_type, $update_task, $update_broker_code, $common_options_code, $bl_code, $bl_method_comments, $bl_parameters, "updateAttribute", $error_message);
					
					if ($status)
						$code = $bl_code;
				}
				
				//save resource util task
				$method_args = array('EVC' => null, 'attributes' => null, 'pks' => null, 'no_cache' => 'true');
				$method_comments = "Update an attribute from table: " . $this->db_table . ".";
				
				return $this->addFunctionToFile($file_path, array(
					"name" => "updateAttribute",
					"static" => true,
					"arguments" => $method_args,
					"code" => $code,
					"comments" => $method_comments
				), $class_name);
			}
		}
		
		return false;
	}

	private function createInsertUpdateAttributeMethod($action_type, $resource_data, $class_name, $file_path, &$error_message) {
		$get_method_exists = PHPCodePrintingHandler::getFunctionFromFile($file_path, "get", $class_name);
		$insert_method_exists = PHPCodePrintingHandler::getFunctionFromFile($file_path, "insert", $class_name);
		$update_attribute_method_exists = PHPCodePrintingHandler::getFunctionFromFile($file_path, "updateAttribute", $class_name);
		
		if (!$get_method_exists)
			$get_method_exists = $this->createGetMethod("get", $resource_data, $class_name, $file_path, $error_message);
		
		if (!$insert_method_exists)
			$insert_method_exists = $this->createInsertMethod("insert", $resource_data, $class_name, $file_path, $error_message);
		
		if (!$update_attribute_method_exists)
			$update_attribute_method_exists = $this->createUpdateAttributeMethod("update_attribute", $resource_data, $class_name, $file_path, $error_message);
		
		if (!$get_method_exists)
			$error_message = "Error: Couldn't find any service for get action.";
		else if (!$insert_method_exists)
			$error_message = "Error: Couldn't find any service for insert action.";
		else if (!$update_attribute_method_exists)
			$error_message = "Error: Couldn't find any service for update_attribute action.";
		else {
			$code = '$item_data = self::get($EVC, $pks, $no_cache);

if (!empty($item_data))
	return self::updateAttribute($EVC, $attributes, $pks, $no_cache);

if (is_array($pks))
	$attributes = is_array($attributes) ? array_merge($attributes, $pks) : $pks;

return self::insert($EVC, $attributes, $no_cache);';
			
			//prepare task in business logic layer
			$task = $this->loadTask("update");
			
			if ($task && isset($task["item_type"]) && $task["item_type"] == "businesslogic") {
				//echo "<pre>createInsertUpdateAttributeMethod \nbroker_code:$broker_code\ntask:";print_r($task);die();
				$bl_method_comments = "Insert or update an attribute from table: " . $this->db_table . ".";
				$bl_code = '$item_data = $this->get($data);

if (!empty($item_data))
	return $this->updateAttribute($data);

if (isset($data["pks"]) && is_array($data["pks"]))
	$data["attributes"] = isset($data["attributes"]) && is_array($data["attributes"]) ? array_merge($data["attributes"], $data["pks"]) : $data["pks"];

return $this->insert($data);';
				
				$bl_task = $task;
				$bl_task["service"]["service_id"] = isset($bl_task["service"]["service_id"]) ? $bl_task["service"]["service_id"] : null;
				$bl_task["service"]["service_id"] = strstr($bl_task["service"]["service_id"], ".", true) . ".insertUpdateAttribute";
				$bl_task["service"]["method"] = "insertUpdateAttribute";
				
				if ($this->createTaskBusinessLogicResourceService($bl_task, $bl_code, $bl_method_comments, $error_message)) {
					$bl_task = $this->convertTaskToBusinessLogicResourceService($bl_task);
					$bl_broker_code = $this->getBrokerCode($bl_task);
					$bl_task_code = $this->getTaskCode($action_type, $bl_task, $bl_broker_code);
					
					$exists_logged_user_id_attribute = $this->containsLoggedUserIdAttribute();
					
					$code = '$options = array(
	"no_cache" => $no_cache
);
$data = array(
	"attributes" => $attributes,
	"pks" => $pks,';
					
					if ($exists_logged_user_id_attribute)
						$code .= '
	"logged_user_id" => self::getLoggedUserId($EVC)';
					
					$code .= '
);
$result = ' . $bl_task_code . ';
return $result;';
				}
			}
			
			//save resource util task
			$method_args = array('EVC' => null, 'attributes' => null, 'pks' => null, 'no_cache' => 'true');
			$method_comments = "Insert or update an attribute from table: " . $this->db_table . ".";
			
			return $this->addFunctionToFile($file_path, array(
				"name" => "insertUpdateAttribute",
				"static" => true,
				"arguments" => $method_args,
				"code" => $code,
				"comments" => $method_comments
			), $class_name);
		}
		
		return false;
	}

	private function createInsertDeleteAttributeMethod($action_type, $resource_data, $class_name, $file_path, &$error_message) {
		$get_method_exists = PHPCodePrintingHandler::getFunctionFromFile($file_path, "get", $class_name);
		$insert_method_exists = PHPCodePrintingHandler::getFunctionFromFile($file_path, "insert", $class_name);
		$delete_method_exists = PHPCodePrintingHandler::getFunctionFromFile($file_path, "delete", $class_name);
		
		if (!$get_method_exists)
			$get_method_exists = $this->createGetMethod("get", $resource_data, $class_name, $file_path, $error_message);
		
		if (!$insert_method_exists)
			$insert_method_exists = $this->createInsertMethod("insert", $resource_data, $class_name, $file_path, $error_message);
		
		if (!$delete_method_exists)
			$delete_method_exists = $this->createDeleteMethod("delete", $resource_data, $class_name, $file_path, $error_message);
		
		if (!$get_method_exists)
			$error_message = "Error: Couldn't find any service for get action.";
		else if (!$insert_method_exists)
			$error_message = "Error: Couldn't find any service for insert action.";
		else if (!$delete_method_exists)
			$error_message = "Error: Couldn't find any service for delete action.";
		else {
			$code = '$exists = false;

//note that the $attributes should only have 1 attribute name based in the html element that this action was called.
if (is_array($attributes))
	foreach ($attributes as $attr_name => $attr_value)
		if ($attr_value) {
			$exists = true;
			break;
		}
	
$item_data = self::get($EVC, $pks, $no_cache);

if (!empty($item_data))
	return $exists || self::delete($EVC, $pks, $no_cache);

if (is_array($pks))
	$attributes = is_array($attributes) ? array_merge($attributes, $pks) : $pks;

return !$exists || self::insert($EVC, $attributes, $no_cache);';
			
			//prepare task in business logic layer
			$task = $this->loadTask("insert");
			
			if ($task && isset($task["item_type"]) && $task["item_type"] == "businesslogic") {
				//echo "<pre>createInsertDeleteAttributeMethod \nbroker_code:$broker_code\ntask:";print_r($task);die();
				$bl_method_name = $this->getTaskBusinessLogicResourceServiceClassMethodName($task);
				$bl_method_name = $bl_method_name ? $bl_method_name : "insert";
				$bl_method_comments = "Insert or delete a record based if a value from an attribute, from table: " . $this->db_table . ", exists or not.";
				$bl_code = '$exists = false;

//note that the $data["attributes"] should only have 1 attribute name based in the html element that this action was called.
if (isset($data["attributes"]) && is_array($data["attributes"]))
	foreach ($data["attributes"] as $attr_name => $attr_value)
		if ($attr_value) {
			$exists = true;
			break;
		}
	
$item_data = $this->get($data);

if (!empty($item_data))
	return $exists || $this->delete($data);

if (isset($data["pks"]) && is_array($data["pks"]))
	$data["attributes"] = isset($data["attributes"]) && is_array($data["attributes"]) ? array_merge($data["attributes"], $data["pks"]) : $data["pks"];

return !$exists || $this->' . $bl_method_name . '($data);';
				
				$bl_task = $task;
				$bl_task["service"]["service_id"] = isset($bl_task["service"]["service_id"]) ? $bl_task["service"]["service_id"] : null;
				$bl_task["service"]["service_id"] = strstr($bl_task["service"]["service_id"], ".", true) . ".insertDeleteAttribute";
				$bl_task["service"]["method"] = "insertDeleteAttribute";
				
				if ($this->createTaskBusinessLogicResourceService($bl_task, $bl_code, $bl_method_comments, $error_message)) {
					$bl_task = $this->convertTaskToBusinessLogicResourceService($bl_task);
					$bl_broker_code = $this->getBrokerCode($bl_task);
					$bl_task_code = $this->getTaskCode($action_type, $bl_task, $bl_broker_code);
					
					$exists_logged_user_id_attribute = $this->containsLoggedUserIdAttribute();
					
					$code = '$options = array(
	"no_cache" => $no_cache
);
$data = array(
	"attributes" => $attributes,
	"pks" => $pks,';
					
					if ($exists_logged_user_id_attribute)
						$code .= '
	"logged_user_id" => self::getLoggedUserId($EVC)';
					
					$code .= '
);
$result = ' . $bl_task_code . ';
return $result;';
				}
			}
			
			//save resource util task
			$method_args = array('EVC' => null, 'attributes' => null, 'pks' => null, 'no_cache' => 'true');
			$method_comments = "Insert or delete a record based if a value from an attribute, from table: " . $this->db_table . ", exists or not.";
			
			return $this->addFunctionToFile($file_path, array(
				"name" => "insertDeleteAttribute",
				"static" => true,
				"arguments" => $method_args,
				"code" => $code,
				"comments" => $method_comments
			), $class_name);
		}
		
		return false;
	}

	private function createMultipleInsertDeleteAttributeMethod($action_type, $resource_data, $class_name, $file_path, &$error_message) {
		$task = $this->loadTask("delete_all");
		$insert_method_exists = PHPCodePrintingHandler::getFunctionFromFile($file_path, "insert", $class_name);
		
		if (!$insert_method_exists)
			$insert_method_exists = $this->createInsertMethod("insert", $resource_data, $class_name, $file_path, $error_message);
		
		if (!$task)
			$error_message = "Error: Couldn't find any service for delete_all action.";
		else if (!$insert_method_exists)
			$error_message = "Error: Couldn't find any service for insert action.";
		else {
			$task = $this->loadTaskParams($task);
			$task = $this->loadTaskParamsWithDefaultValues($task, '$delete_data');
			$task = $this->resetTaskOptionNoCache($task);
			$broker_code = $this->getBrokerCode($task);
			$options_code = self::getBrokerSettingsOptionsCode($this->WorkFlowTaskHandler, $action_type, $task);
			if ($options_code) {
				$task["service"]["options"] = "options";
				$task["service"]["options_type"] = "variable";
			}
			else {
				unset($task["service"]["options"]);
				unset($task["service"]["options_type"]);
			}
			$task_code = $this->getTaskCode($action_type, $task, $broker_code, "");
			
			$code = '';
			
			if ($options_code) {
				$options_code = '$options = ' . $options_code . ';
';
				$code .= $options_code;
			}
			
			$code .= '$delete_data = array(
	"conditions" => $pks
);
' . $task_code . ';

$items = array();

//note that the $attributes should only have 1 attribute name based in the html element that this action was called.
if (is_array($attributes))
	foreach ($attributes as $attr_name => $attr_value)
		if ($attr_value) {
			$item = $pks;
			
			if (is_array($attr_value)) {
				for ($i = 0, $t = count($attr_value); $i < $t; $i++) 
					if ($attr_value[$i] || is_numeric($attr_value[$i])) {
						$item[$attr_name] = $attr_value[$i];
						$items[] = $item;
					}
			}
			else if ($attr_value || is_numeric($attr_value)) {
				$item[$attr_name] = $attr_value;
				$items[] = $item;
			}
		}

$status = true;

if (!empty($items))
	for ($i = 0, $t = count($items); $i < $t; $i++)
		if (!self::insert($EVC, $items[$i], $no_cache))
			$status = false;

return $status;';
			
			//prepare task in business logic layer
			if ($task && isset($task["item_type"]) && $task["item_type"] == "businesslogic") {
				//echo "<pre>createMultipleInsertDeleteAttributeMethod \nbroker_code:$broker_code\ntask:";print_r($task);die();
				$bl_method_comments = "Delete all records and insert new ones, based in an attribute, from table: " . $this->db_table . ", exists or not.";
				$bl_code = str_replace('self::insert($EVC, $items[$i], $no_cache)', '$this->insert(array("attributes" => $items[$i], "options" => $options))', $code);
				$bl_parameters = array('attributes', 'pks');
				$status = $this->prepareTaskBusinessLogicResourceService($action_type, $task, $broker_code, $options_code, $bl_code, $bl_method_comments, $bl_parameters, "multipleInsertDeleteAttribute", $error_message);
				
				if ($status)
					$code = $bl_code;
			}
			
			//save resource util task
			$method_args = array('EVC' => null, 'attributes' => null, 'pks' => null, 'no_cache' => 'true');
			$method_comments = "Delete all records and insert new ones, based in an attribute, from table: " . $this->db_table . ", exists or not.";
			
			return $this->addFunctionToFile($file_path, array(
				"name" => "multipleInsertDeleteAttribute",
				"static" => true,
				"arguments" => $method_args,
				"code" => $code,
				"comments" => $method_comments
			), $class_name);
		}
		
		return false;
	}

	private function createDeleteMethod($action_type, $resource_data, $class_name, $file_path, &$error_message) {
		$task = $this->loadTask("delete");
		
		if (!$task) 
			$error_message = "Error: Couldn't find any service for $action_type action.";
		else {
			$task = $this->loadTaskParams($task);
			$task = $this->loadTaskParamsWithDefaultValues($task, '$pks');
			$task = $this->resetTaskOptionNoCache($task);
			$broker_code = $this->getBrokerCode($task);
			$options_code = self::getBrokerSettingsOptionsCode($this->WorkFlowTaskHandler, $action_type, $task);
			if ($options_code) {
				$task["service"]["options"] = "options";
				$task["service"]["options_type"] = "variable";
			}
			else {
				unset($task["service"]["options"]);
				unset($task["service"]["options_type"]);
			}
			$task_code = $this->getTaskCode($action_type, $task, $broker_code, "\t\t");
			
			$attrs = WorkFlowDBHandler::getTableFromTables($this->tables, $this->db_table);
			
			if ($attrs) {
				$pks_name = array();
				
				foreach ($attrs as $attr_name => $attr)
					if (!empty($attr["primary_key"]))
						$pks_name[] = $attr_name;
				
				//prepare code
				$pks_previous_code = self::getUpdateActionPreviousCode($this->tables, $this->db_table, $pks_name, $task, $this->WorkFlowTaskHandler, $broker_code, '$pks', true);
				$pks_previous_code = str_replace("\n", "\n\t", $pks_previous_code);
				
				$code = '$status = false;
if ($pks) {
	$status = true;
	
	' . $pks_previous_code . '
	
	if ($status) {
		';
				
				if ($options_code) {
					$options_code = str_replace("\n", "\n\t\t", $options_code);
					$options_code = '$options = ' . $options_code . ';
		';
					$code .= $options_code;
				}
				
				$code .= '$status = ' . $task_code . ';
	}
}

return $status;';
				
				//prepare task in business logic layer
				if (isset($task["item_type"]) && $task["item_type"] == "businesslogic") {
					//echo "<pre>createDeleteMethod \nbroker_code:$broker_code\ntask:";print_r($task);die();
					$bl_method_comments = "Delete parsed resource record from table: " . $this->db_table . ".";
					$bl_parameters = array('pks');
					$this->prepareTaskBusinessLogicResourceService($action_type, $task, $broker_code, $options_code, $code, $bl_method_comments, $bl_parameters, "delete", $error_message);
				}
				
				//save resource util task
				$method_args = array('EVC' => null, 'pks' => null, 'no_cache' => 'true');
				$method_comments = "Delete record from table: " . $this->db_table . ".";
				
				return $this->addFunctionToFile($file_path, array(
					"name" => "delete",
					"static" => true,
					"arguments" => $method_args,
					"code" => $code,
					"comments" => $method_comments
				), $class_name);
			}
		}
		
		return false;
	}

	private function createMultipleDeleteMethod($action_type, $resource_data, $class_name, $file_path, &$error_message) {
		$delete_method_exists = PHPCodePrintingHandler::getFunctionFromFile($file_path, "delete", $class_name);
		
		if (!$delete_method_exists)
			$delete_method_exists = $this->createDeleteMethod("delete", $resource_data, $class_name, $file_path, $error_message);
		
		if (!$delete_method_exists)
			$error_message = "Error: Couldn't find any service for delete action.";
		else {
			//prepare code
			$code = '$status = true;

if ($pks)
	for ($i = 0, $t = count($pks); $i < $t; $i++)
		if (!self::delete($EVC, $pks[$i], $no_cache))
			$status = false;

return $status;';
			
			//prepare task in business logic layer
			$task = $this->loadTask("delete");
			
			if ($task && isset($task["item_type"]) && $task["item_type"] == "businesslogic") {
				//echo "<pre>createMultipleDeleteMethod \nbroker_code:$broker_code\ntask:";print_r($task);die();
				$bl_method_name = $this->getTaskBusinessLogicResourceServiceClassMethodName($task);
				$bl_method_name = $bl_method_name ? $bl_method_name : "delete";
				$bl_method_comments = "Delete multiple records at once parsed resource record from table: " . $this->db_table . ".";
				$bl_code = '$status = true;
$pks = isset($data["pks"]) ? $data["pks"] : null;

if ($pks)
for ($i = 0, $t = count($pks); $i < $t; $i++) {
	$data["pks"] = $pks[$i];
	
	if (!$this->' . $bl_method_name . '($data))
		$status = false;
}

return $status;';
				$bl_task = $task;
				$bl_task["service"]["service_id"] = isset($bl_task["service"]["service_id"]) ? $bl_task["service"]["service_id"] : null;
				$bl_task["service"]["service_id"] = strstr($bl_task["service"]["service_id"], ".", true) . ".multipleDelete";
				$bl_task["service"]["method"] = "multipleDelete";
				
				if ($this->createTaskBusinessLogicResourceService($bl_task, $bl_code, $bl_method_comments, $error_message)) {
					$bl_task = $this->convertTaskToBusinessLogicResourceService($bl_task);
					$bl_broker_code = $this->getBrokerCode($bl_task);
					$bl_task_code = $this->getTaskCode($action_type, $bl_task, $bl_broker_code);
					
					$exists_logged_user_id_attribute = $this->containsLoggedUserIdAttribute();
					
					$code = '$options = array(
	"no_cache" => $no_cache
);
$data = array(
	"pks" => $pks,';
					
					if ($exists_logged_user_id_attribute)
						$code .= '
	"logged_user_id" => self::getLoggedUserId($EVC)';
					
					$code .= '
);
$result = ' . $bl_task_code . ';
return $result;';
				}
			}
			
			//save resource util task
			$method_args = array('EVC' => null, 'pks' => null, 'no_cache' => 'true');
			$method_comments = "Delete multiple records at once from table: " . $this->db_table . ".";
			
			return $this->addFunctionToFile($file_path, array(
				"name" => "multipleDelete",
				"static" => true,
				"arguments" => $method_args,
				"code" => $code,
				"comments" => $method_comments
			), $class_name);
		}
		
		return false;
	}

	private function createGetMethod($action_type, $resource_data, $class_name, $file_path, &$error_message) {
		$task = $this->loadTask("get");
		
		if (!$task) 
			$error_message = "Error: Couldn't find any service for $action_type action.";
		else {
			$task = $this->loadTaskParams($task);
			$task = $this->loadTaskParamsWithDefaultValues($task, '$pks');
			$task = $this->resetTaskOptionNoCache($task);
			$broker_code = $this->getBrokerCode($task);
			$options_code = self::getBrokerSettingsOptionsCode($this->WorkFlowTaskHandler, $action_type, $task);
			if ($options_code) {
				$task["service"]["options"] = "options";
				$task["service"]["options_type"] = "variable";
			}
			else {
				unset($task["service"]["options"]);
				unset($task["service"]["options_type"]);
			}
			$task_code = $this->getTaskCode($action_type, $task, $broker_code);
			
			//prepare code
			$code = "";
			
			if ($options_code) {
				$options_code = '$options = ' . $options_code . ';
';
				$code .= $options_code;
			}
			
			$code .= '$result = ' . $task_code . ';
';
			
			if (isset($task["item_type"])) {
				if ($task["item_type"] == "ibatis" || $task["item_type"] == "db")
					$code .= '
$result = $result[0];';
				else if ($task["item_type"] == "hibernate")
					$code .= self::getHibernateGetActionNextCode('$result');
			}
			
			$next_code = self::getSelectItemActionNextCode($this->tables, $this->db_table, '$result');
			$code .= $next_code ? '

' . trim($next_code) : '';
			
			$code .= '
return $result;';
			
			//prepare task in business logic layer
			if (isset($task["item_type"]) && $task["item_type"] == "businesslogic") {
				//echo "<pre>createGetMethod \nbroker_code:$broker_code\ntask:";print_r($task);die();
				$bl_method_comments = "Get a parsed resource record from table: " . $this->db_table . ".";
				$bl_parameters = array('pks');
				$this->prepareTaskBusinessLogicResourceService($action_type, $task, $broker_code, $options_code, $code, $bl_method_comments, $bl_parameters, "get", $error_message);
			}
			
			//save resource util task
			$method_args = array('EVC' => null, 'pks' => null, 'no_cache' => 'false');
			$method_comments = "Get a record from table: " . $this->db_table . ".";
			
			return $this->addFunctionToFile($file_path, array(
				"name" => "get",
				"static" => true,
				"arguments" => $method_args,
				"code" => $code,
				"comments" => $method_comments
			), $class_name);
		}
		
		return false;
	}

	private function createGetAllMethod($action_type, $resource_data, $class_name, $file_path, &$error_message) {
		$task = $this->loadTask("get_all");
		//echo "<pre>createGetAllMethod task:";print_r($task);die();
		
		if (!$task) 
			$error_message = "Error: Couldn't find any service for $action_type action.";
		else {
			$task = $this->loadTaskConditions($task);
			$task = $this->loadTaskLimitAndStart($task);
			$task = $this->loadSort($task);
			$task = $this->resetTaskOptionNoCache($task);
			$broker_code = $this->getBrokerCode($task);
			$options_code = self::getBrokerSettingsOptionsCode($this->WorkFlowTaskHandler, $action_type, $task);
			if ($options_code) {
				$task["service"]["options"] = "options";
				$task["service"]["options_type"] = "variable";
			}
			else {
				unset($task["service"]["options"]);
				unset($task["service"]["options_type"]);
			}
			$task_code = $this->getTaskCode($action_type, $task, $broker_code);
			
			//prepare code
			$code = $this->getTaskConditionsCode($task);
			
			if ($options_code) {
				$options_code = '$options = ' . $options_code . ';
';
				$code .= $options_code;
			}
			
			$code .= '$result = ' . $task_code . ';';
			
			if (isset($task["item_type"]) && $task["item_type"] == "hibernate")
				$code .= self::getHibernateGetAllActionNextCode('$result');
			
			$next_code = self::getSelectItemsActionNextCode($this->tables, $this->db_table, '$result');
			$code .= $next_code ? '

' . $next_code : '';
			
			$code .= '
return $result;';
			
			//prepare task in business logic layer
			if (isset($task["item_type"]) && $task["item_type"] == "businesslogic") {
				//echo "<pre>createGetAllMethod \nbroker_code:$broker_code\ntask:";print_r($task);die();
				$bl_method_comments = "Get parsed resource records from table: " . $this->db_table . ".";
				$bl_parameters = array('conditions', 'conditions_type', 'conditions_case', 'conditions_join');
				$this->prepareTaskBusinessLogicResourceService($action_type, $task, $broker_code, $options_code, $code, $bl_method_comments, $bl_parameters, "getAll", $error_message);
			}
			
			//save resource util task
			$method_args = array('EVC' => null, 'limit' => 'false', 'start' => 'false', 'conditions' => 'false', 'conditions_type' => 'false', 'conditions_case' => 'false', 'conditions_join' => 'false', 'sort' => 'false', 'no_cache' => 'false');
			$method_comments = "Get records from table: " . $this->db_table . ".";
			
			return $this->addFunctionToFile($file_path, array(
				"name" => "getAll",
				"static" => true,
				"arguments" => $method_args,
				"code" => $code,
				"comments" => $method_comments
			), $class_name);
		}
		
		return false;
	}

	private function createCountMethod($action_type, $resource_data, $class_name, $file_path, &$error_message) {
		$task = $this->loadTask("count");
		
		if (!$task) 
			$error_message = "Error: Couldn't find any service for $action_type action.";
		else {
			$task = $this->loadTaskConditions($task);
			$task = $this->resetTaskOptionNoCache($task);
			$broker_code = $this->getBrokerCode($task);
			$options_code = self::getBrokerSettingsOptionsCode($this->WorkFlowTaskHandler, $action_type, $task);
			if ($options_code) {
				$task["service"]["options"] = "options";
				$task["service"]["options_type"] = "variable";
			}
			else {
				unset($task["service"]["options"]);
				unset($task["service"]["options_type"]);
			}
			$task_code = $this->getTaskCode($action_type, $task, $broker_code);
			
			//prepare code
			$code = $this->getTaskConditionsCode($task);
			
			if ($options_code) {
				$options_code = '$options = ' . $options_code . ';
';
				$code .= $options_code;
			}
			
			$code .= '$result = ' . $task_code . ';
';
		
			if (isset($task["item_type"]) && ($task["item_type"] == "ibatis" || $task["item_type"] == "db"))
				$code .= '
return isset($result[0]["total"]) ? $result[0]["total"] : null;';
			else
				$code .= '
return $result;';
			
			//prepare task in business logic layer
			if (isset($task["item_type"]) && $task["item_type"] == "businesslogic") {
				//echo "<pre>createCountMethod \nbroker_code:$broker_code\ntask:";print_r($task);die();
				$bl_method_comments = "Count parsed resource records from table: " . $this->db_table . ".";
				$bl_parameters = array('conditions', 'conditions_type', 'conditions_case', 'conditions_join');
				$this->prepareTaskBusinessLogicResourceService($action_type, $task, $broker_code, $options_code, $code, $bl_method_comments, $bl_parameters, "count", $error_message);
			}
			
			//save resource util task
			$method_args = array('EVC' => null, 'conditions' => 'false', 'conditions_type' => 'false', 'conditions_case' => 'false', 'conditions_join' => 'false', 'no_cache' => 'false');
			$method_comments = "Count records from table: " . $this->db_table . ".";
			
			return $this->addFunctionToFile($file_path, array(
				"name" => "count",
				"static" => true,
				"arguments" => $method_args,
				"code" => $code,
				"comments" => $method_comments
			), $class_name);
		}
		
		return false;
	}

	//TODO: In the future change this method to create multiple getAllOptions methods based in the 'resource_data[attribute]', if attribute exists.
	private function createGetAllOptionsMethod($action_type, $resource_data, $class_name, $file_path, &$error_message) {
		$get_all_method_exists = PHPCodePrintingHandler::getFunctionFromFile($file_path, "getAll", $class_name);
		
		if (!$get_all_method_exists)
			$get_all_method_exists = $this->createGetAllMethod("get_all", $resource_data, $class_name, $file_path, $error_message);
		
		if (!$get_all_method_exists)
			$error_message = "Error: Couldn't find any service for get_all action.";
		else {
			$options_settings = SequentialLogicalActivityBLResourceCreator::getTableOptionsSettings($this->db_table, $this->tables, $resource_data);
			
			if (!$options_settings)
				$error_message = "Error: No primary attribute or no valid attributes when trying to create the getAllOptions method.";
			else {
				$keys = isset($options_settings["keys"]) ? $options_settings["keys"] : null;
				$values = isset($options_settings["values"]) ? $options_settings["values"] : null;
				
				//prepare code
				$code = '$result = self::getAll($EVC, $limit, $start, $conditions, $conditions_type, $conditions_case, $conditions_join, $sort, $no_cache);

$options = array();

if ($result) 
	for ($i = 0, $t = count($result); $i < $t; $i++) {
		$item = $result[$i];
		$key = ';
			
				for ($i = 0, $t = count($keys); $i < $t; $i++)
					$code .= ($i > 0 ? ' . "_" . ' : "") . '$item["' . $keys[$i] . '"]';
				
				$code .= ';
		$value = ';
				
				for ($i = 0, $t = count($values); $i < $t; $i++)
					$code .= ($i > 0 ? ' . "_" . ' : "") . '$item["' . $values[$i] . '"]';
				
				$code .= ';
		$options[$key] = $value;
	}

return $options;';
				
				//prepare task in business logic layer
				$task = $this->loadTask("get_all");
				
				if ($task && isset($task["item_type"]) && $task["item_type"] == "businesslogic") {
					//echo "<pre>createGetAllOptionsMethod \nbroker_code:$broker_code\ntask:";print_r($task);die();
					$bl_method_name = $this->getTaskBusinessLogicResourceServiceClassMethodName($task);
					$bl_method_name = $bl_method_name ? $bl_method_name : "getAll";
					$bl_method_comments = "Get parsed resource key-value pair list from table: " . $this->db_table . ", where the key is the table primary key and the value is the table attribute label.";
					$bl_code = str_replace('$result = self::getAll($EVC, $limit, $start, $conditions, $conditions_type, $conditions_case, $conditions_join, $sort, $no_cache);', '$result = $this->' . $bl_method_name . '($data);', $code);
					$bl_task = $task;
					$bl_task["service"]["service_id"] = isset($bl_task["service"]["service_id"]) ? $bl_task["service"]["service_id"] : null;
					$bl_task["service"]["service_id"] = strstr($bl_task["service"]["service_id"], ".", true) . ".getAllOptions";
					$bl_task["service"]["method"] = "getAllOptions";
					
					if ($this->createTaskBusinessLogicResourceService($bl_task, $bl_code, $bl_method_comments, $error_message)) {
						$bl_task = $this->convertTaskToBusinessLogicResourceService($bl_task);
						$bl_broker_code = $this->getBrokerCode($bl_task);
						$bl_task_code = $this->getTaskCode($action_type, $bl_task, $bl_broker_code);
						
						$code = '$options = array(
	"no_cache" => $no_cache,
	"limit" => $limit,
	"start" => $start,
	"sort" => $sort
);
$data = array(
	"conditions" => $conditions,
	"conditions_type" => $conditions_type,
	"conditions_case" => $conditions_case,
	"conditions_join" => $conditions_join,
);
$result = ' . $bl_task_code . ';
return $result;';
					}
				}
				
				//save resource util task
				$method_args = array('EVC' => null, 'limit' => 'false', 'start' => 'false', 'conditions' => 'false', 'conditions_type' => 'false', 'conditions_case' => 'false', 'conditions_join' => 'false', 'sort' => 'false', 'no_cache' => 'false');
				$method_comments = "Get key-value pair list from table: " . $this->db_table . ", where the key is the table primary key and the value is the table attribute label.";
				
				return $this->addFunctionToFile($file_path, array(
					"name" => "getAllOptions",
					"static" => true,
					"arguments" => $method_args,
					"code" => $code,
					"comments" => $method_comments
				), $class_name);
			}
		}
		
		return false;
	}
	/* Old method - DEPRECATED
	private function createGetAllOptionsMethod($action_type, $resource_data, $class_name, $file_path, &$error_message) {
		$task = $this->loadTask("get_all");
		
		if (!$task) 
			$error_message = "Error: Couldn't find any service for $action_type action.";
		else {
			$task = $this->loadTaskConditions($task);
			$task = $this->loadTaskLimitAndStart($task);
			$task = $this->loadSort($task);
			$task = $this->resetTaskOptionNoCache($task);
			$broker_code = $this->getBrokerCode($task);
			$options_code = self::getBrokerSettingsOptionsCode($this->WorkFlowTaskHandler, $action_type, $task);
			if ($options_code) {
				$task["service"]["options"] = "options";
				$task["service"]["options_type"] = "variable";
			}
			else {
				unset($task["service"]["options"]);
				unset($task["service"]["options_type"]);
			}
			$task_code = $this->getTaskCode($action_type, $task, $broker_code);
			
			$options_settings = SequentialLogicalActivityBLResourceCreator::getTableOptionsSettings($this->db_table, $this->tables, $resource_data);
			
			if (!$options_settings)
				$error_message = "Error: No primary attribute name when trying to create the getAllOptions method.";
			else {
				$keys = isset($options_settings["keys"]) ? $options_settings["keys"] : null;
				$values = isset($options_settings["values"]) ? $options_settings["values"] : null;
				
				//prepare code
				$code = $this->getTaskConditionsCode($task);
					
				if ($options_code) {
					$options_code = '$options = ' . $options_code . ';
';
					$code .= $options_code;
				}
				
				$code .= '$result = ' . $task_code . ';
';
				
				if (isset($task["item_type"]) && $task["item_type"] == "hibernate")
					$code .= self::getHibernateGetAllActionNextCode('$result');
				
				$code .= '
$options = array();

if ($result) 
	for ($i = 0, $t = count($result); $i < $t; $i++) {
		$item = $result[$i];
		$key = ';
			
				for ($i = 0, $t = count($keys); $i < $t; $i++)
					$code .= ($i > 0 ? ' . "_" . ' : "") . '$item["' . $keys[$i] . '"]';
				
				$code .= ';';
				
				$next_code = self::getSelectItemActionNextCode($this->tables, $this->db_table, '$item', $values);
				$code .= $next_code ? '
		
		' . trim($next_code) . '
		' : '';

				$code .= '
		$value = ';
				
				for ($i = 0, $t = count($values); $i < $t; $i++)
					$code .= ($i > 0 ? ' . "_" . ' : "") . '$item["' . $values[$i] . '"]';
				
				$code .= ';
		$options[$key] = $value;
	}

return $options;';
				
				//prepare task in business logic layer
				if (isset($task["item_type"]) && $task["item_type"] == "businesslogic") {
					//echo "<pre>createGetAllOptionsMethod \nbroker_code:$broker_code\ntask:";print_r($task);die();
					$bl_method_comments = "Get parsed resource key-value pair list from table: " . $this->db_table . ", where the key is the table primary key and the value is the table attribute label.";
					$bl_parameters = array('conditions', 'conditions_type', 'conditions_case', 'conditions_join');
					$this->prepareTaskBusinessLogicResourceService($action_type, $task, $broker_code, $options_code, $code, $bl_method_comments, $bl_parameters, "getAllOptions", $error_message); //cannot be getAllOptions bc the CommonService class already has this method.
				}
				
				//save resource util task
				$method_args = array('EVC' => null, 'limit' => 'false', 'start' => 'false', 'conditions' => 'false', 'conditions_type' => 'false', 'conditions_case' => 'false', 'conditions_join' => 'false', 'sort' => 'false', 'no_cache' => 'false');
				$method_comments = "Get key-value pair list from table: " . $this->db_table . ", where the key is the table primary key and the value is the table attribute label.";
				
				return $this->addFunctionToFile($file_path, array(
					"name" => "getAllOptions",
					"static" => true,
					"arguments" => $method_args,
					"code" => $code,
					"comments" => $method_comments
				), $class_name);
			}
		}
		
		return false;
	}*/
	
	private function addFunctionToFile($file_path, $method_data, $class_name) {
		//check if method doesn't exist already, bc meanwhile it may was created before. Note that it is possible to happen multiple concurrent calls of this function with the same method name. So just in case we check if exists again...
		$file_method_exists = PHPCodePrintingHandler::getFunctionFromFile($file_path, $method_data["name"], $class_name);
		
		if (!$file_method_exists) {
			$status = PHPCodePrintingHandler::addFunctionToFile($file_path, $method_data, $class_name);
			
			debug_log("[SequentialLogicalActivityBLResourceCreator->addFunctionToFile] Method created:\n- path: $file_path\n- class: $class_name\n- method: " . print_r($method_data, 1) . "\n- created: $status\n");
			
			return $status;
		}
		else
			debug_log("[SequentialLogicalActivityBLResourceCreator->addFunctionToFile] Method already exists:\n- path: $file_path\n- class: $class_name\n- method: " . print_r($method_data, 1) . "\n");
		
		return true;
	}
	
	private function prepareTaskBusinessLogicResourceService($action_type, $task, $broker_code, $options_code, &$code, $method_comments, $parameters, $method_name, &$error_message) {
		$parsed_code = str_replace($options_code, '', $code);
		$has_logged_user_id = preg_match('/(\$[a-z]+\["logged_user_id"\]\s*=\s*)self::getLoggedUserId\(\$EVC\);/', $parsed_code);
		
		$bl_code = '$options = isset($data["options"]) ? $data["options"] : null;
$this->mergeOptionsWithBusinessLogicLayer($options);

';
		$bl_code .= $this->getUtilMethodArgsInBusinessLogicResourceServiceCode($parameters);
		$bl_code .= str_replace($broker_code, '$this->getBusinessLogicLayer()', $parsed_code);
		
		if ($has_logged_user_id) {
			$bl_code = preg_replace('/(\\$[a-z]+\["logged_user_id"\]\s*=\s*)self::getLoggedUserId\(\\$EVC\);/', '$1$data["logged_user_id"];', $bl_code);
			
			if (!$parameters)
				$parameters = array("logged_user_id");
			else if (!in_array("logged_user_id", $parameters))
				$parameters[] = "logged_user_id";
		}
		
		$bl_task = $task;
		
		if ($method_name) {
			$bl_task["service"]["service_id"] = isset($bl_task["service"]["service_id"]) ? $bl_task["service"]["service_id"] : null;
			$bl_task["service"]["service_id"] = strstr($bl_task["service"]["service_id"], ".", true) . "." . $method_name;
			$bl_task["service"]["method"] = $method_name;
		}
		
		$status = $this->createTaskBusinessLogicResourceService($bl_task, $bl_code, $method_comments, $error_message);
		
		if ($status) {
			$this->is_flush_cache = true; //set flush cache flag to true, so we can delete the cache from the business logic.
			
			//set the $code with new code
			$bl_task = $this->convertTaskToBusinessLogicResourceService($bl_task);
			$bl_task_code = $this->getTaskCode($action_type, $bl_task, $broker_code);
			
			$new_code = preg_replace("/\n\t+\);$/", "\n);", preg_replace("/\n\t+/", "\n\t", trim($options_code))) . "\n";
			
			if ($parameters) {
				$new_code .= '$data = array(';
				
				foreach ($parameters as $parameter_name)
					$new_code .= '
	"' . $parameter_name . '" => ' . ($parameter_name == "logged_user_id" ? 'self::getLoggedUserId($EVC)' : '$' . $parameter_name) . ',';
				
				$new_code .= '
);';
			}
			else
				$new_code .= '$data = null;';
			
			$new_code .= '
$result = ' . $bl_task_code . ';

return $result;';
			
			$code = $new_code;
		}
		
		return $status;
	}
	
	private function getUtilMethodArgsInBusinessLogicResourceServiceCode($parameters) {
		$code = "";
		
		if ($parameters)
			foreach ($parameters as $parameter_name)
				$code .= "\${$parameter_name} = \$data[\"$parameter_name\"];\n";
		
		if ($code)
			$code .= "\n";
		
		return $code;
	}
	
	private function createTaskBusinessLogicResourceService($task, $code, $comments, &$error_message) {
		$status = false;
		$file_path = $this->getTaskBusinessLogicResourceServiceFilePath($task);
		
		if ($file_path) {
			$class_name = $this->getTaskBusinessLogicResourceServiceClassName($task);
			$method_name = $this->getTaskBusinessLogicResourceServiceClassMethodName($task);
			
			if ($class_name && $method_name) {
				$file_exists = file_exists($file_path) && PHPCodePrintingHandler::getClassFromFile($file_path, $class_name);
				$file_method_exists = false;
				
				if (!$file_exists) {
					$task["service"]["path"] = isset($task["service"]["path"]) ? $task["service"]["path"] : null;
					$task["service"]["service_id"] = isset($task["service"]["service_id"]) ? $task["service"]["service_id"] : null;
					
					$original_file_path = dirname($file_path) . "/" . basename($task["service"]["path"]);
					$obj_data = PHPCodePrintingHandler::getClassFromFile($original_file_path, strstr($task["service"]["service_id"], ".", true));
					
					if ($obj_data) {
						$obj_data["includes"] = PHPCodePrintingHandler::getIncludesFromFile($original_file_path);
						
						$file_exists = PHPCodePrintingHandler::addClassToFile($file_path, array(
							"name" => $class_name,
							"extends" => isset($obj_data["extends"]) ? $obj_data["extends"] : null,
							"includes" => isset($obj_data["includes"]) ? $obj_data["includes"] : null
						));
					}
				}
				
				if (!$file_exists)
					$error_message = "Error trying to create file '" . substr($file_path, strlen(LAYER_PATH)) . "'!";
				else {
					$file_method_exists = PHPCodePrintingHandler::getFunctionFromFile($file_path, $method_name, $class_name);
					$status = true;
					
					//create new function in Business Logic Resource
					if (!$file_method_exists) {
						$status = $this->addFunctionToFile($file_path, array(
							"name" => $method_name,
							"arguments" => array("data" => null),
							"code" => $code,
							"comments" => $comments
						), $class_name);
					}
				}
			}
		}
		
		return $status;
	}
	
	private function convertTaskToBusinessLogicResourceService($task) {
		$file_path = $this->getTaskBusinessLogicResourceServiceFilePath($task);
		
		if ($file_path) {
			$class_name = $this->getTaskBusinessLogicResourceServiceClassName($task);
			$method_name = $this->getTaskBusinessLogicResourceServiceClassMethodName($task);
			
			if ($class_name && $method_name) {
				$bl_task = $task;
				$bl_task["service"]["path"] = (isset($task["service"]["path"]) ? dirname($task["service"]["path"]) : "") . "/$class_name.php";
				$bl_task["service"]["service_id"] = "$class_name.$method_name";
				$bl_task["service"]["obj"] = $class_name;
				$bl_task["service"]["method"] = $method_name;
				$bl_task["service"]["parameters"] = "data";
				$bl_task["service"]["parameters_type"] = "variable";
				$bl_task["service"]["options"] = "options";
				$bl_task["service"]["options_type"] = "variable";
				
				return $bl_task;
			}
		}
		
		return null;
	}
	
	private function getTaskBusinessLogicResourceServiceFilePath($task) {
		$class_name = $this->getTaskBusinessLogicResourceServiceClassName($task);
		
		if ($class_name) {
			$service = isset($task["service"]) ? $task["service"] : null;
			$bean_file_name = isset($task["bean_file_name"]) ? $task["bean_file_name"] : null;
			$bean_name = isset($task["bean_name"]) ? $task["bean_name"] : null;
			
			if ($bean_name && $bean_file_name && !empty($service["path"])) {
				$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($this->user_beans_folder_path . $bean_file_name, $this->user_global_variables_file_path);
				$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
				
				if ($obj) {
					$layer_path = $obj->getLayerPathSetting();
					
					if (is_dir($layer_path)) //be sure is not a rest broker and that layer folder really exists
						return $layer_path . (isset($service["path"]) ? dirname($service["path"]) : "") . "/$class_name.php";
				}
			}
		}
		
		return null;
	}
	
	private function getTaskBusinessLogicResourceServiceClassName($task) {
		$service = isset($task["service"]) ? $task["service"] : null;
		$service_id = isset($service["service_id"]) ? $service["service_id"] : null;
		$class_name = strstr($service_id, ".", true);
		
		if ($class_name) {
			$class_name = substr($class_name, 0, - strlen("Service"));
			return $class_name . "ResourceService";
		}
	}
	
	private function getTaskBusinessLogicResourceServiceClassMethodName($task) {
		$service = isset($task["service"]) ? $task["service"] : null;
		$service_id = isset($service["service_id"]) ? $service["service_id"] : null;
		$method_name = strstr($service_id, ".");
		
		if ($method_name)
			return substr($method_name, 1);
	}
	
	private function containsLoggedUserIdAttribute() {
		$attrs = WorkFlowDBHandler::getTableFromTables($this->tables, $this->db_table);
		$exists_logged_user_id_attribute = false;
		
		if ($attrs)
			foreach ($attrs as $attr_name => $attr) {
				$attr_type = isset($attr["type"]) ? $attr["type"] : null;
				
				if ( (ObjTypeHandler::isDBAttributeNameACreatedUserId($attr_name) || ObjTypeHandler::isDBAttributeNameAModifiedUserId($attr_name) ) && (ObjTypeHandler::isDBTypeNumeric($attr_type) || ObjTypeHandler::isPHPTypeNumeric($attr_type))) {
					$exists_logged_user_id_attribute = true;
					break;
				}
			}
			
		return $exists_logged_user_id_attribute;
	}
	
	private function initTableUIProps($brokers, $no_cache = false) {
		$this->tables_ui_props = array();
		$selected_presentation_id = $this->PresentationLayer->getSelectedPresentationId();
		
		//prepare paths for active_brokers. Basically call the getTableUIProps for the project path, then for the resource folder and then without any path (global to multiple projects).
		$paths_to_filter = array();
		
		if ($this->broker_path_to_filter)
			$paths_to_filter[] = $this->broker_path_to_filter;
		
		if ($this->broker_path_to_filter != $selected_presentation_id . "/")
			$paths_to_filter[] = $selected_presentation_id . "/";
		
		if ($this->default_broker_path_to_filter)
			$paths_to_filter[] = $this->default_broker_path_to_filter;
		
		$paths_to_filter[] = "";
		
		//set some default active_brokers
		$active_brokers = array();
		$active_brokers_folder = array();
		
		foreach ($paths_to_filter as $path_to_filter) {
			foreach ($brokers as $broker_name => $broker) {
				$active_brokers[$broker_name] = true;
				
				if (is_a($broker, "IBusinessLogicBrokerClient") || is_a($broker, "IDataAccessBrokerClient"))
					$active_brokers_folder[$broker_name] = $path_to_filter;
			}
			//print_r($active_brokers_folder);die();
			
			//get tables_ui_props for active brokers
			$tables_ui_props = $this->getTableUIProps($active_brokers, $active_brokers_folder, $no_cache);
			//echo "<pre>initTableUIProps tables_ui_props ($path_to_filter):";print_r($tables_ui_props);die();
			//echo "initTableUIProps tables_ui_props ($path_to_filter)\n";print_r($tables_ui_props);
			
			//join $tables_ui_props with $this->tables_ui_props
			if (is_array($tables_ui_props)) {
				//$prop_type: "tables" or "brokers"
				foreach ($tables_ui_props as $prop_type => $props) {
					if (empty($this->tables_ui_props[$prop_type]))
						$this->tables_ui_props[$prop_type] = $props;
					else if (is_array($props)) {
						//$prop_type == "tables" then $prop_name is the $table_name where the $prop_value are the found brokers services
						//$prop_type == "brokers" then $prop_name: "business_logic_broker_name", "ibatis_broker_name", "db_broker_name" and "hibernate_broker_name" where $prop_value is the $broker_name
						foreach ($props as $prop_name => $prop_value) { 
							if (empty($this->tables_ui_props[$prop_type][$prop_name]))
								$this->tables_ui_props[$prop_type][$prop_name] = $prop_value;
							else if (is_array($prop_value) && $prop_type == "tables") {
								$table_name = $prop_name;
								
								foreach ($prop_value as $broker_name => $broker_services) {
									if (empty($this->tables_ui_props[$prop_type][$table_name][$broker_name]))
										$this->tables_ui_props[$prop_type][$table_name][$broker_name] = $broker_services;
									else if (is_array($broker_services))
										foreach ($broker_services as $broker_service_type => $service) {
											if (empty($this->tables_ui_props[$prop_type][$table_name][$broker_name][$broker_service_type]))
												$this->tables_ui_props[$prop_type][$table_name][$broker_name][$broker_service_type] = $service;
											else if (($broker_service_type == "relationships" || $broker_service_type == "relationships_count") && is_array($service))
												foreach ($service as $foreign_table_name => $foreign_service)
													if (empty($this->tables_ui_props[$prop_type][$table_name][$broker_name][$broker_service_type][$foreign_table_name]))
														$this->tables_ui_props[$prop_type][$table_name][$broker_name][$broker_service_type][$foreign_table_name] = $foreign_service;
										}
								}
							}
						}
					}
				}
			}
		}
		
		//echo "<pre>initTableUIProps tables_ui_props:";print_r($this->tables_ui_props);die();
	}
	
	//Any change here must be replicated in the url of the view/presentation/create_presentation_uis_automatically.php
	/* return Array(
		[tables] => Array(
			[mstockmanager_product] => Array(
				[soa] => Array(
					[insert] => $service,
					[update] => $service,
					...
				),
				[iorm] => Array(
					[count] => $service,
					[delete] => $service,
					[get] => $service,
					...
				),
				[dbdata] => Array(
					[update_pks] => $service,
					[delete] => $service,
					[get_all] => $service,
					[count] => $service,
					[relationships] => Array(
						[mstockmanager_batch] => $service,
						[mstockmanager_entry_order_product] => $service,
						[mstockmanager_exit_order_product] => $service,
						[mstockmanager_category] => $service
					),
					[relationships_count] => Array(
						[mstockmanager_batch] => $service,
						[mstockmanager_entry_order_product] => $service
					),
					...
				),
				[horm] => Array()
			)
		),
		[brokers] => Array(
			[business_logic_broker_name] => soa,
			[ibatis_broker_name] => iorm,
			[db_broker_name] => dbdata,
			[hibernate_broker_name] => horm,
		)
	)*/
	private function getTableUIProps($active_brokers, $active_brokers_folder = array(), $no_cache = false) {
		$this->UserCacheHandler->config(60 * 60, true); //1 hour
		$cache_id = "tables_ui_props/" . md5($this->bean_name . "_" . $this->bean_file_name . "_" . $this->path . "_" . $this->db_broker . "_" . $this->db_driver . "_" . $this->db_type . "_" . $this->db_table . "_" . $this->db_table_alias . "_" . json_encode($active_brokers) . "_" . json_encode($active_brokers_folder));
		
		if (!$no_cache && $this->UserCacheHandler->isValid($cache_id)) {
			$tables_ui_props = $this->UserCacheHandler->read($cache_id);
			
			if ($tables_ui_props) 
				return $tables_ui_props;
		}
		
		$active_brokers = is_array($active_brokers) ? $active_brokers : array();
		$active_brokers_folder = is_array($active_brokers_folder) ? $active_brokers_folder : array();
		
		//prepare db drivers
		$db_drivers = WorkFlowBeansFileHandler::getLayerDBDrivers($this->user_global_variables_file_path, $this->user_beans_folder_path, $this->PresentationLayer, true);
		
		$selected_db_driver_props = isset($db_drivers[$this->db_driver]) ? $db_drivers[$this->db_driver] : null;
		$db_layer = $db_layer_file = null;
		
		if ($selected_db_driver_props) {
			$db_layer = $selected_db_driver_props[2];
			$db_layer_file = $selected_db_driver_props[1];
		}
		
		$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($this->filter_by_layout);
		
		$get_tables_ui_props_url = $this->project_url_prefix . "phpframework/presentation/get_presentation_tables_ui_props_automatically?bean_name=" . $this->bean_name . "&bean_file_name=" . $this->bean_file_name . "$filter_by_layout_url_query&path=" . $this->path . "&db_layer=" . $this->db_layer . "&db_layer_file=$db_layer_file&db_driver=" . $this->db_driver . "&include_db_driver=" . $this->include_db_driver . "&type=" . $this->db_type;
		
		$post_data = array(
			"ab" => $active_brokers,
			"abf" => $active_brokers_folder,
			"st" => array($this->db_table),
			"sta" => array($this->db_table => $this->db_table_alias),
		);
		//echo "<pre>post_data:";print_r($post_data);
		$content = $this->UserAuthenticationHandler->getURLContent($get_tables_ui_props_url, $post_data);
		//echo "<pre>content:$content";
		$tables_ui_props = json_decode($content, true);
		//echo "<pre>$get_tables_ui_props_url\n<br>";print_r($post_data);print_r($tables_ui_props);die();
		
		debug_log("[SequentialLogicalActivityBLResourceCreator->getTableUIProps]:\n- Call getURLContent for: $get_tables_ui_props_url\n- With Post data: " . print_r($post_data, 1) . "\n- With raw response: $content\n- With decoded response: " . print_r($tables_ui_props, 1) . "\n");
		
		$this->UserCacheHandler->write($cache_id, $tables_ui_props);
		
		return $tables_ui_props;
	}

	private function getTables() {
		$this->UserCacheHandler->config(60 * 60, true); //1 hour
		$cache_id = "tables_props/" . md5($this->db_type . "_" . $this->db_driver);
		
		if ($this->UserCacheHandler->isValid($cache_id)) {
			$tables = $this->UserCacheHandler->read($cache_id);
			
			if ($tables) 
				return $tables;
		}
		
		//prepare tables
		$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
		
		if ($this->db_type == "diagram") { //TRYING TO GET THE DB TABLES FROM THE TASK FLOW
			$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($this->workflow_paths_id, "db_diagram", $this->db_driver);
			$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
			//$tasks = $WorkFlowDataAccessHandler->getTasks();
		}
		else { //TRYING TO GET THE DB TABLES DIRECTLY FROM DB
			//get db driver object
			$db_drivers = WorkFlowBeansFileHandler::getLayerDBDrivers($this->user_global_variables_file_path, $this->user_beans_folder_path, $this->PresentationLayer, true);
			$db_driver_props = isset($db_drivers[$this->db_driver]) ? $db_drivers[$this->db_driver] : null;
			$db_driver_bean_file_name = isset($db_driver_props[1]) ? $db_driver_props[1] : null;
			$db_driver_bean_name = !empty($db_driver_props[2]) ? $db_driver_props[2] : $this->db_driver;
			//print_r($db_driver_props);die();
			
			if ($db_driver_bean_file_name && $db_driver_bean_name) {
				$WorkFlowDBHandler = new WorkFlowDBHandler($this->user_beans_folder_path, $this->user_global_variables_file_path);
				$tasks = $WorkFlowDBHandler->getUpdateTaskDBDiagram($db_driver_bean_file_name, $db_driver_bean_name);
				$WorkFlowDataAccessHandler->setTasks($tasks);
				//$tasks = $WorkFlowDataAccessHandler->getTasks();
			}
		}
		
		$tables = $WorkFlowDataAccessHandler->getTasksAsTables();
		
		$this->UserCacheHandler->write($cache_id, $tables);
		
		return $tables;
	}

	//this function has the same name than in the create_presentation_uis_automatically.js
	private function loadTask($broker_service_type) {
		if ($this->db_table && $this->tables_ui_props && $this->layer_brokers_settings) {
			$business_logic_brokers = isset($this->layer_brokers_settings["business_logic_brokers"]) ? $this->layer_brokers_settings["business_logic_brokers"] : null;
			$ibatis_brokers = isset($this->layer_brokers_settings["ibatis_brokers"]) ? $this->layer_brokers_settings["ibatis_brokers"] : null;
			$hibernate_brokers = isset($this->layer_brokers_settings["hibernate_brokers"]) ? $this->layer_brokers_settings["hibernate_brokers"] : null;
			$db_brokers = isset($this->layer_brokers_settings["db_brokers"]) ? $this->layer_brokers_settings["db_brokers"] : null;
			
			$layer_brokers = array(
				"businesslogic" => $business_logic_brokers, //first priority
				"ibatis" => $ibatis_brokers, //second priority
				"hibernate" => $hibernate_brokers, //third priority
				"db" => $db_brokers, //fourth priority
			);
			//echo "broker_service_type:$broker_service_type\n";
			
			//use the filter_by_layout or selected project path or default_broker_path_to_filter as folder to create new service
			$new_service_folder_path = $this->broker_path_to_filter ? $this->broker_path_to_filter : $this->default_broker_path_to_filter; 
			//echo "<pre>loadTask for table '".$this->db_table."' tables_ui_props:";print_r($this->tables_ui_props);die();
			
			$real_table_name = WorkFlowDBHandler::getTableTaskRealNameFromTasks($this->tables_ui_props["tables"], $this->db_table);
			
			foreach ($layer_brokers as $item_type => $bs) 
				if ($bs) {
					for ($i = 0, $t = count($bs); $i < $t; $i++) {
						$b = $bs[$i];
						$broker_name = $b[0];
						$broker_bean_file_name = $b[1];
						$broker_bean_name = $b[2];
						
						$service = isset($this->tables_ui_props["tables"][$real_table_name][$broker_name][$broker_service_type]) ? $this->tables_ui_props["tables"][$real_table_name][$broker_name][$broker_service_type] : null;
						
						if ($service) {
							//echo "1";print_r($service);
							$task = array(
								"broker_name" => $broker_name,
								"bean_name" => $broker_bean_name,
								"bean_file_name" => $broker_bean_file_name,
								"item_type" => $item_type,
								"service" => $service,
							);
							
							//tries to convert sql to simple task
							self::convertQueryDataTaskToSimpleTask($task, $this->tables);
							
							return $task;
						}
					}
					
					//if it gets here, creates a new service in this layer, before it continues to the next layer.
					for ($i = 0, $t = count($bs); $i < $t; $i++) {
						$b = $bs[$i];
						$broker_name = $b[0];
						$broker_bean_file_name = $b[1];
						$broker_bean_name = $b[2];
						
						//echo "createBrokerService($broker_bean_file_name, $broker_bean_name, $new_service_folder_path, $broker_service_type)";die();
						$status = $this->createBrokerService($broker_bean_file_name, $broker_bean_name, $new_service_folder_path, $broker_service_type);
						
						//get new tables_ui_props but only for this broker
						if ($status) {
							$active_brokers = array($broker_name => true);
							$active_brokers_folder = array($broker_name => $new_service_folder_path);
							
							$broker_tables_ui_props = $this->getTableUIProps($active_brokers, $active_brokers_folder, true);
							//echo "<pre>broker_tables_ui_props:";print_r($broker_tables_ui_props);die();
							
							$real_broker_table_name = WorkFlowDBHandler::getTableTaskRealNameFromTasks(isset($broker_tables_ui_props["tables"]) ? $broker_tables_ui_props["tables"] : null, $this->db_table);
							
							$service = isset($broker_tables_ui_props["tables"][$real_broker_table_name][$broker_name][$broker_service_type]) ? $broker_tables_ui_props["tables"][$real_broker_table_name][$broker_name][$broker_service_type] : null;
							//echo "broker_service_type:$broker_service_type";print_r($broker_tables_ui_props["tables"][$real_broker_table_name][$broker_name]);
							
							if ($service) {
								//echo "2";print_r($service);
								$this->tables_ui_props["tables"][$real_table_name][$broker_name][$broker_service_type] = $service; //save service for next time
								
								$task = array(
									"broker_name" => $broker_name,
									"bean_name" => $broker_bean_name,
									"bean_file_name" => $broker_bean_file_name,
									"item_type" => $item_type,
									"service" => $service,
								);
								
								//tries to convert sql to simple task
								self::convertQueryDataTaskToSimpleTask($task, $this->tables);
								
								return $task;
							}
						}
					}
					
					//only passes to the next layer if it cannot create the service in this layer.
				}
		}
		
		return null;
	}

	private function createBrokerService($bean_file_name, $bean_name, $path, $broker_service_type) {
		$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($this->user_beans_folder_path . $bean_file_name, $this->user_global_variables_file_path);
		$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
		
		if ($obj) {
			$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($this->filter_by_layout);
			
			//sleep for a while (less than 1 sec), bc if this class will run asynchronously with multiple concorrent process and we want to avoid to create duplicated service files /in business logic, ibatis or hibernate layers folders)
			$rand = rand(10000, 1000000) / 1000000;
			usleep($rand);
			
			$rand = rand(10000, 1000000) / 1000000;
			usleep($rand);
			
			//prepare some vars
			$method_name = self::getMethodName($broker_service_type);
			$query_id = str_replace(array(" ", "."), "_", strtolower($this->db_table_alias ? $this->db_table_alias : $this->db_table));
			$rule_name = $broker_service_type . "_" . $query_id;
			$query_type = $broker_service_type;
			
			if ($broker_service_type == "update_pks") {
				$rule_name = "update_" . $query_id . "_primary_keys";
				$query_type = "update";
			}
			else if ($broker_service_type == "get_all") {
				$rule_name = "get_" . $query_id . "_items";
				$query_type = "select";
			}
			else if ($broker_service_type == "count") {
				$rule_name = "count_" . $query_id . "_items";
				$query_type = "select";
			}
			else if ($broker_service_type == "get")
				$query_type = "select";
			
			//create service in layers
			if (is_a($obj, "BusinessLogicLayer")) {
				$class_name = self::getClassName($this->db_table_alias ? $this->db_table_alias : $this->db_table) . "Service";
				$default_service_file_name = $class_name . ".php";
				$default_service_file_abs_path = $obj->getLayerPathSetting() . $path . $default_service_file_name;
				
				//check if folder path is allowed inside of $filter_by_layout
				$allowed = !$this->filter_by_layout || $this->UserAuthenticationHandler->isLayoutInnerFilePermissionAllowed(dirname($default_service_file_abs_path), $this->filter_by_layout, "layer", UserAuthenticationHandler::$PERMISSION_BELONG_NAME, false, false);
				
				if (!$allowed)
					return false;
				
				//check if method already exists in default file and if it does, return true.
				if (file_exists($default_service_file_abs_path)) {
					$method_data = PHPCodePrintingHandler::getFunctionFromFile($default_service_file_abs_path, $method_name, $class_name);
					
					if ($method_data)
						return true;
				}
				
				//only create method if not exists yet
				$url = $this->project_url_prefix . "phpframework/businesslogic/create_business_logic_objs_automatically?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path";
				
				$layer_brokers_settings = WorkFlowBeansFileHandler::getLayerBrokersSettings($this->user_global_variables_file_path, $this->user_beans_folder_path, $obj->getBrokers(), '');
				$db_brokers = isset($layer_brokers_settings["db_brokers"]) ? $layer_brokers_settings["db_brokers"] : null;
				$ibatis_brokers = isset($layer_brokers_settings["ibatis_brokers"]) ? $layer_brokers_settings["ibatis_brokers"] : null;
				$hibernate_brokers = isset($layer_brokers_settings["hibernate_brokers"]) ? $layer_brokers_settings["hibernate_brokers"] : null;
				
				$layer_brokers = array(
					"db" => $db_brokers, //first priority
					"ibatis" => $ibatis_brokers, //second priority
					"hibernate" => $hibernate_brokers, //third priority
				);
				
				foreach ($layer_brokers as $item_type => $bs) {
					for ($i = 0, $t = count($bs); $i < $t; $i++) {
						$b = $bs[$i];
						$broker_name = $b[0];
						$broker_bean_file_name = $b[1];
						$broker_bean_name = $b[2];
						
						//prepare post_data - for db_brokers
						$files = array(
							$this->db_table => array(
								"all" => $broker_name
							)
						);
						$aliases = array(
							$this->db_table => array(
								"all" => $this->db_table_alias ? $class_name : ""
							)
						);
						
						if ($item_type == "ibatis") {
							$sub_layer_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($this->user_beans_folder_path . $broker_bean_file_name, $broker_bean_name, $this->user_global_variables_file_path);
							$file = $path . "$query_id.xml";
							$abs_file = LAYER_PATH . $sub_layer_folder_name . "/$file";
							$rule_data = file_exists($abs_file) ? WorkFlowDataAccessHandler::getXmlQueryOrMapData($abs_file, $rule_name, array($query_type)) : null;
							$create_ibatis_rule = !file_exists($abs_file) || !$rule_data;
							
							if ($create_ibatis_rule) {
								$this->createBrokerService($broker_bean_file_name, $broker_bean_name, $path, $broker_service_type);
								
								clearstatcache();
								//clearstatcache(true, $abs_file);
							}
							
							if (!file_exists($abs_file))
								$files = null;
							else {
								$files = array(
									$file => array(
										"all" => $broker_name
									)
								);
								$aliases = array(
									$file => array(
										"all" => $this->db_table_alias ? $class_name : ""
									)
								);
							}
						}
						else if ($item_type == "hibernate") {
							$sub_layer_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($this->user_beans_folder_path . $broker_bean_file_name, $broker_bean_name, $this->user_global_variables_file_path);
							$file = $path . "$query_id.xml";
							$abs_file = LAYER_PATH . $sub_layer_folder_name . "/$file";
							
							if (!file_exists($abs_file)) {
								$this->createBrokerService($broker_bean_file_name, $broker_bean_name, $path, $broker_service_type);
								
								clearstatcache();
								//clearstatcache(true, $abs_file);
							}
							
							if (!file_exists($abs_file))
								$files = null;
							else {
								$files = array(
									$file => array(
										"all" => $broker_name
									)
								);
								$aliases = array(
									$file => array(
										"all" => $this->db_table_alias ? $class_name : ""
									)
								);
							}
						}
						
						if ($files) {
							$post_data = array(
								"db_driver" => $this->db_driver,
								"type" => $this->db_type,
								"files" => $files,
								"aliases" => $aliases,
								"step_1" => true,
								"include_db_driver" => $this->include_db_driver,
								"overwrite" => false,
								"json" => true,
							);
							//print_r($post_data);
							
							//create file automatically
							$raw_statuses = $this->UserAuthenticationHandler->getURLContent($url, $post_data);
							//echo "url:$url<br/>\nhtml:$statuses\n\n\n";
							$statuses = $raw_statuses ? json_decode($raw_statuses, true) : $raw_statuses;
							//print_r($statuses);die();
							
							debug_log("[SequentialLogicalActivityBLResourceCreator->createBrokerService][based in BusinessLogicLayer]:\n- Call getURLContent for: $url\n- With Post data: " . print_r($post_data, 1) . "\n- With raw response: $raw_statuses\n- With decoded response: " . print_r($statuses, 1) . "\n");
							
							//check if file was created and if so checks if the correspondent function exists based in broker_service_type. If yes copies the function to $default_service_file_abs_path, or if it doesn't exists, rename the created file to $default_service_file_abs_path.
							if ($statuses) {
								$item = $statuses[0];
								$created_file_path = isset($item[0]) ? $item[0] : null;
								$created_file_abs_path = $obj->getLayerPathSetting() . $created_file_path;
								
								//if file was created successfully
								if (file_exists($created_file_abs_path)) {
									$class_name = pathinfo($created_file_path, PATHINFO_FILENAME);
									$method_data = PHPCodePrintingHandler::getFunctionFromFile($created_file_abs_path, $method_name, $class_name);
									$file_method_exists = !empty($method_data);
									/*echo "created_file_abs_path:$created_file_abs_path\n";
									echo "class_name:$class_name\n";
									echo "method_name:$method_name\n";
									echo "file_method_exists:$file_method_exists\n";
									echo "content:".file_get_contents($created_file_abs_path)."\n";
									die();*/
									
									//if method exists
									if ($file_method_exists) {
										$created_file_abs_path = str_replace("//", "/", $created_file_abs_path);
										//echo "created_file_abs_path:$created_file_abs_path\n";
										//echo "default_file_abs_path:$default_service_file_abs_path\n";
										
										if ($created_file_abs_path == $default_service_file_abs_path) {
											$status = true;
											//echo "files are the same\n";
										}
										//copy correspondent method from created_file_abs_path to default_service_file_abs_path
										else if (file_exists($default_service_file_abs_path)) { 
											$method_data["code"] = PHPCodePrintingHandler::getFunctionCodeFromFile($created_file_abs_path, $method_data["name"], $class_name);
											$comments = isset($method_data["comments"]) && is_array($method_data["comments"]) ? trim(implode("\n", $method_data["comments"])) : "";
											$comments .= isset($method_data["doc_comments"]) && is_array($method_data["doc_comments"]) ? ($comments ? "\n" : "") . trim(implode("\n", $method_data["doc_comments"])) : "";
											$method_data["comments"] = str_replace("\n\t", "\n", $comments);
											
											$class_data = PHPCodePrintingHandler::getClassOfFile($default_service_file_abs_path);
											$class_data_name = isset($class_data["name"]) ? $class_data["name"] : null;
											
											$status = PHPCodePrintingHandler::addFunctionToFile($default_service_file_abs_path, $method_data, $class_data_name);
											//echo "addFunctionToFile:$status\n";
											
											@unlink($created_file_abs_path);
										}
										//rename created_file_abs_path to default_service_file_abs_path
										else {
											$class_data = PHPCodePrintingHandler::getClassOfFile($created_file_abs_path); //must be here before the rename
											$status = rename($created_file_abs_path, $default_service_file_abs_path);
											//echo "rename:$status\n";
											
											if ($status) {
												$class_data_name = isset($class_data["name"]) ? $class_data["name"] : null;
												$class_data_namespace = isset($class_data["namespace"]) ? $class_data["namespace"] : null;
												//echo "class_data:";print_r($class_data);echo "\n";
												
												$src_class_name = PHPCodePrintingHandler::prepareClassNameWithNameSpace($class_data_name, $class_data_namespace);
												$dst_class_name = PHPCodePrintingHandler::prepareClassNameWithNameSpace(pathinfo($default_service_file_abs_path, PATHINFO_FILENAME), $class_data_namespace);
												
												$status = $src_class_name == $dst_class_name || PHPCodePrintingHandler::renameClassFromFile($default_service_file_abs_path, $src_class_name, $dst_class_name);
												/*echo "src_class_name:$src_class_name\n";
												echo "dst_class_name:$dst_class_name\n";
												echo "renameClassFromFile:$status\n";*/
												
												//if rename was unsuccessfully, delete file
												if (!$status)
													@unlink($default_service_file_abs_path);
											}
											else //if rename was unsuccessfully, delete file
												@unlink($created_file_abs_path);
										}
										//echo "Final status:$status\n";
										//die();
										
										//get out from this function if stsatus is ok, otherwise continues to next broker
										if ($status)
											return true;
									}
									//if method does NOT exists, remove created file
									else if ($created_file_abs_path != $default_service_file_abs_path)
										@unlink($created_file_abs_path);
								}
							}
						}
					}
				}
			} 
			else if (is_a($obj, "IbatisDataAccessLayer")) {
				$default_service_file_name = $query_id . ".xml";
				$default_service_file_abs_path = $obj->getLayerPathSetting() . $path . $default_service_file_name;
				
				//check if folder path is allowed inside of $filter_by_layout
				$allowed = !$this->filter_by_layout || $this->UserAuthenticationHandler->isLayoutInnerFilePermissionAllowed(dirname($default_service_file_abs_path), $this->filter_by_layout, "layer", UserAuthenticationHandler::$PERMISSION_BELONG_NAME, false, false);
				
				if (!$allowed)
					return false;
				
				//check if rule already exists in default file and if it does return true
				if (file_exists($default_service_file_abs_path)) {
					$rule_data = WorkFlowDataAccessHandler::getXmlQueryOrMapData($default_service_file_abs_path, $rule_name, array($query_type));
					//print_r($rule_data);
					
					if ($rule_data)
						return true;
				}
				
				//create new rule but only if doesn't exists yet
				$url = $this->project_url_prefix . "phpframework/dataaccess/create_data_access_objs_automatically?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path&item_type=ibatis";
				
				//prepare post_data
				$post_data = array(
					"db_broker" => $this->db_broker,
					"db_driver" => $this->db_driver,
					"type" => $this->db_type,
					"st" => array($this->db_table),
					"sta" => array($this->db_table => $this->db_table_alias),
					"step_2" => true,
					"overwrite" => false,
					"with_maps" => false,
					"json" => true,
				);
				
				//create file automatically
				$raw_statuses = $this->UserAuthenticationHandler->getURLContent($url, $post_data);
				$statuses = $raw_statuses ? json_decode($raw_statuses, true) : $raw_statuses;
				//echo "<pre>$html\n<br>";print_r($statuses);die();
				
				debug_log("[SequentialLogicalActivityBLResourceCreator->createBrokerService][based in IbatisDataAccessLayer]:\n- Call getURLContent for: $url\n- With Post data: " . print_r($post_data, 1) . "\n- With raw response: $raw_statuses\n- With decoded response: " . print_r($statuses, 1) . "\n");
				
				//check if file was created and if so checks if the correspondent function exists based in broker_service_type. If yes copies the function to $default_service_file_abs_path, or if it doesn't exists, rename the created file to $default_service_file_abs_path.
				if ($statuses) {
					$item = $statuses[0];
					$created_file_path = isset($item[0]) ? $item[0] : null;
					$created_file_abs_path = $obj->getLayerPathSetting() . $created_file_path;
					
					//if file was created successfully
					if (file_exists($created_file_abs_path)) {
						$rule_data = WorkFlowDataAccessHandler::getXmlQueryOrMapData($created_file_abs_path, $rule_name, array($query_type));
						$file_rule_exists = !empty($rule_data);
						
						//if method exists
						if ($file_rule_exists) {
							$created_file_abs_path = str_replace("//", "/", $created_file_abs_path);
							
							if ($created_file_abs_path == $default_service_file_abs_path)
								$status = true;
							//copy correspondent method from created_file_abs_path to default_service_file_abs_path
							else if (file_exists($default_service_file_abs_path)) { 
								$object = array(
									"queries" => array(
										array(
											"name" => "queries",
											"childs" => array(
												$query_type => array(
													array(
														"name" => $query_type,
														"@" => array(
															"id" => isset($rule_data["@"]["id"]) ? $rule_data["@"]["id"] : null
														),
														"value" => $sql = XMLFileParser::getValue($rule_data)
													)
												)
											)
										)
									)
								);
								
								$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
								$status = $WorkFlowDataAccessHandler->createTableQueriesFromObjectData($default_service_file_abs_path, $object);
								
								//delete cache for this xml file so it can load again
								//echo "cache exists(".$obj->getSQLClient()->getSQLMapClientCache()->cachedXMLElmExists($default_service_file_abs_path)."):".$obj->getSQLClient()->getSQLMapClientCache()->getCachedFilePath($default_service_file_abs_path);die();
								$obj->getSQLClient()->getSQLMapClientCache()->deleteCachedXMLElm($default_service_file_abs_path);
								
								@unlink($created_file_abs_path);
							}
							//rename created_file_abs_path to default_service_file_abs_path
							else { 
								$status = rename($created_file_abs_path, $default_service_file_abs_path);
								
								if (!$status) //if rename was unsuccessfully, delete file
									@unlink($created_file_abs_path);
							}
							
							//get out from this function if stsatus is ok, otherwise continues to next broker
							if ($status)
								return true;
						}
						//if method does NOT exists, remove created file
						else if ($created_file_abs_path != $default_service_file_abs_path)
							@unlink($created_file_abs_path);
					}
				}
			}
			else if (is_a($obj, "HibernateDataAccessLayer")) {
				$default_service_file_name = $query_id . ".xml";
				$default_service_file_abs_path = $obj->getLayerPathSetting() . $path . $default_service_file_name;
				
				//check if folder path is allowed inside of $filter_by_layout
				$allowed = !$this->filter_by_layout || $this->UserAuthenticationHandler->isLayoutInnerFilePermissionAllowed(dirname($default_service_file_abs_path), $this->filter_by_layout, "layer", UserAuthenticationHandler::$PERMISSION_BELONG_NAME, false, false);
				
				if (!$allowed)
					return false;
				
				//create hibernate obj but only if does not exists yet
				if (file_exists($default_service_file_abs_path)) 
					return true;
				else {
					$url = $this->project_url_prefix . "phpframework/dataaccess/create_data_access_objs_automatically?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path&item_type=hibernate";
					
					//prepare post_data
					$post_data = array(
						"db_broker" => $this->db_broker,
						"db_driver" => $this->db_driver,
						"type" => $this->db_type,
						"st" => array($this->db_table),
						"sta" => array($this->db_table => $this->db_table_alias),
						"step_2" => true,
						"overwrite" => false,
						"with_maps" => false,
						"json" => true,
					);
					
					//create file automatically
					$raw_statuses = $this->UserAuthenticationHandler->getURLContent($url, $post_data);
					$statuses = $raw_statuses ? json_decode($raw_statuses, true) : $raw_statuses;
					//echo "<pre>$html\n<br>";print_r($statuses);die();
					
					debug_log("[SequentialLogicalActivityBLResourceCreator->createBrokerService][based in HibernateDataAccessLayer]:\n- Call getURLContent for: $url\n- With Post data: " . print_r($post_data, 1) . "\n- With raw response: $raw_statuses\n- With decoded response: " . print_r($statuses, 1) . "\n");
					
					//check if file was created and if so checks if the correspondent function exists based in broker_service_type. If yes copies the function to $default_service_file_abs_path, or if it doesn't exists, rename the created file to $default_service_file_abs_path.
					if ($statuses) {
						$item = $statuses[0];
						$created_file_path = isset($item[0]) ? $item[0] : null;
						$created_file_abs_path = $obj->getLayerPathSetting() . $created_file_path;
						
						if ($created_file_abs_path == $default_service_file_abs_path)
							return true;
					}
				}
			}
		}
	}

	//this function has the same name than in the create_presentation_uis_automatically.js
	private function loadTaskParams($task) {
		$broker_name = isset($task["broker_name"]) ? $task["broker_name"] : null;
		$broker_bean_name = isset($task["bean_name"]) ? $task["bean_name"] : null;
		$broker_bean_file_name = isset($task["bean_file_name"]) ? $task["bean_file_name"] : null;
		$item_type = isset($task["item_type"]) ? $task["item_type"] : null;
		$service = isset($task["service"]) ? $task["service"] : null;
		
		$get_business_logic_properties_url = $this->project_url_prefix . "phpframework/businesslogic/get_business_logic_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#&service=#service#";
		$get_query_properties_url = $this->project_url_prefix . "phpframework/dataaccess/get_query_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&db_driver=#db_driver#&db_type=#db_type#&path=#path#&query_type=#query_type#&query=#query#&obj=#obj#&relationship_type=#relationship_type#";
		$url = null;
		
		$service_path = isset($service["path"]) ? $service["path"] : null;
		$service_id = isset($service["service_id"]) ? $service["service_id"] : null;
		$service_type = isset($service["service_type"]) ? $service["service_type"] : null;
		
		//get params for this $service
		switch ($item_type) {
			case "businesslogic":
				$url = $get_business_logic_properties_url;
				$url = str_replace("#bean_name#", $broker_bean_name, $url);
				$url = str_replace("#bean_file_name#", $broker_bean_file_name, $url);
				$url = str_replace("#path#", $service_path, $url);
				$url = str_replace("#service#", $service_id, $url);
				break;
			case "ibatis":
				$url = $get_query_properties_url;
				$url = str_replace("#bean_name#", $broker_bean_name, $url);
				$url = str_replace("#bean_file_name#", $broker_bean_file_name, $url);
				$url = str_replace("#path#", $service_path, $url);
				$url = str_replace("#db_driver#", $this->db_driver, $url);
				$url = str_replace("#db_type#", $this->db_type, $url);
				$url = str_replace("#query_type#", $service_type, $url);
				$url = str_replace("#query#", $service_id, $url);
				$url = str_replace("#obj#", "", $url);
				$url = str_replace("#relationship_type#", "queries", $url);
				break;
			case "hibernate":
				$method = isset($service["service_method"]) ? $service["service_method"] : null;
				$relationship_type = "";
				$query_type = "";
				$available_native_methods = array("insert", "insertAll", "update", "updateAll", "insertOrUpdate", "insertOrUpdateAll", "updatePrimaryKeys", "delete", "deleteAll", "findById", "find", "count");
				$available_relationship_methods = array("findRelationships",  "findRelationship", "countRelationships",  "countRelationship");
				$available_query_methods = array("callInsertSQL", "callInsert", "callUpdateSQL", "callUpdate", "callDeleteSQL", "callDelete", "callSelectSQL", "callSelect", "callProcedureSQL", "callProcedure");
				
				if (in_array($method, $available_native_methods))
					$relationship_type = "native";
				else if (in_array($method, $available_relationship_methods)) {
					$method = isset($service["sma_rel_name"]) ? $service["sma_rel_name"] : null;
					$relationship_type = "relationships";
				}
				else if (in_array($method, $available_query_methods)) {
					$relationship_type = "queries";
					
					switch ($method) {
						case "callInsertSQL":
						case "callInsert":
							$query_type = "insert";
							break;
						case "callUpdateSQL":
						case "callUpdate":
							$query_type = "update";
							break;
						case "callDeleteSQL":
						case "callDelete":
							$query_type = "delete";
							break;
						case "callSelectSQL":
						case "callSelect":
							$query_type = "select";
							break;
						case "callProcedureSQL":
						case "callProcedure":
							$query_type = "procedure";
							break;
					}
				}
				
				$url = $get_query_properties_url;
				$url = str_replace("#bean_name#", $broker_bean_name, $url);
				$url = str_replace("#bean_file_name#", $broker_bean_file_name, $url);
				$url = str_replace("#path#", $service_path, $url);
				$url = str_replace("#db_driver#", $this->db_driver, $url);
				$url = str_replace("#db_type#", $this->db_type, $url);
				$url = str_replace("#query_type#", $query_type, $url);
				$url = str_replace("#query#", $method, $url);
				$url = str_replace("#obj#", $service_id, $url);
				$url = str_replace("#relationship_type#", $relationship_type, $url);
				break;
		}
		
		if ($url) {
			$params = $this->UserAuthenticationHandler->getURLContent($url);
			$params = json_decode($params, true);
			//echo "<pre>$url\n<br>";print_r($params);die();
			
			//prepare parameters
			if ($params) {
				$new_params = array();
				
				foreach ($params as $param_name => $param_type)
					$new_params[] = array(
						"key" => $param_name,
						"key_type" => "string",
						"value" => "",
						"value_type" => $param_type
					);
				
				//set parameter key according with item_type
				$parameters_key = "parameters";
				
				if ($item_type == "hibernate") {
					$available_relationship_methods = array("findRelationships",  "findRelationship", "countRelationships",  "countRelationship");
					$parameters_key = "sma_data";
					
					if (isset($service["service_method"]) && in_array($service["service_method"], $available_relationship_methods))
						$parameters_key = "sma_parent_ids";
				}
				//echo "parameters_key:$parameters_key\n";
				
				$service[$parameters_key . "_type"] = "array";
				$service[$parameters_key] = $new_params;
				
				$task["service"] = $service;
			}
		}
		
		return $task;
	}

	//this function has the same name than in the create_presentation_uis_automatically.js
	private function loadTaskParamsWithDefaultValues($task, $var_prefix) {
		$item_type = isset($task["item_type"]) ? $task["item_type"] : null;
		$service = isset($task["service"]) ? $task["service"] : null;
		$var_prefix = substr($var_prefix, 0, 1) == '$' || substr($var_prefix, 0, 2) == '@$' ? $var_prefix : '$' . $var_prefix;
		
		if ($item_type == "db") {
			$sql = isset($service["sql"]) ? $service["sql"] : null;
			
			if ($sql) {
				preg_match_all("/#([^#]+)#/", $sql, $matches, PREG_PATTERN_ORDER);
				
				if ($matches && $matches[0]) {
					for ($i = 0, $t = count($matches[0]); $i < $t; $i++) {
						$m = $matches[0][$i];
						$name = str_replace("#", "", $m);
						$value = '{' . $var_prefix . '[\'' . $name . '\']}';
						
						$sql = str_replace($m, $value, $sql);
					}
				}
				
				$service["sql"] = trim($sql);
			}
			else if (!empty($task["is_db_dao_action_task"])) {
				$items = array(
					"attributes" => isset($service["attributes"]) ? $service["attributes"] : null, 
					"conditions" => isset($service["conditions"]) ? $service["conditions"] : null
				);
				
				foreach ($items as $type => $a_or_c)
					if (is_array($a_or_c)) {
						foreach ($a_or_c as $idx => $param) {
							$name = isset($param["key"]) ? $param["key"] : null;
							$value = isset($param["value"]) ? $param["value"] : null;
							
							preg_match_all("/#([^#]+)#/", $value, $matches, PREG_PATTERN_ORDER);
							
							if ($matches && $matches[0]) {
								$v = null;
								$t = count($matches[0]);
								
								for ($i = 0; $i < $t; $i++) {
									$m = $matches[0][$i];
									$name = str_replace("#", "", $m);
									$v = '{' . $var_prefix . '[\'' . $name . '\']}';
									
									$value = str_replace($m, $v, $value);
								}
								
								$service[$type][$idx]["value_type"] = "string";
								$service[$type][$idx]["value"] = $value;
								
								//if value is only the variable, covert it from string to variable
								if ($t == 1 && substr($value, 0, 2) == '{$' && $value == $v) {
									$service[$type][$idx]["value_type"] = "variable";
									$service[$type][$idx]["value"] = substr($value, 1, -1); //remove { and }
								}
							}
						}
					}
			}
		}
		else {
			$parameters_key = "parameters";
			
			if ($item_type == "hibernate") {
				$available_relationship_methods = array("findRelationships",  "findRelationship", "countRelationships",  "countRelationship");
				$parameters_key = "sma_data";
				
				if (isset($service["service_method"]) && in_array($service["service_method"], $available_relationship_methods))
					$parameters_key = "sma_parent_ids";
				
				//prepare sma ids variable name
				$service["sma_ids"] = "ids";
				$service["sma_ids_type"] = "variable";
			}
			
			$items = $service[$parameters_key];
			
			if (is_array($items)) {
				foreach ($items as $idx => $param) {
					$name = isset($param["key"]) ? $param["key"] : null;
					
					$items[$idx]["value_type"] = "";
					$items[$idx]["value"] = $var_prefix . '[\'' . $name . '\']';
				}
				
				$service[$parameters_key] = $items;
			}
		}
		
		$task["service"] = $service;
		
		return $task;
	}

	private function loadTaskConditions($task) {
		$item_type = isset($task["item_type"]) ? $task["item_type"] : null;
		
		if ($item_type == "businesslogic" || $item_type == "ibatis") {
			$task["service"]["parameters"] = "data";
			$task["service"]["parameters_type"] = "variable";
		}
		else if ($item_type == "hibernate") {
			$task["service"]["sma_data"] = "data";
			$task["service"]["sma_data_type"] = "variable";
		}
		else if ($item_type == "db" && !empty($task["service"]["sql"]) && isset($task["service"]["sql_type"]) && $task["service"]["sql_type"] == "string")
			$task["service"]["sql"] .= ' WHERE $conds';
		else if ($item_type == "db" && !empty($task["is_db_dao_action_task"])) {
			$task["service"]["conditions"] = "conditions";
			$task["service"]["conditions_type"] = "variable";
			
			//if not a variable, must be an array
			$task_service_options = isset($task["service"]["options"]) ? $task["service"]["options"] : null;
			$task_service_options_type = isset($task["service"]["options_type"]) ? $task["service"]["options_type"] : null;
			
			if (($task_service_options_type != "variable" || !trim($task_service_options)) && !is_array($task_service_options))
				$task["service"]["options"] = array();
			
			//add conditions_join to options
			if (isset($task["service"]["options"]) && is_array($task["service"]["options"]))
				$task["service"]["options"][] = array(
					"key" => "conditions_join",
					"key_type" => "string",
					"value" => "conditions_join",
					"value_type" => "variable",
				);
		}
		
		return $task;					
	}

	private function loadTaskLimitAndStart($task) {
		$item_type = isset($task["item_type"]) ? $task["item_type"] : null;
		
		//prepare limit and start
		$options = $item_type == "hibernate" ? (isset($task["service"]["sma_options"]) ? $task["service"]["sma_options"] : null) : (isset($task["service"]["options"]) ? $task["service"]["options"] : null);
		$options_type = $item_type == "hibernate" ? (isset($task["service"]["sma_options_type"]) ? $task["service"]["sma_options_type"] : null) : (isset($task["service"]["options_type"]) ? $task["service"]["options_type"] : null);
		
		if (!is_array($options)) {
			$options = array();
			$options_type = "array";
		}
		
		$options[] = array(
			"key" => "limit",
			"key_type" => "string",
			"value" => "limit",
			"value_type" => "variable",
		);
		$options[] = array(
			"key" => "start",
			"key_type" => "string",
			"value" => "start",
			"value_type" => "variable",
		);
		
		if ($item_type == "hibernate") {
			$task["service"]["sma_options"] = $options;
			$task["service"]["sma_options_type"] = $options_type;
		}
		else {
			$task["service"]["options"] = $options;
			$task["service"]["options_type"] = $options_type;
		}
		
		return $task;
	}

	private function loadSort($task) {
		$item_type = isset($task["item_type"]) ? $task["item_type"] : null;
		
		//prepare limit and start
		$options = $item_type == "hibernate" ? (isset($task["service"]["sma_options"]) ? $task["service"]["sma_options"] : null) : (isset($task["service"]["options"]) ? $task["service"]["options"] : null);
		$options_type = $item_type == "hibernate" ? (isset($task["service"]["sma_options_type"]) ? $task["service"]["sma_options_type"] : null) : (isset($task["service"]["options_type"]) ? $task["service"]["options_type"] : null);
		
		if (!is_array($options)) {
			$options = array();
			$options_type = "array";
		}
		
		$options[] = array(
			"key" => "sort",
			"key_type" => "string",
			"value" => "sort",
			"value_type" => "variable",
		);
		
		if ($item_type == "hibernate") {
			$task["service"]["sma_options"] = $options;
			$task["service"]["sma_options_type"] = $options_type;
		}
		else {
			$task["service"]["options"] = $options;
			$task["service"]["options_type"] = $options_type;
		}
		
		return $task;
	}

	private function resetTaskOptionNoCache($task) {
		$item_type = isset($task["item_type"]) ? $task["item_type"] : null;
		$options = $item_type == "hibernate" ? (isset($task["service"]["sma_options"]) ? $task["service"]["sma_options"] : null) : (isset($task["service"]["options"]) ? $task["service"]["options"] : null);
		
		if (is_array($options))
			foreach ($options as $idx => $option) {
				$option_key = isset($option["key"]) ? $option["key"] : null;
				$option_key_type = isset($option["key_type"]) ? $option["key_type"] : null;
				
				if ($option_key == "no_cache" && $option_key_type == "string") {
					$options[$idx]["value"] = "no_cache";
					$options[$idx]["value_type"] = "variable";
					break;
				}
			}
		
		if ($item_type == "hibernate")
			$task["service"]["sma_options"] = $options;
		else
			$task["service"]["options"] = $options;
		
		return $task;
	}

	private function getBrokerCode($task) {
		$broker_name = isset($task["broker_name"]) ? $task["broker_name"] : null;
		$item_type = isset($task["item_type"]) ? $task["item_type"] : null;
		
		switch ($item_type) {
			case "businesslogic": return isset($this->layer_brokers_settings["business_logic_brokers_obj"][$broker_name]) ? $this->layer_brokers_settings["business_logic_brokers_obj"][$broker_name] : null;
			case "ibatis": return isset($this->layer_brokers_settings["ibatis_brokers_obj"][$broker_name]) ? $this->layer_brokers_settings["ibatis_brokers_obj"][$broker_name] : null;
			case "hibernate": return isset($this->layer_brokers_settings["hibernate_brokers_obj"][$broker_name]) ? $this->layer_brokers_settings["hibernate_brokers_obj"][$broker_name] : null;
			case "db": return isset($this->layer_brokers_settings["db_brokers_obj"][$broker_name]) ? $this->layer_brokers_settings["db_brokers_obj"][$broker_name] : null;
		}
		
		return null;
	}

	private function getTaskConditionsCode($task) {
		$item_type = isset($task["item_type"]) ? $task["item_type"] : null;
		$code = SequentialLogicalActivityBLResourceCreator::getDefaultConditionsCode();
		
		if ($item_type == "businesslogic" || $item_type == "hibernate")
			$code .= '
$data = array(
	"conditions" => $conditions,
	"conditions_join" => $conditions_join
);
';
		else if ($item_type == "ibatis")
			$code .= '
include_once get_lib("org.phpframework.db.DB");

$conds = DB::getSQLConditions($conditions, $conditions_join);
$data = array(
	"conditions" => $conds, //just in case we have a query with #conditions#
	"searching_condition" => $conds ? " AND ($conds)" : ""
);
';
		else if ($item_type == "db") {
			if (!empty($task["sql"]))
				$code .= '
include_once get_lib("org.phpframework.db.DB");

$conds = DB::getSQLConditions($conditions, $conditions_join);
$conds = $conds ? $conds : "1=1";
';
		else if (!empty($task["is_db_dao_action_task"]) && isset($task["service"]["options_type"]) && $task["service"]["options_type"] == "variable" && isset($task["service"]["options"]) && trim($task["service"]["options"])) {
				$options_var_name = trim($task["service"]["options"]);
				$options_var_name = substr($options_var_name, 0, 1) == '$' || substr($options_var_name, 0, 2) == '@$' ? $options_var_name : '$' . $options_var_name;
				
				$code .= '
' . $options_var_name . ' = is_array(' . $options_var_name . ') ? ' . $options_var_name . ' : array();
' . $options_var_name . '["conditions_join"] = $conditions_join;
';
			}
		}
		
		return $code;
	}

	private function getTaskCode($action_type, $task, $broker_code, $prefix = "") {
		$action_task_type = isset($task["item_type"]) && $task["item_type"] == "db" && !empty($task["is_db_dao_action_task"]) ? "dbdaoaction" : self::getActionTaskType($action_type, isset($task["item_type"]) ? $task["item_type"] : null);
		
		$task_code = self::prepareBrokerCode($this->WorkFlowTaskHandler, $action_task_type, isset($task["service"]) ? $task["service"] : null);
		$code = null;
		
		if ($task_code) {
			if ($prefix)
				$task_code = str_replace("\n", "\n$prefix", $task_code);
			
			$code = $broker_code . "->" . trim($task_code);
			//echo "code:$code";die();
		}
		
		return $code;
	}
	
	/* PRIVATE STATIC FUNCTIONS */
	
	private static function getMethodName($action_type) {
		$method_name = self::getClassName($action_type);
		return strtolower(substr($method_name, 0, 1)) . substr($method_name, 1);
	}

	private static function getClassName($db_table) {
		return str_replace(" ", "", self::getLabel($db_table));
	}

	private static function getLabel($name) {
		return ucwords(strtolower(str_replace(array("-", "_", "."), " ", $name)));
	}

	private static function isAssociativeArray($arr) {
		return is_array($arr) && array_keys($arr) !== range(0, count($arr) - 1);
	}
	
	private static function getPermissionsUserTypeIds($permissions) {
		$user_type_ids = array();
		$aux = null;
		
		if ($permissions) {
			if (is_string($permissions) || is_numeric($permissions) || !self::isAssociativeArray(($permissions)))
				$aux = is_array($permissions) ? $permissions : array($permissions);
			else if (is_array($permissions))
				foreach ($permissions as $k => $v) 
					if ($k == "access" || $k == "view" || $k == "show") {
						if (is_string($v) || is_numeric($v) || !self::isAssociativeArray($v))
							$aux = is_array($v) ? $v : array($v);
						else if (is_array($v) && !empty($v["user_type_ids"]))
							$aux = is_array($v["user_type_ids"]) ? $v["user_type_ids"] : array($v["user_type_ids"]);
					}
		}
		
		if (is_array($aux))
			foreach ($aux as $user_type_id)
				if (is_numeric($user_type_id))
					$user_type_ids[] = $user_type_id;
		
		return $user_type_ids;
	}

	private static function getPermissionsResourceNames($permissions) {
		if ($permissions) {
			if (self::isAssociativeArray($permissions))
				foreach ($permissions as $k => $v) 
					if ($k == "access" || $k == "view" || $k == "show") {
						if (self::isAssociativeArray($v) && !empty($v["resources"])) {
							$resources = is_array($v["resources"]) ? $v["resources"] : array($v["resources"]);
							$names = array();
							
							for ($i = 0; $i < count($resources); $i++) {
								$resource = $resources[$i];
								$resource_name = $resource; //if is string
								
								if (self::isAssociativeArray($resource))
									$resource_name = isset($resource["name"]) ? $resource["name"] : null;
								
								if ($resource_name)
									$names[] = $resource;
							}
							
							return $names;
						}
					}
		}
		
		return null;
	}
	
	//copied from CMSPresentationFormSettingsUIHandler::getBrokerSettingsOptionsCode, so if you change this method, please mae the correspodnent changes in this other method too.
	private static function getBrokerSettingsOptionsCode($WorkFlowTaskHandler, $action_type, $task) {
		$item_type = isset($task["item_type"]) ? $task["item_type"] : null;
		$service = isset($task["service"]) ? $task["service"] : null;
		
		switch ($item_type) {
			case "businesslogic":
			case "ibatis":
				if (!empty($service["options"])) {
					$service["parameters"] = null;
					$service["parameters_type"] = "";
				
					$action_task_type = self::getActionTaskType($action_type, $item_type);
					$code = self::prepareBrokerCode($WorkFlowTaskHandler, $action_task_type, $service);
					
					if ($code) {
						$pos = strpos($code, '", null, ');
						$options = substr($code, $pos + 9, -1);
					}
				}
				break;
			case "hibernate":
				if (!empty($service["sma_options"])) {
					$action_task_type = self::getActionTaskType($action_type, $item_type);
					$code_1 = self::prepareBrokerCode($WorkFlowTaskHandler, $action_task_type, $service);
					
					$service["sma_options"] = null;
					$service["sma_options_type"] = "";
					$code_2 = self::prepareBrokerCode($WorkFlowTaskHandler, $action_task_type, $service);
					
					if ($code_1 && $code_2) {
						$code_2 = substr(trim($code_2), 0, -1);//removes last ')'
						$code_1 = substr(trim($code_1), 0, -1);//removes last ')'
						
						//in some cases the $code_2 ends with "null", so we need to remove it if it doesnt exist in code_1.
						if (strpos($code_1, $code_2) === false && preg_match("/(\(|,)\s*null\s*$/", $code_2))
							$code_2 = preg_replace("/\s*null\s*$/", "", $code_2);
						
						$code_1 = trim( str_replace($code_2, "", $code_1) );
						$code_1 = substr($code_1, 0, 1) == "," ? substr($code_1, 1) : $code_1;//removes comma if exists.
						$options = trim($code_1);
					}
				}
				break;
			case "db":
				if (!empty($service["options"])) {
					$service["sql"] = "test";
					$service["sql_type"] = "variable";
					
					$action_task_type = self::getActionTaskType($action_type, $item_type);
					$code = self::prepareBrokerCode($WorkFlowTaskHandler, $action_task_type, $service);
					
					if ($code) {
						$pos = strpos($code, '($test, ');
						$options = substr($code, $pos + 8, -1);
					}
				}
				break;
		}
		
		return isset($options) && $options != "null" ? $options : "";
	}
	
	private static function prepareBrokerSettingsCommonOptions(&$task, &...$tasks) { //pass multiple arguments
		array_unshift($tasks, $task);
		$tasks[0] = &$task; //pass the reference
		
		//check if tasks options are arrays
		$status = true;
		
		foreach ($tasks as &$task) {
			$task_service_options = isset($task["service"]["options"]) ? $task["service"]["options"] : null;
			$task_service_options_type = isset($task["service"]["options_type"]) ? $task["service"]["options_type"] : null;
			
			if ($task_service_options_type != "array" || !is_array($task_service_options)) {
				$status = false;
				break;
			}
			else if (is_array($task_service_options) && array_key_exists("key", $task_service_options))
				$task["service"]["options"] = array($task_service_options);
		}
		
		unset($task); //remove the latest reference for the $task variable
		
		if ($status) {
			$common_options = array();
			$repeated_options = array();
			
			//get common options from tasks
			$t = count($tasks);
			
			foreach ($tasks as $idx => $task) {
				if (!empty($task["service"]["options"]))
					foreach ($task["service"]["options"] as $idy => $option) {
						$exists_count = 1;
						$option_key = isset($option["key"]) ? $option["key"] : null;
						$option_key_type = isset($option["key_type"]) ? $option["key_type"] : null;
						$option_value = isset($option["value"]) ? $option["value"] : null;
						$option_value_type = isset($option["value_type"]) ? $option["value_type"] : null;
						
						for ($i = 0; $i < $t; $i++) {
							$other_task = $tasks[$i];
							
							if ($i != $idx && !empty($other_task["service"]["options"]))
								foreach ($other_task["service"]["options"] as $other_option) {
									$other_option_key = isset($other_option["key"]) ? $other_option["key"] : null;
									$other_option_key_type = isset($other_option["key_type"]) ? $other_option["key_type"] : null;
									$other_option_value = isset($other_option["value"]) ? $other_option["value"] : null;
									$other_option_value_type = isset($other_option["value_type"]) ? $other_option["value_type"] : null;
									
									if ($other_option_key == $option_key && $other_option_key_type == $option_key_type && $other_option_value == $option_value && $other_option_value_type == $option_value_type) {
										$exists_count++;
										break;
									}
								}
						}
						
						if ($exists_count == $t) {
							$option_id = serialize($option);
							
							if (!in_array($option_id, $repeated_options)) {
								$common_options[] = $option;
								$repeated_options[] = $option_id;
							}
						}
					}
			}
			
			//delete common options from tasks
			$t = count($common_options);
			
			for ($i = 0; $i < $t; $i++) {
				$option = $common_options[$i];
				$option_key = isset($option["key"]) ? $option["key"] : null;
				$option_key_type = isset($option["key_type"]) ? $option["key_type"] : null;
				$option_value = isset($option["value"]) ? $option["value"] : null;
				$option_value_type = isset($option["value_type"]) ? $option["value_type"] : null;
				
				foreach ($tasks as $idx => &$task) {
					if (!empty($task["service"]["options"]))
						foreach ($task["service"]["options"] as $idy => $task_option) {
							$task_option_key = isset($task_option["key"]) ? $task_option["key"] : null;
							$task_option_key_type = isset($task_option["key_type"]) ? $task_option["key_type"] : null;
							$task_option_value = isset($task_option["value"]) ? $task_option["value"] : null;
							$task_option_value_type = isset($task_option["value_type"]) ? $task_option["value_type"] : null;
							
							if ($task_option_key == $option_key && $task_option_key_type == $option_key_type && $task_option_value == $option_value && $task_option_value_type == $option_value_type) {
								unset($task["service"]["options"][$idy]);
								break;
							}
						}
				}
				
				unset($task); //remove the latest reference for the $task variable
			}
			//echo "<pre>";print_r($common_options);print_r($tasks);die();
			
			return $common_options;
		}
				
		return null;
	}

	//copied from CMSPresentationFormSettingsUIHandler::prepareBrokerCode, so if you change this method, please mae the correspodnent changes in this other method too.
	private static function prepareBrokerCode($WorkFlowTaskHandler, $action_task_type, $task_properties) {
		$task = $WorkFlowTaskHandler->getTasksByTag($action_task_type);
		$task = isset($task[0]) ? $task[0] : null;
		
		if ($task) {
			$task["properties"] = $task_properties;
			$task["obj"]->data = $task;
			$code = trim( $task["obj"]->printCode(null, null) );
			$code = substr($code, -1) == ";" ? substr($code, 0, -1) : $code;
			return $code;
		}
		
		return "";
	}

	private static function getActionTaskType($action_type, $item_type) {
		$is_set = in_array($action_type, array("insert", "update", "save", "multiple_save", "update_attribute", "insert_update_attribute", "insert_delete_attribute", "multiple_insert_delete_attribute", "delete", "multiple_delete"));
		
		switch ($item_type) {
			case "businesslogic": return "callbusinesslogic";
			case "ibatis": return "callibatisquery";
			case "hibernate": return "callhibernatemethod";
			case "db": return $is_set ? "setquerydata" : "getquerydata";
		}
		
		return null;
	}

	private static function isDBPrimitiveTask($task) {
		return isset($task["item_type"]) && in_array($task["item_type"], array("ibatis", "hibernate", "db"));
	}
	
	private static function isIbatisTask($task) {
		return isset($task["item_type"]) && $task["item_type"] == "ibatis";
	}

	//This method is called inside of the getUpdateActionPreviousCode too
	//copied from CMSPresentationFormSettingsUIHandler::getInsertActionPreviousCode and SequentialLogicalActivityBLResourceCreator::getInsertActionPreviousCode, so if you change this method, please make the correspodnent changes in this other method too.
	private static function getInsertActionPreviousCode($tables, $table_name, $attributes, $task, $WorkFlowTaskHandler, $broker_code, $var_prefix, $is_insert_task = true, $is_update_task = false, $is_update_attribute_task = false) {
		$code = "";
		$is_db_primitive_action = self::isDBPrimitiveTask($task); //used when insert and update action
		
		if (!$is_db_primitive_action) {
			$code = SequentialLogicalActivityBLResourceCreator::getInsertActionPreviousCode($tables, $table_name, $attributes, $var_prefix, $is_insert_task, $is_update_task);
			$code = preg_replace('/(\$[a-z]+\["logged_user_id"\]\s*=\s*)\$data\["logged_user_id"\];/', '$1self::getLoggedUserId($EVC);', $code);
			$code = preg_replace('/(\$[a-z]+\["logged_user_id"\]\s*=\s*)isset\(\$data\[\"logged_user_id\"\]\)\s*\?\s*\$data\[\"logged_user_id\"\]\s*:\s*null\s*;/', '$1self::getLoggedUserId($EVC);', $code);
		}
		else {
			//prepare code if primitive task
			$var_prefix = substr($var_prefix, 0, 1) == '$' || substr($var_prefix, 0, 2) == '@$' ? $var_prefix : '$' . $var_prefix;
			$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
			$is_ibatis = self::isIbatisTask($task);
			$logged_user_id_code = null;
			
			foreach ($attributes as $attr_name) {
				$attr = isset($attrs[$attr_name]) ? $attrs[$attr_name] : null;
				$is_created_attribute = ObjTypeHandler::isDBAttributeNameACreatedDate($attr_name) || ObjTypeHandler::isDBAttributeNameACreatedUserId($attr_name);
				
				//if is an update action and is a create_date or create_by attribute, ignore attribute
				if ($is_update_task && empty($attr["primary_key"]) && $is_created_attribute) 
					continue;
				
				$type = isset($attr["type"]) ? $attr["type"] : null;
				$allow_null = !isset($attr["null"]) || $attr["null"];
				$is_numeric_type = ObjTypeHandler::isDBTypeNumeric($type) || ObjTypeHandler::isPHPTypeNumeric($type);
				$is_blob_type = ObjTypeHandler::isDBTypeBlob($type);
				
				$is_logged_user_id_attribute = (ObjTypeHandler::isDBAttributeNameACreatedUserId($attr_name) || ObjTypeHandler::isDBAttributeNameAModifiedUserId($attr_name)) && $is_numeric_type;
				
				//Note that the array_key_exists is very important bc of the update_attribute action, otherwisse we are adding attributes when the user only ask us to save another attribute. Is important too for the business logic services where we only want to check the values if they exists, bc the default value is already set inside of the business logic service.
				$array_key_exists = !empty($attr["primary_key"]) || $is_update_attribute_task || ($is_update_task && $is_created_attribute) ? 'array_key_exists("' . $attr_name . '", ' . $var_prefix . ') && ' : '';
				
				//check if field is checkbox/boolean and if yes the default should be replaced by 0, bc it means the user set the checkbox to unchcekd which makes the browser to not include this attribute in the requests...
				//Note that this must happens if strlen($attr["default"]) > 0 or if there is no $attr["default"]. In both cases this must happen! Unless it allows NULL, which in this case we don't need to set the default to 0, bc we can set it to null, as shown in the code in this function.
				$input_type = null;
				CMSPresentationFormSettingsUIHandler::prepareFormInputParameters($attr, $input_type);
				$attr_default = isset($attr["default"]) ? $attr["default"] : null;
				$is_checkbox = (strlen($attr_default) || !$allow_null) && ($input_type == "checkbox" || $input_type == "radio") && $is_numeric_type;
				
				if ($is_checkbox) 
					$attr["default"] = 0; //discart on purpose the $attr["default"], bc the default value may be 1, and we want to set it to 0 instead, since if the user doesn't check the checkbox, it means the browser will return an empty string and we want to save his choice in the DB. If we leave the original $attr["default"] (that could be 1) than is the same that the user check the checkbox, which doesn't make sense.  
				
				//prepare code
				if ($is_insert_task && !empty($attr["primary_key"]) && WorkFlowDataAccessHandler::isAutoIncrementedAttribute($attr)) {
					$code .= self::getInsertActionPreviousCodeIfBrokerSettingsContainsAutoIncrementPrimaryKeys($table_name, $attr_name, $attr, $task, $WorkFlowTaskHandler, $broker_code, $var_prefix);
				}
				else if ($allow_null && ($is_numeric_type || ObjTypeHandler::isDBTypeDate($type))) {
					$code .= 'if (isset(' . $var_prefix . '["' . $attr_name . '"]) && is_numeric(' . $var_prefix . '["' . $attr_name . '"]) && is_string(' . $var_prefix . '["' . $attr_name . '"])) ' . $var_prefix . '["' . $attr_name . '"] += 0;' . "\n"; //convert string to real numeric value. This is very important, bc in the insert and update primitive actions of the DBSQLConverter, the sql must be created with numeric values and without quotes, otherwise the DB server gives a sql error.
					
					$default = isset($attr["default"]) && strlen($attr["default"]) ? (is_numeric($attr["default"]) ? $attr["default"] : '"' . $attr["default"] . '"') : '"DEFAULT"';
					
					if ((ObjTypeHandler::isDBAttributeNameACreatedDate($attr_name) || ObjTypeHandler::isDBAttributeNameAModifiedDate($attr_name)) && ObjTypeHandler::isDBTypeDate($type))
						$default = $type == "date" ? 'date("Y-m-d")' : 'date("Y-m-d H:i:s")';
					else if ($is_logged_user_id_attribute) {
						$logged_user_id_code = '$logged_user_id = self::getLoggedUserId($EVC);' . "\n";
						$default = '$logged_user_id > 0 ? $logged_user_id : ' . $default;
					}
					else if (ObjTypeHandler::isDBAttributeValueACurrentTimestamp($default))
						$default = 'date("Y-m-d H:i:s")';
					else if (empty($attr["default"]) && !strlen($attr["default"]) && ObjTypeHandler::isDBTypeDate($type))
						$default = $is_ibatis ? '"null"' : 'null';
					
					//only add the array_key_exists if is update_attribute action, bc this is a primitive action which needs to have the default values set in the $var_prefix even if there is no $attr_name yet...
					$code .= 'else if (' . $array_key_exists . '!strlen(trim(' . $var_prefix . '["' . $attr_name . '"]))) ' . $var_prefix . '["' . $attr_name . '"] = ' . $default . ';' . "\n";
				}
				else if ($is_numeric_type) { //for the cases with a checkbox where the value doesn't exist and is numeric
					$code .= 'if (isset(' . $var_prefix . '["' . $attr_name . '"]) && is_numeric(' . $var_prefix . '["' . $attr_name . '"]) && is_string(' . $var_prefix . '["' . $attr_name . '"])) ' . $var_prefix . '["' . $attr_name . '"] += 0;' . "\n"; //convert string to real numeric value. This is very important, bc in the insert and update primitive actions of the DBSQLConverter, the sql must be created with numeric values and without quotes, otherwise the DB server gives a sql error.
					
					if (!empty($attr["primary_key"]))
						$code .= 'if (' . $array_key_exists . '!is_numeric(' . $var_prefix . '["' . $attr_name . '"])) ' . $var_prefix . '["' . $attr_name . '"] = "null";' . "\n"; //This is on purpose so it can return empty records or don't do nothing in the DB, bc if the user wrote a pk with a non numeric value, it means is trying to do some hack.
					else {
						$default = isset($attr["default"]) && strlen($attr["default"]) ? (is_numeric($attr["default"]) ? $attr["default"] : '"' . $attr["default"] . '"') : '"DEFAULT"';
						
						if ($is_logged_user_id_attribute) {
							$logged_user_id_code = '$logged_user_id = self::getLoggedUserId($EVC);' . "\n";
							$default = '$logged_user_id > 0 ? $logged_user_id : ' . $default;
						}
						
						$code .= 'if (' . $array_key_exists . '!strlen(trim(' . $var_prefix . '["' . $attr_name . '"]))) ' . $var_prefix . '["' . $attr_name . '"] = ' . $default . ';' . "\n";
					}
				}
				
				if ($is_blob_type)
					$code .= 'if (!empty($_FILES["' . $attr_name . '"]["tmp_name"]) && file_exists($_FILES["' . $attr_name . '"]["tmp_name"])) ' . $var_prefix . '["' . $attr_name . '"] = file_get_contents($_FILES["' . $attr_name . '"]["tmp_name"]);' . "\n";
			}
			
			if ($logged_user_id_code)
				$code = $logged_user_id_code . "\n" . $code;
		}
		
		return $code;
	}

	//copied from CMSPresentationFormSettingsUIHandler::getInsertActionPreviousCodeIfBrokerSettingsContainsAutoIncrementPrimaryKeys, so if you change this method, please make the correspodnent changes in this other method too.
	private static function getInsertActionPreviousCodeIfBrokerSettingsContainsAutoIncrementPrimaryKeys($table_name, $attr_name, $attr, $task, $WorkFlowTaskHandler, $broker_code, $var_prefix) {
		$item_type = isset($task["item_type"]) ? $task["item_type"] : null;
		$service = isset($task["service"]) ? $task["service"] : null;
		$data = null;
		$var_prefix = substr($var_prefix, 0, 1) == '$' || substr($var_prefix, 0, 2) == '@$' ? $var_prefix : '$' . $var_prefix;
		$service_method = isset($service["service_method"]) ? $service["service_method"] : null; 
		
		//checks if auto increment pk exists in attributes
		if ($item_type == "ibatis" && isset($service["service_type"]) && $service["service_type"] == "insert") 
			$data = isset($service["parameters"]) ? $service["parameters"] : null;
		else if ($item_type == "db") {
			if (!empty($service["sql"]))
				$data = $service["sql"];
			else if (!empty($task["is_db_dao_action_task"]))
				$data = $service["attributes"];
		}
		else if ($item_type == "hibernate" && ($service_method == "getData" || $service_method == "setData")) 
			$data = isset($service["sma_sql"]) ? $service["sma_sql"] : null;
		else if ($item_type == "hibernate" && ($service_method == "insert" || $service_method == "callInsert")) 
			$data = isset($service["sma_data"]) ? $service["sma_data"] : null;
		else if ($item_type == "hibernate" && $service_method == "callQuery" && isset($service["sma_query_type"]) && $service["sma_query_type"] == "insert")
			$data = isset($service["sma_data"]) ? $service["sma_data"] : null;
		
		$exists = false;
		
		if ($data) {
			if (is_array($data)) {
				foreach ($data as $item) {
					if (array_key_exists("value", $item) && isset($item["value_type"]) && $item["value_type"] == "string" && (
						$item["value"] == $var_prefix . "['$attr_name']" || 
						$item["value"] == $var_prefix . '["' . $attr_name . '"]' || 
						strpos($item["value"], '{' . $var_prefix . '["' . $attr_name . '"]}') !== false || 
						strpos($item["value"], "{" . $var_prefix . "['" . $attr_name . "']}") !== false
					)) {
						$exists = true;
						break;
					}
				}
			}
			else { //parse sql
				$sql_data = DB::convertDefaultSQLToObject($data);
				
				if (isset($sql_data["type"]) && $sql_data["type"] == "insert" && !empty($sql_data["attributes"]))
					foreach ($sql_data["attributes"] as $attr) {
						$attr_column = isset($attr["column"]) ? $attr["column"] : null;
						
						if ($attr_column == $attr_name) {
							$exists = true;
							break;
						}
					}
			}
		}
		
		//sets max value from DB
		if ($exists) {
			$options = self::getBrokerSettingsOptionsCode($WorkFlowTaskHandler, "insert", $task);
			$options = $options ? str_replace("\n", "\n\t", $options) : "null";
			
			return 'if (!strlen(trim(' . $var_prefix . '["' . $attr_name . '"]))) 
	' . $var_prefix . '["' . $attr_name . '"] = ' . $broker_code . '->findObjectsColumnMax("' . $table_name . '", "' . $attr_name . '", ' . $options . ');' . "\n";
		}
		
		return "";
	}

	//copied from CMSPresentationFormSettingsUIHandler::getInsertActionNextCode, so if you change this method, please make the correspodnent changes in this other method too.
	private static function getInsertActionNextCode($pks_auto_increment, $task, $WorkFlowTaskHandler, $broker_code, $var_prefix) {
		$code = "";
		$var_prefix = substr($var_prefix, 0, 1) == '$' || substr($var_prefix, 0, 2) == '@$' ? $var_prefix : '$' . $var_prefix;
		
		if ($pks_auto_increment) {
			$item_type = isset($task["item_type"]) ? $task["item_type"] : null;
			$service = isset($task["service"]) ? $task["service"] : null;
			$is_db_primitive_action = self::isDBPrimitiveTask($task); //used when insert and update action
			
			if ($item_type == "hibernate" && isset($service["service_method"]) && $service["service_method"] == "insert") {
				$sma_ids = isset($service["sma_ids"]) ? $service["sma_ids"] : null;
				$pk = isset($pks_auto_increment[0]) ? $pks_auto_increment[0] : null;
				
				$code .= $var_prefix . ' = $' . $sma_ids . '["' . $pk . '"];';
			}
			else if ($is_db_primitive_action && $pks_auto_increment) {
				$options = self::getBrokerSettingsOptionsCode($WorkFlowTaskHandler, "insert", $task);
				
				$code .= $var_prefix . ' = ' . $broker_code . '->getInsertedId(' . $options . ');';
			}
		}
		
		if ($code)
			$code = '
if (' . $var_prefix . ') {
	' . $code . '
}';
		
		return $code;
	}

	//copied from CMSPresentationFormSettingsUIHandler::getUpdateActionPreviousCode and SequentialLogicalActivityBLResourceCreator::getUpdateActionPreviousCode, so if you change this method, please make the correspodnent changes in this other method too.
	private static function getUpdateActionPreviousCode($tables, $table_name, $attributes, $task, $WorkFlowTaskHandler, $broker_code, $var_prefix, $is_update_attribute_action = false) {
		$is_db_primitive_action = self::isDBPrimitiveTask($task); //used when insert and update action
		
		if (!$is_db_primitive_action) {
			$code = SequentialLogicalActivityBLResourceCreator::getUpdateActionPreviousCode($tables, $table_name, $attributes, $var_prefix, !$is_update_attribute_action);
			$code = preg_replace('/(\$[a-z]+\["logged_user_id"\]\s*=\s*)\$data\["logged_user_id"\];/', '$1self::getLoggedUserId($EVC);', $code);
			$code = preg_replace('/(\$[a-z]+\["logged_user_id"\]\s*=\s*)isset\(\$data\[\"logged_user_id\"\]\)\s*\?\s*\$data\[\"logged_user_id\"\]\s*:\s*null\s*;/', '$1self::getLoggedUserId($EVC);', $code);
		}
		else {
			$code = self::getInsertActionPreviousCode($tables, $table_name, $attributes, $task, $WorkFlowTaskHandler, $broker_code, $var_prefix, false, !$is_update_attribute_action, $is_update_attribute_action);
			//Note that we have more code in CMSPresentationFormSettingsUIHandler::getUpdateActionPreviousCode, but that do the same than the code in the createUpdateMethod method, this is, the code in the CMSPresentationFormSettingsUIHandler::getUpdateActionPreviousCode prepare the pks to be replaced by new pks, which is what we already do in the createUpdateMethod method.
			
			$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
			
			foreach ($attributes as $attr_name) {
				$attr = isset($attrs[$attr_name]) ? $attrs[$attr_name] : null;
				
				if (!empty($attr["primary_key"])) {
					$attr_type = isset($attr["type"]) ? $attr["type"] : null;
					
					if (ObjTypeHandler::isDBTypeNumeric($attr_type) || ObjTypeHandler::isPHPTypeNumeric($attr_type))
						$code .= 'if (array_key_exists("' . $attr_name . '", ' . $var_prefix . ') && !is_numeric(' . $var_prefix . '["' . $attr_name . '"])) $status = false;' . "\n";
					else
						$code .= 'if (array_key_exists("' . $attr_name . '", ' . $var_prefix . ') && !strlen(trim(' . $var_prefix . '["' . $attr_name . '"]))) $status = false;' . "\n";
				}
			}
		}
		
		return $code;
	}

	//copied from CMSPresentationFormSettingsUIHandler::getHibernateGetActionNextCode, so if you change this method, please make the correspodnent changes in this other method too.
	private static function getHibernateGetActionNextCode($var_prefix) {
		return ""; //this function is deprecated bc the hibernate returns the same result array than ibatis
		
		$var_prefix = substr($var_prefix, 0, 1) == '$' || substr($var_prefix, 0, 2) == '@$' ? $var_prefix : '$' . $var_prefix;
		
		return '
if (' . $var_prefix . ') {
	$hbn_object_name = array_keys(' . $var_prefix . ');
	$hbn_object_name = isset($hbn_object_name[0]) ? $hbn_object_name[0] : null;
' .	 $var_prefix . ' = isset(' . $var_prefix . '[$hbn_object_name]) ? ' . $var_prefix . '[$hbn_object_name] : null;
}
';
	}

	//copied from CMSPresentationFormSettingsUIHandler::getHibernateGetAllActionNextCode, so if you change this method, please make the correspodnent changes in this other method too.
	private static function getHibernateGetAllActionNextCode($var_prefix) {
		return ""; //this function is deprecated bc the hibernate returns the same result array than ibatis
		
		$var_prefix = substr($var_prefix, 0, 1) == '$' || substr($var_prefix, 0, 2) == '@$' ? $var_prefix : '$' . $var_prefix;
		
		return '
if (' . $var_prefix . ') {
	$hbn_object_name = array_keys(' . $var_prefix . '[0]);
	$hbn_object_name = isset($hbn_object_name[0]) ? $hbn_object_name[0] : null;

	$items = array();
	$t = count(' . $var_prefix . ');

	for ($i = 0; $i < $t; $i++)
		$items[] = isset(' . $var_prefix . '[$i][$hbn_object_name]) ? ' . $var_prefix . '[$i][$hbn_object_name] : null;

	' . $var_prefix . ' = $items;
}
';
	}

	private static function getSelectItemActionNextCode($tables, $table_name, $var_prefix, $attrs_name_to_filter = null) {
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		return SequentialLogicalActivityBLResourceCreator::getSelectItemActionNextCode($attrs, $var_prefix, $attrs_name_to_filter);
	}

	private static function getSelectItemsActionNextCode($tables, $table_name, $var_prefix) {
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		return SequentialLogicalActivityBLResourceCreator::getSelectItemsActionNextCode($attrs, $var_prefix);
	}
	
	//copied from CMSPresentationFormSettingsUIHandler::convertQueryDataTaskToSimpleTask, so if you change this method, please make the correspodnent changes in this other method too.
	//convert getquerydata and setquerydata to insert/update/delete/select task groups
	private static function convertQueryDataTaskToSimpleTask(&$task, $tables) {
		$item_type = isset($task["item_type"]) ? $task["item_type"] : null;
		$service = isset($task["service"]) ? $task["service"] : null;
		
		if ($item_type == "db") {
			$data = null;
			$sql_type = "select"; //if no sql, show "select" task group with empty sql.
			
			if (!empty($service["sql"])) {
				$data = DB::convertDefaultSQLToObject($service["sql"]);
				$sql_type = $data && isset($data["type"]) ? $data["type"] : null;
			}
		
			$sql_type_valid = $sql_type == "insert" || $sql_type == "update" || $sql_type == "delete" || $sql_type == "select";
			
			//only convert if sql_type is valid. This is, if sql is a procedure, do nothing
			if ($sql_type_valid) {
				//if sql exists and data is valid
				if ($data) {
					$data["main_table"] = isset($data["table"]) ? $data["table"] : null;
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
					
					//check if is a simple sql and can be converted to insert/update/delete/select task group
					$is_simple_sql = $new_sql == $old_sql && (empty($data["limit"]) || empty($service["options"]) || is_array($service["options"])); //if limit exists and options are an array or null. Note that $service["options"] could be a variable.
					
					if ($is_simple_sql) {
						$data_type = isset($data["type"]) ? $data["type"] : null;
						
						//check if all $data["attributes"] are in $tables[table], bc it could be a select with a count(*)
						if ($data_type == "select" && !empty($data["attributes"]) && !empty($tables[ $data["table"] ]))
							foreach ($data["attributes"] as $attr)
								if (empty($tables[ $data["table"] ][ $attr["column"] ])) {
									$is_simple_sql = false;
									break;
								}
						
						//check if sql is not a simple select statement with only 1 table without joins, group by or sorts
						if ($data_type == "select" && (!empty($data["keys"]) || !empty($data["groups_by"]) || !empty($data["sorts"])))
							$is_simple_sql = false;
						
						//check if conditions have operators that are "="
						if (!empty($data["conditions"]) && ($data_type == "update" || $data_type == "delete" || $data_type == "select"))
							foreach ($data["conditions"] as $attr)
								if (!empty($attr["operator"]) && $attr["operator"] != "=") {
									$is_simple_sql = false;
									break;
								}
						
						//start converting to insert/update/delete/select task group
						if ($is_simple_sql) {
							//remove sql attribute bc is now a new task group.
							unset($service["sql"]);
							unset($service["sql_type"]);
							
							//on insert action, remove primary key auto_increment if exists
							if ($data_type == "insert" && !empty($data["attributes"]) && !empty($tables[ $data["table"] ]))
								foreach ($data["attributes"] as $idx => $attr) {
									$attr = isset($tables[ $data["table"] ][ $attr["column"] ]) ? $tables[ $data["table"] ][ $attr["column"] ] : null;
									
									if ($attr && !empty($attr["primary_key"]) && WorkFlowDataAccessHandler::isAutoIncrementedAttribute($attr))
										unset($data["attributes"][$idx]);
								}
							
							//add new settings
							$task["is_db_dao_action_task"] = true;
							$service["method_name"] = $data_type == "insert" ? "insertObject" : (
								$data_type == "update" ? "updateObject" : (
									$data_type == "delete" ? "deleteObject" : "findObjects"
								)
							);
							$service["table_name"] = isset($data["table"]) ? $data["table"] : null; 
							$service["table_name_type"] = "string"; 
							$service["attributes_type"] = "array";
							$service["attributes"] = array();
							
							if (!empty($data["attributes"]) && ($data_type == "insert" || $data_type == "update" || $data_type == "select"))
								foreach ($data["attributes"] as $idx => $attr)
									$service["attributes"][] = array(
										"key" => $attr["column"],
										"key_type" => "string",
										"value" => $attr["value"],
										"value_type" => "string",
									);
							
							$service["conditions_type"] = "array";
							$service["conditions"] = array();
							
							if (!empty($data["conditions"]) && ($data_type == "insert" || $data_type == "update" || $data_type == "delete" || $data_type == "select"))
								foreach ($data["conditions"] as $idx => $attr) 
									$service["conditions"][] = array(
										"key" => $attr["column"],
										"key_type" => "string",
										"value" => $attr["value"],
										"value_type" => "string",
									);
							
							$task["service"] = $service;
						}
					}
					
					//prepare limit and start in options, if exists
					if (!empty($data["limit"]) && (empty($service["options"]) || is_array($service["options"]))) { //if limit exists options must be an array or null
						$limit = isset($data["limit"]) ? $data["limit"] : null;
						$start = isset($data["start"]) ? $data["start"] : null;
						$service["options_type"] = "array"; //set to array.
						$service["options"] = isset($service["options"]) && is_array($service["options"]) ? $service["options"] : array(); //if null, set it to an array
						$exists_limit = $exists_start = false;
						
						//if sql exists, remove limit and start from sql, bc it will be added in the options
						if (!empty($service["sql"])) { 
							$other_data = $data;
							unset($other_data["limit"]);
							unset($other_data["start"]);
							$service["sql"] = DB::convertObjectToDefaultSQL($other_data);
						}
						
						//replace existent limit and start with right values
						foreach ($service["options"] as $idx => $v) 
							if (isset($v["key"]) && isset($v["key_type"])) {
								if ($v["key"] == "limit" && $v["key_type"] == "string") { //Overwrite limit in options
									$service["options"][$idx]["value"] = $limit; //note that limit can be #xxx#. It doesn't need a numeric value
									$service["options"][$idx]["value_type"] = "string";
									$exists_limit = true;
								}
								else if (strlen("$start") && $v["key"] == "start" && $v["key_type"] == "string") { //Overwrite start in options
									$service["options"][$idx]["value"] = $start; //note that limit can be #xxx#. It doesn't need a numeric value. If start is 0, discard $start.
									$service["options"][$idx]["value_type"] = "string";
									$exists_start = true;
								}
							}
						
						//add limit to options
						if (!$exists_limit)
							$service["options"][] = array(
								"key" => "limit",
								"key_type" => "string",
								"value" => $limit,
								"value_type" => "string",
							);
						
						//add start to options
						if (!$exists_start && strlen("$start"))
							$service["options"][] = array(
								"key" => "start",
								"key_type" => "string",
								"value" => $start,
								"value_type" => "string",
							);
						
						$task["service"] = $service;
					}
				}
			}
			
			//echo "\n".$service["item_type"].":".print_r($service, 1)."\n";
		}
	}
}
?>
