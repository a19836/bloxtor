<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include $EVC->getUtilPath("CMSPresentationLayerUIHandler");
include $EVC->getUtilPath("BreadCrumbsUIHandler");

$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($filter_by_layout);

$head = "";
$main_content = "";

if (!empty($WorkFlowTaskHandler)) {
	//preparing generic interface
	$WorkFlowUIHandler = new WorkFlowUIHandler($WorkFlowTaskHandler, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url);
	
	$BrokersWorkFlowUIHandler = new WorkFlowUIHandler($BrokersWorkFlowTaskHandler, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url);
	$tasks_settings = $BrokersWorkFlowTaskHandler->getLoadedTasksSettings();
	$BrokersWorkFlowUIHandler->setTasksOrderByTag($allowed_tasks);
	
	$tasks_head = $BrokersWorkFlowUIHandler->printTasksCSSAndJS();
	$tasks_contents = $BrokersWorkFlowUIHandler->printTasksProperties();
	
	$js_load_functions = array();
	$js_submit_functions = array();
	$js_complete_functions = array();
	foreach ($tasks_settings as $group_id => $group_tasks)
		foreach ($group_tasks as $task_type => $task_settings)
			if (is_array($task_settings)) {
				$tag = isset($task_settings["tag"]) ? $task_settings["tag"] : null;
				$js_load_functions[$tag] = isset($task_settings["settings"]["callback"]["on_load_task_properties"]) ? $task_settings["settings"]["callback"]["on_load_task_properties"] : null;
				$js_submit_functions[$tag] = isset($task_settings["settings"]["callback"]["on_submit_task_properties"]) ? $task_settings["settings"]["callback"]["on_submit_task_properties"] : null;
				$js_complete_functions[$tag] = isset($task_settings["settings"]["callback"]["on_complete_task_properties"]) ? $task_settings["settings"]["callback"]["on_complete_task_properties"] : null;
			}
	
	$choose_dao_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=dao&path=#path#";
	$choose_lib_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=lib&path=#path#";
	$choose_vendor_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=vendor&path=#path#";

	$get_broker_db_data_url = $project_url_prefix . "phpframework/dataaccess/get_broker_db_data?bean_name=$bean_name&bean_file_name=$bean_file_name";
	$get_file_properties_url = $project_url_prefix . "phpframework/admin/get_file_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#&class_name=#class_name#&type=#type#"; //this will be useful in the brokers tasks in step 3 of automatic creation. Do not delete this!
	$choose_bean_layer_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?bean_name=#bean_name#&bean_file_name=#bean_file_name#$filter_by_layout_url_query&path=#path#";
	$upload_bean_layer_files_from_file_manager_url = $project_url_prefix . "admin/upload_file?bean_name=#bean_name#&bean_file_name=#bean_file_name#$filter_by_layout_url_query&path=#path#";
	$get_tables_ui_props_url = $project_url_prefix . "phpframework/presentation/get_presentation_tables_ui_props_automatically?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path&db_driver=#db_driver#&type=#type#";
	$get_query_properties_url = $project_url_prefix . "phpframework/dataaccess/get_query_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&db_driver=#db_driver#&db_type=#db_type#&path=#path#&query_type=#query_type#&query=#query#&obj=#obj#&relationship_type=#relationship_type#";
	$get_business_logic_properties_url = $project_url_prefix . "phpframework/businesslogic/get_business_logic_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#&service=#service#";
	$get_broker_db_drivers_url = $project_url_prefix . "phpframework/db/get_broker_db_drivers?bean_name=$bean_name&bean_file_name=$bean_file_name&broker=#broker#&item_type=presentation";
	
	$users_management_admin_panel_url = $project_url_prefix . "phpframework/module/user/admin/index?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path&popup=1";
	$get_entity_settings_url = $project_url_prefix . "phpframework/presentation/get_entity_settings?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path#entity#.php";
	$edit_entity_admin_panel_url = $project_url_prefix . "phpframework/presentation/edit_entity?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path#entity#.php";
	$are_entities_hard_coded_url = $project_url_prefix . "phpframework/presentation/are_entities_hard_coded?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path&entities=#entities#";
	$get_current_path_sub_files_url = $project_url_prefix . "admin/get_sub_files?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path";
	$edit_task_source_url = $project_url_prefix . "phpframework/admin/edit_task_source?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path";
	
	$head = $WorkFlowUIHandler->getHeader(array("tasks_css_and_js" => false));
	$head .= WorkFlowPresentationHandler::getFileManagerTreePopupHeader($project_url_prefix);
	$head .= LayoutTypeProjectUIHandler::getHeader();
	$head .= '
	<!-- Add MD5 JS File -->
	<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquery/js/jquery.md5.js"></script>
	
	<!-- Add ACE JS and CSS files -->
	<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ace.js"></script>
	<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ext-language_tools.js"></script>
	
	<!-- Add MyTree main JS and CSS files -->
	<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/jquerymytree/css/style.min.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerymytree/js/mytree.js"></script>
	
	<!-- Add FileManager JS file -->
	<link rel="stylesheet" href="' . $project_url_prefix . 'css/file_manager.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/file_manager.js"></script>
	
	<!-- Add PHPJS Functions -->
	<script type="text/javascript" src="' . $project_common_url_prefix . 'vendor/phpjs/functions/strings/parse_str.js"></script>
	<script type="text/javascript" src="' . $project_common_url_prefix . 'vendor/phpjs/functions/strings/stripslashes.js"></script>
	<script type="text/javascript" src="' . $project_common_url_prefix . 'vendor/phpjs/functions/strings/addcslashes.js"></script>
	
	<!-- Add Layout CSS and JS files -->
	<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/layout.js"></script>
	
	<!-- Add EDIT PHP CODE file -->
	<link rel="stylesheet" href="' . $project_url_prefix . 'css/edit_php_code.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/edit_php_code.js"></script>
	
	<!-- Add CodeHighLight CSS and JS -->
	<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/codehighlight/styles/default.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/codehighlight/highlight.pack.js"></script>
	
	<!-- Add Local JS and CSS files -->
	<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/create_presentation_uis_diagram.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/create_presentation_uis_diagram.js"></script>
	';
	$head .= $tasks_head;
	$head .= $WorkFlowUIHandler->printTasksCSSAndJS();
	$head .= $WorkFlowUIHandler->getJS($workflow_path_id, false, array("resizable_task_properties" => true, "resizable_connection_properties" => true));
	
	$diagram_relative_folder_path = trim(substr($path, strpos($path, "/src/entity/") + strlen("/src/entity/")));
	$diagram_relative_folder_path .= substr($diagram_relative_folder_path, -1) != "/" ? "/" : "";
	
	$head .= '<script>
		var layer_type = "pres";
		var selected_project_id = "' . $selected_project_id . '";
		
		if (typeof Error != "undefined" && Error && Error.hasOwnProperty("stackTraceLimit"))
			Error.stackTraceLimit = undefined; //unlimited stack trace - Some browsers give a javascript error running this service, bc is too heavy. Chrome will give this error: "Uncaught RangeError: Maximum call stack size exceeded". So we need to disable this.
	
		var brokers_allowed_tasks = ' . json_encode($brokers_allowed_tasks) . ';
		var allowed_tasks = ' . json_encode($allowed_tasks) . ';
	
		var js_load_functions = ' . json_encode($js_load_functions) . ';
		var js_submit_functions = ' . json_encode($js_submit_functions) . ';
		var js_complete_functions = ' . json_encode($js_complete_functions) . ';
		
		var table_action_ui_html = \'' . addcslashes(str_replace(array("\n", "\r"), "", CMSPresentationUIAutomaticFilesHandler::getTableUIHtml("#title#", $tasks_contents, $allowed_tasks, "#type#", "#relationship_table#")), "\\'") . '\';
		
		var default_db_driver = "' . $default_db_driver . '";
		
		var business_logic_brokers = ' . json_encode($business_logic_brokers) . ';
		var ibatis_brokers = ' . json_encode($ibatis_brokers) . ';
		var hibernate_brokers = ' . json_encode($hibernate_brokers) . ';
		
		var get_tables_ui_props_url = \'' . $get_tables_ui_props_url . '\';
		var get_query_properties_url = \'' . $get_query_properties_url . '\';
		var get_business_logic_properties_url = \'' . $get_business_logic_properties_url . '\';
		var get_broker_db_drivers_url = \'' . $get_broker_db_drivers_url . '\';
		var are_entities_hard_coded_url = \'' . $are_entities_hard_coded_url . '\';
		var get_current_path_sub_files_url = \'' . $get_current_path_sub_files_url . '\';
		var edit_task_source_url = \'' . $edit_task_source_url . '\';
		
		var page_task_type= "d7975b77"; //page task type id
		
		taskFlowChartObj.TaskFlow.default_connection_line_width = 2;
		taskFlowChartObj.TaskFlow.default_connection_from_target = true;
		
		taskFlowChartObj.setTaskFlowChartObjOption("on_resize_panels_function", onResizeTaskFlowChartPanels); //add default function to reset the top positon of the tasksflow panels, if with_top_bar class exists
		
		ProgrammingTaskUtil.on_programming_task_edit_source_callback = onProgrammingTaskEditSource;
		ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback = onProgrammingTaskChooseCreatedVariable;
		ProgrammingTaskUtil.on_programming_task_choose_page_url_callback = onIncludePageUrlTaskChooseFile;
		ProgrammingTaskUtil.on_programming_task_choose_image_url_callback = onIncludeImageUrlTaskChooseFile;
		ProgrammingTaskUtil.on_programming_task_choose_webroot_file_url_callback = onIncludeWebrootFileUrlTaskChooseFile;
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
		
		PresentationTaskUtil.db_drivers = ' . json_encode($db_drivers) . ';
		PresentationTaskUtil.selected_db_driver = "' . $selected_db_driver . '";
		PresentationTaskUtil.get_broker_db_data_url = "' . $get_broker_db_data_url . '";
		PresentationTaskUtil.on_choose_file_callback = onIncludePageUrlTaskChooseFile;
		PresentationTaskUtil.available_user_types = ' . (isset($available_user_types) ? json_encode($available_user_types) : "null") . ';
		PresentationTaskUtil.available_activities = ' . (isset($available_activities) ? json_encode($available_activities) : "null") . ';
		PresentationTaskUtil.users_management_admin_panel_url = "' . $users_management_admin_panel_url . '";
		PresentationTaskUtil.auto_increment_db_attributes_types = ' . json_encode($auto_increment_db_attributes_types) . ';
		
		PageTaskPropertyObj.templates = ' . json_encode($available_templates) . ';
		PageTaskPropertyObj.on_choose_block_callback = onIncludeBlockTaskChooseFile;
		PageTaskPropertyObj.on_choose_include_callback = onPresentationIncludeFileTaskChoosePage;
		PageTaskPropertyObj.get_page_settings_url = "' . $get_entity_settings_url . '";
		PageTaskPropertyObj.is_page_hard_coded_url = "' . $are_entities_hard_coded_url . '";
		PageTaskPropertyObj.edit_page_admin_panel_url = "' . $edit_entity_admin_panel_url . '";
		PageTaskPropertyObj.diagram_path = "' . $diagram_relative_folder_path . '";
		
		var create_presentation_uis_diagram_files_url = "' . $project_url_prefix . 'phpframework/presentation/create_presentation_uis_diagram_files?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . $filter_by_layout_url_query . '&path=' . $path . '&do_not_save_vars_file=' . (isset($do_not_save_vars_file) ? $do_not_save_vars_file : "") . '&do_not_check_if_path_exists=' . (isset($do_not_check_if_path_exists) ? $do_not_check_if_path_exists : "") . '"; //$do_not_save_vars_file comes from the file: create_page_presentation_uis_diagram_block.php
	';
	$head .= WorkFlowPresentationHandler::getPresentationBrokersHtml($presentation_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url, $upload_bean_layer_files_from_file_manager_url);
	$head .= WorkFlowPresentationHandler::getBusinessLogicBrokersHtml($business_logic_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url);
	$head .= WorkFlowPresentationHandler::getDaoLibAndVendorBrokersHtml($choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $get_file_properties_url);
	$head .= WorkFlowPresentationHandler::getDataAccessBrokersHtml($data_access_brokers, $choose_bean_layer_files_from_file_manager_url);
	$head .= '</script>';
	
	//prepare main_content
	$main_content .= '<div class="create_presentation_uis_diagram' . ($user_module_installed_and_enabled ? '' : ' no_authentication') . '">
	<div class="top_bar">
		<header>
			<div class="title" title="' . $path . '">Presentation UIs Diagram in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($folder_path, $P) . '</div>
			<ul>
				<li class="create_ui_files" data-title="Create UI Files"><a onClick="createUIFiles(this)"><i class="icon create_ui_files"></i> Create UI Files</a></li>
				<li class="save" data-title="Save"><a onClick="saveUIsDiagramFlow()"><i class="icon save"></i> Save</a></li>
			</ul>
		</header>
	</div>';
	
	//prepare popups
	$main_content .= WorkFlowPresentationHandler::getChooseFromFileManagerPopupHtml($bean_name, $bean_file_name, $choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $db_brokers, $data_access_brokers, $ibatis_brokers, $hibernate_brokers, $business_logic_brokers, $presentation_brokers);
	$main_content .= CMSPresentationLayerUIHandler::getChoosePresentationIncludeFromFileManagerPopupHtml($bean_name, $bean_file_name,  $choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $presentation_brokers, "MyDiagramUIFancyPopup");
	
	$main_content .= '
		<div id="choose_page_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
			<div class="title">Choose a Page</div>
			<ul class="mytree">
				<li>
					<label>' . $bean_name . '</label>
					<ul url="' . str_replace("#bean_file_name#", $bean_file_name, str_replace("#bean_name#", $bean_name, $choose_bean_layer_files_from_file_manager_url)) . '"></ul>
				</li>
			</ul>
			<div class="button">
				<input type="button" value="Update" onClick="MyDiagramUIFancyPopup.settings.updateFunction(this)" />
			</div>
		</div>
		
		<div id="choose_page_block_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
			<div class="title">Choose a Block</div>
			<ul class="mytree">
				<li>
					<label>' . $bean_name . '</label>
					<ul url="' . str_replace("#bean_file_name#", $bean_file_name, str_replace("#bean_name#", $bean_name, $choose_bean_layer_files_from_file_manager_url)) . '"></ul>
				</li>
			</ul>
			<div class="button">
				<input type="button" value="Update" onClick="MyDiagramUIFancyPopup.settings.updateFunction(this)" />
			</div>
		</div>
		
		<div id="create_uis_files" class="myfancypopup with_title create_uis_files">
			<h3 class="title">UIs Creation</h3>
			
			<div class="step step_1">
				<div class="info">
					You can create uis based in:
					<ul>
						<li>- new sql queries that will connect directly to the DB</li>
						<li>- or in existent services and rules in the Business Logic and Data Access layers.</li>
					</ul>
					We advise you to click in the button: "Create files with existent services and rules".
				</div>
				
				<div class="overwrite">
					<input type="checkbox" name="overwrite" value="1" checked />
					<label>Do you wish to overwrite the selected items, if they already exists?</label>
				</div>
				
				<div class="users_perms_relative_folder">
					<label>Users Perms Settings:</label>
					<div class="info">
						The users permissions auxiliar files will be searched/created/saved in the following relative folder: 
						<input type="text" value="" placeHolder="write folder path here" /><br>
						Leave this folder path input field in blank if you wish to search in the Project\'s root folder.<br/>
						The users permissions auxiliar files are the files for the login, register, forgot credentials, edit user profile...
						<br>
					</div>
				</div>
				<div class="list_and_edit_users">
					<label>Allow internal users listing and editing for:</label>
					<div class="info">
						<select multiple>
							<option value="" disabled>Choose at least one user to enable this feature...</option>';
	
	if (!empty($available_user_types))
		foreach ($available_user_types as $user_type_id => $user_type_name)
			$main_content .= '<option value="' . $user_type_id . '" ' . ($user_type_id != UserUtil::PUBLIC_USER_TYPE_ID ? 'selected' : '') . '>' . $user_type_name . '</option>';
	
	$main_content .= '
						</select>
					</div>
				</div>
				
				<div class="button">
					<input class="' . (!empty($layer_brokers_settings["data_access_brokers"]) ? '' : 'hidden') . '" type="button" value="Create files now with new sql queries" onClick="saveUISFilesWithNewQueries(this)" />
					<input type="button" value="Continue with existent services and rules" onClick="goToStepAutomaticCreation(this, 2)" />
				</div>
			</div>
			
			<div class="step step_2">
				<div class="existent_tasks">
					<label>Existent Tasks:</label>
					<ul>
						<li class="no_existent_tasks">No selected tasks...</li>
					</ul>
				</div>
				
				<div class="brokers">
					<label>Brokers Settings:</label>
					<div class="info">
						For each broker you can activate, inativate and change the default root path from where the system will search the queries...<br/>
						Leave the folder path input field in blank if you wish to search in the default broker folder path.<br/>
						The paths in the input fields bellow will be added to the root path of each broker.
					</div>
					<table>
						<thead>
							<tr>
								<th class="table_header status">Active/Inative</th>
								<th class="table_header name">Name</th>
								<th class="table_header path">Default Folder Path</th>
							</tr>
						</thead>
						<tbody>';
			
			if ($brokers)	
				foreach ($brokers as $broker_name => $broker)
					if (is_a($broker, "IBusinessLogicBrokerClient") || is_a($broker, "IDataAccessBrokerClient") || is_a($broker, "IDBBrokerClient")) {
						$broker_type_label = is_a($broker, "IBusinessLogicBrokerClient") ? "Business Logic Broker" : (
							is_a($broker, "IHibernateDataAccessBrokerClient") ? "Hibernate Data Access Broker" : (
								is_a($broker, "IIbatisDataAccessBrokerClient") ? "Ibatis Data Access Broker" : (
									is_a($broker, "IDBBrokerClient") ? "DB Broker" : ""
								)
							)
						);
						$main_content .= '
								<tr broker_name="' . $broker_name . '">
									<td class="status"><input type="checkbox" value="1" checked /></td>
									<td class="name">' . $broker_name . '(' . $broker_type_label . ') </td>
									<td class="path">' . (is_a($broker, "IDBBrokerClient") ? '' : '<input type="text" value="" placeHolder="write folder path here" />') . '</td>
								</tr>';
					}
			
			$main_content .= '
						</tbody>
					</table>
				</div>
				
				<div class="error">
					Note that this feature is too heavy and slow and if your browser is not well configured, this won\'t work. <br/>
					You should have the "Maximum Call Stack Size" configured with a large number, otherwise you will get a javascript error.<br/>
					Some of Chrome versions break when running this service. Firefox is OK! <br/>
					(You can get more info in: <a href="https://github.com/v8/v8/wiki/Stack Trace API" target="_blank">https://github.com/v8/v8/wiki/Stack%20Trace%20API</a>)<br/>
					This service can take around 20 minutes.
				</div>
				
				<div class="button">
					<input type="button" value="Go back" onClick="goBackStepAutomaticCreation(this, 1)" />
					<input type="button" value="Find existent services and rules" onClick="goToStepAutomaticCreation(this, 3)" />
				</div>
			</div>
			
			<div class="step step_3">
				<div class="existent_tasks">
					<label>Existent Tasks with Services and Rules:</label>
					<div class="info">Please check the following tasks\' settings:</div>
					
					<ul>
						<li class="no_existent_tasks">No selected tasks...</li>
					</ul>
					
					<div class="error">
						The system tried to detect automatically all table\'s settings, but it couldn\'t for some tables above.<br>
						If you decide to use these settings above please correct them before proceed...
					</div>
					
					<div class="warning">
						The system detected that there are some services/rules that are using attributes that were removed from the user UI. Please check this before proceed...
					</div>
				</div>
				
				<div class="button">
					<input type="button" value="Go back" onClick="goBackStepAutomaticCreation(this, 2)" />
					<input type="button" value="Create files now with existent services and rules" onClick="goToStepAutomaticCreation(this, 4)" />
				</div>
			</div>
			
			<div class="step step_4">
				<div class="existent_tasks">
					<label>Existent Tasks with Files to create:</label>
					
					<ul>
						<li class="no_existent_tasks">No selected tasks...</li>
					</ul>
					
					<div class="warning">
						The system detected that some of the files that will be created/updated automatically, were changed manually. Please check these files before proceed...
					</div>
				</div>
				
				<div class="button">
					<input type="button" value="Go back" onClick="goBackStepAutomaticCreation(this, 3)" />
					<input type="button" value="Save files above" onClick="goToStepAutomaticCreation(this, 5)" />
				</div>
			</div>
			
			<div class="step step_5">
				<div class="files_statuses">
					<label>Files Statuses:</label>
					<div class="files_statuses_table">
						<table>
							<thead>
								<tr>
									<th class="table_header task_label">Task Label</th>
									<th class="table_header file_path">File Path</th>
									<th class="table_header status">Saved</th>
								</tr>
							</thead>
							<tbody>
								<tr><td colpsan="3">loading...</td></tr>
							</tbody>
						</table>
					</div>
				</div>
				
				<div class="button">
					<input type="button" value="Go back" onClick="goBackStepAutomaticCreation(this, 4)" />
					<input type="button" value="Close" onClick="goToStepAutomaticCreation(this, 6)" />
				</div>
			</div>
		</div>';
	
	//prepare workflow
	$menus = array(
		"Flush Cache" => array(
			"class" => "flush_cache", 
			"html" => '<a onClick="return flushCache();"><i class="icon flush_cache"></i> Flush Cache</a>',
		),
		"Empty Diagram" => array(
			"class" => "empty_diagram", 
			"html" => '<a onClick="emptyDiagam();return false;"><i class="icon empty_diagram"></i> Empty Diagram</a>',
		),
		0 => array(
			"class" => "separator",
			"title" => " ", 
			"html" => " ", 
		),
		"Zoom In" => array(
			"class" => "zoom_in", 
			"html" => '<a onClick="zoomInDiagram(this);return false;"><i class="icon zoom_in"></i> Zoom In</a>',
		),
		"Zoom Out" => array(
			"class" => "zoom_out", 
			"html" => '<a onClick="zoomOutDiagram(this);return false;"><i class="icon zoom_out"></i> Zoom Out</a>',
		),
		"Zoom" => array(
			"class" => "zoom", 
			"html" => '
			<a onClick="zoomEventPropagationDiagram(this);return false;"><i class="icon zoom"></i> <input type="range" min="0.5" max="1.5" step=".02" value="1" onInput="zoomDiagram(this);return false;" /> <span>100%</span></a>',
		),
		"Zoom Reset" => array(
			"class" => "zoom_reset", 
			"html" => '<a onClick="zoomResetDiagram(this);return false;"><i class="icon zoom_reset"></i> Zoom Reset</a>',
		),
		1 => array(
			"class" => "separator",
			"title" => " ", 
			"html" => " ", 
		),
		"Create UI Files" => array(
			"class" => "create_ui_files", 
			"html" => '<a onClick="createUIFiles();return false;"><i class="icon create_ui_files"></i> Create UI Files</a>',
		),
		"Load New Files" => array(
			"class" => "load_new_existent_files", 
			"html" => '<a onClick="loadNewExistentFiles();return false;"><i class="icon load_new_existent_files"></i> Load New Files</a>',
		),
		2 => array(
			"class" => "separator",
			"title" => " ", 
			"html" => " ", 
		),
		"Maximize/Minimize Editor Screen" => array(
			"class" => "tasks_flow_full_screen", 
			"html" => '<a onClick="toggleFullScreen(this);return false;"><i class="icon full_screen"></i> Maximize Editor Screen</a>',
		),
		3 => array(
			"class" => "separator",
			"title" => " ", 
			"html" => " ", 
		),
		"Save" => array(
			"class" => "save", 
			"html" => '<a onClick="saveUIsDiagramFlow();return false;"><i class="icon save"></i> Save</a>',
		),
	);
	$WorkFlowUIHandler->setMenus($menus);
	
	$main_content .= $WorkFlowUIHandler->getContent();
	
	$main_content .= '
	<div class="confirm_save hidden">
		<div class="title">Please confirm if the code is correct and if it is, click on the checked button...</div>
		
		<div class="file_code">
			<div class="old_file_code">
				<label>Old code for file:</label>
				<pre><code class="php"></code></pre>
			</div>
			<div class="new_file_code">
				<label>New code for file to be saved:</label>
				<pre><code class="php"></code></pre>
			</div>
		</div>
		
		<div class="buttons">
			<input type="button" name="cancel" value="Cancel" onClick="cancelCheckedFileCode();" />
			<input type="button" name="save" value="Checked" onClick="validateCheckedFileCode();" />
		</div>
		
		<div class="disable_auto_scroll" onClick="enableDisableAutoScroll(this);">Click here to disable auto scroll.</div>
	</div>';
	
	$main_content .= '</div>';
}
else
	$error_message = "Error: Persentation UIs Bean undefined or folder path does not exist!";
?>
