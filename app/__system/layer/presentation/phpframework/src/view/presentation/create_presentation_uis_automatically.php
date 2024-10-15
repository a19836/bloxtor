<?php
include $EVC->getUtilPath("WorkFlowPresentationHandler");
include $EVC->getUtilPath("WorkFlowUIHandler");
include $EVC->getUtilPath("BreadCrumbsUIHandler");

$folder_path = isset($folder_path) ? $folder_path : null;
$P = isset($P) ? $P : null;

$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($filter_by_layout);

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!--Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/create_presentation_uis_automatically.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/create_presentation_uis_automatically.js"></script>';

$main_content = "";

if (!empty($_POST["step_3"])) {
	$main_content .= '
	<div class="statuses">
		<div class="top_bar">
			<header>
				<div class="title" title="' . $path . '">Automatic creation in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($folder_path, $P) . '</div>
				<ul>
					<!--li class="go_back" data-title="Go Back"><a onClick="history.go(-1);"><i class="icon go_back"></i></a></li-->
				</ul>
			</header>
		</div>
		<div class="title">Statuses</div>
		<table>
			<tr>
				<th class="table_name table_header">Type</th>
				<th class="file_path table_header">File Path</th>
				<th class="status table_header">Status</th>
			</tr>';
	
	$exists_status_ok = false;
	
	if (!empty($statuses))
		foreach ($statuses as $table_name => $table_statuses) {
			$table_alias = isset($selected_tables_alias[$table_name]) ? $selected_tables_alias[$table_name] : null;
			
			foreach ($table_statuses as $file_path => $status) {
				$status = ($status ? "ok" : "error");
				
				$main_content .= '<tr>
					<td class="table_name">' . $table_name . ($table_alias ? " => $table_alias" : "") . '</td>
					<td class="file_path">' . $file_path . '</td>
					<td class="status status_' . $status . '">' . strtoupper($status) . '</td>
				</tr>';
				
				if ($status) 
					$exists_status_ok = true;
			}
		}
	
	$main_content .= '
		</table>
	</div>';
	
	if ($exists_status_ok) {
		$main_content .= '<script>
			if (window.parent.refreshAndShowLastNodeChilds) {
				//Refreshing last node clicked in the entities folder.
				window.parent.refreshAndShowLastNodeChilds();
				
				//Getting project node
				var project = window.parent.$("#" + window.parent.last_selected_node_id).parent().closest("li[data-jstree=\'{\"icon\":\"project\"}\']");
				
				//Refreshing blocks folder
				var blocks_folder_id = project.children("ul").children("li[data-jstree=\'{\"icon\":\"blocks_folder\"}\']").attr("id");
				window.parent.refreshAndShowNodeChildsByNodeId(blocks_folder_id);
				
				//Refreshing blocks folder
				var configs_folder_id = project.children("ul").children("li[data-jstree=\'{\"icon\":\"configs_folder\"}\']").attr("id");
				window.parent.refreshAndShowNodeChildsByNodeId(configs_folder_id);
			}
		</script>';
	}
}
else if (!empty($_POST["step_2"])) {
	if (!empty($active_brokers) && !empty($selected_tables)) {
		//PREPARING TABLES PROPS
		//Any change here must be replicated in the method: SequentialLogicalActivityResourceCreator::getTableUIProps
		$create_presentation_uis_files_automatically_url = $project_url_prefix . "phpframework/presentation/create_presentation_uis_files_automatically?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path&db_layer=$db_layer&db_layer_file=$db_layer_file&db_driver=$db_driver&include_db_driver=$include_db_driver&type=$type&authenticated_template=$authenticated_template&non_authenticated_template=$non_authenticated_template&overwrite=$overwrite&users_perms_folder=$users_perms_folder";
		$get_tables_ui_props_url = $project_url_prefix . "phpframework/presentation/get_presentation_tables_ui_props_automatically?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path&db_layer=$db_layer&db_layer_file=$db_layer_file&db_driver=$db_driver&include_db_driver=$include_db_driver&type=$type";
		
		if ($active_brokers)
			foreach ($active_brokers as $broker_name => $aux)
				$active_brokers[$broker_name] = 1;
		
		if ($active_brokers_folder)
			foreach ($active_brokers_folder as $broker_name => $broker_folder)
				if (empty($broker_folder))
					unset($active_brokers_folder[$broker_name]);
		
		if ($selected_tables_alias)
			foreach ($selected_tables_alias as $table_name => $table_alias)
				if (empty($table_alias) )
					unset($selected_tables_alias[$table_name]);
		
		$post_data = array(
			"ab" => $active_brokers,
			"abf" => $active_brokers_folder,
			"st" => $selected_tables,
			"sta" => $selected_tables_alias,
		);
		$tables_ui_props = $selected_tables ? $UserAuthenticationHandler->getURLContent($get_tables_ui_props_url, $post_data) : "";
		//echo "<pre>$get_tables_ui_props_url\n<br>";print_r($tables_ui_props);print_r(json_decode($tables_ui_props, true));print_r($post_data);die();
		$tables_ui_props = json_decode($tables_ui_props, true);
		
		$WorkFlowUIHandler = new WorkFlowUIHandler($WorkFlowTaskHandler, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url);
		$tasks_settings = $WorkFlowTaskHandler->getLoadedTasksSettings();
		$WorkFlowUIHandler->setTasksOrderByTag($allowed_tasks);
		
		$tasks_head = $WorkFlowUIHandler->printTasksCSSAndJS();
		$tasks_contents = $WorkFlowUIHandler->printTasksProperties();
		
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
		
		$choose_bean_layer_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?bean_name=#bean_name#&bean_file_name=#bean_file_name#$filter_by_layout_url_query&path=#path#";
		$upload_bean_layer_files_from_file_manager_url = $project_url_prefix . "admin/upload_file?bean_name=#bean_name#&bean_file_name=#bean_file_name#$filter_by_layout_url_query&path=#path#";
		$choose_dao_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=dao&path=#path#";
		$choose_lib_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=lib&path=#path#";
		$choose_vendor_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=vendor&path=#path#";

		$get_file_properties_url = $project_url_prefix . "phpframework/admin/get_file_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#&class_name=#class_name#&type=#type#";
		$get_query_properties_url = $project_url_prefix . "phpframework/dataaccess/get_query_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&db_driver=$db_driver&db_type=$type&path=#path#&query_type=#query_type#&query=#query#&obj=#obj#&relationship_type=#relationship_type#";
		$get_business_logic_properties_url = $project_url_prefix . "phpframework/businesslogic/get_business_logic_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#&service=#service#";
		$get_broker_db_drivers_url = $project_url_prefix . "phpframework/db/get_broker_db_drivers?bean_name=$bean_name&bean_file_name=$bean_file_name&broker=#broker#&item_type=presentation";
		$edit_task_source_url = $project_url_prefix . "phpframework/admin/edit_task_source?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path";
		
		$head = '
		<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ace.js"></script>
		<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ext-language_tools.js"></script>
		<script language="javascript" type="text/javascript" src="' . $external_libs_url_prefix["taskflowchart"] . 'js/ExternalLibHandler.js"></script>
		<script language="javascript" type="text/javascript" src="' . $external_libs_url_prefix["taskflowchart"] . 'js/TaskFlowChart.js"></script>
		
		<!-- Add MyTree main JS and CSS files -->
		<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/jquerymytree/css/style.min.css" type="text/css" charset="utf-8" />
		<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerymytree/js/mytree.js"></script>

		<!-- Add FileManager JS file -->
		<link rel="stylesheet" href="' . $project_url_prefix . 'css/file_manager.css" type="text/css" charset="utf-8" />
		<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/file_manager.js"></script>
		
		<!-- Add Icons CSS file -->
		<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />
		
		<!-- Add Layout JS file -->
		<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/layout.js"></script>
		
		<link rel="stylesheet" href="' . $project_url_prefix . 'css/edit_php_code.css" type="text/css" charset="utf-8" />
		<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/edit_php_code.js"></script>	
		
		<!-- Add PHPJS Functions -->
		<script type="text/javascript" src="' . $project_common_url_prefix . 'vendor/phpjs/functions/strings/parse_str.js"></script>
		<script type="text/javascript" src="' . $project_common_url_prefix . 'vendor/phpjs/functions/strings/stripslashes.js"></script>
		<script type="text/javascript" src="' . $project_common_url_prefix . 'vendor/phpjs/functions/strings/addcslashes.js"></script>
		
		' . $head . '
		' . $tasks_head . '
		
		<script>
		if (typeof Error != "undefined" && Error && Error.hasOwnProperty("stackTraceLimit"))
			Error.stackTraceLimit = undefined; //unlimited stack trace - Some browsers give a javascript error running this service, bc is too heavy. Chrome will give this error: "Uncaught RangeError: Maximum call stack size exceeded". So we need to disable this.
		
		var force_user_action = ' . ($force_user_action ? 1 : 0) . ';
		
		var tables = ' . json_encode($tables) . ';
		var tables_ui_props = ' . (!empty($tables_ui_props["tables"]) ? json_encode($tables_ui_props["tables"]) : "null") . ';
		var brokers_props = ' . (!empty($tables_ui_props["brokers"]) ? json_encode($tables_ui_props["brokers"]) : "null") . ';
		
		tables = tables ? tables : {};
		tables_ui_props = tables_ui_props ? tables_ui_props : {};
		
		var js_load_functions = ' . json_encode($js_load_functions) . ';
		var js_submit_functions = ' . json_encode($js_submit_functions) . ';
		var js_complete_functions = ' . json_encode($js_complete_functions) . ';
		var table_group_html = \'' . str_replace("<script>", "<\' + \'script>", str_replace("</script>", "</\' + \'script>", addcslashes(str_replace(array("\n", "\r"), "", CMSPresentationUIAutomaticFilesHandler::getTableGroupHtml("#table_name#", $foreign_keys, $tasks_contents, $allowed_tasks, $with_items_list_ui, $with_view_item_ui, $with_insert_item_form_ui, $with_update_item_form_ui, $with_fks_ui)), "\\'"))) . '\';
		var foreign_table_html = \'' . str_replace("<script>", "<\' + \'script>", str_replace("</script>", "</\' + \'script>", addcslashes(str_replace(array("\n", "\r"), "", CMSPresentationUIAutomaticFilesHandler::getForeignTableRowHtml("#table_name#", "#foreign_table_name#",  $tasks_contents, $allowed_tasks)), "\\'"))) . '\';
		
		var business_logic_brokers = ' . json_encode($business_logic_brokers) . ';
		var ibatis_brokers = ' . json_encode($ibatis_brokers) . ';
		var hibernate_brokers = ' . json_encode($hibernate_brokers) . ';
		
		var active_brokers = ' . json_encode($active_brokers) . ';
		var active_brokers_folder = ' . json_encode($active_brokers_folder) . ';
		var selected_tables_alias = ' . json_encode($selected_tables_alias) . ';
		var users_perms = ' . json_encode($users_perms) . ';
		var list_and_edit_users = ' . json_encode($list_and_edit_users) . ';
		
		var create_presentation_uis_files_automatically_url = \'' . $create_presentation_uis_files_automatically_url . '\';
		var get_tables_ui_props_url = \'' . $get_tables_ui_props_url . '\';
		var get_query_properties_url = \'' . $get_query_properties_url . '\';
		var get_business_logic_properties_url = \'' . $get_business_logic_properties_url . '\';
		var get_broker_db_drivers_url = \'' . $get_broker_db_drivers_url . '\';
		var edit_task_source_url = \'' . $edit_task_source_url . '\';
		
		ProgrammingTaskUtil.on_programming_task_edit_source_callback = onProgrammingTaskEditSource;
		ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback = onProgrammingTaskChooseCreatedVariable;
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
		';
		
		$head .= WorkFlowPresentationHandler::getFileManagerTreePopupHeader($project_url_prefix);
		$head .= WorkFlowPresentationHandler::getPresentationBrokersHtml($presentation_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url, $upload_bean_layer_files_from_file_manager_url);
		$head .= WorkFlowPresentationHandler::getBusinessLogicBrokersHtml($business_logic_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url);
		$head .= WorkFlowPresentationHandler::getDaoLibAndVendorBrokersHtml($choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $get_file_properties_url);
		$head .= WorkFlowPresentationHandler::getDataAccessBrokersHtml($data_access_brokers, $choose_bean_layer_files_from_file_manager_url);
		$head .= '$(function () {
			onLoadTableSettings();
		});';
		$head .= '</script>';
		$head .= LayoutTypeProjectUIHandler::getHeader();
		
		$main_content = WorkFlowPresentationHandler::getChooseFromFileManagerPopupHtml($bean_name, $bean_file_name, $choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $db_brokers, $data_access_brokers, $ibatis_brokers, $hibernate_brokers, $business_logic_brokers, $presentation_brokers);
		
		$main_content .= '
		<div class="tables_settings">
			<div class="top_bar">
				<header>
					<div class="title" title="' . $path . '">Automatic creation in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($folder_path, $P) . '</div>
					<ul>
						<!--li class="go_back" data-title="Go Back"><a onClick="history.go(-1);"><i class="icon go_back"></i></a></li-->
						<li class="continue" data-title="Continue"><a onClick="save(this)"><i class="icon continue"></i> Continue</a></li>
					</ul>
				</header>
			</div>
			<div class="title">Please check the following table\'s settings</div>
			<div class="tables_groups">';
		
		$t = count($selected_tables);
		for ($i = 0; $i < $t; $i++) {
			$table_name = $selected_tables[$i];
			$table = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
			
			if ($table)
				$main_content .= CMSPresentationUIAutomaticFilesHandler::getTableGroupHtml($table_name, $foreign_keys, $tasks_contents, $allowed_tasks, $with_items_list_ui, $with_view_item_ui, $with_insert_item_form_ui, $with_update_item_form_ui, $with_fks_ui, $selected_tables_alias);
		}
		
		$main_content .= '</div>
			<div class="error">
				<label>The system tried to detect automatically all table\'s settings, but it couldn\'t for the following ones:</label>
				<ul></ul>
				<label>If you decide to use these settings above please correct them before proceed...</label>
			</div>
			<div class="stas_form">
				<form method="post">
					<textarea name="sta">' . json_encode($selected_tables_alias) . '</textarea>
					<textarea name="statuses"></textarea>
					
					<input type="hidden" name="step_3" value="Continue" />
				</form>
			</div>
		</div>';
	}
	
	if (empty($selected_tables))
		$main_content .= '<div class="main_error">No tables selected. Please go back and select at least one table...</div>';
	else if (empty($active_brokers))
		$main_content .= '<div class="main_error">No brokers active. Please go back and select at least one broker...</div>';
}
else if (!empty($_POST["step_1"])) {
	$head .= '<script>
		var users_management_admin_panel_url = \'' . $project_url_prefix . "phpframework/module/user/admin/index?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path" . '\';
	</script>';
	
	$main_content = '<div class="select_tables">
		<div class="top_bar">
			<header>
				<div class="title" title="' . $path . '">Automatic creation in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($folder_path, $P) . '</div>
				<ul>
					<!--li class="go_back" data-title="Go Back"><a onClick="history.go(-1);"><i class="icon go_back"></i></a></li-->
					<li class="continue" data-title="Continue"><a onClick="submitForm(this);"><i class="icon continue"></i> Continue</a></li>
				</ul>
			</header>
		</div>
		<div class="title">Please select the table objects that you wish to create</div>
		<form method="post">
			<input type="hidden" name="db_layer" value="' . $db_layer . '" />
			<input type="hidden" name="db_layer_file" value="' . $db_layer_file . '" />
			<input type="hidden" name="db_driver" value="' . $db_driver . '" />
			<input type="hidden" name="include_db_driver" value="' . $include_db_driver . '" />
			<input type="hidden" name="type" value="' . $type . '" />
			<input type="hidden" name="authenticated_template" value="' . $authenticated_template . '" />
			<input type="hidden" name="force_user_action" value="' . $force_user_action . '" />';
			

	if (!empty($tables_name)) {
		$main_content .= '<div class="select_buttons">
				<a onclick="$(\'.select_tables .tables input\').attr(\'checked\', \'checked\')">Select All</a>
				<a onclick="$(\'.select_tables .tables input\').removeAttr(\'checked\')">Deselect All</a>
			</div>
			<div class="tables">';
		
		$t = count($tables_name);
		for ($i = 0; $i < $t; $i++) {	
			$table_name = $tables_name[$i];
		
			$main_content .= '<div class="table">
						<input type="checkbox" name="st[]" value="' . $table_name . '" />
						<input type="hidden" name="sta[' . $table_name . ']" value="" />
						<label title="Click here to enter a different table alias..." onClick="addTableAlias(this)">' . $table_name . '</label>
					</div>';
		}
		$main_content .= '</div>
			<div class="options">
				<div class="with_items_list_ui">
					<input type="checkbox" name="with_items_list_ui" value="1" checked />
					<label>Create Items List UI?</label>
				</div>
				<div class="with_view_item_ui">
					<input type="checkbox" name="with_view_item_ui" value="1" checked />
					<label>Create View Item UI?</label>
				</div>
				<div class="with_insert_item_form_ui">
					<input type="checkbox" name="with_insert_item_form_ui" value="1" checked />
					<label>Create Insert Item Form UI?</label>
				</div>
				<div class="with_update_item_form_ui">
					<input type="checkbox" name="with_update_item_form_ui" value="1" checked />
					<label>Create Edit Item Form UI?</label>
				</div>
				<div class="with_fks_ui">
					<input type="checkbox" name="with_fks_ui" value="1" checked />
					<label>Create Foreign Tables UI?</label>
				</div>
				<div class="overwrite">
					<input type="checkbox" name="overwrite" value="1" />
					<label>Do you wish to overwrite the selected items, if they already exists?</label>
				</div>
			</div>
			<div class="brokers">
				<label>Brokers Settings:</label>
				<span class="info">For each broker you can activate, inativate and change the default root path from where the system will search the queries...<br/>
				Leave the folder path input field in blank if you wish to search in the default broker folder path.<br/>
				The paths in the input fields bellow will be added to the root path of each broker.</span>
				<table>
					<tr>
						<th class="table_header status">Active/Inative</th>
						<th class="table_header name">Name</th>
						<th class="table_header path">Default Folder Path</th>
					</tr>';
			
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
					<tr>
						<td class="status"><input type="checkbox" name="active_brokers[' . $broker_name . ']" value="1" checked /></td>
						<td class="name">' . $broker_name . ' (' . $broker_type_label . ') </td>
						<td class="path">' . (is_a($broker, "IDBBrokerClient") ? '' : '<input type="text" name="active_brokers_folder[' . $broker_name . ']" value="' . $broker_path_to_filter . '" placeHolder="write folder path here" />') . '</td>
					</tr>';
			}
		
		$main_content .= '</table>
			</div>
			<div class="users_permissions">
				<label>Users Permissions Settings:</label>
				<span class="info">
					If you wish to have user authentication please fill the user permissions bellow. Otherwise please uncheck the boxes bellow.<br/>
					If you wish to edit the users permissions please open the <a href="javascript:void(0)" onclick="openUsersManagementAdminPanelPopup(this)">Users Management Admin Panel</a>
				</span>
				
				<div class="users_management_admin_panel_popup myfancypopup">
					<iframe></iframe>
				</div>';
		
		if (!empty($available_user_types) && !empty($available_activities)) {
			$main_content .= '
				<table>
					<tr>
						<th class="table_header status">User Type</th>';
			
			foreach ($available_activities as $activity_id => $activity_name)
				$main_content .= '<th class="table_header ' . $activity_name . '">' . ucwords($activity_name) . '</th>';
			
			$main_content .= '</tr>';
			
			foreach ($available_user_types as $user_type_id => $user_type_name) {
				$main_content .= '<tr>
						<td class="name">' . $user_type_name . '</td>';
					
				foreach ($available_activities as $activity_id => $activity_name)
					$main_content .= '<td class="' . $activity_name . '"><input type="checkbox" name="users_perms[' . $user_type_id . '][' . $activity_id . ']" value="1" /></td>';
				
				$main_content .= '</tr>';
			}
			
			$main_content .= '</table>
			<div class="users_perms_folder">
				The users permissions auxiliar files will be searched/created/saved in the following folder: 
				<select name="users_perms_folder">
					<option value="project_root_folder">In this Project\'s root folder</option>
					<option value="project_current_folder">In this current folder</option>
				</select>
				<div class="info">The users permissions auxiliar files are the files for the login, register, forgot credentials...</div>
			</div>
			
			<div class="list_and_edit_users">
				<label>Allow internal users listing and editing for:</label>
				<div class="info">
					<select name="list_and_edit_users[]" multiple>
						<option value="" disabled>Choose at least one user to enable this feature...</option>';

			if ($available_user_types)
				foreach ($available_user_types as $user_type_id => $user_type_name)
					$main_content .= '<option value="' . $user_type_id . '" ' . ($user_type_id != UserUtil::PUBLIC_USER_TYPE_ID ? 'selected' : '') . '>' . $user_type_name . '</option>';

	$main_content .= '
						</select>
					</div>
				</div>
				
				<div class="non_authenticated_template">
					<label>Auxiliar Files Template:</label>
					<select name="non_authenticated_template">
						<option value="">-- DEFAULT --</option>';

			$t = count($available_templates);
			for ($i = 0; $i < $t; $i++)
				$main_content .= '<option>' . $available_templates[$i] . '</option>';
			
			$main_content .= '	
					</select>
				</div>';
		}
		else
			$main_content .= '<div class="error">There was an error loading the available users type and activities. Probable the user module is not installed yet...</div>';
		
		$main_content .= '
			</div>
			
			<input type="hidden" name="step_2" value="Continue" />';
	}
	else {
		if ($type == "diagram")
			$main_content .= '<div class="error">There are no tables created in the DB Diagram.<br/>Please go to the DB Layer that you wish, create the correspondent DB Diagram and then execute again this action.</div>';
		else 
			$main_content .= '<div class="error">We couldn\'t detect any tables in the DB.</div>';
	}
	
	$main_content .= '
		</form>
	</div>';
}
else {
	$main_content = '<div class="select_layers">
		<div class="top_bar">
			<header>
				<div class="title" title="' . $path . '">Automatic creation in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($folder_path, $P) . '</div>
				<ul>
					<li class="continue" data-title="Continue"><a onClick="submitForm(this);"><i class="icon continue"></i> Continue</a></li>
				</ul>
			</header>
		</div>
		<div class="title">Please select the DB Driver and click continue</div>
		<form method="post">';

	if (empty($path)) 
		$main_content .= '<div class="error">You cannot execute this action with an undefined path.</div>';
	else if (empty($PEVC)) 
		$main_content .= '<div class="error">Bean name doesn\'t exist. If this problem persists, please talk with the sys-admin.</div>';
	else if (!is_dir($folder_path)) 
		$main_content .= '<div class="error">You can only execute this action inside of a folder.</div>';
	else if (!empty($db_drivers)) {
		$main_content .= '
			<script>
				var default_db_driver = "' . $default_db_driver . '";
			</script>
			
			<div class="db_driver">
				<label>DB Driver:</label>
				<select name="db_driver" onChange="onChangeDBDriver(this)">';
		
		if ($db_drivers)
			foreach ($db_drivers as $db_driver_name => $db_driver_props) {
				$db_driver_bean_file_name = isset($db_driver_props[1]) ? $db_driver_props[1] : null;
				$db_driver_bean_name = isset($db_driver_props[2]) ? $db_driver_props[2] : null;
				
				$main_content .= '<option bean_file_name="' . $db_driver_bean_file_name . '" bean_name="' . $db_driver_bean_name . '" value="' . $db_driver_name . '"' . ($selected_db_driver == $db_driver_name ? " selected" : "") . '>' . $db_driver_name . ($db_driver_props ? '' : ' (Rest)') . '</option>'; //only show the ocal db drivers, this is ignore all the dbdrivers coming from REST brokers.
			}
		
		$main_content .= '	
				</select>
				<input type="hidden" name="db_layer" value="' . $db_layer . '" />
				<input type="hidden" name="db_layer_file" value="' . $db_layer_file . '" />
			</div>
			<div class="type">
				<label>Type:</label>
				<select name="type">
					<option value="db">From DB Server</option>
					<option value="diagram">From DB Diagram</option>
				</select>
			</div>
			<div class="authenticated_template">
				<label>Template:</label>
				<select name="authenticated_template">
					<option value="">-- DEFAULT --</option>';

		$t = count($available_templates);
		for ($i = 0; $i < $t; $i++)
			$main_content .= '<option>' . $available_templates[$i] . '</option>';
		
		$main_content .= '	
				</select>
			</div>
			<div class="include_db_driver">
				<input type="checkbox" name="include_db_driver" value="1"' . ($include_db_driver ? " checked" : "") . ' />
				<label>Hard-code db-driver?</label>
			</div>';
		
		$main_content .= '
			<input type="hidden" name="step_1" value="Continue" />
			
			<div class="error">
				Note that this feature is too heavy and slow and if your browser is not well configured, this won\'t work. <br/>
				You should have the "Maximum Call Stack Size" configured with a large number, otherwise you will get a javascript error.<br/>
				Some of Chrome versions break when running this service. Firefox is OK! <br/>
				(You can get more info in: <a href="https://github.com/v8/v8/wiki/Stack Trace API" target="_blank">https://github.com/v8/v8/wiki/Stack%20Trace%20API</a>)<br/>
				This service can take around 20 minutes.
			</div>
			
			<div class="error">
				<input type="checkbox" name="force_user_action" value="1" /> Please check this box if your browser is not well configured and is not letting you execute this script until the end...
			</div>';
	}
	else
		$main_content .= '<div class="error">There are no DB Layers.<br/>Apparently you have your Presentation Layer without any Business Logic, Data Access or DB Layers, which means your application is not correctly configured.<br/>Please go to "Layers Management" Menu and configure correclty your Presentation Layer.</div>';
	
	$main_content .= '
		</form>
	</div>';
}

?>
