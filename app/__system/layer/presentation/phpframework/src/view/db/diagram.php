<?php
include $EVC->getUtilPath("WorkFlowUIHandler");

if ($bean_name) {
	$WorkFlowUIHandler = new WorkFlowUIHandler($WorkFlowTaskHandler, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url);
	
	$head = '
	<!-- Add ACE Editor JS files -->
	<script src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ace.js"></script>
	<script src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ext-language_tools.js"></script>
	';
	$head .= $WorkFlowUIHandler->getHeader();
	$head .= '
	<!-- Add Layout CSS file -->
	<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/layout.js"></script>
	
	<!-- Add Local JS and CSS files -->
	<link rel="stylesheet" href="' . $project_url_prefix . 'css/db/diagram.css" type="text/css" charset="utf-8" />
	<script>
		var get_updated_db_diagram_url = \'' . $project_url_prefix . 'db/get_updated_db_diagram?layer_bean_folder_name=' . $layer_bean_folder_name . '&bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&path=' . $workflow_path_id . '\';
		var get_db_data_url = \'' . $project_url_prefix . 'db/get_db_data?layer_bean_folder_name=' . $layer_bean_folder_name . '&bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&table=#table#\';
		var create_diagram_sql_url = \'' . $project_url_prefix . 'db/create_diagram_sql?layer_bean_folder_name=' . $layer_bean_folder_name . '&bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&popup=1\';
		var sync_diagram_with_db_server_url = \'' . $project_url_prefix . 'db/sync_diagram_with_db_server?layer_bean_folder_name=' . $layer_bean_folder_name . '&bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '\';
		
		var task_type_id = "' . WorkFlowDBHandler::TASK_TABLE_TYPE . '";
	</script>
	<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/db/diagram.js"></script>';
	$head .= $WorkFlowUIHandler->getJS($workflow_path_id, false, array("resizable_task_properties" => true, "resizable_connection_properties" => true));
	
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
		"Add new Table" => array(
			"class" => "add_new_table", 
			"html" => '<a onClick="addNewTable();return false;"><i class="icon add"></i> Add new Table</a>',
		),
		"Load Tables from DB Server" => array(
			"class" => "update_db_diagram_automatically", 
			"title" => "Update DB Diagram Automatically",
			"html" => '<a onClick="return updateDBDiagram();"><i class="icon update_db_diagram_automatically"></i> Load Tables from DB Server</a>',
		),
		"Sort Tables Automatically" => array(
			"class" => "sort_tables", 
			"title" => "Sort Tables Automatically", 
			"html" => '<a onClick="sortWorkflowTables();return false;"><i class="icon sort"></i> Sort Tables</a>'
		),
		2 => array(
			"class" => "separator",
			"title" => " ", 
			"html" => " ", 
		),
		"Create Diagram's SQL" => array(
			"class" => "create_diagram_sql", 
			"html" => '<a onClick="createDiagamSQL();return false;"><i class="icon create_diagram_sql"></i> Create Diagram\'s SQL</a>',
		),
		3 => array(
			"class" => "separator",
			"title" => " ", 
			"html" => " ", 
		),
		"Enable Auto-Sync Diagram with DB Server" => array(
			"class" => "sync_automatically_with_db_server hidden", 
			"html" => '<a onClick="toggleAutoSyncWithDBServer(this, true);return false;"><i class="icon server"></i> Enable Auto-Sync Diagram with DB Server</a>',
		),
		"Sync Diagram NOW with DB Server" => array(
			"class" => "sync_now_with_db_server", 
			"html" => '<a onClick="syncNowWithDBServer(this);return false;"><i class="icon server"></i> Sync Diagram NOW with DB Server</a>',
		),
		4 => array(
			"class" => "separator",
			"title" => " ", 
			"html" => " ", 
		),
		"Maximize/Minimize Editor Screen" => array(
			"class" => "tasks_flow_full_screen", 
			"html" => '<a onClick="toggleFullScreen(this);return false;"><i class="icon full_screen"></i> Maximize Editor Screen</a>',
		),
		5 => array(
			"class" => "separator",
			"title" => " ", 
			"html" => " ", 
		),
		"Save" => array(
			"class" => "save", 
			"html" => '<a onClick="return saveDBDiagram();"><i class="icon save"></i> Save</a>',
		),
	);
	$WorkFlowUIHandler->setMenus($menus);
	
	$main_content = '
	<div class="top_bar' . ($popup ? " in_popup" : "") . '">
		<header>
			<div class="title">Tables Diagram for DB: \'' . $bean_name . '\'</div>
			<ul>
				<li class="save" data-title="Save"><a onClick="saveDBDiagram()"><i class="icon save"></i> Save</a></li>
			</ul>
		</header>
	</div>';
	$main_content .= $WorkFlowUIHandler->getContent();
	
	//set task properties, like charsets and storage engines
	if (!empty($DBDriver))
		$main_content .= '<script>
			taskFlowChartObj.TaskFlow.default_connection_line_width = 2;
			taskFlowChartObj.TaskFlow.default_connection_from_target = true;
			taskFlowChartObj.TaskFlow.default_similar_connections_gap = 100;
			
			DBTableTaskPropertyObj.column_types = ' . json_encode($DBDriver->getDBColumnTypes()) . ';
			DBTableTaskPropertyObj.column_simple_types = ' . json_encode($DBDriver->getDBColumnSimpleTypes()) . ';
			DBTableTaskPropertyObj.column_numeric_types = ' . json_encode($DBDriver->getDBColumnNumericTypes()) . ';
			DBTableTaskPropertyObj.column_mandatory_length_types = ' . json_encode($DBDriver->getDBColumnMandatoryLengthTypes()) . ';
			DBTableTaskPropertyObj.column_types_ignored_props = ' . json_encode($DBDriver->getDBColumnTypesIgnoredProps()) . ';
			DBTableTaskPropertyObj.column_types_hidden_props = ' . json_encode($DBDriver->getDBColumnTypesHiddenProps()) . ';
			DBTableTaskPropertyObj.table_charsets = ' . json_encode($DBDriver->listTableCharsets()) . ';
			DBTableTaskPropertyObj.table_collations = ' . json_encode($DBDriver->listTableCollations()) . ';
			DBTableTaskPropertyObj.table_storage_engines = ' . json_encode($DBDriver->listStorageEngines()) . ';
			DBTableTaskPropertyObj.column_charsets = ' . json_encode($DBDriver->listColumnCharsets()) . ';
			DBTableTaskPropertyObj.column_collations = ' . json_encode($DBDriver->listColumnCollations()) . ';
			DBTableTaskPropertyObj.allow_column_sorting = ' . ($DBDriver->allowTableAttributeSorting() ? "true" : "false") . ';
			DBTableTaskPropertyObj.allow_modify_table_encoding = ' . ($DBDriver->allowModifyTableEncoding() ? "true" : "false") . ';
			DBTableTaskPropertyObj.allow_modify_table_storage_engine = ' . ($DBDriver->allowModifyTableStorageEngine() ? "true" : "false") . ';
			
			DBTableTaskPropertyObj.on_load_task_properties_callback = onLoadDBTableTaskProperties;
			DBTableTaskPropertyObj.on_submit_task_properties_callback = onSubmitDBTableTaskProperties;
			DBTableTaskPropertyObj.on_task_creation_callback = onDBTableTaskCreation;
			DBTableTaskPropertyObj.on_task_deletion_callback = onDBTableTaskDeletion;
			DBTableTaskPropertyObj.on_update_simple_attributes_html_with_table_attributes_callback = onUpdateSimpleAttributesHtmlWithTableAttributes;
			DBTableTaskPropertyObj.on_update_table_attributes_html_with_simple_attributes_callback = onUpdateTableAttributesHtmlWithSimpleAttributes;
			DBTableTaskPropertyObj.on_add_task_properties_attribute_callback = onAddTaskPropertiesAttribute;
			DBTableTaskPropertyObj.on_before_remove_task_properties_attribute_callback = onBeforeRemoveTaskPropertiesAttribute;
			DBTableTaskPropertyObj.on_before_sort_task_properties_attributes = onBeforeSortTaskPropertiesAttributes;
		</script>';
	
	$main_content .= '<div class="loading_panel"></div>';
}
else {
	$error_message = "Error: DB Name undefined!";
}
?>
