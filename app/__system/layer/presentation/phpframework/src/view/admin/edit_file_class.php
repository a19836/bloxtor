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

include_once $EVC->getUtilPath("WorkFlowPresentationHandler");
include $EVC->getUtilPath("BreadCrumbsUIHandler");

$selected_project_id = isset($selected_project_id) ? $selected_project_id : null;
$file_path = isset($file_path) ? $file_path : null;
$obj = isset($obj) ? $obj : null;
$is_class_equal_to_file_name = isset($is_class_equal_to_file_name) ? $is_class_equal_to_file_name : null;
$db_brokers = isset($db_brokers) ? $db_brokers : null;
$data_access_brokers = isset($data_access_brokers) ? $data_access_brokers : null;
$ibatis_brokers = isset($ibatis_brokers) ? $ibatis_brokers : null;
$hibernate_brokers = isset($hibernate_brokers) ? $hibernate_brokers : null;
$business_logic_brokers = isset($business_logic_brokers) ? $business_logic_brokers : null;
$presentation_brokers = isset($presentation_brokers) ? $presentation_brokers : null;

$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($filter_by_layout);
$is_obj_valid = !empty($obj_data) || !$class_id;

$head = '
	<!-- Add MD5 JS File -->
	<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquery/js/jquery.md5.js"></script>

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

	<!-- Edit PHP Code JS file -->
	<link rel="stylesheet" href="' . $project_url_prefix . 'css/edit_php_code.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/edit_php_code.js"></script>

	<!-- Edit File Includes JS and CSS files -->
	<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/edit_file_includes.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/edit_file_includes.js"></script>

	<!-- Local JS and CSS files -->
	<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/edit_file_class.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/edit_file_class.js"></script>
';

if ($is_obj_valid) {
	$choose_bean_layer_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?bean_name=#bean_name#&bean_file_name=#bean_file_name#$filter_by_layout_url_query&path=#path#";
	$upload_bean_layer_files_from_file_manager_url = $project_url_prefix . "admin/upload_file?bean_name=#bean_name#&bean_file_name=#bean_file_name#$filter_by_layout_url_query&path=#path#";
$get_file_properties_url = $project_url_prefix . "phpframework/admin/get_file_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#&class_name=#class_name#&type=#type#";
	$choose_dao_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=dao&path=#path#";
	$choose_lib_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=lib&path=#path#";
	$choose_vendor_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=vendor&path=#path#";

	$class_id_for_js = addcslashes(preg_replace("/\\+/", "\\", $class_id), '\\'); //must duplicate the back-slashes, otherwise in javascript it will remove it the back-slashes and merge the multiple namespaces of the object class (in case the namespace exists)
	
	$head .= '
	<script>
	var layer_type = "' . ($item_type == "presentation" ? "pres" : ($item_type == "businesslogic" ? "bl" : $item_type)) . '";
	var selected_project_id = "' . $selected_project_id . '";

	var original_class_id = \'' . $class_id_for_js . '\';
	var save_object_url = \'' . $project_url_prefix . 'phpframework/admin/save_file_class?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&path=' . $path . '&item_type=' . $item_type . '&class=#class_id#\';

	var new_use_html = \'' . str_replace("'", "\\'", str_replace("\n", "", WorkFlowPHPFileHandler::getUseHTML())) .'\';
	var new_include_html = \'' . str_replace("'", "\\'", str_replace("\n", "", WorkFlowPHPFileHandler::getInludeHTML())) .'\';
	var new_property_html = \'' . str_replace("'", "\\'", str_replace("\n", "", WorkFlowPHPFileHandler::getPropertyHTML())) .'\';
	';

	$head .= WorkFlowPresentationHandler::getPresentationBrokersHtml($presentation_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url, $upload_bean_layer_files_from_file_manager_url);
	$head .= WorkFlowPresentationHandler::getBusinessLogicBrokersHtml($business_logic_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url);
	$head .= WorkFlowPresentationHandler::getDaoLibAndVendorBrokersHtml($choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $get_file_properties_url);
	$head .= WorkFlowPresentationHandler::getDataAccessBrokersHtml($data_access_brokers, $choose_bean_layer_files_from_file_manager_url);
	$head .= '</script>';
	$head .= LayoutTypeProjectUIHandler::getHeader();

	$main_content = '
		<div class="top_bar' . ($popup ? " in_popup" : "") . '">
			<header>
				<div class="title" title="' . $path . '">
					' . ($class_id ? "Edit" : "Add") . ' Class <span class="class_name"></span> in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($is_class_equal_to_file_name ? dirname($file_path) : $file_path, $obj, $is_class_equal_to_file_name) . '
				</div>
				<ul>
					<li class="save" data-title="Save"><a onClick="saveFileClass({on_success: replaceNewNameInUrl, class_url_attr_name: \'service\'})"><i class="icon save"></i> Save</a></li>
					<li class="sub_menu" onClick="openSubmenu(this)">
						<i class="icon sub_menu"></i>
						<ul>
							<li class="full_screen" title="Maximize/Minimize Editor Screen"><a onclick="toggleFullScreen(this)"><i class="icon full_screen"></i> Maximize Editor Screen</a></li>
							<li class="separator"></li>
							<li class="save" title="Save"><a onClick="saveFileClass({on_success: replaceNewNameInUrl, class_url_attr_name: \'service\'})"><i class="icon save"></i> Save</a></li>
						</ul>
					</li>
				</ul>
			</header>
		</div>';

	$main_content .= WorkFlowPresentationHandler::getChooseFromFileManagerPopupHtml($bean_name, $bean_file_name, $choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $db_brokers, $data_access_brokers, $ibatis_brokers, $hibernate_brokers, $business_logic_brokers, $presentation_brokers);
	
	$get_layer_sub_files_url = str_replace("#bean_name#", $bean_name, str_replace("#bean_file_name#", $bean_file_name, $choose_bean_layer_files_from_file_manager_url));
	$main_content .= WorkFlowPHPFileHandler::getChoosePHPClassFromFileManagerHtml($get_layer_sub_files_url);
	
	$extends = isset($obj_data["extends"]) ? $obj_data["extends"] : null;
	$extends = is_array($extends) ? implode(", ", $extends) : $extends;
	
	$implements = isset($obj_data["implements"]) ? $obj_data["implements"] : null;
	$implements = is_array($implements) ? implode(", ", $implements) : $implements;
	
	$main_content .= '
	<div class="includes_obj file_class_obj with_top_bar_section' . ($popup ? " in_popup" : "") . '">
		<div class="name">
			<label>Name:</label>
			<input type="text" value="' . (isset($obj_data["name"]) ? $obj_data["name"] : "") . '" placeHolder="Class Name" title="Class Name" onFocus="disableTemporaryAutoSaveOnInputFocus(this)" onBlur="undoDisableTemporaryAutoSaveOnInputBlur(this)" />
		</div>
		<div class="extend">
			<label>Extends:</label>
			<input type="text" value="' . $extends . '" />
			<span class="icon search" onClick="getClassFromFileManager(this, \'input\')" title="Get file from File Manager">Search</span>
		</div>
		<div class="implement">
			<label>Implements:</label>
			<input type="text" value="' . $implements . '" />
			<span class="icon search" onClick="getClassFromFileManager(this, \'input\')" title="Get file from File Manager">Search</span>
		</div>
		<div class="abstract">
			<label>Is Abstract:</label>
			<input type="checkbox" value="1" ' . (!empty($obj_data["abstract"]) ? "checked" : "") . ' />
		</div>
		<div class="visibility">
			<label>Is Visible:</label>
			<input type="checkbox" value="1" ' . (empty($is_hidden) ? "checked" : "") . ' />
			<span class="icon info" title="Hide this class from other projects or direct access">Info</span>
		</div>
		<div class="namespace">
			<label>Namespace:</label>
			<input type="text" value="' . (isset($obj_data["namespace"]) ? $obj_data["namespace"] : "") . '" placeHolder="Some\Namespace\Here\If\Apply" />
		</div>
		<div class="uses">
			<label>Uses:</label>
			<span class="icon add" onClick="addNewUse(this)" title="Add Use">Add</span>
			<div class="fields">';

	$uses = isset($obj_data["uses"]) ? $obj_data["uses"] : null;
	if ($uses)
		foreach ($uses as $use => $alias) 
			$main_content .= WorkFlowPHPFileHandler::getUseHTML($use, $alias);

	$main_content .= '
			</div>
		</div>
		<div class="includes">
			<label>Includes:</label>
			<span class="icon add" onClick="addNewInclude(this)" title="Add Include">Add</span>
			<div class="fields">';

	$includes = isset($obj_data["includes"]) ? $obj_data["includes"] : null;
	if ($includes) {
		$t = count($includes);
		for ($i = 0; $i < $t; $i++) {
			$include = $includes[$i];
			
			if ($include && !empty($include[0]))
				$main_content .= WorkFlowPHPFileHandler::getInludeHTML($include);
		}
	}
	
	$main_content .= '
			</div>
		</div>
		<div class="properties">
			<label>Properties:</label>
			<span class="icon add" onClick="addNewProperty(this)" title="Add Property">Add</span>
			<table>
				<thead>
					<tr>
						<th class="name table_header">Name</th>
						<th class="value table_header">Value</th>
						<th class="type table_header">Type</th>
						<th class="static table_header">Static</th>
						<th class="var_type table_header">Var Type</th>
						<th class="comments table_header">Comments</th>
						<th class="icon_cell table_header"><span class="icon add" onClick="addNewProperty(this)" title="Add Property">Add</span></th>
					</tr>
				</thead>
				<tbody class="fields">';

	$properties = isset($obj_data["properties"]) ? $obj_data["properties"] : null;
	if ($properties) {
		$t = count($properties);
		for ($i = 0; $i < $t; $i++) {
			$property = $properties[$i];
			
			$main_content .= WorkFlowPHPFileHandler::getPropertyHTML($property);
		}
	}
	
	$main_content .= '
				</tbody>
			</table>
		</div>
		<div class="comments">
			<label>Comments:</label>
			<textarea>' . (isset($comments) ? htmlspecialchars($comments, ENT_NOQUOTES) : "") . '</textarea>
		</div>
	</div>';
}
else {
	$title = ($class_id ? "Edit" : "Add") . ' Class <span class="class_name">' . $class_id . '</span>';
	
	$main_content = '
		<div class="top_bar' . ($popup ? " in_popup" : "") . '">
			<header>
				<div class="title" title="' . $path . '">' . $title . '</div>
			</header>
		</div>';
	
	$main_content .= '<div class="error">Error: The system couldn\'t detect the selected object. Please refresh and try again...</div>';
}

//$head = $CssAndJSFilesOptimizer->prepareHtmlWithOptimizedCssAndJSFiles($head, false);
?>
