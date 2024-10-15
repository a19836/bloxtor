<?php
include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once $EVC->getUtilPath("WorkFlowBrokersSelectedDBVarsHandler");
include_once $EVC->getUtilPath("WorkFlowUIHandler");
include_once $EVC->getUtilPath("WorkFlowQueryHandler");
include_once $EVC->getUtilPath("WorkFlowPresentationHandler");
include_once $EVC->getUtilPath("CMSPresentationUIAutomaticFilesHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectUIHandler");

class SequentialLogicalActivityUIHandler {
	
	public static function getHeader($EVC, $PEVC, $UserAuthenticationHandler, $bean_name, $bean_file_name, $path, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $user_beans_folder_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $filter_by_layout, $opts = false) {
		$head = "";
		
		$main_div_selector = isset($opts["main_div_selector"]) ? $opts["main_div_selector"] : null;
		$allowed_tasks_tag = isset($opts["allowed_tasks_tag"]) ? $opts["allowed_tasks_tag"] : null;
		$extra_tasks_folder_path = isset($opts["extra_tasks_folder_path"]) ? $opts["extra_tasks_folder_path"] : null;
		$tasks_groups_by_tag = isset($opts["tasks_groups_by_tag"]) ? $opts["tasks_groups_by_tag"] : null;
		$ui_menu_widgets_selector = isset($opts["ui_menu_widgets_selector"]) ? $opts["ui_menu_widgets_selector"] : null;
		
		if (!$allowed_tasks_tag)
			$allowed_tasks_tag = array("slaitemsingle", "slaitemgroup");
		
		/* DEPRECATED: the sequentiallogicalactivity tasks are now in the lib/org/phpframework/workflow/task/sequentiallogicalactivity/
		if (!$extra_tasks_folder_path) 
			$extra_tasks_folder_path = $EVC->getViewsPath() . "sequentiallogicalactivity/tasks/";
		*/
		
		if (!$tasks_groups_by_tag)
			$tasks_groups_by_tag = array(
				"SLA Groups/Actions" => array("slaitemsingle", "slaitemgroup")
			);
		
		if (!$ui_menu_widgets_selector)
			$ui_menu_widgets_selector = ".sla_ui_menu_widgets_backup";
		
		$P = $PEVC->getPresentationLayer();
		
		//getting available projects
		$presentation_projects = CMSPresentationLayerHandler::getPresentationLayerProjectsFiles($user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
		
		$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
		$LayoutTypeProjectHandler->filterPresentationLayerProjectsByUserAndLayoutPermissions($presentation_projects, $filter_by_layout, null, array(
				"do_not_filter_by_layout" => array(
					"bean_name" => $bean_name,
					"bean_file_name" => $bean_file_name,
					"project" => $P->getSelectedPresentationId(),
				)
			));
		//echo "<pre>";print_r($presentation_projects);die();
		$presentation_projects = array_keys($presentation_projects);
		
		//prepare layers settings and brokers
		$brokers = $P->getBrokers();
		$layer_brokers_settings = WorkFlowBeansFileHandler::getLayerBrokersSettings($user_global_variables_file_path, $user_beans_folder_path, $brokers, '$EVC->getBroker');
		$presentation_layer_label = WorkFlowBeansFileHandler::getLayerNameFromBeanObject($bean_name, $P) . " (Self)";
		//echo "<pre>";print_r($layer_brokers_settings);die();
		
		$presentation_brokers = array( array($presentation_layer_label, $bean_file_name, $bean_name) );
		$presentation_brokers_obj = array("default" => '$EVC->getPresentationLayer()');
		$layer_brokers_settings["presentation_brokers"] = $presentation_brokers;
		$layer_brokers_settings["presentation_brokers_obj"] = $presentation_brokers_obj;
		
		$business_logic_brokers = $layer_brokers_settings["business_logic_brokers"];
		$business_logic_brokers_obj = $layer_brokers_settings["business_logic_brokers_obj"];
		
		$data_access_brokers = $layer_brokers_settings["data_access_brokers"];
		$data_access_brokers_obj = $layer_brokers_settings["data_access_brokers_obj"];

		$ibatis_brokers = $layer_brokers_settings["ibatis_brokers"];
		$ibatis_brokers_obj = $layer_brokers_settings["ibatis_brokers_obj"];

		$hibernate_brokers = $layer_brokers_settings["hibernate_brokers"];
		$hibernate_brokers_obj = $layer_brokers_settings["hibernate_brokers_obj"];
		
		$db_brokers = $layer_brokers_settings["db_brokers"];
		$db_brokers_obj = $layer_brokers_settings["db_brokers_obj"];
		
		$brokers_settings = array(
			$presentation_layer_label => isset($presentation_brokers[0]) ? $presentation_brokers[0] : null
		);
		$brokers_name_by_obj_code = array(
			$presentation_brokers_obj["default"] => $presentation_layer_label,
		);
		
		foreach ($business_logic_brokers as $b) {
			$broker_name = isset($b[0]) ? $b[0] : null;
			$brokers_settings[$broker_name] = $b;
			$brokers_name_by_obj_code[ $business_logic_brokers_obj[$broker_name] ] = $broker_name;
		}
		
		foreach ($data_access_brokers as $b) {
			$broker_name = isset($b[0]) ? $b[0] : null;
			$brokers_settings[$broker_name] = $b;
			$brokers_name_by_obj_code[ $data_access_brokers_obj[$broker_name] ] = $broker_name;
		}
		
		//prepare tasks
		$default_allowed_tasks_tag = array("createform", "callfunction", "callobjectmethod", "restconnector", "soapconnector");
		$allowed_tasks_tag = is_array($allowed_tasks_tag) ? $allowed_tasks_tag : ($allowed_tasks_tag ? array($allowed_tasks_tag) : array());
		$allowed_tasks_tag = $allowed_tasks_tag ? array_merge($default_allowed_tasks_tag, $allowed_tasks_tag) : $default_allowed_tasks_tag;
		
		if ($data_access_brokers_obj) {
			$allowed_tasks_tag[] = "query";
			$allowed_tasks_tag[] = "getquerydata";
			$allowed_tasks_tag[] = "setquerydata";

			if ($ibatis_brokers_obj) 
				$allowed_tasks_tag[] = "callibatisquery";
			
			if ($hibernate_brokers_obj)
				$allowed_tasks_tag[] = "callhibernatemethod";
		}
		else if ($db_brokers_obj) {
			$allowed_tasks_tag[] = "query";
			$allowed_tasks_tag[] = "getquerydata";
			$allowed_tasks_tag[] = "setquerydata";
		}
		
		if ($business_logic_brokers_obj) 
			$allowed_tasks_tag[] = "callbusinesslogic";
		
		$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
		$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
		$WorkFlowTaskHandler->setAllowedTaskTags($allowed_tasks_tag);
		
		if ($extra_tasks_folder_path)
			$WorkFlowTaskHandler->addTasksFolderPath($extra_tasks_folder_path);
		//echo "extra_tasks_folder_path:$extra_tasks_folder_path";die();
		
		//preparing task settings
		$default_tasks_groups_by_tag = array(
			"disabled" => array("createform", "callfunction", "callobjectmethod", "restconnector", "soapconnector", "query", "getquerydata", "setquerydata", "callibatisquery", "callhibernatemethod", "callbusinesslogic"),
		);
		$tasks_groups_by_tag = is_array($tasks_groups_by_tag) ? $tasks_groups_by_tag : ($tasks_groups_by_tag ? array($tasks_groups_by_tag) : array());
		$tasks_groups_by_tag = $tasks_groups_by_tag ? array_merge($default_tasks_groups_by_tag, $tasks_groups_by_tag) : $default_tasks_groups_by_tag;
		//echo "<pre>";print_r($tasks_groups_by_tag);die();
		
		$WorkFlowUIHandler = new WorkFlowUIHandler($WorkFlowTaskHandler, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url);
		$WorkFlowUIHandler->setTasksGroupsByTag($tasks_groups_by_tag);
		$tasks_settings = $WorkFlowTaskHandler->getLoadedTasksSettings();
		
		$tasks_contents = array();
		$js_load_functions = array();
		
		foreach ($tasks_settings as $group_id => $group_tasks) {
			foreach ($group_tasks as $task_type => $task_settings) {
				if (is_array($task_settings)) {
					$tag = isset($task_settings["tag"]) ? $task_settings["tag"] : null;
					$tasks_contents[$tag] = isset($task_settings["task_properties_html"]) ? $task_settings["task_properties_html"] : null;
					$js_load_functions[$tag] = isset($task_settings["settings"]["callback"]["on_load_task_properties"]) ? $task_settings["settings"]["callback"]["on_load_task_properties"] : null;
				}
			}
		}
		
		//prepare selected_db_vars
		$selected_db_vars = WorkFlowBrokersSelectedDBVarsHandler::getBrokersSelectedDBVars($brokers);
		$db_drivers = isset($selected_db_vars["db_brokers_drivers"]) ? $selected_db_vars["db_brokers_drivers"] : null;
		$selected_dal_broker = isset($selected_db_vars["dal_broker"]) ? $selected_db_vars["dal_broker"] : null;
		$selected_db_driver = isset($selected_db_vars["db_driver"]) ? $selected_db_vars["db_driver"] : null;
		$selected_type = isset($selected_db_vars["type"]) ? $selected_db_vars["type"] : null;
		//echo "<pre>";print_r($selected_db_vars);die();
		
		//filter db drivers
		if ($db_drivers) //not sure if we should be doing this filter... Note that the $db_drivers will appear in the "db driver combox/select field" in the widgets properties of the LayoutUIEditor.
			foreach ($db_drivers as $layer_name => &$layer_db_drivers)
				$LayoutTypeProjectHandler->filterLayerBrokersDBDriversNamesFromLayoutName($P, $layer_db_drivers, $filter_by_layout); //filter db_drivers by $filter_by_layout
		//echo "<pre>";print_r($db_drivers);die();
		
		//prepare generic urls
		$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($filter_by_layout);
		
		$get_query_properties_url = $project_url_prefix . "phpframework/dataaccess/get_query_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&db_driver=#db_driver#&db_type=#db_type#&path=#path#&query_type=#query_type#&query=#query#&obj=#obj#&relationship_type=#relationship_type#";
		$get_query_result_properties_url = $project_url_prefix . "phpframework/dataaccess/get_query_result_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&db_driver=" . $selected_db_driver . "&module_id=#module_id#&query_type=#query_type#&query=#query#&rel_name=#rel_name#&obj=#obj#&relationship_type=#relationship_type#";
		$get_business_logic_properties_url = $project_url_prefix . "phpframework/businesslogic/get_business_logic_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#&service=#service#";
		$get_business_logic_result_properties_url = $project_url_prefix . "phpframework/businesslogic/get_business_logic_result_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&module_id=#module_id#&service=#service#&db_driver=" . $selected_db_driver;
		$get_util_result_properties_url = $project_url_prefix . "phpframework/presentation/get_util_result_properties?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path&class_path=#class_path#&class_name=#class_name#&method=#method#&db_driver=" . $selected_db_driver;
		$get_broker_db_drivers_url = $project_url_prefix . "phpframework/db/get_broker_db_drivers?bean_name=$bean_name&bean_file_name=$bean_file_name&broker=#broker#&item_type=presentation";
		$edit_task_source_url = $project_url_prefix . "phpframework/admin/edit_task_source?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path";
		
		//preparing sla urls
		$get_input_data_method_settings_url = $project_url_prefix . 'phpframework/sequentiallogicalactivity/get_input_data_method_settings';
		$get_sla_action_result_properties_url = $project_url_prefix . "phpframework/sequentiallogicalactivity/get_sla_action_result_properties";
		$convert_sla_settings_to_php_code_url = $project_url_prefix . "phpframework/sequentiallogicalactivity/convert_sla_settings_to_php_code?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path";
		$create_sla_settings_code_url = $project_url_prefix . "phpframework/sequentiallogicalactivity/create_sla_settings_code";
		$create_sla_resource_url = $project_url_prefix . "phpframework/sequentiallogicalactivity/create_sla_resource?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path";
		
		//prepare head and js_head
		$head .= WorkFlowPresentationHandler::getFileManagerTreePopupHeader($project_url_prefix);
		
		$js_head = '
		var default_extension = "' . $P->getPresentationFileExtension() . '";
		
		//prepare generic urls
		var get_query_properties_url = \'' . $get_query_properties_url . '\';
		var get_query_result_properties_url = \'' . $get_query_result_properties_url . '\';
		var get_business_logic_properties_url = \'' . $get_business_logic_properties_url . '\';
		var get_business_logic_result_properties_url = \'' . $get_business_logic_result_properties_url . '\';
		var get_util_result_properties_url = \'' . $get_util_result_properties_url . '\';
		var get_broker_db_drivers_url = \'' . $get_broker_db_drivers_url . '\';
		var edit_task_source_url = \'' . $edit_task_source_url . '\';
		
		//prepare sla urls
		var get_input_data_method_settings_url = \'' . $get_input_data_method_settings_url . '\';
		var get_sla_action_result_properties_url = \'' . $get_sla_action_result_properties_url . '\';
		var convert_sla_settings_to_php_code_url = \'' . $convert_sla_settings_to_php_code_url . '\';
		var create_sla_settings_code_url = \'' . $create_sla_settings_code_url . '\';
		var create_sla_resource_url = \'' . $create_sla_resource_url . '\';
		
		//prepare workflow tasks
		var js_load_functions = ' . json_encode($js_load_functions) . ';
		
		ProgrammingTaskUtil.on_programming_task_edit_source_callback = onProgrammingTaskEditSource;
		ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback = onProgrammingTaskChooseCreatedVariable;
		ProgrammingTaskUtil.on_programming_task_choose_object_method_callback = onProgrammingTaskChooseObjectMethod;
		ProgrammingTaskUtil.on_programming_task_choose_function_callback = onProgrammingTaskChooseFunction;
		ProgrammingTaskUtil.on_programming_task_choose_file_path_callback = onIncludeFileTaskChooseFile;
		ProgrammingTaskUtil.on_programming_task_choose_folder_path_callback = onIncludeFolderTaskChooseFile;
		ProgrammingTaskUtil.on_programming_task_choose_page_url_callback = onIncludePageUrlTaskChooseFile;
		ProgrammingTaskUtil.on_programming_task_choose_image_url_callback = onIncludeImageUrlTaskChooseFile;
		ProgrammingTaskUtil.on_programming_task_choose_webroot_file_url_callback = onIncludeWebrootFileUrlTaskChooseFile;
		ProgrammingTaskUtil.on_programming_task_properties_new_html_callback = typeof addProgrammingTaskUtilInputsContextMenu == "function" ? addProgrammingTaskUtilInputsContextMenu : null;
		
		if (typeof CreateFormTaskPropertyObj != "undefined" && CreateFormTaskPropertyObj) {
			CreateFormTaskPropertyObj.editor_ready_func = initLayoutUIEditorWidgetResourceOptions;
			CreateFormTaskPropertyObj.layout_ui_editor_menu_widgets_elm_selector = \'' . $ui_menu_widgets_selector . '\';
		}
		
		if (typeof LayerOptionsUtilObj != "undefined" && LayerOptionsUtilObj)
			LayerOptionsUtilObj.on_choose_db_driver_callback = onChooseDBDriver;
		
		if (typeof CallBusinessLogicTaskPropertyObj != "undefined" && CallBusinessLogicTaskPropertyObj) {
			CallBusinessLogicTaskPropertyObj.on_choose_business_logic_callback = onBusinessLogicTaskChooseBusinessLogic;
			CallBusinessLogicTaskPropertyObj.brokers_options = ' . json_encode($business_logic_brokers_obj) . ';
		}

		if (typeof CallIbatisQueryTaskPropertyObj != "undefined" && CallIbatisQueryTaskPropertyObj) {
			CallIbatisQueryTaskPropertyObj.on_choose_query_callback = onChooseIbatisQuery;
			CallIbatisQueryTaskPropertyObj.brokers_options = ' . json_encode($ibatis_brokers_obj) . ';
		}

		if (typeof CallHibernateMethodTaskPropertyObj != "undefined" && CallHibernateMethodTaskPropertyObj) {
			CallHibernateMethodTaskPropertyObj.on_choose_hibernate_object_method_callback = onChooseHibernateObjectMethod;
			CallHibernateMethodTaskPropertyObj.brokers_options = ' . json_encode($hibernate_brokers_obj) . ';
		}

		if (typeof GetQueryDataTaskPropertyObj != "undefined" && GetQueryDataTaskPropertyObj) {
			GetQueryDataTaskPropertyObj.brokers_options = ' . json_encode(array_merge($db_brokers_obj, $data_access_brokers_obj)) . ';
		}

		if (typeof SetQueryDataTaskPropertyObj != "undefined" && SetQueryDataTaskPropertyObj) {
			SetQueryDataTaskPropertyObj.brokers_options = ' . json_encode(array_merge($db_brokers_obj, $data_access_brokers_obj)) . ';
		}
		
		if (typeof DBQueryTaskPropertyObj != "undefined" && DBQueryTaskPropertyObj) {
			DBQueryTaskPropertyObj.show_properties_on_connection_drop = true;
		}
		
		//prepare brokers
		var brokers_settings = ' . json_encode($brokers_settings) . ';
		var brokers_name_by_obj_code = ' . json_encode($brokers_name_by_obj_code) . ';
		';
		
		//prepare query task settings
		$edit_query_added = false;
		
		if ($db_drivers) {
			$edit_query_added = true;
			
			$QueryWorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
			$QueryWorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
			$QueryWorkFlowTaskHandler->setAllowedTaskTags(array("query"));
			
			$QueryWorkFlowUIHandler = new WorkFlowUIHandler($QueryWorkFlowTaskHandler, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url);
			
			$WorkFlowQueryHandler = new WorkFlowQueryHandler($QueryWorkFlowUIHandler, $project_url_prefix, $project_common_url_prefix, $db_drivers, $selected_dal_broker, $selected_db_driver, $selected_type, "", array(), array(), array(), array());
			
			//prepare query task settings - Add Javascript
			$query_js_head = $WorkFlowQueryHandler->getDataAccessJavascript($bean_name, $bean_file_name, $path, "presentation", null, null);
			$js_head .= str_replace('<script>', '', str_replace('</script>', '', $query_js_head));
			$js_head .= 'get_broker_db_data_url += "&global_default_db_driver_broker=' . (isset($GLOBALS["default_db_broker"]) ? $GLOBALS["default_db_broker"] : "") . '";'; //$GLOBALS["default_db_broker"] corresponds to the default broker name of the DBLayer inside of the DataAccessLayer brokers.
			
			//prepare query task settings - Add taskworkflow html
			$html = $WorkFlowQueryHandler->getGlobalTaskFlowChar();
			$html .= $WorkFlowQueryHandler->getQueryBlockHtml();
			$js_head .= 'var query_task_html = \'' . addcslashes(str_replace("\n", "", $html), "\\'") . '\';';
			
			//prepare query task settings - Add choose_db_table_or_attribute html
			if ($main_div_selector) {
				$html = $WorkFlowQueryHandler->getChooseQueryTableOrAttributeHtml("choose_db_table_or_attribute");
				$js_head .= '
				var choose_db_table_or_attribute_elm = $( \'' . addcslashes(str_replace("\n", "", $html), "\\'") . '\' );
				
				$(function() {
					if ($("#choose_db_table_or_attribute").length == 0)
						$("' . $main_div_selector . '").append(choose_db_table_or_attribute_elm);
				});
				';
			}
			
			$js_head .= WorkFlowBrokersSelectedDBVarsHandler::printSelectedDBVarsJavascriptCode($project_url_prefix, $bean_name, $bean_file_name, null);
		}
		else if ($main_div_selector) { //add choose_db_table_or_attribute_elm html
			$brokers_bean_layers = WorkFlowBeansFileHandler::getLocalBeanLayersFromBrokers($user_global_variables_file_path, $user_beans_folder_path, $brokers, true, true, $brokers_beans_files_path, $brokers_beans_brokers_name);
			//echo "<pre>";print_r($brokers_bean_layers);die();
			//echo "<pre>";print_r(array_keys($brokers_bean_layers));die();
			//echo "<pre>";print_r($brokers_beans_brokers_name);die();
			
			$selected_dal_broker = "";
			$selected_db_driver = "";
			$selected_type = "db";
			$db_brokers_drivers = array();
			
			foreach ($brokers_bean_layers as $layer_bean_name => $layer_obj) {
				if (is_a($layer_obj, "DataAccessLayer") || is_a($layer_obj, "DBLayer")) {
					$layer_brokers_name = isset($brokers_beans_brokers_name[$layer_bean_name]) ? $brokers_beans_brokers_name[$layer_bean_name] : null;
					$layer_db_drivers = is_a($layer_obj, "DBLayer") ? $layer_obj->getDBDriversName() : $layer_obj->getBrokersDBDriversName();
					
					$LayoutTypeProjectHandler->filterLayerBrokersDBDriversNamesFromLayoutName($P, $layer_db_drivers, $filter_by_layout); //filter db_drivers by $filter_by_layout
					
					if ($layer_db_drivers) {
						$selected_dal_broker = isset($layer_brokers_name[0]) ? $layer_brokers_name[0] : null;
						$selected_db_driver = isset($layer_db_drivers[0]) ? $layer_db_drivers[0] : null;
						
						foreach ($layer_brokers_name as $layer_broker_name)
							$db_brokers_drivers[$layer_broker_name] = $layer_db_drivers;
					}
				}
			}
			//echo "<pre>";print_r($db_brokers_drivers);die();
			
			if ($db_brokers_drivers) {
				$edit_query_added = true;
				$selected_db_vars = array(
					"dal_broker" => $selected_dal_broker,
					"db_driver" => $selected_db_driver,
					"type" => $selected_type,
					"db_table" => "", //needs to set this with empty string so it can create the default_db_table js var
					"db_brokers_drivers" => $db_brokers_drivers
				);
				
				//create choose_db_table_or_attribute_elm popup
				$WorkFlowQueryHandler = new WorkFlowQueryHandler(null, $project_url_prefix, $project_common_url_prefix, isset($selected_db_vars["db_brokers_drivers"]) ? $selected_db_vars["db_brokers_drivers"] : null, $selected_dal_broker, $selected_db_driver, $selected_type, "", array(), array(), array(), array());
				$html = $WorkFlowQueryHandler->getChooseQueryTableOrAttributeHtml("choose_db_table_or_attribute");
				
				$js_head .= '
				var choose_db_table_or_attribute_elm = $( \'' . addcslashes(str_replace("\n", "", $html), "\\'") . '\' );
				
				$(function() {
					if ($("#choose_db_table_or_attribute").length == 0)
						$("' . $main_div_selector . '").append(choose_db_table_or_attribute_elm);
				});
				';
				
				//prepare db_brokers_drivers_tables_attributes var
				$js_head .= WorkFlowBrokersSelectedDBVarsHandler::printSelectedDBVarsJavascriptCode($project_url_prefix, $bean_name, $bean_file_name, $selected_db_vars);
			}
		}
		
		if ($edit_query_added) {
			$js_head .= '
				if (typeof choose_db_table_or_attribute_elm != "undefined") {
					getDBTables("' . $selected_dal_broker . '", "' . $selected_db_driver . '", "' . $selected_type . '");
				
					var db_tables = db_brokers_drivers_tables_attributes["' . $selected_dal_broker . '"] && db_brokers_drivers_tables_attributes["' . $selected_dal_broker . '"]["' . $selected_db_driver . '"] ? db_brokers_drivers_tables_attributes["' . $selected_dal_broker . '"]["' . $selected_db_driver . '"]["' . $selected_type . '"] : null;
					
					if (db_tables) {
						var html = "<option></option>";
						for (var db_table in db_tables) {
							html += "<option>" + db_table + "</option>";
						}
						choose_db_table_or_attribute_elm.find(".db_table select").html(html);
					}
					
					choose_db_table_or_attribute_elm.find(".db_broker > select").change(function() {
						onChangePopupDBBrokers(this);
					});
					
					choose_db_table_or_attribute_elm.find(".db_driver > select").change(function() {
						onChangePopupDBDrivers(this);
					});
					
					choose_db_table_or_attribute_elm.find(".type > select").change(function() {
						onChangePopupDBTypes(this);
					});
				}
				
				on_new_html_callback = typeof addProgrammingTaskUtilInputsContextMenu == "function" ? addProgrammingTaskUtilInputsContextMenu : null;
			';
			
			//add edit_query css and js bc of the choose_db_table_or_attribute_elm and others
			$head .= '
			<!-- DBQUERY TASK - Add Edit-Query JS and CSS files -->
			<link rel="stylesheet" href="' . $project_url_prefix . 'css/dataaccess/edit_query.css" type="text/css" charset="utf-8" />
			<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/dataaccess/edit_query.js"></script>';
		}
		
		//prepare available activities and user types
		$module_user_util_path = $PEVC->getModulePath("user/UserUtil", $PEVC->getCommonProjectName());
		$is_module_user_installed = file_exists($module_user_util_path); //Do not use CMSPresentationUIAutomaticFilesHandler::isUserModuleInstalled bc we only want to know if the module is installed or not. It doesn't matter if is enable. Bc if later the user disable this module, we can still see the user resources in the html.
		
		$user_module_installed_and_enabled = CMSPresentationUIAutomaticFilesHandler::isUserModuleInstalled($PEVC);
		$available_user_types = $available_activities = array();
		
		if ($user_module_installed_and_enabled) {
			$available_user_types = CMSPresentationUIAutomaticFilesHandler::getAvailableUserTypes($PEVC);
			$available_activities = CMSPresentationUIAutomaticFilesHandler::getAvailableActivities($PEVC);
		}
		
		$js_head .= '
		var user_module_installed = ' . ($is_module_user_installed ? "true" : "false") . ';
		var available_user_types = ' . json_encode($available_user_types) . ';
		var available_activities = ' . json_encode($available_activities) . ';';
		
		//prepare workflow
		$ret = self::getWorkflowHeader($PEVC, $UserAuthenticationHandler, $bean_name, $bean_file_name, $path, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $user_beans_folder_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $filter_by_layout, $WorkFlowTaskHandler, $opts);
		
		if ($ret) {
			$workflow_js_head = isset($ret["js_head"]) ? $ret["js_head"] : null;
			$set_workflow_file_url = isset($ret["set_workflow_file_url"]) ? $ret["set_workflow_file_url"] : null;
			$get_workflow_file_url = isset($ret["get_workflow_file_url"]) ? $ret["get_workflow_file_url"] : null;
			
			$js_head .= $workflow_js_head;
		}
		
		//prepare numeric types
		$js_head .= '
		var php_numeric_types = ' . json_encode(ObjTypeHandler::getPHPNumericTypes()) . ';
		var db_numeric_types = ' . json_encode(ObjTypeHandler::getDBNumericTypes()) . ';
		var db_blob_types = ' . json_encode(ObjTypeHandler::getDBBlobTypes()) . ';';
		
		//prepare internal attributes
		$internal_attribute_names = array_values( array_map('strtolower', array_unique( array_merge( ObjTypeHandler::getDBAttributeNameCreatedDateAvailableValues(), ObjTypeHandler::getDBAttributeNameModifiedDateAvailableValues(), ObjTypeHandler::getDBAttributeNameCreatedUserIdAvailableValues(), ObjTypeHandler::getDBAttributeNameModifiedUserIdAvailableValues() ) ) ) );
		
		$js_head .= '
		var internal_attribute_names = ' . json_encode($internal_attribute_names) . ';';
		
		//echo "head:<textarea>";print_r($head);echo "</textarea>";die();
		//echo "js_head:";print_r($js_head);die();
		//echo "tasks_contents:";print_r($tasks_contents);die();
		//echo "layer_brokers_settings:";print_r($layer_brokers_settings);die();
		//echo "presentation_projects:";print_r($presentation_projects);die();
		//echo "db_drivers:";print_r($db_drivers);die();
		//echo "WorkFlowTaskHandler:".get_class($WorkFlowTaskHandler);die();
		//echo "WorkFlowUIHandler:".get_class($WorkFlowUIHandler);die();
		
		return array(
			"head" => $head,
			"js_head" => $js_head,
			"tasks_contents" => $tasks_contents,
			"layer_brokers_settings" => $layer_brokers_settings,
			"presentation_projects" => $presentation_projects,
			"db_drivers" => $db_drivers,
			"WorkFlowTaskHandler" => $WorkFlowTaskHandler,
			"WorkFlowUIHandler" => $WorkFlowUIHandler,
			
			"set_workflow_file_url" => isset($set_workflow_file_url) ? $set_workflow_file_url : null,
			"get_workflow_file_url" => isset($get_workflow_file_url) ? $get_workflow_file_url : null,
		);
	}
	
	public static function getWorkflowHeader($PEVC, $UserAuthenticationHandler, $bean_name, $bean_file_name, $path, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $user_beans_folder_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $filter_by_layout, $WorkFlowTaskHandler, $opts = false) {
		$workflow_tasks_id = isset($opts["workflow_tasks_id"]) ? $opts["workflow_tasks_id"] : null;
		$path_extra = isset($opts["path_extra"]) ? $opts["path_extra"] : null;
		
		//preparing workflow urls
		if ($workflow_tasks_id) {
			$get_workflow_tasks_id = $workflow_tasks_id . "&path_extra=_$path_extra";
			$get_tmp_workflow_tasks_id = $workflow_tasks_id . "_tmp&path_extra=_{$path_extra}_" . rand(0, 1000);
			
			$set_workflow_file_url = $project_url_prefix . "workflow/set_workflow_file?path={$get_workflow_tasks_id}";
			$get_workflow_file_url = $project_url_prefix . "workflow/get_workflow_file?path={$get_workflow_tasks_id}";
			$get_tmp_workflow_file_url = $project_url_prefix . "workflow/get_workflow_file?path={$get_tmp_workflow_tasks_id}";
			$set_tmp_workflow_file_url = $project_url_prefix . "workflow/set_workflow_file?path={$get_tmp_workflow_tasks_id}";
			$create_sla_workflow_file_from_settings_url = $project_url_prefix . "phpframework/sequentiallogicalactivity/create_sla_workflow_file_from_settings?path={$get_tmp_workflow_tasks_id}&loaded_tasks_settings_cache_id=" . $WorkFlowTaskHandler->getLoadedTasksSettingsCacheId();
			$create_sla_settings_from_workflow_file_url = $project_url_prefix . "phpframework/sequentiallogicalactivity/create_sla_settings_from_workflow_file?path={$get_tmp_workflow_tasks_id}";
			
			$js_head = '
			//prepare workflow urls
			var get_workflow_file_url = \'' . $get_workflow_file_url . '\';
			var get_tmp_workflow_file_url = \'' . $get_tmp_workflow_file_url . '\';
			var set_tmp_workflow_file_url = \'' . $set_tmp_workflow_file_url . '\';
			var create_sla_workflow_file_from_settings_url = \'' . $create_sla_workflow_file_from_settings_url . '\';
			var create_sla_settings_from_workflow_file_url = \'' . $create_sla_settings_from_workflow_file_url . '\';
			';
			
			return array(
				"js_head" => $js_head,
				"set_workflow_file_url" => $set_workflow_file_url,
				"get_workflow_file_url" => $get_workflow_file_url,
			);
		}
		
		return null;
	}
	
	public static function getUIMenuWidgetsHTML($EVC, $project_url_prefix, $layout_ui_editor_user_widget_folders_path, $webroot_cache_folder_path, $webroot_cache_folder_url) {
		$webroot_path = $EVC->getWebrootPath();
		$ui_menu_widgets_html = WorkFlowPresentationHandler::getUIEditorWidgetsHtml($webroot_path, $project_url_prefix, $webroot_cache_folder_path, $webroot_cache_folder_url, array("avoided_widgets" => array("php")));
		$ui_menu_widgets_html .= WorkFlowPresentationHandler::getExtraUIEditorWidgetsHtml($webroot_path, $EVC->getViewsPath() . "presentation/common_editor_widget/", $webroot_cache_folder_path, $webroot_cache_folder_url);
		$ui_menu_widgets_html .= WorkFlowPresentationHandler::getUserUIEditorWidgetsHtml($webroot_path, $layout_ui_editor_user_widget_folders_path, $webroot_cache_folder_path, $webroot_cache_folder_url);
		
		return $ui_menu_widgets_html;
	}
	
	public static function getSLAHtml($EVC, $PEVC, $project_url_prefix, $project_common_url_prefix, $layout_ui_editor_user_widget_folders_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $contents, $db_drivers, $presentation_projects, $WorkFlowUIHandler, $opts = null) {
		$groups_flow_html = self::getGroupsFlowHtml($EVC, $PEVC, $project_url_prefix, $project_common_url_prefix, $layout_ui_editor_user_widget_folders_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $contents, $db_drivers, $presentation_projects, $opts);
		
		$tasks_flow_html = self::getTasksFlowHtml($EVC, $project_url_prefix, $project_common_url_prefix, $layout_ui_editor_user_widget_folders_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $contents, $db_drivers, $presentation_projects, $WorkFlowUIHandler, $opts);
		
		$html = '
			<div class="sla">
				<ul class="tabs tabs_transparent tabs_right tabs_icons">
					<li id="sla_groups_flow_tab"><a href="#sla_groups_flow" onClick="onClickSLAGroupsFlowTab(this);return false;"><i class="icon sla_tab"></i> By Sequential Actions</a></li>
					<li id="tasks_flow_tab"><a href="#ui" onClick="onClickSLATaskWorkflowTab(this);return false;"><i class="icon tasks_flow_tab"></i> By Diagram</a></li>
				</ul>
				
				' . $groups_flow_html . '
				' . $tasks_flow_html . '
			</div>';
		
		return $html;
	}
	
	public static function getTasksFlowHtml($EVC, $project_url_prefix, $project_common_url_prefix, $layout_ui_editor_user_widget_folders_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $contents, $db_drivers, $presentation_projects, $WorkFlowUIHandler, $opts = null) {
		$save_func = $opts && isset($opts["save_func"]) ? $opts["save_func"]: null;
		
		$html = '
			<div id="ui">
				' . WorkFlowPresentationHandler::getTaskFlowContentHtml($WorkFlowUIHandler, array(
					"save_func" => $save_func, 
					"generate_code_from_tasks_flow_label" => "Generate Groups from Diagram", 
					"generate_code_from_tasks_flow_func" => "generateSLAGroupsFromTasksFlow", 
					"generate_tasks_flow_from_code_label" => "Generate Diagram from Groups/Actions",
					"generate_tasks_flow_from_code_func" => "generateSLATasksFlowFromGroups", 
				)) . '
				<div class="sla_tasks_flow_ui_menu_widgets_backup hidden">
					' . self::getUIMenuWidgetsHTML($EVC, $project_url_prefix, $layout_ui_editor_user_widget_folders_path, $webroot_cache_folder_path, $webroot_cache_folder_url) . '
				</div>
				
				<script>
					var ui = $(".sla #ui");
					var mwb = ui.find(".sla_tasks_flow_ui_menu_widgets_backup");
					var create_form_task_html = ui.find(".create_form_task_html");
					create_form_task_html.find(".ptl_settings > .layout-ui-editor > .menu-widgets").append( mwb.contents().clone() );
					ui.find(".inlinehtml_task_html > .layout-ui-editor > .menu-widgets").append( mwb.contents() );
					mwb.remove();
					
						create_form_task_html.children(".separate_settings_from_input, .form_input, .form_input_data, .separate_input_from_result, .result, .task_property_exit").remove();
				</script>
			</div>';
		
		return $html;
	}
	
	public static function getGroupsFlowHtml($EVC, $PEVC, $project_url_prefix, $project_common_url_prefix, $layout_ui_editor_user_widget_folders_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $contents, $db_drivers, $presentation_projects, $opts = null) {
		$extra_short_actions_html = $opts && isset($opts["extra_short_actions_html"]) ? $opts["extra_short_actions_html"] : "";
		
		$html = '
			<div id="sla_groups_flow" class="sla_groups_flow">
				<nav>
					<a class="add_sla_group" onClick="addAndInitNewSLAGroup(this)">Add Action <i class="icon add"></i></a>
					<a class="collapse_sla_groups" onClick="collapseSLAGroups(this)">Collapse Actions <i class="icon collapse_content"></i></a>
					<a class="expand_sla_groups" onClick="expandSLAGroups(this)">Expand Actions <i class="icon expand_content"></i></a>
					
					' . $extra_short_actions_html . '
				</nav>
				
				<ul class="sla_groups sla_main_groups">
					<li class="sla_group_item sla_group_default">
						' . self::getGroupItemHtml($EVC, $PEVC, $project_url_prefix, $project_common_url_prefix, $layout_ui_editor_user_widget_folders_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $contents, $db_drivers, $presentation_projects) . '
					</li>
					<li class="sla_group_empty_items">There are no groups available...</li>
				</ul>
			</div>';
		
		return $html;
	}
	
	public static function getGroupItemHtml($EVC, $PEVC, $project_url_prefix, $project_common_url_prefix, $layout_ui_editor_user_widget_folders_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $contents, $db_drivers, $presentation_projects) {
		$ui_menu_widgets_html = self::getUIMenuWidgetsHTML($EVC, $project_url_prefix, $layout_ui_editor_user_widget_folders_path, $webroot_cache_folder_path, $webroot_cache_folder_url);
		
		$module_common_util_path = $PEVC->getModulePath("common/CommonModuleUI", $EVC->getCommonProjectName());
		$is_module_common_installed = file_exists($module_common_util_path);
		
		$module_user_util_path = $PEVC->getModulePath("user/UserUtil", $EVC->getCommonProjectName());
		$is_module_user_installed = file_exists($module_user_util_path); //Do not use CMSPresentationUIAutomaticFilesHandler::isUserModuleInstalled bc we only want to know if the module is installed or not. It doesn't matter if is enable. Bc if later the user disable this module, we can still see the user resources in the html.
		
		$html = '
		<header class="sla_group_header">
			<i class="icon expand_content toggle" onClick="toggleGroupBody(this)"></i>
			<input class="result_var_name result_var_name_output" type="text" placeHolder="Result Variable Name or leave it empty for direct output" title="This action will only appear in the output if this field is empty. If this \'Result Variable Name\' cotains a value, the output will be putted to this correspondent variable." />
			
			<i class="icon remove" onClick="removeGroupItem(this)"></i>
			<i class="icon move_down" onClick="moveDownGroupItem(this)"></i>
			<i class="icon move_up" onClick="moveUpGroupItem(this)"></i>
			
			<select class="action_type" onChange="onChangeSLAInputType(this)">
				' . ($db_drivers ? '<optgroup label="DataBase Actions">
					<option value="insert">Insert object into data-base</option>
					<option value="update">Update object into data-base</option>
					<option value="delete">Delete object into data-base</option>
					<option value="select">Get object(s) from data-base</option>
					<option value="count">Count objects from data-base</option>
					<option value="procedure">Call Procedure from data-base</option>
					<option value="getinsertedid">Get inserted object id</option>
				<optgroup>' : '') . '
				
				<optgroup label="Broker Actions">
					<option value="callbusinesslogic">Call business logic service</option>
					' . ($db_drivers ? '
					<option value="callibatisquery">Call ibatis rule</option>
					<option value="callhibernatemethod">Call hibernate rule</option>
					<option value="getquerydata">Get sql query results</option>
					<option value="setquerydata">Set sql query</option>' : '') . '
					<option value="callfunction">Call function</option>
					<option value="callobjectmethod">Call object method</option>
					<option value="restconnector">Call rest connector</option>
					<option value="soapconnector">Call soap connector</option>
				<optgroup>
				
				<optgroup label="Message Actions">
					' . ($is_module_common_installed ? '
						<option value="show_ok_msg">Show OK message</option>
						<option value="show_ok_msg_and_stop">Show OK message and stop</option>
						<option value="show_ok_msg_and_die">Show OK message and die</option>
						<option value="show_ok_msg_and_redirect">Show OK message and redirect</option>
						<option value="show_error_msg">Show error message</option>
						<option value="show_error_msg_and_stop">Show error message and stop</option>
						<option value="show_error_msg_and_die">Show error message and die</option>
						<option value="show_error_msg_and_redirect">Show error message and redirect</option>
					' : '') . '
					<option value="alert_msg">Alert message</option>
					<option value="alert_msg_and_stop">Alert message and stop</option>
					<option value="alert_msg_and_redirect">Alert message and redirect</option>
				<optgroup>
				
				<optgroup label="Page Actions">
					<option value="refresh">Refresh page</option>
					<option value="redirect">Redirect to page</option>
					<option value="return_previous_record" title="Filter a records list and return previous record">Return previous record</option>
					<option value="return_next_record" title="Filter a records list and return next record">Return next record</option>
					<option value="return_specific_record" title="Filter a records list and return specific record">Return a specific record</option>
				<optgroup>
				
				<optgroup label="Other Actions">
					<option value="include_file">Include File</option>
					<option value="html">Design HTML Form</option>
					
					<option disabled></option>
					<option value="code">Execute code</option>
					<option value="array">Result from array</option>
					<option value="string">Result from string/value</option>
					<option value="variable">Result from variable</option>
					<option value="sanitize_variable">Sanitize variable</option>
					<option value="validate_variable">Validate variable</option>
					
					<option disabled></option>
					' . ($is_module_user_installed ? '<option value="check_logged_user_permissions">Check Logged User Permissions</option>' : '') . '
					<option value="list_report">List Report</option>
					<option value="call_block">Call Block</option>
					<option value="call_view">Call View</option>
					<option value="draw_graph">Draw Graph</option>
					
					<option disabled></option>
					<option value="loop">Loop</option>
					<option value="group">Group</option>
				<optgroup>
			</select>
			
			<div class="clear"></div>
			
			<div class="sla_group_sub_header">
				<select class="condition_type" onChange="onGroupConditionTypeChange(this)">
					<option value="execute_always">Always execute</option>
					<option value="execute_if_var">Only execute if variable exists:</option>
					<option value="execute_if_not_var">Only execute if variable doesn\'t exists:</option>
					<option value="execute_if_post_button">Only execute if submit button was clicked via POST:</option>
					<option value="execute_if_not_post_button">Only execute if submit button was not clicked via POST:</option>
					<option value="execute_if_get_button">Only execute if submit button was clicked via GET:</option>
					<option value="execute_if_not_get_button">Only execute if submit button was not clicked via GET:</option>
					<!--option value="execute_if_post_resource">Only execute if resource is via POST:</option>
					<option value="execute_if_not_post_resource">Only execute if resource is not via POST:</option-->
					<option value="execute_if_get_resource">Only execute if resource is:</option>
					<option value="execute_if_not_get_resource">Only execute if resource is not:</option>
					<option value="execute_if_previous_action">Only execute if previous action executed correctly</option>
					<option value="execute_if_not_previous_action">Only execute if previous action was not executed correctly</option>
					<option value="execute_if_condition" title="This is relative php code that will execute when the module runs. Aditionally this code will be parsed as a string! This is, quotes will be added to this code! MUST 1 LINE CODE!">Only execute if condition is valid:</option>
					<option value="execute_if_not_condition" title="This is relative php code that will execute when the module runs. Aditionally this code will be parsed as a string! This is, quotes will be added to this code! MUST 1 LINE CODE!">Only execute if condition is invalid:</option>
					<option value="execute_if_code" title="This is absolute php code that will execute directly, before this module runs. Which means when this module runs, this condition can only be true or false. Note that this code will not be parsed as a string! This is, no quotes will be added to this code! MUST 1 LINE CODE!">Only execute if code is valid:</option>
					<option value="execute_if_not_code" title="This is absolute php code that will execute directly, before this module runs. Which means when this module runs, this condition can only be true or false Note that this code will not be parsed as a string! This is, no quotes will be added to this code! MUST 1 LINE CODE!">Only execute if code is invalid:</option>
				</select>
				
				<input class="condition_value" type="text" placeHolder="Variable/Name/Condition here" />
				
				<div class="clear"></div>
				
				<div class="action_description">
					<label>Description</label>
					<textarea placeHolder="Explain this action here..."></textarea>
				</div>
			</div>
		</header>
		
		<div class="selected_task_properties sla_group_body">
			<textarea class="undefined_action_value hidden"></textarea> <!-- This will be used everytime a broker-action or database-action does not exists -->
			
			<section class="html_action_body">
				<!-- FORM -->
				' . (isset($contents["createform"]) ? $contents["createform"] : "") . '
				
				<div class="sla_ui_menu_widgets_backup hidden">
					' . $ui_menu_widgets_html . '
				</div>
				
				<script>
					var sla_main_groups = $(".sla_groups_flow .sla_main_groups");
					var mwb = sla_main_groups.find(".sla_ui_menu_widgets_backup");
					var create_form_task_html = sla_main_groups.find(".create_form_task_html");
					create_form_task_html.find(".ptl_settings > .layout-ui-editor > .menu-widgets").append( mwb.contents().clone() );
					sla_main_groups.find(".inlinehtml_task_html > .layout-ui-editor > .menu-widgets").append( mwb.contents() );
					mwb.remove();
					
						create_form_task_html.children(".separate_settings_from_input, .form_input, .form_input_data, .separate_input_from_result, .result, .task_property_exit").remove();
				</script>
			</section>
			
			' . ($db_drivers ? '
			<section class="database_action_body">
				<header>
					<div class="dal_broker">
						<label>Broker: </label>
						<select class="task_property_field" onChange="updateDALActionBroker(this);"></select>
					</div>
					<div class="db_driver">
						<label>DB Driver: </label>
						<select class="task_property_field" onChange="updateDBActionDriver(this);"></select>
					</div>
					<div class="db_type">
						<label>DB Type: </label>
						<select class="task_property_field" onChange="updateDBActionType(this);">
							<option value="db">From DB Server</option>
							<option value="diagram">From DB Diagram</option>
						</select>
					</div>
				</header>
				<article></article>
				<footer>
					<div class="opts">
						<label class="main_label">Options:</label>
						<input type="text" class="task_property_field options_code" name="options" />
						<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
						<select class="task_property_field options_type" name="options_type" onChange="LayerOptionsUtilObj.onChangeOptionsType(this)">
							<option value="">code</option>
							<option>string</option>
							<option>variable</option>
							<option>array</option>
						</select>
						<div class="options array_items"></div>
					</div>
				</footer>
			</section>
			' : '') . '
			
			<section class="broker_action_body">
				' . (isset($contents["callbusinesslogic"]) ? $contents["callbusinesslogic"] : "") . '
				' . (isset($contents["callibatisquery"]) ? $contents["callibatisquery"] : "") . '
				' . (isset($contents["callhibernatemethod"]) ? $contents["callhibernatemethod"] : "") . '
				' . (isset($contents["getquerydata"]) ? $contents["getquerydata"] : "") . '
				' . (isset($contents["setquerydata"]) ? $contents["setquerydata"] : "") . '
				' . (isset($contents["callfunction"]) ? $contents["callfunction"] : "") . '
				' . (isset($contents["callobjectmethod"]) ? $contents["callobjectmethod"] : "") . '
				' . (isset($contents["restconnector"]) ? $contents["restconnector"] : "") . '
				' . (isset($contents["soapconnector"]) ? $contents["soapconnector"] : "") . '
			</section>
			
			<section class="message_action_body">
				<div class="message">
					<label>Message: </label>
					<input class="task_property_field" />
					<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
				</div>
				<div class="redirect_url">
					<label>Redirect Url: </label>
					<input class="task_property_field" />
					<span class="icon search search_page_url" onclick="onIncludePageUrlTaskChooseFile(this)" title="Search Page">Search page</span>
				</div>
			</section>
			
			<section class="redirect_action_body" title="Redirect URL must be a string!!!">
				<div class="redirect_type">
					<label>Redirect Type: </label>
					<select class="task_property_field">
						<option value="server" title="Redirect will only be based on http header: Location">On server side</option>
						<option value="client" title="Redirect will only be based on javascript code: document.location" selected>On client side</option>
						<option value="server_client" title="Redirect will be based on http header and javascript code">On server and client side</option>
					</select>
				</div>
				<div class="redirect_url">
					<label>Redirect Url: </label>
					<input class="task_property_field" />
					<span class="icon search search_page_url" onclick="onIncludePageUrlTaskChooseFile(this)" title="Search Page">Search page</span>
				</div>
			</section>
			
			<section class="records_action_body">
				<div class="records_variable_name" title="Name of the variable with the records that you wish to filter. Note that this variable must be an array with multiple items, where each item is a db record! This field can contains directly the array variable too...">
					<label>Records Variable Name: </label>
					<input class="task_property_field" placeHolder="Name of the variable with the records that you wish to filter" />
					<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
				</div>
				<div class="index_variable_name" title="Variable name which contains the index to filter. This variable name corresponds to a _GET variable. Note that this can contains directly the numeric index value too.">
					<label>Index Variable Name: </label>
					<input class="task_property_field" placeHolder="Variable name of index to filter" />
					<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
				</div>
			</section>
			
			' . ($is_module_user_installed ? '<section class="check_logged_user_permissions_action_body">
				<p>Please edit the users and their permissions that the logged user should have.</p>
				<p>Note that the logged user only need to contain one of the added permissions bellow.</p>
				<input class="entity_path_var_name" type="hidden" value="$entity_path" />
				
				<div class="all_permissions_checked">
					<input type="checkbox" value="1" />
					<label>Please select this field, if the logged user should have all the added permissions bellow...</label>
				</div>
				
				<div class="logged_user_id">
					<label>Logged User Id:</label>
					<input type="text" placeHolder="eg: $GLOBALS[\'logged_user_id\']" />
					<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
				</div>
				
				<div class="users_perms">
					<table>
						<thead>
							<tr>
								<th class="user_type_id">User</th>
								<th class="activity_id">Permission</th>
								<th class="actions">
									<i class="icon add" onClick="addUserPermission(this)"></i>
								</th>
							</tr>
						</thead>
						<tbody>
							<tr class="no_users"><td colspan="2">There are no configured users...</td></tr>
						</tbody>
					</table>
				</div>
			</section>' : '') . '
			
			<section class="code_action_body">
				<textarea class="task_property_field">&lt;?

	?&gt;</textarea>
			</section>
			
			<section class="array_action_body array_items"></section>
			
			<section class="string_action_body">
				<div class="string">
					<label>String: </label>
					<input class="task_property_field" />
					<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
				</div>
				<div class="operator">
					<label>Operator: </label>
					<select class="task_property_field">
						<option value="">Assign/Equal</option>
						<option value="+=">Increment</option>
						<option value="-=">Decrement</option>
					</select>
				</div>
			</section>
			
			<section class="variable_action_body" title="Variable input could be a PHP variable name (like $foo[\'bar\']) or something like #foo[bar]#">
				<div class="variable">
					<label>Variable: </label>
					<input class="task_property_field" />
					<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
				</div>
				<div class="operator">
					<label>Operator: </label>
					<select class="task_property_field">
						<option value="">Assign/Equal</option>
						<option value="+=">Increment</option>
						<option value="-=">Decrement</option>
					</select>
				</div>
			</section>
			
			<section class="sanitize_variable_action_body" title="Variable input could be a PHP variable name (like $foo[\'bar\']) or something like #foo[bar]#">
				<label>Variable: </label>
				<input class="task_property_field" />
				<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
			</section>
			
			<section class="validate_variable_action_body" title="Variable input could be a PHP variable name (like $foo[\'bar\']) or something like #foo[bar]#">
				<div class="method">
					<label>Method: </label>
					<select class="task_property_field" onChange="onChangeVariableValidatorMethodName(this)">
						<optgroup label="Value Validations">
							<option value="TextValidator::isEmail">value is an email</option>
							<option value="TextValidator::isDomain">value is a domain</option>
							<option value="TextValidator::isPhone">value is a phone number</option>
							<option value="TextValidator::isNumber">value is a number</option>
							<option value="TextValidator::isDecimal">value is decimal</option>
							<option value="TextValidator::isSmallInt">value is a small int</option>
							<option value="TextValidator::isDate">value is a date</option>
							<option value="TextValidator::isDateTime">value is a date time</option>
							<option value="TextValidator::isTime">value is a time</option>
							<option value="TextValidator::isIPAddress">value is an ip address</option>
							<option value="TextValidator::isFileName">value is a file name</option>
							
							<option value="TextValidator::checkMinLength">value has min length</option>
							<option value="TextValidator::checkMaxLength">value has max length</option>
							<option value="TextValidator::checkMinValue">value has min value</option>
							<option value="TextValidator::checkMaxValue">value has max value</option>
							<option value="TextValidator::checkMinWords">value has min words</option>
							<option value="TextValidator::checkMaxWords">value has max words</option>
							<option value="TextValidator::checkMinDate">value has min date</option>
							<option value="TextValidator::checkMaxDate">value has max date</option>
						</optgroup>
						
						<option value="" disabled></option>
						
						<optgroup label="Other Validations">
							<option value="ObjTypeHandler::isPHPTypeNumeric">type is php numeric</option>
							<option value="ObjTypeHandler::isDBTypeNumeric">type is db numeric</option>
							<option value="ObjTypeHandler::isDBTypeDate">type is db date</option>
							<option value="ObjTypeHandler::isDBTypeText">type is db text</option>
							<option value="ObjTypeHandler::isDBTypeBoolean">type is db boolean</option>
							<option value="ObjTypeHandler::isDBAttributeNameATitle">attribute name is a db title</option>
							<option value="ObjTypeHandler::isDBAttributeNameACreatedDate">attribute name is a db created date</option>
							<option value="ObjTypeHandler::isDBAttributeNameAModifiedDate">attribute name is a db modified date</option>
							<option value="ObjTypeHandler::isDBAttributeValueACurrentTimestamp">attribute value is a db current timestamp</option>
						</optgroup>
					</select>
				</div>
				<div class="variable">
					<label>Variable: </label>
					<input class="task_property_field" />
					<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
				</div>
				<div class="offset">
					<label>Offset: </label>
					<input class="task_property_field" type="number" />
				</div>
			</section>
			
			<section class="list_report_action_body" title="Variable input could be a PHP variable name (like $foo[\'bar\']) or something like #foo[bar]#">
				<div class="type">
					<label>Type: </label>
					<select class="task_property_field">
						<option value="txt">Text - Tab delimiter</option>
						<option value="csv">CSV - Comma Separated Values</option>
						<option value="xls">Excel</option>
					</select>
				</div>
				
				<div class="doc_name">
					<label>Document Name: </label>
					<input class="task_property_field" />
					<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
				</div>
				
				<div class="variable">
					<label>Variable: </label>
					<input class="task_property_field" />
					<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
				</div>
				
				<div class="continue">
					<label>Stop Action: </label>
					<select class="task_property_field">
						<option value="">Continue</option>
						<option value="stop">Stop</option>
						<option value="die">Die</option>
					</select>
				</div>
				
				<div class="info">
					This variable should be an array with other associative sub-arrays. <br>
					Something similar with a result array returned from a query made to a Data-Base...
				</div>
			</section>
			
			<section class="call_block_action_body">
				<div class="block">
					<label>Block to be called: </label>
					<input class="task_property_field" />
					<span class="icon search search_page_url" onclick="onIncludeBlockTaskChooseFile(this)" title="Search Block">Search block</span>
				</div>
				
				<div class="project">
					<label>Block Project: </label>
					<select class="task_property_field project">
						<option value="">-- Current Project --</option>';
		
		if ($presentation_projects)
			foreach ($presentation_projects as $project)
				$html .= '<option>' . $project . '</option>';
		
		$html .= '
					</select>
				</div>
			</section>
			
			<section class="call_view_action_body">
				<div class="view">
					<label>View to be called: </label>
					<input class="task_property_field" />
					<span class="icon search search_page_url" onclick="onIncludeViewTaskChooseFile(this)" title="Search View">Search view</span>
				</div>
				
				<div class="project">
					<label>View Project: </label>
					<select class="task_property_field project">
						<option value="">-- Current Project --</option>';
		
		if ($presentation_projects)
			foreach ($presentation_projects as $project)
				$html .= '<option>' . $project . '</option>';
		
		$html .= '
					</select>
				</div>
			</section>
			
			<section class="include_file_action_body">
				<label>File to include: </label>
				<input class="task_property_field path" />
				<input class="once task_property_field once" type="checkbox" value="1" title="Check here to active the include ONCE feature">
				<span class="icon search search_page_url" onclick="onIncludeFileTaskChooseFile(this)" title="Search File">Search file</span>
			</section>
			
			<section class="draw_graph_action_body">
				<div class="info">For more information or options about "Drawing a Graph" and how it works, please open the "<a href="https://www.chartjs.org/" target="chartjs">https://www.chartjs.org/</a>" web-page.</div>
				
				<ul>
					<li><a href="#draw_graph_settings">Settings</a></li>
					<li><a href="#draw_graph_js_code" onClick="onDrawGraphJSCodeTabClick(this)">JS Code</a></li>
				</ul>
				
				<div class="draw_graph_settings" id="draw_graph_settings">
					<div class="include_graph_library">
						<label>Include Graph Library: </label>
						<select class="task_property_field">
							<option value="">Don\'t load, because was previously loaded</option>
							<option value="cdn_even_if_exists">Always load from CDN</option>
							<option value="cdn_if_not_exists">Only load from CDN if doesn\'t exists yet</option>
						</select>
					</div>
					<div class="graph_width">
						<label>Graph Width: </label>
						<input class="task_property_field" />
						<span class="icon add_variable" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
					</div>
					<div class="graph_height">
						<label>Graph Height: </label>
						<input class="task_property_field" />
						<span class="icon add_variable" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
					</div>
					
					<div class="labels_variable">
						<label>Labels Variable: </label>
						<input class="task_property_field" title="Only fill this field if apply..." />
						<span class="icon add_variable" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
					</div>
					
					<div class="graph_data_sets">
						<label>Data Sets: <span class="icon add" onClick="addDrawGraphSettingsDataSet(this)">Add</span></label>
						<ul>
							<li class="no_data_sets">No data sets defined yet...</li>
						</ul>
					</div>
				</div>
				
				<div class="draw_graph_js_code" id="draw_graph_js_code">
					<textarea class="task_property_field"></textarea>
				</div>
			</section>
			
			<section class="loop_action_body">
				<header>
					<a onclick="addAndInitNewSLASubGroup(this)">Add new sub-action</a>
					
					<div class="records_variable_name" title="Name of the variable with the records that you wish to loop. Note that this variable must be an array with multiple items. This field can contains directly the array variable too...">
						<label>Records Variable Name: </label>
						<input class="task_property_field" placeHolder="Name of the variable with the records that you wish to loop" />
						<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
					</div>
					<div class="records_start_index" title="Numeric value with the start index for the loop. If no value especified, the system will loop from the beginning of the main array. Default: 0">
						<label>Start Index: </label>
						<input class="task_property_field" placeHolder="numeric start index for loop. Default: 0" />
						<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
					</div>
					<div class="records_end_index" title="Numeric value with the end index for the loop. If no value especified, the system will loop until the end of the main array. Default count($array)">
						<label>End Index: </label>
						<input class="task_property_field" placeHolder="numeric end index for loop. Default: count(array)" />
						<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
					</div>
					<div class="array_item_key_variable_name" title="Variable name which contains the current key in the loop. This variable name corresponds to the variable that will be initialize when the loop is running with the correspondent item key/index.">
						<label>Item Key Variable Name: </label>
						<input class="task_property_field" placeHolder="Variable name of array item key" />
						<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
					</div>
					<div class="array_item_value_variable_name" title="Variable name which contains the current item in the loop. This variable name corresponds to the variable that will be initialize when the loop is running with the correspondent item value.">
						<label>Item Value Variable Name: </label>
						<input class="task_property_field" placeHolder="Variable name of array item value" />
						<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
					</div>
				</header>
				
				<article class="sla_sub_groups">
					<div class="sla_group_empty_items">There are no sub-groups available...</div>
				</article>
			</section>
			
			<section class="group_action_body">
				<header>
					<span>It works like a method/function where the \'result variables\' from the sub-groups are locals...</span>
					<a onclick="addAndInitNewSLASubGroup(this)">Add new sub-group</a>
					
					<div class="group_name" title="Group name which corresponds to the method/function name. This name is used to access this group \'result variables\' outside of the group. If no group name is filled, we cannot access the inner \'result variables\' from outside this group.">
						<label>Group Name: </label>
						<input class="task_property_field" placeHolder="Group Name" />
						<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
					</div>
				</header>
				
				<article class="sla_sub_groups">
					<div class="sla_group_empty_items">There are no sub-groups available...</div>
				</article>
			</section>
		</div>';
		
		return $html;
	}
}
?>
