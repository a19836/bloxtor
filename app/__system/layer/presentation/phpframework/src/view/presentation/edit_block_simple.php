<?php
include_once $EVC->getUtilPath("WorkFlowPresentationHandler"); //must be include_once bc the workflow module calls the WorkFlowPresentationHandler too, and if is not include_once, it will give a php error bc we are including the WorkFlowPresentationHandler twice.
include_once $EVC->getUtilPath("BreadCrumbsUIHandler");

$file_path = isset($file_path) ? $file_path : null;
$P = isset($P) ? $P : null;
$PEVC = isset($PEVC) ? $PEVC : null;
$module_id = isset($module_id) ? $module_id : null;
$presentation_brokers = isset($presentation_brokers) ? $presentation_brokers : null;
$block_settings = isset($block_settings) ? $block_settings : null;
$block_join_points = isset($block_join_points) ? $block_join_points : null;
$block_local_join_points = isset($block_local_join_points) ? $block_local_join_points : null;

$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($filter_by_layout);

//prepare some default configurations. This configurations can be changed from the edit_page_module_block too.
$query_string = isset($_SERVER["QUERY_STRING"]) ? preg_replace("/dont_save_cookie=([^&])*/", "", str_replace(array("&edit_block_type=advanced", "&edit_block_type=simple"), "", $_SERVER["QUERY_STRING"])) : "";
$title = isset($title) ? $title : 'Edit Block (Visual Workspace): ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($file_path, $P, true);
$title_icons = isset($title_icons) ? $title_icons : '<li class="show_advanced_ui" title="Switch to Code Workspace"><a href="?' . $query_string . '&edit_block_type=advanced"><i class="icon show_advanced_ui"></i> Switch to Code Workspace</a></li>';
$save_url = !empty($save_url) ? $save_url : $project_url_prefix . "phpframework/presentation/save_block_simple?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path";

$call_module_file_prefix_url = $project_url_prefix . "phpframework/module/" . $module_id . "/#module_file_path#?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path";
$call_common_module_file_prefix_url = $project_common_url_prefix . "module/common/#module_file_path#?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path";
$get_block_handler_source_code_url = $project_url_prefix . "phpframework/presentation/get_module_handler_source_code?bean_name=$bean_name&bean_file_name=$bean_file_name&project=$path&block=#block#";
$module_admin_panel_url = !empty($module_group_id) ? $project_url_prefix . "/module/" . $module_group_id . "/admin/?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path" : "";

$presentation_project_webroot_url = getPresentationProjectWebrootUrl($PEVC, $user_global_variables_file_path);
$presentation_project_common_webroot_url = getPresentationProjectCommonWebrootUrl($PEVC, $user_global_variables_file_path);

$choose_bean_layer_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?bean_name=#bean_name#&bean_file_name=#bean_file_name#$filter_by_layout_url_query&path=#path#";
$upload_bean_layer_files_from_file_manager_url = $project_url_prefix . "admin/upload_file?bean_name=#bean_name#&bean_file_name=#bean_file_name#$filter_by_layout_url_query&path=#path#";
$choose_dao_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=dao&path=#path#";
$choose_lib_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=lib&path=#path#";
$choose_vendor_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=vendor&path=#path#";

$get_file_properties_url = $project_url_prefix . "phpframework/admin/get_file_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#&class_name=#class_name#&type=#type#";

$templates_regions_html_url = $project_url_prefix . "phpframework/presentation/templates_regions_html?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path"; //used in widget: src/view/presentation/common_editor_widget/template_region/import_region_html.xml which is used in the Layout_ui_editor from the taskflowchart->inlinehtml and createform tasks, like in the workflow module.

//echo "<pre>";print_r($block_settings);echo "</pre>";die();
$block_settings_obj = CMSPresentationLayerJoinPointsUIHandler::convertBlockSettingsArrayToObj($block_settings);
//echo "<pre>";print_r($block_settings_obj);echo "</pre>";die();
//echo "<pre>" . json_encode($block_settings_obj) . "</pre>";die();

$head = '
<!-- Add MD5 JS Files -->
<script type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquery/js/jquery.md5.js"></script>

<!-- Add MyTree main JS and CSS files -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/jquerymytree/css/style.min.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerymytree/js/mytree.js"></script>

<!-- Add FileManager JS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/file_manager.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/file_manager.js"></script>

<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS and JS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/layout.js"></script>

<!-- Add PHP CODE CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/edit_php_code.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/edit_php_code.js"></script>

<!-- Add PHPJS Functions -->
<script type="text/javascript" src="' . $project_common_url_prefix . 'vendor/phpjs/functions/strings/parse_str.js"></script>
<script type="text/javascript" src="' . $project_common_url_prefix . 'vendor/phpjs/functions/strings/stripslashes.js"></script>
<script type="text/javascript" src="' . $project_common_url_prefix . 'vendor/phpjs/functions/strings/addcslashes.js"></script>

<!-- Add local Responsive Iframe CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/responsive_iframe.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/responsive_iframe.js"></script>

<!-- Add local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/edit_block_simple.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/edit_block_simple.js"></script>

<!-- Add Join Point CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/module_join_points.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/module_join_points.js"></script>

<script>
' . WorkFlowBrokersSelectedDBVarsHandler::printSelectedDBVarsJavascriptCode($project_url_prefix, $bean_name, $bean_file_name, isset($selected_db_vars) ? $selected_db_vars : null) . '
var layer_type = "pres";
var selected_project_id = "' . (isset($selected_project_id) ? $selected_project_id : null) . '";
var file_modified_time = ' . (isset($file_modified_time) ? $file_modified_time : "null") . '; //for version control

var save_object_url = \'' . $save_url . '\';
var call_module_file_prefix_url = \'' . $call_module_file_prefix_url . '\';
var call_common_module_file_prefix_url = \'' . $call_common_module_file_prefix_url . '\';
var get_block_handler_source_code_url = \'' . $get_block_handler_source_code_url . '\';
var module_admin_panel_url = \'' . $module_admin_panel_url . '\';
var presentation_project_webroot_url = \'' . $presentation_project_webroot_url . '\';
var presentation_project_common_webroot_url = \'' . $presentation_project_common_webroot_url . '\';
var system_project_webroot_url = \'' . $project_url_prefix . '\';
var system_project_common_webroot_url = \'' . $project_common_url_prefix . '\';

var templates_regions_html_url = \'' . $templates_regions_html_url . '\';  //used in widget: src/view/presentation/common_editor_widget/template_region/import_region_html.xml and in the Layout_ui_editor from the taskflowchart->inlinehtml and createform tasks, for the workflow module.

var selected_project_url_prefix = \'' . $selected_project_url_prefix . '\';
var selected_project_common_url_prefix = \'' . $selected_project_common_url_prefix . '\';

var block_settings_obj = ' . (isset($block_settings_obj) ? json_encode($block_settings_obj) : "null") . ';
var brokers_db_drivers = ' . (isset($brokers_db_drivers) ? json_encode($brokers_db_drivers) : "null") . ';
var load_module_settings_function = null;
var is_popup = ' . ($popup ? 1 : 0) . ';
';

$head .= WorkFlowPresentationHandler::getPresentationBrokersHtml($presentation_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url, $upload_bean_layer_files_from_file_manager_url);
$head .= WorkFlowPresentationHandler::getDaoLibAndVendorBrokersHtml($choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $get_file_properties_url);

$head .= '</script>';

$head .= CMSPresentationLayerJoinPointsUIHandler::getHeader();
$head .= LayoutTypeProjectUIHandler::getHeader();

$main_content = '
	<div class="top_bar' . ($popup ? " in_popup" : "") . '">
		<header>
			<div class="title" title="' . $path . '">' . $title . '</div>
			<ul>
				<li class="save" data-title="Save Block"><a onClick="saveBlock()"><i class="icon save"></i> Save</a></li>
				<li class="sub_menu" onclick="openSubmenu(this)">
					<i class="icon sub_menu"></i>
					<ul>
						' . $title_icons . '
						<li class="separator"></li>
						<li class="toggle_module_data" title="Toggle Module Info"><a class="toggle_icon" onClick="showOrHideModuleData(this)"><i class="icon toggle_module_data"></i> <span>Show Module Info</span> <input type="checkbox" /></a></li>
						<li class="separator"></li>
						<li class="full_screen" title="Maximize/Minimize Editor Screen"><a onClick="toggleFullScreen(this)"><i class="icon full_screen"></i> Maximize Editor Screen</a></li>
						<li class="separator"></li>
						<li class="save" title="Save Block"><a onClick="saveBlock()"><i class="icon save"></i> Save</a></li>
					</ul>
				</li>
			</ul>
		</header>
	</div>';

if (!empty($module)) {
	if (empty($module["enabled"]))
		$main_content .='<div class="invalid">Warning: This module is currently DISABLED!</div>';
	
	if (!empty($hard_coded))
		$main_content .='<div class="invalid">Alert: The system detected that the block id is different than the current file name. We advise you to edit this file with the Advanced UI, otherwise you may overwrite other people\'s changes...</div>';
	
	$main_content .= WorkFlowPresentationHandler::getChooseFromFileManagerPopupHtml($bean_name, $bean_file_name, $choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, null, null, null, null, null, $presentation_brokers);
	
	$image = '<span class="no_photo">No Photo</span>';
	
	if (!empty($module["images"][0]["url"])) {
		if (preg_match("/\.svg$/i", $module["images"][0]["url"]) && !empty($module["images"][0]["path"]) && file_exists($module["images"][0]["path"]))
			$image = file_get_contents($module["images"][0]["path"]);
		else
			$image = '<img src="' . $module["images"][0]["url"] . '" />';
	}
	
	$main_content .= '
	<div class="block_obj' . (!$popup ? " with_top_bar_section" : "") . '">
		<div class="module_data">
			<input type="hidden" name="module_id" value="' . $module_id . '" />
			<div class="module_img">' . $image . '</div>
			<div class="module_label">' . (isset($module["label"]) ? $module["label"] : "") . '</div>
			<div class="module_description">
				' . (isset($module["description"]) ? str_replace("\n", "<br>", $module["description"]) : "") . '
				' . (!empty($exists_admin_panel) ? '<a class="open_module_admin_panel_popup" href="javascript:void(0)" onClick="openModuleAdminPanelPopup()">Open ' . (isset($module["group_id"]) ? $module["group_id"] : "") . ' admin panel</a>' : '') . '
			</div>
		</div>';
	
	if (!empty($module["settings_html"])) 
		$main_content .= '
			<div class="module_settings">
				<label>Module\'s Settings:</label>
				<div class="settings">
					' . (isset($module["settings_html"]) ? $module["settings_html"] : "") . '
				</div>
			</div>';
	
	$main_content .= CMSPresentationLayerJoinPointsUIHandler::getBlockJoinPointsJavascriptObjs($block_join_points, $block_local_join_points);
	$main_content .= CMSPresentationLayerJoinPointsUIHandler::getBlockJoinPointsHtml(isset($module["join_points"]) ? $module["join_points"] : null, $block_id, empty($obj_data["code"]), isset($module["module_handler_impl_file_path"]) ? $module["module_handler_impl_file_path"] : null);
	
	$main_content .= '
		<script>
			load_module_settings_function = ' . (!empty($module["load_module_settings_js_function"]) ? $module["load_module_settings_js_function"] : 'null') . ';
		</script>
	</div>';
}
else 
	$main_content .= '<div class="error">Error: The system couldn\'t detect the correspondent block\'s module. Please fix this on the advacend UI</div>';

//$head = $CssAndJSFilesOptimizer->prepareHtmlWithOptimizedCssAndJSFiles($head, false);

function getPresentationProjectWebrootUrl($EVC, $user_global_variables_file_path) {
	$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $EVC->getConfigPath("pre_init_config")));
	$PHPVariablesFileHandler->startUserGlobalVariables();
	
	include $EVC->getConfigPath("config");
	$url = $project_url_prefix;
	
	$PHPVariablesFileHandler->endUserGlobalVariables();
	
	return $url;
}
function getPresentationProjectCommonWebrootUrl($EVC, $user_global_variables_file_path) {
	$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $EVC->getConfigPath("pre_init_config")));
	$PHPVariablesFileHandler->startUserGlobalVariables();
		
	include $EVC->getConfigPath("config");
	$url = $project_common_url_prefix;
	
	$PHPVariablesFileHandler->endUserGlobalVariables();
	
	return $url;
}
?>
