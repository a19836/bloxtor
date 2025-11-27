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

include $EVC->getUtilPath("WorkFlowUIHandler");
include $EVC->getUtilPath("BreadCrumbsUIHandler");
include $EVC->getUtilPath("TourGuideUIHandler");

$obj_data = isset($obj_data) ? $obj_data : null;
$db_drivers = isset($db_drivers) ? $db_drivers : null;
$selected_db_broker = isset($selected_db_broker) ? $selected_db_broker : null;
$selected_db_driver = isset($selected_db_driver) ? $selected_db_driver : null;
$selected_type = isset($selected_type) ? $selected_type : null;
$selected_table = isset($selected_table) ? $selected_table : null;
$selected_tables_name = isset($selected_tables_name) ? $selected_tables_name : null;
$selected_table_attrs = isset($selected_table_attrs) ? $selected_table_attrs : null;
$map_php_types = isset($map_php_types) ? $map_php_types : null;
$map_db_types = isset($map_db_types) ? $map_db_types : null;
$item_type = isset($item_type) ? $item_type : null;
$is_hbn_obj_equal_to_file_name = isset($is_hbn_obj_equal_to_file_name) ? $is_hbn_obj_equal_to_file_name : null;
$file_path = isset($file_path) ? $file_path : null;
$obj = isset($obj) ? $obj : null;
$WorkFlowTaskHandler = isset($WorkFlowTaskHandler) ? $WorkFlowTaskHandler : null;
$rel_type = isset($rel_type) ? $rel_type : null;
$is_covertable_sql = isset($is_covertable_sql) ? $is_covertable_sql : null;
$name = isset($name) ? $name : null;
$parameter_class = isset($parameter_class) ? $parameter_class : null;
$parameter_map = isset($parameter_map) ? $parameter_map : null;
$result_class = isset($result_class) ? $result_class : null;
$result_map = isset($result_map) ? $result_map : null;
$sql = isset($sql) ? $sql : null;

$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($filter_by_layout);
$is_obj_valid = $obj_data || !$query_id;

$head = "";
$main_content = "";

if ($is_obj_valid) {
	$get_layer_sub_files_url = $project_url_prefix . "admin/get_sub_files?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
	$manage_ai_action_url = $openai_encryption_key ? $project_url_prefix . "phpframework/ai/manage_ai_action?bean_name=$bean_name&bean_file_name=$bean_file_name" : null;

	$WorkFlowUIHandler = new WorkFlowUIHandler($WorkFlowTaskHandler, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url);
	$WorkFlowQueryHandler = new WorkFlowQueryHandler($WorkFlowUIHandler, $project_url_prefix, $project_common_url_prefix, $db_drivers, $selected_db_broker, $selected_db_driver, $selected_type, $selected_table, $selected_tables_name, $selected_table_attrs, $map_php_types, $map_db_types);

	//PREPARING HEADER
	$head = $WorkFlowUIHandler->getHeader();
	$head .= LayoutTypeProjectUIHandler::getHeader();
}

$head .= '
<!-- Add MyTree main JS and CSS files -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/jquerymytree/css/style.min.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerymytree/js/mytree.js"></script>

<!-- Add FileManager JS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/file_manager.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/file_manager.js"></script>

<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Icons CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS and JS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/layout.js"></script>

<!-- Edit QUERY JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/dataaccess/edit_query.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/dataaccess/edit_query.js"></script>

<!-- Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/dataaccess/edit_single_query.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/dataaccess/edit_single_query.js"></script>';

if ($is_obj_valid) {
	$head .= $WorkFlowQueryHandler->getHeader();
	$head .= $WorkFlowQueryHandler->getDataAccessJavascript($bean_name, $bean_file_name, $path, $item_type, $hbn_obj_id, $get_layer_sub_files_url);
	$head .= '<script>
	var manage_ai_action_url = \'' . $manage_ai_action_url . '\';
	var save_data_access_object_url = \'' . $project_url_prefix . 'phpframework/dataaccess/save_query?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&path=' . $path . '&item_type=' . $item_type . '&obj=' . $hbn_obj_id . '&relationship_type=' . $relationship_type . '\';
	var remove_data_access_object_url = \'' . $project_url_prefix . 'phpframework/dataaccess/remove_query?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&path=' . $path . '&item_type=' . $item_type . '&obj=' . $hbn_obj_id . '&relationship_type=' . $relationship_type . '&query_id=#obj_id#&query_type=' . $query_type . '\';
	var old_obj_id = \'' . $query_id . '\';
	var old_obj_type = \'' . $rel_type . '\';
	var is_covertable_sql = ' . ($is_covertable_sql ? 1 : 0) . ';
	</script>';

	//PREPARING GLOBAL WORKFLOW
	$main_content = $WorkFlowQueryHandler->getGlobalTaskFlowChar();

	$rand = rand(0, 1000);

	//PREPARING MAIN CONTENT FOR QUERY OBJECT
	$main_content .= '<div class="edit_single_query' . ($is_covertable_sql ? " covertable_sql" : "") . '">
		<div class="top_bar' . ($popup ? " in_popup" : "") . '">
			<header>
				<div class="title" title="' . $path . '">
					' . ($query_id ? "Edit" : "Add") . ' <span class="query_type"></span> SQL Query: <span class="query_name"></span> in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($is_hbn_obj_equal_to_file_name ? dirname($file_path) . "/$hbn_obj_id" : $file_path, $obj, $is_hbn_obj_equal_to_file_name) . '
				</div>
				<ul>
					<li class="save" data-title="Save Query"><a onClick="saveQueryObject(onSuccessSingleQuerySave)"><i class="icon save"></i> Save</a></li>
					<li class="sub_menu" onClick="openSubmenu(this)">
						<i class="icon sub_menu"></i>
						<ul>
							<li class="add_new_table select_query" title="Add new Table"><a onclick="return addNewTask(' . $rand . ');"><i class="icon add"></i> Add Table</a></li>
							<li class="update_tables_attributes select_query" title="Update Tables\' Attributes"><a onclick="return updateQueryDBBroker(' . $rand . ', false);"><i class="icon update_tables_attributes"></i> Update Tables\' Attributes</a></li>
							<li class="separator"></li>
							<li class="is_convertable_sql" title="Is SQL convertable"><a onClick="onChangeIsConvertableSQL(this)" previous_auto_convert="1"><i class="icon toggle_ids"></i> <span>' . ($is_covertable_sql ? "Dis" : "En") . 'able SQL convertable</span> <input type="checkbox"' . ($is_covertable_sql ? " checked" : "") . '/></a></li>
							<li class="separator"></li>
							<li class="toggle_ui select_query" title="Toggle Query Diagram"><a class="toggle_icon active" onclick="return showOrHideSingleQueryUI(this, ' . $rand . ');"><i class="icon toggle_ui"></i> <span>Hide Query Diagram</span> <input type="checkbox" checked/></a></li>
							<li class="toggle_settings" title="Toggle Query Settings"><a class="toggle_icon active" onclick="return showOrHideSingleQuerySettings(this, ' . $rand . ');"><i class="icon toggle_settings"></i> <span>Hide Query Settings</span> <input type="checkbox" checked/></a></li>
							<li class="toggle_main_settings" title="Toggle Main Settings"><a onClick="toggleMainSettingsPanel(this, \'.edit_single_query\')"><i class="icon toggle_ids"></i> <span>Show Main Settings</span> <input type="checkbox"/></a></li>
							<li class="separator select_query"></li>
							<li class="create_sql_from_ui select_query" title="Generate SQL From Diagram"><a onClick="autoUpdateSqlFromUI(' . $rand . ')"><i class="icon create_sql_from_ui"></i> Generate SQL From Diagram</a></li>
							<li class="create_ui_from_sql select_query" title="Generate Diagram From Settings"><a onClick="autoUpdateUIFromSql(' . $rand . ')"><i class="icon create_ui_from_sql"></i> Generate Diagram From Settings</a></li>
							<li class="separator"></li>
							<li class="ai" title="Generate SQL through AI"><a onClick="openGenerateSQLPopup(this)"><i class="icon ai"></i> Generate SQL through AI</a></li>
							<li class="ai" title="Explain SQL through AI"><a onClick="explainSQL(this)"><i class="icon ai"></i> Explain SQL through AI</a></li>
							<li class="ai" title="Open Code Chat Bot"><a onClick="openCodeChatBot(this)"><i class="icon ai"></i> Open Code Chat Bot</a></li>
							<li class="separator"></li>
							<li class="full_screen" title="Maximize/Minimize Editor Screen"><a onClick="toggleFullScreen(this)"><i class="icon full_screen"></i> Maximize Editor Screen</a></li>
							<li class="separator"></li>
							<li class="save" title="Save Query"><a onClick="saveQueryObject(onSuccessSingleQuerySave)"><i class="icon save"></i> Save</a></li>
						</ul>
					</li>
				</ul>
			</header>
		</div>';

	//PREPARING POPUPS
	//	PREPARING CHOOSE TABLE OR ATTRIBUTE
	$main_content .= $WorkFlowQueryHandler->getChooseQueryTableOrAttributeHtml("choose_db_table_or_attribute");
	
	//	PREPARING CHOOSE OBJ TYPE FROM FILE MANAGER
	$main_content .= $WorkFlowQueryHandler->getChooseDAOObjectFromFileManagerHtml("choose_dao_object_from_file_manager");
	
	//	PREPARING CHOOSE RESULT/PARAMETER MAP ID
	$main_content .= $WorkFlowQueryHandler->getChooseAvailableMapIdHtml("choose_map_id");
	
	//PREPARING MAIN ATTRIBUTES
	$data = array(
		"type" => $rel_type, 
		"name" => $name, 
		"parameter_class" => $parameter_class, 
		"parameter_map" => $parameter_map, 
		"result_class" => $result_class, 
		"result_map" => $result_map, 
		"sql" => $sql
	);
	
	$settings = array(
		"init_ui" => true,
		"init_workflow" => true,
		"encapsulate_parameter_and_result_settings" => true,
	);
	
	$sql_html = $WorkFlowQueryHandler->getQueryBlockHtml(false, $settings, $data);
	$sql_html = str_replace("#rand#", $rand, $sql_html);
	
	$main_content .= '
<div class="data_access_obj with_top_bar_section">	
	<div class="relationships">
		<div class="rels">
			' . $sql_html . '
		</div>
	</div>
</div>';
	
	$main_content .= TourGuideUIHandler::getHtml($entity, $project_url_prefix, $project_common_url_prefix, $online_tutorials_url_prefix);
}
else {
	$title = ($query_id ? "Edit" : "Add") . " Query";
	
	$main_content = '
		<div class="top_bar' . ($popup ? " in_popup" : "") . '">
			<header>
				<div class="title" title="' . $path . '">' . $title . '</div>
			</header>
		</div>';
	
	$main_content .= '<div class="error">Error: The system couldn\'t detect the selected object. Please refresh and try again...</div>';
}

$main_content .= '</div>';

//$head = $CssAndJSFilesOptimizer->prepareHtmlWithOptimizedCssAndJSFiles($head, false);
?>
