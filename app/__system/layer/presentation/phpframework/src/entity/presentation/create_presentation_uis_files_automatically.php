<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include $EVC->getUtilPath("CMSPresentationUIDiagramFilesHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");
UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);
UserAuthenticationHandler::checkActionsMaxNum($UserAuthenticationHandler);

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null;
//$db_layer = isset($_GET["db_layer"]) ? $_GET["db_layer"] : null;
$db_layer_file = isset($_GET["db_layer_file"]) ? $_GET["db_layer_file"] : null;
$db_driver = isset($_GET["db_driver"]) ? $_GET["db_driver"] : null;
$include_db_driver = isset($_GET["include_db_driver"]) ? $_GET["include_db_driver"] : null;
$type = isset($_GET["type"]) ? $_GET["type"] : null;
$authenticated_template = isset($_GET["authenticated_template"]) ? $_GET["authenticated_template"] : null;
$non_authenticated_template = isset($_GET["non_authenticated_template"]) ? $_GET["non_authenticated_template"] : null;
$overwrite = isset($_GET["overwrite"]) ? $_GET["overwrite"] : null;
$tables_alias = !empty($_POST["sta"]) ? $_POST["sta"] : $_GET["sta"];
$users_perms = !empty($_POST["users_perms"]) ? $_POST["users_perms"] : (isset($_GET["users_perms"]) ? $_GET["users_perms"] : null);
$users_perms_folder = !empty($_POST["users_perms_folder"]) ? $_POST["users_perms_folder"] : (isset($_GET["users_perms_folder"]) ? $_GET["users_perms_folder"] : null);
$list_and_edit_users = !empty($_POST["list_and_edit_users"]) ? $_POST["list_and_edit_users"] : (isset($_GET["list_and_edit_users"]) ? $_GET["list_and_edit_users"] : null);
$form_type = !empty($_GET["form_type"]) ? $_GET["form_type"] : "settings";
$files_creation_type = 1;

//create var: with user authentication

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

if ($path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$UserCacheHandler = $PHPFrameWork->getObject("UserCacheHandler"); //$PHPFrameWork is the same than $EVC->getPresentationLayer()->getPHPFrameWork(); //Use EVC instead of PEVC, bc is relative to the __system admin panel
		
		$P = $PEVC->getPresentationLayer();
		
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$layer_path = $P->getLayerPathSetting();
		$selected_project_id = $P->getSelectedPresentationId();
		$folder_path = $layer_path . $path;//it should be a folder.

		if (is_dir($folder_path)) {
			$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($folder_path, "layer", "access");
			
			//get db driver object
			$db_drivers = WorkFlowBeansFileHandler::getLayerDBDrivers($user_global_variables_file_path, $user_beans_folder_path, $P, true);
			$db_driver_props = isset($db_drivers[$db_driver]) ? $db_drivers[$db_driver] : null;
			$db_driver_bean_file_name = !empty($db_driver_props[1]) ? $db_driver_props[1] : $db_layer_file;
			$db_driver_bean_name = !empty($db_driver_props[2]) ? $db_driver_props[2] : $db_driver;
			
			//prepare tables
			$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
			
			$relative_path = str_replace($PEVC->getEntitiesPath(), "", $folder_path);
			$absolute_path = $folder_path;
			
			if ($type == "diagram") { //TRYING TO GET THE DB TABLES FROM THE TASK FLOW
				$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $db_driver);
				$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
				//$tasks = $WorkFlowDataAccessHandler->getTasks();
			}
			else { //TRYING TO GET THE DB TABLES DIRECTLY FROM DB
				$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
				$tasks = $WorkFlowDBHandler->getUpdateTaskDBDiagram($db_driver_bean_file_name, $db_driver_bean_name);
				$WorkFlowDataAccessHandler->setTasks($tasks);
				//$tasks = $WorkFlowDataAccessHandler->getTasks();
			}
			
			$tables = $WorkFlowDataAccessHandler->getTasksAsTables();
			//print_r($tables);die();
			
			if ($tables_alias) 
				foreach ($tables_alias as $table_name => $table_alias)
					$tables_alias[$table_name] = strtolower(str_replace(array("-", " "), "_", $table_alias));
			
			$statuses = array();
			
			//Preparing settings
			$settings = htmlspecialchars_decode( file_get_contents("php://input") );
			$settings = json_decode($settings, true);
			//print_r($settings);die();
			
			if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === "POST" && is_array($settings) && $settings) {
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
				$UserAuthenticationHandler->incrementUsedActionsTotal();
				
				//check if $folder_path belongs to filter_by_layout and if not, add it.
				$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
				$LayoutTypeProjectHandler->createLayoutTypePermissionsForFilePathAndLayoutTypeName($filter_by_layout, $folder_path);
				
				//getting default bolean values
				$DBDriverWorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $db_driver_bean_file_name, $user_global_variables_file_path);
				$DBDriverObj = $DBDriverWorkFlowBeansFileHandler->getBeanObject($db_driver_bean_name);
				$boolean_available_values = $DBDriverObj->getDBBooleanTypeAvailableValues();
				$boolean_available_options = array();
				
				if ($boolean_available_values)
					foreach ($boolean_available_values as $k => $v)
						$boolean_available_options[] = array("value" => $k, "label" => strtoupper($v) . "."); //for some reason if the $v is equal to "true" or "false", it will give an infinit loop and it will appear as blank in the select option field. So we must add some char
				
				//getting tables alias from payload bc the sta doesn't come from $_POST["sta"]
				if (array_key_exists("sta", $settings)) {
					$tables_alias = $settings["sta"];
					unset($settings["sta"]);
				}
					
				//getting tables alias from payload bc the users_perms doesn't come from $_POST["users_perms"]
				if (array_key_exists("users_perms", $settings)) {
					$users_perms = $settings["users_perms"];
					unset($settings["users_perms"]);
				}
				
				//getting tables alias from payload bc the list_and_edit_users doesn't come from $_POST["list_and_edit_users"]
				if (array_key_exists("list_and_edit_users", $settings)) {
					$list_and_edit_users = $settings["list_and_edit_users"];
					unset($settings["list_and_edit_users"]);
				}
				
				//print_r($users_perms);die();
				//print_r($list_and_edit_users);die();
				//print_r($settings);die();
				
				$allowed_tasks = array("callbusinesslogic", "callibatisquery", "callhibernatemethod", "getquerydata", "setquerydata");
				$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
				$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
				$WorkFlowTaskHandler->setAllowedTaskTags($allowed_tasks);
				$WorkFlowTaskHandler->initWorkFlowTasks();
				
				//prepare default blocks
				$statuses["*"] = array();
				
				$extra_head_id = "{$relative_path}extra_head";
				$extra_head_code = getExtraHeadCode();
				CMSPresentationUIAutomaticFilesHandler::saveBlockCode($PEVC, $extra_head_id, $extra_head_code, $overwrite, $statuses["*"]);
				
				$menu_id = "{$relative_path}menu";
				$menus_code = getMenusCode($settings, $tables, $absolute_path, $relative_path, $overwrite, $tables_alias);
				CMSPresentationUIAutomaticFilesHandler::saveBlockCode($PEVC, $menu_id, $menus_code, $overwrite, $statuses["*"]);
				
				//prepare default global vars file
				$vars_file_id = "{$relative_path}vars";
				$vars_file_include_code = '$EVC->getConfigPath("' . $vars_file_id . '")';
				
				//prepare some constant vars
				$width = 150;
				$height = 200;
				
				$page_task_type_id = "d7975b77";
				$listing_task_type_id = "c79d127a";
				$view_task_type_id = "3d6046d9";
				$form_task_type_id = "91151018";
				
				//prepare generic page settings
				$generic_page_settings = array(
					"regions_blocks" => array(
						array("region" => "Head", "block" => $extra_head_id),
						array("region" => "Menu", "block" => $menu_id),
					),
					"template_params" => array(),
				);
				
				//prepare generic authentication settings
				$generic_authentication_type = $generic_authentication_users = $generic_users_perms = null;
				
				if ($users_perms) {
					$generic_authentication_type = "authenticated";
					$generic_authentication_users = $generic_users_perms = array();
					
					foreach ($users_perms as $user_type_id => $activities)
						foreach ($activities as $activity_id => $active) 
							if ($active) {
								if ($activity_id == UserUtil::ACCESS_ACTIVITY_ID)
									$generic_authentication_users[] = array("user_type_id" => $user_type_id);
								else
									$generic_users_perms[] = array("user_type_id" => $user_type_id, "activity_id" => $activity_id);
							}
				}
				
				$authentication_files_relative_folder_path = $users_perms_folder == "project_current_folder" ? $relative_path  : "";
				
				/* Start: dashboard Page */
				//create index block with content widgets
				$table_menu_items = getHomePageMenusItems($settings, $relative_path, $tables_alias);
				$index_block_id = "{$relative_path}index";
				$index_block_code = getIndexPageCode("dashboard", $table_menu_items);
				CMSPresentationUIAutomaticFilesHandler::saveBlockCode($PEVC, $index_block_id, $index_block_code, $overwrite, $statuses["*"]);
				
				//prepare page settings
				$task_page_settings = $generic_page_settings;
				$task_page_settings["regions_blocks"][] = array("region" => "Content", "block" => $index_block_id);
				$task_page_settings["template_params"][] = array("name" => "Page Title", "value" => "Admin Panel - Dashboard");
				
				$task_id = getTaskId($absolute_path, $overwrite, "page", null, "index");
				$task_file_name = getTaskPageId($absolute_path, $overwrite, "index");
				
				$tasks_details = array(
					$task_id => array(
						"label" => $task_file_name,
						"id" => $task_id,
						"type" => $page_task_type_id,
						"tag" => "page",
						"offset_top" => 20,
						"offset_left" => 20,
						"width" => $width,
						"height" => $height,
						"properties" => array(
							"exits" => array(
								"default_exit" => array("color" => "#4070FF"),
							),
							"file_name" => $task_file_name,
							"template" => $authenticated_template,
							"join_type" => "list",
							"authentication_type" => $generic_authentication_type,
							"authentication_users" => $generic_authentication_users,
							"page_settings" => $task_page_settings,
							"files_to_create" => array(
								"$selected_project_id/src/block/$index_block_id" => false,
							),
						),
						"exits" => array(),
					)
				);
				
				//create page file
				$table_statuses = array();
				foreach ($tasks_details as $task_id => $task)
					if (isset($task["tag"]) && $task["tag"] == "page") {
						$js_funcs = array();
						$js_code = "";
						
						CMSPresentationUIDiagramFilesHandler::createPageFile($PEVC, $UserCacheHandler, $cms_page_cache_path_prefix, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $relative_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $list_and_edit_users, $authenticated_template, $non_authenticated_template, $authenticated_template, $table_statuses, $js_funcs, $js_code, $tasks_details, $task);
						
						//prepare created_files and statuses
						foreach ($table_statuses as $t_id => $task_statuses) {
							//prepare statuses
							foreach ($task_statuses as $file_path => $file_data)
								$statuses["*"][$file_path] = !empty($file_data["modified_time"]) ? true : false;
							
							//prepare created_files
							$tasks_details = addCreatedFilesToTaskDetails($tasks_details, $t_id, $task_statuses);
						}
						
						//prepare task page settings
						$tasks_details[$task_id] = getTaskWithUpdatedFilePageSettings($PEVC, $task, $statuses["*"], $absolute_path);
					}
				
				//save diagram xml
				saveTasksDetailsToDiagamXML($workflow_paths_id, $bean_name, $path, $tasks_details);
				/* End: dashboard Page */
				
				/* Start: tables pages */
				//prepare tables tasks
				$tables_tasks_details = array();
				
				//print_r($settings);
				foreach ($settings as $table_name => $brokers_settings) {
					$table_alias = isset($tables_alias[$table_name]) ? $tables_alias[$table_name] : null;
					$tn = $table_alias ? $table_alias : $table_name;
					$tn_plural = CMSPresentationFormSettingsUIHandler::getPlural($tn);
					$tn_label = CMSPresentationFormSettingsUIHandler::getName($tn);
					$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
					$statuses[$table_name] = array();
					
					//echo "START PROCESS WITH TABLE: $table_name<br>\n";
					
					$tasks_details = array();
					$task_relative_path = $relative_path . CMSPresentationUIDiagramFilesHandler::getLabelFileName($tn) . "/";
					$task_absolute_path = $absolute_path . CMSPresentationUIDiagramFilesHandler::getLabelFileName($tn) . "/";
					
					$default_task_properties_ui_attributes = array();
					if ($attrs) 
						foreach ($attrs as $attr_name => $attr) {
							$default_task_properties_ui_attributes[$attr_name] = array("active" => 1);
							$attr_type = isset($attr["type"]) ? $attr["type"] : null;
							
							if ($attr_type == "boolean" || (in_array($attr_type, array("tinyint", "bit")) && isset($attr["length"]) && $attr["length"] == 1)) {
								$default_task_properties_ui_attributes[$attr_name]["list_type"] = "manual";
								$default_task_properties_ui_attributes[$attr_name]["manual_list"] = $attr_type == "boolean" ? $boolean_available_options : array(
									array("value" => 0, "label" => "NO"),
									array("value" => 1, "label" => "YES"),
								);
								//print_r($default_task_properties_ui_attributes);die();
							}
						}
					
					$sub_menu_id = "{$task_relative_path}sub_menu";
					$sub_menus_code = getTableSubMenusCode($settings, $tables, $absolute_path, $relative_path, $overwrite, $tables_alias, $table_name);
					CMSPresentationUIAutomaticFilesHandler::saveBlockCode($PEVC, $sub_menu_id, $sub_menus_code, $overwrite, $statuses[$table_name]);
					
					//prepare table index page
					$table_menu_items = getTableMenusItems($settings, $tables, $absolute_path, $relative_path, $overwrite, $tables_alias, $table_name);
					$index_block_id = "{$task_relative_path}index";
					$index_block_code = getIndexPageCode($tn_plural, $table_menu_items);
					CMSPresentationUIAutomaticFilesHandler::saveBlockCode($PEVC, $index_block_id, $index_block_code, $overwrite, $statuses[$table_name]);
					
					$task_page_settings = $generic_page_settings;
					$task_page_settings["regions_blocks"][] = array("region" => "Sub Menu", "block" => $sub_menu_id);
					$task_page_settings["regions_blocks"][] = array("region" => "Content", "block" => $index_block_id);
					$task_page_settings["template_params"][] = array("name" => "Page Title", "value" => CMSPresentationFormSettingsUIHandler::getName($tn_plural));
					
					$task_id = getTaskId($task_absolute_path, $overwrite, "page", $table_name, "index");
					$task_file_name = getTaskPageId($task_absolute_path, $overwrite, "index");
					
					$tasks_details[$task_id] = array(
						"label" => $task_file_name,
						"id" => $task_id,
						"type" => $page_task_type_id,
						"tag" => "page",
						"offset_top" => 20,
						"offset_left" => 580,
						"width" => $width,
						"height" => $height,
						"properties" => array(
							"exits" => array(
								"default_exit" => array("color" => "#4070FF"),
							),
							"file_name" => $task_file_name,
							"template" => $authenticated_template,
							"join_type" => "list",
							"authentication_type" => $generic_authentication_type,
							"authentication_users" => $generic_authentication_users,
							"page_settings" => $task_page_settings,
							"files_to_create" => array(
								"$selected_project_id/src/block/$index_block_id" => false,
							),
						),
						"exits" => array(),
					);
					
					//prepare brokers pages
					foreach ($brokers_settings as $broker_key => $broker_settings) {
						//echo "broker_key:$broker_key\n";
						
						switch ($broker_key) {
							case "get_all": //list table ui
								$task_exits = array();
								$links = array();
								
								if (!empty($brokers_settings["get"]))
									$task_exits[] = array(
										"task_id" => getTaskId($task_absolute_path, $overwrite, "page", $table_name, "view"),
										"label" => "View",
										"type" => "Straight",
										"overlay" => "Forward Arrow",
										"color" => "#4070FF",
										"properties" => array(
											"connection_type" => "link",
											"connection_title" => "View $tn",
											"connection_class" => "view",
										),
									);
								
								if (!empty($brokers_settings["update"]) || !empty($brokers_settings["delete"]))
									$task_exits[] = array(
										"task_id" => getTaskId($task_absolute_path, $overwrite, "page", $table_name, "edit"),
										"label" => "Edit",
										"type" => "Straight",
										"overlay" => "Forward Arrow",
										"color" => "#4070FF",
										"properties" => array(
											"connection_type" => "link",
											"connection_title" => "Edit $tn",
											"connection_class" => "edit",
										),
									);
								
								//add link to insert new item at the top of page
								if (!empty($brokers_settings["insert"])) 
									$links[] = array(
										"url" => getTaskPageUrl($task_absolute_path, $task_relative_path, $overwrite, "add"),
										"value" => "Add new $tn",
										"class" => "add",
										"title" => "Add new $tn",
									);
								
								//add task exits for the relationships
								prepareRelationshipsTaskExits($table_name, $tables_alias, $brokers_settings, $tn, $tn_label, $absolute_path, $relative_path, $overwrite, $task_exits);
								
								//prepare links and comboboxes for the foreign attributes
								$task_properties_ui_attributes = $default_task_properties_ui_attributes;
								prepareFKsActionsAndAttributesSettings($settings, $tables, $table_name, $tables_alias, $tn, $db_driver, $include_db_driver, $type, $absolute_path, $relative_path, $overwrite, $task_properties_ui_attributes, $form_type, !empty($brokers_settings["insert"]) || !empty($brokers_settings["update"]));
								//print_r($task_properties_ui_attributes);
								
								//prepare brokers
								$brokers_services_and_rules = $brokers_settings;
								unset($brokers_services_and_rules["get"]); //this unset is very important otherwise the ajax delete file will be messy bc it will try to get the item after delete it.
								unset($brokers_services_and_rules["relationships"]);
								unset($brokers_services_and_rules["relationships_count"]);
								
								//prepare offsets. Hard code the offsets bc the diagram will be more beautifull
								$offset_top = 20;
								$offset_left = 300;
								
								//prepare task details
								$task_id = getTaskId($task_absolute_path, $overwrite, "page", $table_name, "list");
								$inner_task_id = getTaskId($task_absolute_path, $overwrite, "listing", $table_name, "list");
								$task_file_name = getTaskPageId($task_absolute_path, $overwrite, "list");
								
								//prepare page settings
								$task_page_settings = $generic_page_settings;
								$task_page_settings["regions_blocks"][] = array("region" => "Sub Menu", "block" => $sub_menu_id);
								
								$task_page_settings["regions_blocks"][] = array("region" => "Content", "block" => "$task_relative_path$task_file_name");
								$task_page_settings["template_params"][] = array("name" => "Page Title", "value" => getPageTitle($tn, "get_all"));
								
								$tasks_details[$task_id] = array(
									"label" => $task_file_name,
									"id" => $task_id,
									"type" => $page_task_type_id,
									"tag" => "page",
									"offset_top" => $offset_top,
									"offset_left" => $offset_left,
									"width" => $width,
									"height" => $height,
									"properties" => array(
										"exits" => array(
											"default_exit" => array("color" => "#4070FF"),
										),
										"file_name" => $task_file_name,
										"template" => $authenticated_template,
										"join_type" => "list",
										"links" => $links,
										"authentication_type" => $generic_authentication_type,
										"authentication_users" => $generic_authentication_users,
										"page_settings" => $task_page_settings,
									),
									"exits" => array(),
									"tasks" => array(
										$inner_task_id => array(
											"label" => CMSPresentationFormSettingsUIHandler::getName($tn_plural) . " List",
											"id" => $inner_task_id,
											"type" => $listing_task_type_id,
											"tag" => "listing",
											"offset_top" => $offset_top + 50,
											"offset_left" => $offset_left + 10,
											"properties" => array(
												"exits" => array(
													"default_exit" => array("color" => "#4070FF"),
												),
												"listing_type" => "", //to draw a table_list
												"choose_db_table" => array(
													"db_driver" => $db_driver,
													"include_db_driver" => $include_db_driver,
													"db_type" => $type,
													"db_table" => $table_name,
													"db_table_alias" => $table_alias,
												),
												"action" => array(
													"single_insert" => !empty($brokers_settings["insert"]) ? 1 : 0,
													"single_update" => !empty($brokers_settings["update"]) ? 1 : 0,
													"single_delete" => !empty($brokers_settings["delete"]) ? 1 : 0,
													"multiple_delete" => !empty($brokers_settings["delete"]) ? 1 : 0,
													"single_delete_confirmation_message" => null, //cannot be empty string "", otherwise there will not be any confirmation message
													"multiple_delete_confirmation_message" => null, //cannot be empty string "", otherwise there will not be any confirmation message
													"single_delete_ok_redirect_url" => null,
													"multiple_delete_ok_redirect_url" => null,
												),
												"links" => array(),
												"pagination" => array(
													"active" => 1,
													"rows_per_page" => 500,
												),
												"attributes" => $task_properties_ui_attributes,
												"brokers_services_and_rules" => $brokers_services_and_rules,
												"users_perms" => $generic_users_perms,
											),
											"exits" => array(
												"default_exit" => $task_exits,
											),
										)
									),
								);
								break;
							
							case "get": //view form ui
								$task_exits = array();
								
								if (!empty($brokers_settings["get_all"]))
									$task_exits[] = array(
										"task_id" => getTaskId($task_absolute_path, $overwrite, "page", $table_name, "list"),
										"label" => "View all " . CMSPresentationFormSettingsUIHandler::getName($tn_plural),
										"type" => "Straight",
										"overlay" => "Forward Arrow",
										"color" => "#4070FF",
										"properties" => array(
											"connection_type" => "link",
											"connection_title" => CMSPresentationFormSettingsUIHandler::getName($tn_plural) . " List",
											"connection_class" => "view-all",
										),
									);
								
								if (!empty($brokers_settings["update"]) || !empty($brokers_settings["delete"]))
									$task_exits[] = array(
										"task_id" => getTaskId($task_absolute_path, $overwrite, "page", $table_name, "edit"),
										"label" => "Edit",
										"type" => "Straight",
										"overlay" => "Forward Arrow",
										"color" => "#4070FF",
										"properties" => array(
											"connection_type" => "link",
											"connection_title" => "Edit $tn",
											"connection_class" => "edit",
										),
									);
								
								//add task exits for the relationships
								prepareRelationshipsTaskExits($table_name, $tables_alias, $brokers_settings, $tn, $tn_label, $absolute_path, $relative_path, $overwrite, $task_exits);
								
								//prepare links and comboboxes for the foreign attributes
								$task_properties_ui_attributes = $default_task_properties_ui_attributes;
								prepareFKsActionsAndAttributesSettings($settings, $tables, $table_name, $tables_alias, $tn, $db_driver, $include_db_driver, $type, $absolute_path, $relative_path, $overwrite, $task_properties_ui_attributes, $form_type);
								
								//prepare brokers
								$brokers_services_and_rules = $brokers_settings;
								unset($brokers_services_and_rules["insert"]);
								unset($brokers_services_and_rules["update"]);
								unset($brokers_services_and_rules["update_pks"]);
								unset($brokers_services_and_rules["delete"]);
								
								//prepare offsets. Hard code the offsets bc the diagram will be more beautifull
								$offset_top = 270;
								$offset_left = 40;
								
								//prepare task details
								$task_id = getTaskId($task_absolute_path, $overwrite, "page", $table_name, "view");
								$inner_task_id = getTaskId($task_absolute_path, $overwrite, "view", $table_name, "view");
								$task_file_name = getTaskPageId($task_absolute_path, $overwrite, "view");
								
								//prepare page settings
								$task_page_settings = $generic_page_settings;
								$task_page_settings["regions_blocks"][] = array("region" => "Sub Menu", "block" => $sub_menu_id);
								
								$task_page_settings["regions_blocks"][] = array("region" => "Content", "block" => "$task_relative_path$task_file_name");
								$task_page_settings["template_params"][] = array("name" => "Page Title", "value" => getPageTitle($tn, "get"));
								
								$tasks_details[$task_id] = array(
									"label" => $task_file_name,
									"id" => $task_id,
									"type" => $page_task_type_id,
									"tag" => "page",
									"offset_top" => $offset_top,
									"offset_left" => $offset_left,
									"width" => $width,
									"height" => $height,
									"properties" => array(
										"exits" => array(
											"default_exit" => array("color" => "#4070FF"),
										),
										"file_name" => $task_file_name,
										"template" => $authenticated_template,
										"join_type" => "list",
										"links" => array(),
										"authentication_type" => $generic_authentication_type,
										"authentication_users" => $generic_authentication_users,
										"page_settings" => $task_page_settings,
									),
									"exits" => array(),
									"tasks" => array(
										$inner_task_id => array(
											"label" => "View " . $tn_label,
											"id" => $inner_task_id,
											"type" => $view_task_type_id,
											"tag" => "view",
											"offset_top" => $offset_top + 50,
											"offset_left" => $offset_left + 10,
											"properties" => array(
												"exits" => array(
													"default_exit" => array("color" => "#4070FF"),
												),
												"choose_db_table" => array(
													"db_driver" => $db_driver,
													"include_db_driver" => $include_db_driver,
													"db_type" => $type,
													"db_table" => $table_name,
													"db_table_alias" => $table_alias,
												),
												"links" => array(),
												"attributes" => $task_properties_ui_attributes,
												"brokers_services_and_rules" => $brokers_services_and_rules,
												"users_perms" => $generic_users_perms,
											),
											"exits" => array(
												"default_exit" => $task_exits,
											),
										)
									),
								);
								break;
							
							case "insert": //add form ui
								$task_exits = array();
								$single_insert_ok_msg_redirect_url = null;
								
								if (!empty($brokers_settings["get_all"])) {
									$task_exits[] = array(
										"task_id" => getTaskId($task_absolute_path, $overwrite, "page", $table_name, "list"),
										"label" => "View all " . CMSPresentationFormSettingsUIHandler::getName($tn_plural),
										"type" => "Straight",
										"overlay" => "Forward Arrow",
										"color" => "#4070FF",
										"properties" => array(
											"connection_type" => "link",
											"connection_title" => CMSPresentationFormSettingsUIHandler::getName($tn_plural) . " List",
											"connection_class" => "view-all",
										),
									);
									
									//prepare redirect url for successfully insert if get_all exists
									$single_insert_ok_msg_redirect_url = getTaskPageUrl($task_absolute_path, $task_relative_path, $overwrite, "list");
								}
								
								if (!empty($brokers_settings["update"]) || !empty($brokers_settings["delete"])) {
									//prepare redirect url for successfully insert if get_all exists - overwrite previous url. The edit url takes precedent!
									$single_insert_ok_msg_redirect_url = getTaskPageUrl($task_absolute_path, $task_relative_path, $overwrite, "edit");
								}
								
								//prepare links and comboboxes for the foreign attributes
								$task_properties_ui_attributes = $default_task_properties_ui_attributes;
								prepareFKsActionsAndAttributesSettings($settings, $tables, $table_name, $tables_alias, $tn, $db_driver, $include_db_driver, $type, $absolute_path, $relative_path, $overwrite, $task_properties_ui_attributes, $form_type, !empty($brokers_settings["insert"]));
								
								//prepare brokers
								$brokers_services_and_rules = $brokers_settings;
								unset($brokers_services_and_rules["get_all"]);
								unset($brokers_services_and_rules["count"]);
								unset($brokers_services_and_rules["get"]);
								unset($brokers_services_and_rules["update"]);
								unset($brokers_services_and_rules["update_pks"]);
								unset($brokers_services_and_rules["delete"]);
								unset($brokers_services_and_rules["relationships"]);
								unset($brokers_services_and_rules["relationships_count"]);
								
								//prepare offsets. Hard code the offsets bc the diagram will be more beautifull
								$offset_top = 20;
								$offset_left = 20;
								
								//prepare task details
								$task_id = getTaskId($task_absolute_path, $overwrite, "page", $table_name, "add");
								$inner_task_id = getTaskId($task_absolute_path, $overwrite, "form", $table_name, "add");
								$task_file_name = getTaskPageId($task_absolute_path, $overwrite, "add");
								
								//prepare page settings
								$task_page_settings = $generic_page_settings;
								$task_page_settings["regions_blocks"][] = array("region" => "Sub Menu", "block" => $sub_menu_id);
								
								$task_page_settings["regions_blocks"][] = array("region" => "Content", "block" => "$task_relative_path$task_file_name");
								$task_page_settings["template_params"][] = array("name" => "Page Title", "value" => getPageTitle($tn, "insert"));
								
								$tasks_details[$task_id] = array(
									"label" => $task_file_name,
									"id" => $task_id,
									"type" => $page_task_type_id,
									"tag" => "page",
									"offset_top" => $offset_top,
									"offset_left" => $offset_left,
									"width" => $width,
									"height" => $height,
									"properties" => array(
										"exits" => array(
											"default_exit" => array("color" => "#4070FF"),
										),
										"file_name" => $task_file_name,
										"template" => $authenticated_template,
										"join_type" => "list",
										"links" => array(),
										"authentication_type" => $generic_authentication_type,
										"authentication_users" => $generic_authentication_users,
										"page_settings" => $task_page_settings,
									),
									"exits" => array(),
									"tasks" => array(
										$inner_task_id => array(
											"label" => "Add " . $tn_label,
											"id" => $inner_task_id,
											"type" => $form_task_type_id,
											"tag" => "form",
											"offset_top" => $offset_top + 50,
											"offset_left" => $offset_left + 10,
											"properties" => array(
												"exits" => array(
													"default_exit" => array("color" => "#4070FF"),
												),
												"choose_db_table" => array(
													"db_driver" => $db_driver,
													"include_db_driver" => $include_db_driver,
													"db_type" => $type,
													"db_table" => $table_name,
													"db_table_alias" => $table_alias,
												),
												"action" => array(
													"single_insert" => 1,
													"single_insert_ok_msg_redirect_url" => $single_insert_ok_msg_redirect_url,
												),
												"links" => array(),
												"attributes" => $task_properties_ui_attributes,
												"brokers_services_and_rules" => $brokers_services_and_rules,
												"users_perms" => $generic_users_perms,
											),
											"exits" => array(
												"default_exit" => $task_exits,
											),
										)
									),
								);
								
								break;
							
							case "update": //edit form ui
							case "delete": //delete form ui
								$task_exits = array();
								
								if (!empty($brokers_settings["get_all"]))
									$task_exits[] = array(
										"task_id" => getTaskId($task_absolute_path, $overwrite, "page", $table_name, "list"),
										"label" => "View all " . CMSPresentationFormSettingsUIHandler::getName($tn_plural),
										"type" => "Straight",
										"overlay" => "Forward Arrow",
										"color" => "#4070FF",
										"properties" => array(
											"connection_type" => "link",
											"connection_title" => CMSPresentationFormSettingsUIHandler::getName($tn_plural) . " List",
											"connection_class" => "view-all",
										),
									);
								
								if (!empty($brokers_settings["get"]))
									$task_exits[] = array(
										"task_id" => getTaskId($task_absolute_path, $overwrite, "page", $table_name, "view"),
										"label" => "View",
										"type" => "Straight",
										"overlay" => "Forward Arrow",
										"color" => "#4070FF",
										"properties" => array(
											"connection_type" => "link",
											"connection_title" => "View $tn",
											"connection_class" => "view",
										),
									);
								
								//add task exits for the relationships
								prepareRelationshipsTaskExits($table_name, $tables_alias, $brokers_settings, $tn, $tn_label, $absolute_path, $relative_path, $overwrite, $task_exits);
								
								//prepare links and comboboxes for the foreign attributes
								$task_properties_ui_attributes = $default_task_properties_ui_attributes;
								prepareFKsActionsAndAttributesSettings($settings, $tables, $table_name, $tables_alias, $tn, $db_driver, $include_db_driver, $type, $absolute_path, $relative_path, $overwrite, $task_properties_ui_attributes, $form_type, !empty($brokers_settings["update"]));
								
								//prepare broker_settings
								$single_delete_ok_msg_redirect_url = !empty($brokers_settings["delete"]) && !empty($brokers_settings["get_all"]) ? getTaskPageUrl($task_absolute_path, $task_relative_path, $overwrite, "list") : "";
								
								//prepare brokers
								$brokers_services_and_rules = $brokers_settings;
								unset($brokers_services_and_rules["insert"]);
								
								//prepare offsets. Hard code the offsets bc the diagram will be more beautifull
								$offset_top = 270;
								$offset_left = 520;
								
								//prepare task details
								$task_id = getTaskId($task_absolute_path, $overwrite, "page", $table_name, "edit");
								$inner_task_id = getTaskId($task_absolute_path, $overwrite, "form", $table_name, "edit");
								$task_file_name = getTaskPageId($task_absolute_path, $overwrite, "edit");
								
								//jump if already exists. This happens bc if the $brokers_settings contains update and delete, it will execute twice this code, so we need to avoid it and continue the loop for the remaining items.
								if (!empty($tasks_details[$task_id]))
									continue 2;
								
								//prepare page settings
								$task_page_settings = $generic_page_settings;
								$task_page_settings["regions_blocks"][] = array("region" => "Sub Menu", "block" => $sub_menu_id);
								
								$task_page_settings["regions_blocks"][] = array("region" => "Content", "block" => "$task_relative_path$task_file_name");
								$task_page_settings["template_params"][] = array("name" => "Page Title", "value" => getPageTitle($tn, "update"));
								
								$tasks_details[$task_id] = array(
									"label" => $task_file_name,
									"id" => $task_id,
									"type" => $page_task_type_id,
									"tag" => "page",
									"offset_top" => $offset_top,
									"offset_left" => $offset_left,
									"width" => $width,
									"height" => $height,
									"properties" => array(
										"exits" => array(
											"default_exit" => array("color" => "#4070FF"),
										),
										"file_name" => $task_file_name,
										"template" => $authenticated_template,
										"join_type" => "list",
										"links" => array(),
										"authentication_type" => $generic_authentication_type,
										"authentication_users" => $generic_authentication_users,
										"page_settings" => $task_page_settings,
									),
									"exits" => array(),
									"tasks" => array(
										$inner_task_id => array(
											"label" => "Edit " . $tn_label,
											"id" => $inner_task_id,
											"type" => $form_task_type_id,
											"tag" => "form",
											"offset_top" => $offset_top + 50,
											"offset_left" => $offset_left + 10,
											"properties" => array(
												"exits" => array(
													"default_exit" => array("color" => "#4070FF"),
												),
												"choose_db_table" => array(
													"db_driver" => $db_driver,
													"include_db_driver" => $include_db_driver,
													"db_type" => $type,
													"db_table" => $table_name,
													"db_table_alias" => $table_alias,
												),
												"action" => array(
													"single_update" => !empty($brokers_settings["update"]) ? 1 : 0,
													"single_delete" => !empty($brokers_settings["delete"]) ? 1 : 0,
													"single_delete_confirmation_message" => null, //cannot be empty string "", otherwise there will not be any confirmation message
													"single_delete_ok_msg_redirect_url" => $single_delete_ok_msg_redirect_url,
												),
												"links" => array(),
												"attributes" => $task_properties_ui_attributes,
												"brokers_services_and_rules" => $brokers_services_and_rules,
												"users_perms" => $generic_users_perms,
											),
											"exits" => array(
												"default_exit" => $task_exits,
											),
										)
									),
								);
								break;
							
							case "relationships": //relationships uis
								//prepare offsets. Automatic offsets bc we don't know how many relationships exists, so it must be automatically but only after all the other tasks
								$table_task_count = 0;
								$table_row_count = 1;
								$offset_top = 570;
								$offset_left = 100;
								
								if ($broker_settings)
									foreach ($broker_settings as $relationship_table => $relationship_table_settings) 
										if ($relationship_table_settings) {
											$relationship_table_alias = isset($tables_alias[$relationship_table]) ? $tables_alias[$relationship_table] : null;
											$rn = $relationship_table_alias ? $relationship_table_alias : $relationship_table;
											$rn_plural = CMSPresentationFormSettingsUIHandler::getPlural($rn);
											$rn_label = CMSPresentationFormSettingsUIHandler::getName($rn);
											
											$rn_relative_path = $relative_path . CMSPresentationUIDiagramFilesHandler::getLabelFileName($rn) . "/";
											$rn_absolute_path = $absolute_path . CMSPresentationUIDiagramFilesHandler::getLabelFileName($rn) . "/";
											
											$t = count($relationship_table_settings);
											for ($i = 0; $i < $t; $i++) {
												$relationship_table_broker_settings = $relationship_table_settings[$i];
												
												if ($relationship_table_broker_settings) {
													//prepare relationship
													$rn_attrs = WorkFlowDBHandler::getTableFromTables($tables, $relationship_table);
													$relationship_task_properties_ui_attributes = array();
													if ($rn_attrs) 
														foreach ($rn_attrs as $attr_name => $attr) 
															$relationship_task_properties_ui_attributes[$attr_name] = array("active" => 1);
													
													$relationship_table_count_broker_settings = isset($brokers_settings["relationships_count"][$relationship_table][$i]) ? $brokers_settings["relationships_count"][$relationship_table][$i] : null;
													$relationship_table_brokers_settings = isset($settings[$relationship_table]) ? $settings[$relationship_table] : null;
													
													$relationship_table_brokers_settings["get_all"] = $relationship_table_broker_settings;
													$relationship_table_brokers_settings["count"] = $relationship_table_count_broker_settings;
													
													//prepare links
													$links = array();
													
													if (!empty($brokers_settings["get_all"]))
														$links[] = array(
															"url" => getTaskPageUrl($task_absolute_path, $task_relative_path, $overwrite, "list"),
															"value" => "View all " . CMSPresentationFormSettingsUIHandler::getName($tn_plural),
															"class" => "view-all",
															"title" => "View all " . CMSPresentationFormSettingsUIHandler::getName($tn_plural),
														);
													
													if (!empty($brokers_settings["get"])) {
														$pks_query_string = getTablePKsQueryStringFromCurrentURL($attrs, $form_type);
														
														if ($pks_query_string)
															$links[] = array(
																"url" => getTaskPageUrl($task_absolute_path, $task_relative_path, $overwrite, "view") . "?" . $pks_query_string,
																"value" => "View $tn_label",
																"class" => "view",
																"title" => "View $tn_label",
															);
													}
													
													if (!empty($relationship_table_brokers_settings["insert"])) {
														$rn_fks_query_string = getFKQueryStringFromCurrentURL($table_name, $rn_attrs, $form_type);										
														
														$links[] = array(
															"url" => getTaskPageUrl($rn_absolute_path, $rn_relative_path, $overwrite, "add") . "?" . $rn_fks_query_string,
															"value" => "Add new $rn_label",
															"class" => "add",
															"title" => "Add new $rn_label",
														);
													}
													
													//prepare inner links
													$inner_links = array();
													
													if (!empty($relationship_table_brokers_settings["get"])) 
														$inner_links[] = array(
															"url" => getTaskPageUrl($rn_absolute_path, $rn_relative_path, $overwrite, "view"),
															"value" => "View",
															"class" => "view",
															"title" => "View $rn_label",
														);
													
													if (!empty($relationship_table_brokers_settings["update"]) || !empty($relationship_table_brokers_settings["delete"]))
														$inner_links[] = array(
															"url" => getTaskPageUrl($rn_absolute_path, $rn_relative_path, $overwrite, "edit"),
															"value" => "Edit",
															"class" => "edit",
															"title" => "Edit $rn_label",
														);
													
													//add links to relationships
													prepareRelationshipsLinks($relationship_table, $tables_alias, $relationship_table_brokers_settings, $rn, $rn_label, $absolute_path, $relative_path, $overwrite, $rn_attrs, $inner_links, $form_type);
													
													//prepare links and comboboxes for the foreign attributes
													prepareFKsActionsAndAttributesSettings($settings, $tables, $relationship_table, $tables_alias, $tn, $db_driver, $include_db_driver, $type, $absolute_path, $relative_path, $overwrite, $relationship_task_properties_ui_attributes, $form_type, !empty($brokers_services_and_rules["insert"]) || !empty($brokers_services_and_rules["update"]));
													
													//prepare brokers
													$brokers_services_and_rules = $relationship_table_brokers_settings;
													unset($brokers_services_and_rules["get"]); //this unset is very important otherwise the ajax delete file will be messy bc it will try to get the item after delete it.
													unset($brokers_services_and_rules["relationships"]);
													unset($brokers_services_and_rules["relationships_count"]);
													
													if (!empty($relationship_table_brokers_settings["insert"]))
														unset($brokers_services_and_rules["insert"]);
													
													if (!empty($relationship_table_brokers_settings["update"]))
														unset($brokers_services_and_rules["update"]);
													
													if (!empty($relationship_table_brokers_settings["delete"]))
														unset($brokers_services_and_rules["delete"]);
													
													//prepare task details
													$suffix = $i > 0 ? "_" . ($i + 1) : "";
													$relationship_page_id = "relationship_" . strtolower($rn) . $suffix;
													$task_id = getTaskId($task_absolute_path, $overwrite, "page", $table_name, $relationship_page_id);
													$inner_task_id = getTaskId($task_absolute_path, $overwrite, "listing", $table_name, $relationship_page_id);
													$task_file_name = getTaskPageId($task_absolute_path, $overwrite, $relationship_page_id);
													
													//prepare page settings
													$task_page_settings = $generic_page_settings;
													$task_page_settings["regions_blocks"][] = array("region" => "Sub Menu", "block" => $sub_menu_id);
													
													$task_page_settings["regions_blocks"][] = array("region" => "Content", "block" => "$task_relative_path$task_file_name");
													$task_page_settings["template_params"][] = array("name" => "Page Title", "value" => getPageTitle($rn, "get_all"));
													
													$tasks_details[$task_id] = array(
														"label" => $task_file_name,
														"id" => $task_id,
														"type" => $page_task_type_id,
														"tag" => "page",
														"offset_top" => $offset_top,
														"offset_left" => $offset_left,
														"width" => $width,
														"height" => $height,
														"properties" => array(
															"exits" => array(
																"default_exit" => array("color" => "#4070FF"),
															),
															"file_name" => $task_file_name,
															"template" => $authenticated_template,
															"join_type" => "list",
															"links" => $links,
															"authentication_type" => $generic_authentication_type,
															"authentication_users" => $generic_authentication_users,
															"page_settings" => $task_page_settings,
														),
														"exits" => array(),
														"tasks" => array(
															$inner_task_id => array(
																"label" => $tn_label . " " . CMSPresentationFormSettingsUIHandler::getName($rn_plural) . " List",
																"id" => $inner_task_id,
																"type" => $listing_task_type_id,
																"tag" => "listing",
																"offset_top" => $offset_top + 50,
																"offset_left" => $offset_left + 10,
																"properties" => array(
																	"exits" => array(
																		"default_exit" => array("color" => "#4070FF"),
																	),
																	"listing_type" => "", //to draw a table_list
																	"choose_db_table" => array(
																		"db_driver" => $db_driver,
																		"include_db_driver" => $include_db_driver,
																		"db_type" => $type,
																		"db_table" => $relationship_table,
																		"db_table_alias" => $relationship_table_alias,
																		"db_table_parent" => $table_name,
																		"db_table_parent_alias" => $table_alias,
																	),
																	"action" => array(
																		"single_insert" => !empty($brokers_services_and_rules["insert"]) ? 1 : 0,
																		"single_update" => !empty($brokers_services_and_rules["update"]) ? 1 : 0,
																		"single_delete" => !empty($brokers_services_and_rules["delete"]) ? 1 : 0,
																		"multiple_delete" => !empty($brokers_services_and_rules["delete"]) ? 1 : 0,
																		"single_delete_confirmation_message" => null, //cannot be empty string "", otherwise there will not be any confirmation message
																		"multiple_delete_confirmation_message" => null, //cannot be empty string "", otherwise there will not be any confirmation message
																		"single_delete_ok_redirect_url" => null,
																		"multiple_delete_ok_redirect_url" => null,
																	),
																	"links" => $inner_links,
																	"pagination" => array(
																		"active" => 1,
																		"rows_per_page" => 500,
																	),
																	"attributes" => $relationship_task_properties_ui_attributes,
																	"brokers_services_and_rules" => $brokers_services_and_rules,
																	"users_perms" => $generic_users_perms,
																),
																"exits" => array(
																	"default_exit" => array(),
																),
															)
														),
													);
													
													//prepare offsets
													if ($table_task_count != count($tasks_details)) {
														//echo "$table_name:$broker_key:$relationship_table:$offset_left,$offset_top\n";
														$table_task_count = count($tasks_details);
														$is_even = $table_task_count % 2 == 0; //if true, $table_task_count is even number. Everytime is even, it means is a new row, oterwise if is odd is a new column.
														$table_row_count += $is_even ? 1 : 0;
														$offset_left = $is_even ? 100 * $table_row_count : $offset_left + $width + 100;
														$offset_top = $is_even ? $offset_top + $height + 50 : $offset_top;
													}
												}
											}
										}
								break;
						}
					} //end of brokers_settings loop
					
					$tables_tasks_details[$table_name] = $tasks_details;
					
				} //end of $settings loop
				
				//print_r(array_keys($tables_tasks_details));print_r($tables_tasks_details);die();
				//print_r($tasks_details);die();
				
				//Do this here, bc if this code is inside of the $settings loop and if $overwrite is false, then when we get a task_id through getTaskId(...), we will get a name with "_cp", bc the relationship file was created before in the previous loop item. By calling CMSPresentationUIDiagramFilesHandler::createPageFile after all getTaskId methods be called, we avoid this issue.
				foreach ($tables_tasks_details as $table_name => $tasks_details) {
					$table_alias = isset($tables_alias[$table_name]) ? $tables_alias[$table_name] : null;
					$tn = $table_alias ? $table_alias : $table_name;
					$task_relative_path = "$relative_path$tn/";
					$table_statuses = array();
					
					//save task details pages
					foreach ($tasks_details as $task_id => $task)
						if (isset($task["tag"]) && $task["tag"] == "page") {
							$js_funcs = array();
							$js_code = "";
							
							CMSPresentationUIDiagramFilesHandler::createPageFile($PEVC, $UserCacheHandler, $cms_page_cache_path_prefix, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $task_relative_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $list_and_edit_users, $authenticated_template, $non_authenticated_template, $authenticated_template, $table_statuses, $js_funcs, $js_code, $tasks_details, $task);
							
							//prepare created_files and statuses
							foreach ($table_statuses as $t_id => $task_statuses) {
								//prepare statuses
								foreach ($task_statuses as $file_path => $file_data)
									$statuses[$table_name][$file_path] = !empty($file_data["modified_time"]) ? true : false;
								
								//prepare created_files
								$tasks_details = addCreatedFilesToTaskDetails($tasks_details, $t_id, $task_statuses);
							}
							
							//prepare task page settings
							$tasks_details[$task_id] = getTaskWithUpdatedFilePageSettings($PEVC, $task, isset($statuses[$table_name]) ? $statuses[$table_name] : null, "$absolute_path$tn/");
						}
					
					//save diagram xml
					saveTasksDetailsToDiagamXML($workflow_paths_id, $bean_name, "$path$tn/", $tasks_details);
					
				} //end of $tables_tasks_details loop
				/* End: tables pages */
				
				$auth_page_and_block_ids = CMSPresentationUIDiagramFilesHandler::getAuthPageAndBlockIds();
				
				/* Start: vars */
				//prepare vars and save them to file
				$vars = array(
					"admin_url" => "{\$project_url_prefix}$relative_path",
					"logout_url" => "{\$project_url_prefix}" . $auth_page_and_block_ids["logout_page_id"],
					"login_url" => "{\$project_url_prefix}" . $auth_page_and_block_ids["login_page_id"],
					"register_url" => "{\$project_url_prefix}" . $auth_page_and_block_ids["register_page_id"],
					"forgot_credentials_url" => "{\$project_url_prefix}" . $auth_page_and_block_ids["forgot_credentials_page_id"],
					"edit_profile_url" => "{\$project_url_prefix}" . $auth_page_and_block_ids["edit_profile_page_id"],
					"list_and_edit_users_url" => "{\$project_url_prefix}" . $auth_page_and_block_ids["list_and_edit_users_page_id"],
					"list_users_url" => "{\$project_url_prefix}" . $auth_page_and_block_ids["list_users_page_id"],
					"edit_user_url" => "{\$project_url_prefix}" . $auth_page_and_block_ids["edit_user_page_id"],
					"add_user_url" => "{\$project_url_prefix}" . $auth_page_and_block_ids["add_user_page_id"],
				);
				CMSPresentationUIDiagramFilesHandler::addVarsFile($PEVC, $vars_file_id, $vars);
				CMSPresentationUIDiagramFilesHandler::addUserAccessControlToVarsFile($PEVC, $vars_file_id, $list_and_edit_users, array("list_and_edit_users_url", "list_users_url", "edit_user_url", "add_user_url"));
				/* End: vars */
				
				/* Start: statuses */
				//prepare statuses and entitites save action time cache
				foreach ($statuses as $table_name => $table_statuses)
					foreach ($table_statuses as $file_path => $status) {
						unset($statuses[$table_name][$file_path]);
						
						$fp = str_replace($layer_path, "", $file_path);
						
						$is_auth_reserved_file = false;
						if ($auth_page_and_block_ids)
							foreach ($auth_page_and_block_ids as $k => $v)
								if ($k != "access_id" && $k != "object_type_page_id") {
									$path = $selected_project_id . (strpos($k, "_page_id") > 0 ? "/src/entity/" : "/src/block/") . $v . ".php";
									
									if ($fp == $path) {
										$is_auth_reserved_file = true;
										break;
									}
								}
						
						if ($is_auth_reserved_file)
							$statuses["*"][$fp] = $status;
						else
							$statuses[$table_name][$fp] = $status;
						
						//update save creation status but only for entities file
						if (strpos($fp, "$selected_project_id/src/entity/") === 0) 
							CMSPresentationLayerHandler::cacheEntitySaveActionTime($PEVC, $UserCacheHandler, $cms_page_cache_path_prefix, $file_path);
					}
				//print_r($statuses);die("END\n");
				/* End: statuses */
			}
		}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
}

//Gets the entity file path for the page task. then gets the blocks from file and update the $task["page_settings"] with the correspondent blocks. This is very important bc if there is authentication, the CMSPresentationUIDiagramFilesHandler::createPageFile will create new blocks and template params and includes, which we should get directly from file and the update the $task.
function getTaskWithUpdatedFilePageSettings($PEVC, $task, $table_statuses, $task_absolute_path) {
	$P = $PEVC->getPresentationLayer();
	$selected_project_id = $P->getSelectedPresentationId();
	$extension = $P->getPresentationFileExtension();
	
	$file_path = !empty($task["properties"]["file_name"]) ? $task_absolute_path . $task["properties"]["file_name"] . "." . $extension : null;
	//echo "\n$file_path(".$table_statuses[$file_path]."):".file_exists($file_path);
	
	if (file_exists($file_path) && !empty($table_statuses[$file_path])) {
		$code = file_get_contents($file_path);
		
		$includes = CMSFileHandler::getIncludes($code, false);
		//echo "<pre>";print_r($includes);
		
		$regions_blocks = CMSFileHandler::getRegionsBlocks($code);
		//echo "<pre>";print_r($regions_blocks);
		
		$template_params = CMSFileHandler::getParamsValues($code);;
		//echo "<pre>";print_r($template_params);
		
		$page_settings = isset($task["properties"]["page_settings"]) ? $task["properties"]["page_settings"] : null;
		$page_settings["includes"] = $page_settings["regions_blocks"] = $page_settings["template_params"] = array();
		//print_r($task["properties"]["page_settings"]);
		
		if ($includes)
			foreach ($includes as $include)
				$page_settings["includes"][] = array(
					"path" => isset($include["path"]) ? $include["path"] : null,
					"once" => isset($include["once"]) ? $include["once"] : null,
				);
		
		if ($regions_blocks)
			foreach ($regions_blocks as $region_block)
				$page_settings["regions_blocks"][] = array(
					"region" => isset($region_block["region"]) ? $region_block["region"] : null,
					"block" => isset($region_block["block"]) ? $region_block["block"] : null,
					"project" => !empty($region_block["block_project"]) && $region_block["block_project"] != $selected_project_id ? $region_block["block_project"] : "",
				);
		
		if ($template_params)
			foreach ($template_params as $param)
				$page_settings["template_params"][] = array(
					"name" => isset($param["param"]) ? $param["param"] : null,
					"value" => isset($param["value"]) ? $param["value"] : null,
				);
		
		$task["properties"]["page_settings"] = $page_settings;
		//print_r($task["properties"]["page_settings"]);
	}
	
	return $task;
}

//prepare created_files
function addCreatedFilesToTaskDetails($tasks_details, $task_id, $task_statuses) {
	foreach ($tasks_details as $t_id => $task_details) {
		if ($t_id == $task_id) {
			foreach ($task_statuses as $file_path => $file_data)
				if ($file_data && !empty($file_data["modified_time"]) && !empty($file_data["file_id"]) && file_exists($file_path)) {
					if (empty($tasks_details[$t_id]["properties"]["created_files"]))
						$tasks_details[$t_id]["properties"]["created_files"] = array();
					
					$tasks_details[$t_id]["properties"]["created_files"][ $file_data["file_id"] ] = isset($file_data["modified_time"]) ? $file_data["modified_time"] : null;
					//echo "\n$t_id:".$file_data["file_id"];
				}
			
			break;
		}
		else if (!empty($task_details["tasks"]))
			$tasks_details[$t_id]["tasks"] = addCreatedFilesToTaskDetails($task_details["tasks"], $task_id, $task_statuses);
	}
	
	return $tasks_details;
}

//add a ui diagram xml file with the tasks_details inside of $task_relative_path folder.
function saveTasksDetailsToDiagamXML($workflow_paths_id, $bean_name, $xml_relative_path, $tasks_details) {
	prepareTasksDetailsForDiagramXMLFile($tasks_details);
	
	$xml_relative_path .= substr($xml_relative_path, -1) == "/" ? "" : "/"; //must have the "/" at the end otherwise the $workflow_path will not be correct.
	$workflow_path = WorkFlowTasksFileHandler::getTaskFilePathByPath($workflow_paths_id, "presentation_ui", "_{$bean_name}_" . md5($xml_relative_path));
	
	if (!file_exists($workflow_path))
		return WorkFlowTasksFileHandler::createTasksFile($workflow_path, array("tasks" => $tasks_details));
	else {
		//load old tasks from existent file
		$WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($workflow_path);
		$WorkFlowTasksFileHandler->init();
		$tasks = $WorkFlowTasksFileHandler->getWorkflowData();
		
		//overwrite existent task but keep current offsets and sizes and add new tasks. Leave other old tasks...
		$attrs_to_keep = array("offset_top", "offset_left", "width", "height");
		
		foreach ($tasks_details as $task) {
			$task_id = isset($task["id"]) ? $task["id"] : null;
			
			if (!empty($tasks["tasks"]))
				foreach ($tasks["tasks"] as $old_task) {
					$old_task_id = isset($old_task["id"]) ? $old_task["id"] : null;
					
					if ($old_task_id == $task_id) {
						foreach ($attrs_to_keep as $attr_to_keep) 
							if (strlen($old_task[$attr_to_keep])) 
								$task[$attr_to_keep] = $old_task[$attr_to_keep];
						
						break;
					}
				}
			
			$tasks["tasks"][$task_id] = $task;
		}
		
		return WorkFlowTasksFileHandler::createTasksFile($workflow_path, $tasks);
	}
}

function prepareTasksDetailsForDiagramXMLFile(&$tasks_details) {
	foreach ($tasks_details as $task_id => $task) {
		unset($tasks_details[$task_id]["properties"]["brokers_services_and_rules"]);
		unset($tasks_details[$task_id]["properties"]["files_to_create"]);
		
		if (!empty($task["tasks"])) {
			prepareTasksDetailsForDiagramXMLFile($task["tasks"]);
			$new_sub_tasks = array();
			
			foreach ($task["tasks"] as $sub_task_id => $sub_task) { //Note that it cannot be repeated $task_id or $sub_task_id
				$tasks_details[$sub_task_id] = $sub_task;
				$new_sub_tasks[$sub_task_id] = " > div:nth-child(10)";
			}
			
			$tasks_details[$task_id]["tasks"] = $new_sub_tasks;
		}
		
		//prepare task attributes - convert it to array with numeric keys - bc if we save it as it is, it will create xml nodes with the correspondent attributes names and these names are not compatible with the xml syntax, this is, the db attribute names can have the '+' symbol, but the xml nodes cannot!
		if (!empty($task["properties"]["attributes"])) {
			$new_attributes = array();
			
			foreach ($task["properties"]["attributes"] as $attribute_name => $attribute) {
				$attribute["name"] = $attribute_name;
				
				if (isset($attribute["include_db_driver"]))
					$attribute["include_db_driver"] = !empty($attribute["include_db_driver"]) ? 1 : 0;
				
				$new_attributes[] = $attribute;
			}
			
			$tasks_details[$task_id]["properties"]["attributes"] = $new_attributes;
		}
		
		if (isset($task["properties"]["choose_db_table"]["include_db_driver"])) //it could be a page, so we need to check if the exists, bc only exists if task is listing, form or view.
			$tasks_details[$task_id]["properties"]["choose_db_table"]["include_db_driver"] = !empty($task["properties"]["choose_db_table"]["include_db_driver"]) ? 1 : 0;
	}
}

function getTaskId($task_absolute_path, $overwrite, $task_type, $table_name, $page_id) {
	$page_id = getValidadedTaskPageId($task_absolute_path, $overwrite, $page_id);
	return "task_{$task_type}" . ($table_name ? "_{$table_name}" : "") . "_{$page_id}";
}

function getPageIdFromTaskId($task_type, $table_name, $task_id) {
	return substr($task_id, strlen("task_{$task_type}" . ($table_name ? "_{$table_name}" : "") . "_"));
}

function getTaskPageUrl($task_absolute_path, $task_relative_path, $overwrite, $page_id) {
	$page_id = getValidadedTaskPageId($task_absolute_path, $overwrite, $page_id);
	$task_relative_path .= substr($task_relative_path, -1) == "/" ? "" : "/";
	
	return "{\$project_url_prefix}$task_relative_path$page_id";
}

function getTaskPageId($task_absolute_path, $overwrite, $page_id) {
	$page_id = getValidadedTaskPageId($task_absolute_path, $overwrite, $page_id);
	return $page_id;
}

function getValidadedTaskPageId($task_absolute_path, $overwrite, $page_id) {
	if ($overwrite)
		return $page_id;
	
	//checks if $page_id file exists, and if so, create a new one that does not exists
	$task_absolute_path .= substr($task_absolute_path, -1) == "/" ? "" : "/";
	CMSPresentationLayerHandler::configureUniqueFileId($page_id, $task_absolute_path, ".php");
	
	return $page_id;
}

function prepareRelationshipsLinks($table_name, $tables_alias, $brokers_settings, $tn, $tn_label, $absolute_path, $relative_path, $overwrite, $attrs, &$links, $form_type) {
	if ($brokers_settings && !empty($brokers_settings["relationships"]))
		foreach ($brokers_settings["relationships"] as $relationship_table => $relationship_table_settings) 
			if ($relationship_table_settings) {
				$relationship_table_alias = isset($tables_alias[$relationship_table]) ? $tables_alias[$relationship_table] : null;
				$rn = $relationship_table_alias ? $relationship_table_alias : $relationship_table;
				
				$t = count($relationship_table_settings);
				for ($j = 0; $j < $t; $j++) 
					if ($relationship_table_settings[$j]) {
						$page_id = "relationship_" . strtolower($rn) . ($j > 0 ? "_" . ($j + 1) : "");
						$rn_label = CMSPresentationFormSettingsUIHandler::getPlural(CMSPresentationFormSettingsUIHandler::getName($rn));
						$fks_query_string = getFKQueryStringForTable($relationship_table, $attrs, $form_type, $tn);
						
						$tn_relative_path = $relative_path . CMSPresentationUIDiagramFilesHandler::getLabelFileName($tn) . "/";
						$tn_absolute_path = $absolute_path . CMSPresentationUIDiagramFilesHandler::getLabelFileName($tn) . "/";
						
						$links[] = array(
							"url" => getTaskPageUrl($tn_absolute_path, $tn_relative_path, $overwrite, $page_id) . "?$fks_query_string",
							"value" => $rn_label,
							"class" => "relationship-table-item relationship-{$tn} relationship-{$tn}-" . strtolower($rn),
							"title" => "View $rn_label of this $tn_label",
						);
					}
			}
}

function prepareRelationshipsTaskExits($table_name, $tables_alias, $brokers_settings, $tn, $tn_label, $absolute_path, $relative_path, $overwrite, &$task_exits) {
	if ($brokers_settings && !empty($brokers_settings["relationships"]))
		foreach ($brokers_settings["relationships"] as $relationship_table => $relationship_table_settings) 
			if ($relationship_table_settings) {
				$relationship_table_alias = $tables_alias[$relationship_table];
				$rn = $relationship_table_alias ? $relationship_table_alias : $relationship_table;
				
				$t = count($relationship_table_settings);
				for ($j = 0; $j < $t; $j++) 
					if ($relationship_table_settings[$j]) {
						$suffix = $j > 0 ? "_" . ($j + 1) : "";
						$task_id = getTaskId("$absolute_path$tn/", $overwrite, "page", $table_name, "relationship_" . strtolower($rn) . $suffix);
						$rn_label = CMSPresentationFormSettingsUIHandler::getPlural(CMSPresentationFormSettingsUIHandler::getName($rn));
						
						$task_exits[] = array(
							"task_id" => $task_id,
							"label" => $rn_label,
							"type" => "Straight",
							"overlay" => "Forward Arrow",
							"color" => "#4070FF",
							"properties" => array(
								"connection_type" => "link",
								"connection_title" => "View $rn_label of this $tn_label",
								"connection_class" => "relationship-table-item relationship-{$tn} relationship-{$tn}-" . strtolower($rn),
							),
						);
					}
			}
}

//If there are FKs columns, check if exist the GET CODE for the FK TABLE, and if exists, create a link to go directly to the FK UI.
function prepareFKsActionsAndAttributesSettings($settings, $tables, $table_name, $tables_alias, $tn, $db_driver, $include_db_driver, $type, $absolute_path, $relative_path, $overwrite, &$task_properties_ui_attributes, $form_type, $is_editable = false) {
	$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
	
	if ($attrs) 
		foreach ($attrs as $attr_name => $attr) 
			if (!empty($attr["fk"][0]["table"])) { //setting fk link
				$attr_fk = WorkFlowDataAccessHandler::getTableAttributeFKTable($attr["fk"], $tables);
				$fk_table = isset($attr_fk["table"]) ? $attr_fk["table"] : null;
				$fk_attr = isset($attr_fk["attribute"]) ? $attr_fk["attribute"] : null;
				
				$fk_attrs = WorkFlowDBHandler::getTableFromTables($tables, $fk_table);
				
				if ($fk_attrs && !empty($fk_attrs[$fk_attr])) {
					$title_attr = WorkFlowDataAccessHandler::getTableAttrTitle($fk_attrs, $fk_table);
					
					if ($title_attr) {
						$fk_alias = isset($tables_alias[$fk_table]) ? $tables_alias[$fk_table] : null;
						$fkn = $fk_alias ? $fk_alias : $fk_table;
						
						$attribute_settings = array(
							"list_type" => "from_db",
							"db_driver" => $db_driver,
							"include_db_driver" => $include_db_driver,
							"db_type" => $type,
							"db_table" => $fk_table,
							"db_table_alias" => $fk_alias,
							"db_attribute_label" => $title_attr,
							"db_attribute_fk" => $fk_attr,
						);
						
						if (!empty($settings[$fk_table]) && !empty($settings[$fk_table]["get"]) && !$is_editable) {
							$fkn_relative_path = $relative_path . CMSPresentationUIDiagramFilesHandler::getLabelFileName($fkn) . "/";
							$fkn_absolute_path = $absolute_path . CMSPresentationUIDiagramFilesHandler::getLabelFileName($fkn) . "/";
							
							$fks_query_string = getFKQueryStringForTable($fk_table, $attrs, $form_type, $tn);
							$attribute_settings["link"] = getTaskPageUrl($fkn_absolute_path, $fkn_relative_path, $overwrite, "view") . "?$fks_query_string";
						}
						
						if (!empty($task_properties_ui_attributes[$attr_name]))
							$task_properties_ui_attributes[$attr_name] = array_merge($task_properties_ui_attributes[$attr_name], $attribute_settings);
						else
							$task_properties_ui_attributes[$attr_name] = $attribute_settings;
					}
				}
			}
}

function getExtraHeadCode() {
	$code = '<?php
$block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);//must be the same than this file name.

$block_settings[$block_id] = array(
	"str" => \'<script>
function goToItem(url, table, pks) {
	if (url) {
		url += url.indexOf("?") == -1 ? "?" : "";
		var cancel = false;
		
		if (pks && pks.length) {
			var query_string = "";
	
			for (var i = 0; i < pks.length; i++) {
				var pk = pks[i];
			
				if (pk) {
					var pk_label = pk.replace(/_/g, " ").toLowerCase().replace(/(^([a-zA-Z\\p{M}]))|([ -][a-zA-Z\\p{M}])/g,
						function(s){
							return s.toUpperCase();
						});
					
					var pk_value = prompt("Please insert the correspondent value for the " + table + "\\\'s attribute: " + pk_label);
					if (pk_value == null || pk_value == "")
						cancel = true;
					
					pk_value = pk_value == null ? "" : pk_value;
					query_string += (query_string ? "&" : "") + pk + "=" + pk_value;
				}
			}
		
			url += query_string;
		}
		
		if (!cancel)
			document.location = url;
	}
	else {
		alert("Error: URL cannot be undefined in the goToItem function!");
	}
}
</script>\',
);

$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("echostr", $block_id, $block_settings[$block_id]);
?>';

	return $code;
}

function getHomePageMenusItems($settings, $relative_path, $tables_alias) {
	$menu_items = array();
	
	foreach ($settings as $table_name => $table_settings) {
		$table_alias = isset($tables_alias[$table_name]) ? $tables_alias[$table_name] : null;
		$tn = $table_alias ? $table_alias : $table_name;
		
		$name = CMSPresentationFormSettingsUIHandler::getName($tn);
		$plural = CMSPresentationFormSettingsUIHandler::getPlural($name);
		$tn_relative_path = $relative_path . CMSPresentationUIDiagramFilesHandler::getLabelFileName($tn) . "/";
		
		$menu_items[] = array(
			"label" => $plural,
			"url" => "{\$project_url_prefix}$tn_relative_path",
			"title" => $plural,
			"class" => "index",
		);
	}
	
	return $menu_items;
}

function getTableMenusItems($settings, $tables, $absolute_path, $relative_path, $overwrite, $tables_alias, $table_name) {
	$menu_items = array();
	
	$table_settings = isset($settings[$table_name]) ? $settings[$table_name] : null;
	
	if ($table_settings) {
		$get_all = isset($table_settings["get_all"]) ? $table_settings["get_all"] : null;
		$get = isset($table_settings["get"]) ? $table_settings["get"] : null;
		$insert = isset($table_settings["insert"]) ? $table_settings["insert"] : null;
		$update = isset($table_settings["update"]) ? $table_settings["update"] : null;
		$delete = isset($table_settings["delete"]) ? $table_settings["delete"] : null;
		$relationships = isset($table_settings["relationships"]) ? $table_settings["relationships"] : null;
		
		$table_alias = isset($tables_alias[$table_name]) ? $tables_alias[$table_name] : null;
		$tn = $table_alias ? $table_alias : $table_name;
		
		$task_relative_path = $relative_path . CMSPresentationUIDiagramFilesHandler::getLabelFileName($tn) . "/";
		$task_absolute_path = $absolute_path . CMSPresentationUIDiagramFilesHandler::getLabelFileName($tn) . "/";
		
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		
		if (!empty($attrs)) {
			$pks = array();
			foreach ($attrs as $attr_name => $attr)
				if (!empty($attr["primary_key"]))
					$pks[] = $attr_name;
			
			if ($get_all)
				$menu_items[] = array(
					"label" => getPageTitle($tn, "get_all"),
					"url" => getTaskPageUrl($task_absolute_path, $task_relative_path, $overwrite, "list"),
					"title" => getPageTitle($tn, "get_all"),
					"class" => "list",
				);
			
			if ($insert)
				$menu_items[] = array(
					"label" => getPageTitle($tn, "insert"),
					"url" => getTaskPageUrl($task_absolute_path, $task_relative_path, $overwrite, "add"),
					"title" => getPageTitle($tn, "insert"),
					"class" => "add",
				);
			
			if ($get)
				$menu_items[] = array(
					"label" => getPageTitle($tn, "get"),
					"url" => 'javascript:goToItem(\'' . getTaskPageUrl($task_absolute_path, $task_relative_path, $overwrite, "view") . '\', \'' . $tn . '\', [\'' . implode("', '", $pks) . '\'])',
					"title" => getPageTitle($tn, "get"),
					"class" => "view",
				);
			
			if ($update || $delete)
				$menu_items[] = array(
					"label" => getPageTitle($tn, "update"),
					"url" => 'javascript:goToItem(\'' . getTaskPageUrl($task_absolute_path, $task_relative_path, $overwrite, "edit") . '\', \'' . $tn . '\', [\'' . implode("', '", $pks) . '\'])',
					"title" => getPageTitle($tn, "update"),
					"class" => "edit",
				);
			
			if ($relationships)
				foreach ($relationships as $relationship_table => $relationship_table_brokers) 
					if ($relationship_table_brokers) {
						$relationship_table_alias = isset($tables_alias[$relationship_table]) ? $tables_alias[$relationship_table] : null;
						$rn = $relationship_table_alias ? $relationship_table_alias : $relationship_table;
						
						$t = count($relationship_table_brokers);
						for ($i = 0; $i < $t; $i++) {
							$page_id = "relationship_" . strtolower($rn) . ($i > 0 ? "_" . ($i + 1) : "");
							
							$menu_items[] = array(
								"label" => getPageTitle($rn, "get_all"),
								"url" => 'javascript:goToItem(\'' . getTaskPageUrl($task_absolute_path, $task_relative_path, $overwrite, $page_id) . '\', \'' . $tn . '\', [\'' . implode("', '", $pks) . '\'])',
								"title" => getPageTitle($rn, "get_all") . " for a specific " . CMSPresentationFormSettingsUIHandler::getName($tn) . "'s Item",
								"class" => "relationship",
							);
						}
					}
		}
	}
	
	return $menu_items;
}

function getMenusCode($settings, $tables, $absolute_path, $relative_path, $overwrite, $tables_alias) {
	$elements_code = '';
	
	foreach ($settings as $table_name => $table_settings) {
		$table_alias = isset($tables_alias[$table_name]) ? $tables_alias[$table_name] : null;
		$tn = $table_alias ? $table_alias : $table_name;
		$label = CMSPresentationFormSettingsUIHandler::getName($tn);
		
		$elements_code .= '
		array(
			"label" => "' . $label . '",
			"url" => "#",
			"title" => "' . $label . '",
			"class" => "' . $tn . '",
			"menus" => array(';
		
		$menu_items = getTableMenusItems($settings, $tables, $absolute_path, $relative_path, $overwrite, $tables_alias, $table_name);
		
		foreach ($menu_items as $menu_item) {
			$elements_code .= '
				array(';
			
			foreach ($menu_item as $l => $v)
				$elements_code .= '
					"' . addcslashes($l, '"') . '" => "' . addcslashes($v, '"') . '",';
			
			$elements_code .= '
				),';
		}
		
		$elements_code .= '
			)
		),';
	}
	
	$elements_code .= $elements_code ? "\n\t" : "";
	
	$code = '<?php
$block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);//must be the same than this file name.

$block_settings[$block_id] = array(
	"type" => "",
	"class" => "menu",
	"title" => "",
	"items_type" => "",
	"menus" => array(' . $elements_code . '),
	"style_type" => "template",
	"template_type" => "user_defined",
	"ptl" => array(
		"code" => "
<ptl:if is_array(\$input)>
	<!-- Note that this does not need the UL element bc the template already contains the UL. -->
	
	<ptl:foreach \$input i item>
		
		<!-- Nav Item - Components Collapse Menu -->
		<li class=\"nav-item <ptl:echo @\$item[class]/>\">
			<ptl:echo @\$item[previous_html]/>
			
			<ptl:if @\$item[menus]>
				<a class=\"nav-link collapsed\" href=\"#\" data-toggle=\"collapse\" data-target=\"#collapseLayouts-<ptl:echo \$i/>\" aria-expanded=\"false\" aria-controls=\"collapseLayouts-<ptl:echo \$i/>\" title=\\"<ptl:echo @\$item[title]/>\\" <ptl:echo @\$item[attrs]/>>
					<span class=\"sb-nav-link-icon\"><i class=\"fas fa-table\"></i></span>
					<span><ptl:echo @\$item[label]/></span>
					<span class=\"sb-sidenav-collapse-arrow\"><i class=\"fas fa-angle-down\"></i></span>
				</a>
				<ptl:echo @\$item[next_html]/>
				
				<div class=\"collapse\" id=\"collapseLayouts-<ptl:echo \$i/>\" aria-labelledby=\"headingOne\" data-parent=\"#sidenavAccordion\">
					<nav class=\"sb-sidenav-menu-nested nav\">
						<ptl:foreach \$item[menus] j sub_item>
							<ptl:getSubMenuHTML \$sub_item />
						</ptl:foreach>
					</nav>
				</div>
		    	<ptl:else>
		    		<a class=\"nav-link\" href=\"<ptl:echo @\$item[url]/>\" title=\\"<ptl:echo @\$item[title]/>\\" <ptl:echo @\$item[attrs]/>><ptl:echo \$item[label]/></a>
		    		
		    		<ptl:echo @\$item[next_html]/>
			</ptl:if>
		</li>
	</ptl:foreach>
</ptl:if>	

<ptl:function:getSubMenuHTML item>
	<a class=\"nav-link <ptl:echo @\$item[class]/>\" href=\"<ptl:echo @\$item[url]/>\" title=\\"<ptl:echo @\$item[title]/>\\" <ptl:echo @\$item[attrs]/>><ptl:echo @\$item[label]/></a>
</ptl:function>",
	),
	"css" => "",
);

$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("menu/show_menu", $block_id, $block_settings[$block_id]);
?>';

	return $code;
}

function getTableSubMenusCode($settings, $tables, $absolute_path, $relative_path, $overwrite, $tables_alias, $table_name) {
	$menu_items = getTableMenusItems($settings, $tables, $absolute_path, $relative_path, $overwrite, $tables_alias, $table_name);
	$elements_code = '';
	
	if ($menu_items)
		foreach ($menu_items as $menu_item) {
			$elements_code .= '
		array(';
			
			foreach ($menu_item as $l => $v)
				$elements_code .= '
			"' . addcslashes($l, '"') . '" => "' . addcslashes($v, '"') . '",';
			
			$elements_code .= '
		),';
		}
	
	$elements_code .= $elements_code ? "\n\t" : "";
	
	$code = '<?php
$block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);//must be the same than this file name.

$block_settings[$block_id] = array(
	"type" => "",
	"class" => "menu",
	"title" => "",
	"items_type" => "",
	"menus" => array(' . $elements_code . '),
	"style_type" => "template",
	"template_type" => "user_defined",
	"ptl" => array(
		"code" => "
<ptl:getMenuHTML \$input />

<ptl:function:getMenuHTML menus>
	<ptl:if is_array(\$menus)>
		<ptl:foreach \$menus i item>
			<a class=\"list-group-item list-group-item-action\" href=\"<ptl:echo @\$item[url]/>\" title=\\"<ptl:echo @\$item[title]/>\\"><i class=\"fas fa-fw fa-table\"></i> <ptl:echo @\$item[label]/></a>
			
			<ptl:if @\$item[menus]>
		    		<ptl:getMenuHTML \$item[menus] />
			</ptl:if>
		</ptl:foreach>
	</ptl:if>	
</ptl:function>",
	),
	"css" => "",
);

$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("menu/show_menu", $block_id, $block_settings[$block_id]);
?>';

	return $code;
}

function getIndexPageCode($page_name, $menu_items) {
	$html = '<h1 class="' . strtolower(str_replace(array(" ", "_"), "-", $page_name)) . '">' . ucwords(str_replace(array("-", "_"), " ", $page_name)) . '</h1>';
	
	if ($menu_items) {
		$html .= '<div class="align-items-center justify-content-between">';
		
		foreach ($menu_items as $menu_item) {
			$menu_item_url = isset($menu_item["url"]) ? $menu_item["url"] : null;
			$menu_item_title = isset($menu_item["title"]) ? $menu_item["title"] : null;
			$menu_item_label = isset($menu_item["label"]) ? $menu_item["label"] : null;
			
			$html .= '
		<div class="col-xl-3 col-md-4 col-sm-6 float-left">
			<a class="text-white stretched-link text-decoration-none" href="' . $menu_item_url . '" title="' . $menu_item_title . '">
				<div class="card bg-primary mt-2 mb-2 ml-2 mr-2">
	                     <div class="card-body">' . ucwords(str_replace(array("-", "_"), " ", $menu_item_label)) . '</div>
	                     <div class="card-footer bg-primary d-flex align-items-center justify-content-between">
	                         <span class="small text-white stretched-link" href="#">View Details</span>
	                         <div class="small text-white"><i class="fas fa-angle-right"></i></div>
	                     </div>
				</div>
			</a>
		</div>';
		}
		
		$html .= '</div>';
	}
	
	$code = '<?php
$block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);//must be the same than this file name.

$block_settings[$block_id] = array(
	"str" => "' . addcslashes($html, '"') . '",
);

$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("echostr", $block_id, $block_settings[$block_id]);
?>';

	return $code;
}

function getPageTitle($table_name, $type) {
	$name = CMSPresentationFormSettingsUIHandler::getName($table_name);
	$plural = CMSPresentationFormSettingsUIHandler::getPlural($name);
	$type = strtolower($type);
	
	switch ($type) {
		case "get_all": return $plural . "' List";
		case "get": return $name . " Properties";
		case "insert": return "Add new $name";
		case "update": return "Edit $name";
		case "delete": return "Delete $name";
	}
	
	return "";
}

function getFKQueryStringForTable($fk_table_name, $attrs, $form_type, $tn) {
	$fk_query_string = "";
	$lfktn = strtolower($fk_table_name);
	
	foreach ($attrs as $attr_name => $attr)
		if (isset($attr["fk"]) && is_array($attr["fk"]))
			foreach ($attr["fk"] as $fk) {
				$fk_table_lower = isset($fk["table"]) ? strtolower($fk["table"]) : null;
				
				if ($fk_table_lower == $lfktn && isset($fk["attribute"])) {
					/*if ($form_type == "ptl") //JP 2021-08-10: This is not tested so I disabled it, just in case.
						$value = '<ptl:echo @\\$' . $tn . '[' . $attr_name . '] />';
					else*/
						$value = "#$attr_name#";
					
					$fk_query_string .= ($fk_query_string ? "&" : "") . $fk["attribute"] . "=" . $value;
				}
			}
	
	return $fk_query_string;
}

function getFKQueryStringFromCurrentURL($table_name, $fk_attrs, $form_type) {
	$fk_query_string = "";
	$ltn = strtolower($table_name);
	
	if ($fk_attrs)
		foreach ($fk_attrs as $attr_name => $attr)
			if (isset($attr["fk"]) && is_array($attr["fk"]))
				foreach ($attr["fk"] as $fk) {
					$fk_table_lower = isset($fk["table"]) ? strtolower($fk["table"]) : null;
					
					if ($fk_table_lower == $ltn && isset($fk["attribute"])) {
						if ($form_type == "ptl")
							$value = '<ptl:echo isset(\\$_GET[' . $fk["attribute"] . ']) ? \\$_GET[' . $fk["attribute"] . '] : null />';
						else
							$value = '#_GET[' . $fk["attribute"] . ']#';
						
						$fk_query_string .= ($fk_query_string ? "&" : "") . $attr_name . "=" . $value;
					}
				}
	
	return $fk_query_string;
}

function getTablePKsQueryStringFromCurrentURL($attrs, $form_type) {
	$query_string = "";
	
	if ($attrs)
		foreach ($attrs as $attr_name => $attr)
			if (!empty($attr["primary_key"])) {
				if ($form_type == "ptl")
					$value = '<ptl:echo isset(\\$_GET[' . $attr_name . ']) ? \\$_GET[' . $attr_name . '] : null />';
				else
					$value = '#_GET[' . $attr_name . ']#';
				
				$query_string .= ($query_string ? "&" : "") . $attr_name . "=" . $value;
			}
	
	return $query_string;
}
?>
