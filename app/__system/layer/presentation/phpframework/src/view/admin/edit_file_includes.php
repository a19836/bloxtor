<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once $EVC->getUtilPath("WorkFlowPresentationHandler");
include $EVC->getUtilPath("BreadCrumbsUIHandler");

$selected_project_id = isset($selected_project_id) ? $selected_project_id : null;
$file_path = isset($file_path) ? $file_path : null;
$obj = isset($obj) ? $obj : null;
$db_brokers = isset($db_brokers) ? $db_brokers : null;
$data_access_brokers = isset($data_access_brokers) ? $data_access_brokers : null;
$ibatis_brokers = isset($ibatis_brokers) ? $ibatis_brokers : null;
$hibernate_brokers = isset($hibernate_brokers) ? $hibernate_brokers : null;
$business_logic_brokers = isset($business_logic_brokers) ? $business_logic_brokers : null;
$presentation_brokers = isset($presentation_brokers) ? $presentation_brokers : null;

$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($filter_by_layout);

$choose_bean_layer_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?bean_name=#bean_name#&bean_file_name=#bean_file_name#$filter_by_layout_url_query&path=#path#";
$upload_bean_layer_files_from_file_manager_url = $project_url_prefix . "admin/upload_file?bean_name=#bean_name#&bean_file_name=#bean_file_name#$filter_by_layout_url_query&path=#path#";
$get_file_properties_url = $project_url_prefix . "phpframework/admin/get_file_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#&class_name=#class_name#&type=#type#";
$choose_dao_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=dao&path=#path#";
$choose_lib_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=lib&path=#path#";
$choose_vendor_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=vendor&path=#path#";

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

<!-- Edit Code JS file -->
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/edit_php_code.js"></script>

<!-- Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/edit_file_includes.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/edit_file_includes.js"></script>

<script>
var layer_type = "' . ($item_type == "presentation" ? "pres" : ($item_type == "businesslogic" ? "bl" : $item_type)) . '";
var selected_project_id = "' . $selected_project_id . '";

var save_object_url = \'' . $project_url_prefix . 'phpframework/admin/save_file_includes?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&path=' . $path . '&item_type=' . $item_type . '\';

var new_use_html = \'' . str_replace("'", "\\'", str_replace("\n", "", WorkFlowPHPFileHandler::getUseHTML())) .'\';
var new_include_html = \'' . str_replace("'", "\\'", str_replace("\n", "", WorkFlowPHPFileHandler::getInludeHTML())) .'\';
';

$head .= WorkFlowPresentationHandler::getPresentationBrokersHtml($presentation_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url, $upload_bean_layer_files_from_file_manager_url);
$head .= WorkFlowPresentationHandler::getBusinessLogicBrokersHtml($business_logic_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url);
$head .= WorkFlowPresentationHandler::getDaoLibAndVendorBrokersHtml($choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $get_file_properties_url);
$head .= WorkFlowPresentationHandler::getDataAccessBrokersHtml($data_access_brokers, $choose_bean_layer_files_from_file_manager_url);
$head .= '</script>';
$head .= LayoutTypeProjectUIHandler::getHeader();

$main_content = '
	<div class="top_bar">
		<header>
			<div class="title" title="' . $path . '">Manage Includes in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($file_path, $obj, true) . '</div>
			<ul>
				<li class="save" data-title="Save"><a onClick="saveIncludes()"><i class="icon save"></i> Save</a></li>
				<li class="sub_menu" onClick="openSubmenu(this)">
					<i class="icon sub_menu"></i>
					<ul>
						<li class="full_screen" title="Maximize/Minimize Editor Screen"><a onclick="toggleFullScreen(this)"><i class="icon full_screen"></i> Maximize Editor Screen</a></li>
						<li class="separator"></li>
						<li class="save" title="Save"><a onClick="saveIncludes()"><i class="icon save"></i> Save</a></li>
					</ul>
				</li>
			</ul>
		</header>
	</div>';

$main_content .= WorkFlowPresentationHandler::getChooseFromFileManagerPopupHtml($bean_name, $bean_file_name, $choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $db_brokers, $data_access_brokers, $ibatis_brokers, $hibernate_brokers, $business_logic_brokers, $presentation_brokers);

$main_content .= '
<div class="includes_obj with_top_bar_section">
	<div class="namespace">
		<label>Namespace:</label>
		<input type="text" value="' . (!empty($obj_data["namespaces"]) ? $obj_data["namespaces"][0] : '') . '" placeHolder="Some\Namespace\Here\If\Apply" />
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
</div>
';

//$head = $CssAndJSFilesOptimizer->prepareHtmlWithOptimizedCssAndJSFiles($head, false);
?>
