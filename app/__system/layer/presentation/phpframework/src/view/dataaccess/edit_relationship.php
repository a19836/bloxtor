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

$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($filter_by_layout);
$is_obj_valid = $obj_data || !$query_id;

$head = "";
$main_content = "";

if ($is_obj_valid) {
	$get_layer_sub_files_url = $project_url_prefix . "admin/get_sub_files?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";

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

<!-- Add Layout CSS and JS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/layout.js"></script>

<!-- Edit HBN OBJ JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/dataaccess/edit_hbn_obj.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/dataaccess/edit_hbn_obj.js"></script>

<!-- Edit QUERY JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/dataaccess/edit_query.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/dataaccess/edit_query.js"></script>

<!-- Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/dataaccess/edit_relationship.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/dataaccess/edit_relationship.js"></script>';

if ($is_obj_valid) {
	$head .= $WorkFlowQueryHandler->getHeader();
	$head .= $WorkFlowQueryHandler->getDataAccessJavascript($bean_name, $bean_file_name, $path, $item_type, $hbn_obj_id, $get_layer_sub_files_url);
	$head .= '<script>
	var save_data_access_object_url = \'' . $project_url_prefix . 'phpframework/dataaccess/save_query?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&path=' . $path . '&item_type=' . $item_type . '&obj=' . $hbn_obj_id . '&relationship_type=' . $relationship_type . '\';
	var remove_data_access_object_url = \'' . $project_url_prefix . 'phpframework/dataaccess/remove_query?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&path=' . $path . '&item_type=' . $item_type . '&obj=' . $hbn_obj_id . '&relationship_type=' . $relationship_type . '&query_id=#obj_id#&query_type=' . $query_type . '\';
	var old_obj_id = \'' . $query_id . '\';
	var old_obj_type = \'' . $query_type . '\';
	</script>';

	//PREPARING GLOBAL WORKFLOW
	$main_content = $WorkFlowQueryHandler->getGlobalTaskFlowChar();

	//PREPARING MAIN CONTENT FOR RELATIONSHIP OBJECT
	$main_content .= '<div class="edit_relationship">
		<div class="top_bar' . ($popup ? " in_popup" : "") . '">
			<header>
				<div class="title" title="' . $path . '">
					' . ($query_id ? "Edit" : "Add") . ' <span class="query_type"></span> Relationship: <span class="query_name"></span> in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($is_hbn_obj_equal_to_file_name ? dirname($file_path) . "/$hbn_obj_id" : $file_path, $obj, $is_hbn_obj_equal_to_file_name) . '
				</div>
				
				<ul>
					<li class="save" data-title="Save Relationship"><a onClick="saveQueryObject(onSuccessSingleQuerySave)"><i class="icon save"></i> Save</a></li>
					<li class="sub_menu" onClick="openSubmenu(this)">
						<i class="icon sub_menu"></i>
						<ul>
							<li class="toggle_main_settings" title="Toggle Main Settings"><a onClick="toggleMainSettingsPanel(this, \'.edit_relationship\')"><i class="icon toggle_ids"></i> <span>Show Main Settings</span> <input type="checkbox"/></a></li>
							<li class="separator"></li>
							<li class="full_screen" title="Maximize/Minimize Editor Screen"><a onClick="toggleFullScreen(this)"><i class="icon full_screen"></i> Maximize Editor Screen</a></li>
							<li class="separator"></li>
							<li class="save" title="Save Relationship"><a onClick="saveQueryObject(onSuccessSingleQuerySave)"><i class="icon save"></i> Save</a></li>
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
	
	$settings = array(
		"encapsulate_parameter_and_result_settings" => true,
	);
			
	$sql_html = $WorkFlowQueryHandler->getQueriesBlockHtml(array($obj_data), true, (isset($obj_data["name"]) ? $obj_data["name"] : null), false, $settings);
	
	$main_content .= '
	<div class="data_access_obj with_top_bar_section">	
		<div class="hbn_obj_relationships">
			<div class="relationships">
				<div class="rels">
					' . $sql_html . '
				</div>
			</div>
		</div>
	</div>';
}
else {
	$title = ($query_id ? "Edit" : "Add") . " Relationship";
	
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
