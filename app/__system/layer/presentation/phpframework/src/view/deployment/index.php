<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.cms.wordpress.WordPressUrlsParser");
include $EVC->getUtilPath("WorkFlowUIHandler");
include $EVC->getUtilPath("WorkFlowPresentationHandler");

$WorkFlowUIHandler = new WorkFlowUIHandler($WorkFlowTaskHandler, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url);

$SubWorkFlowUIHandler = new WorkFlowUIHandler($SubWorkFlowTaskHandler, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url);

$template_tasks_types_by_tag = array(
	"server" => isset($WorkFlowTaskHandler->getTasksByTag("server")[0]["type"]) ? $WorkFlowTaskHandler->getTasksByTag("server")[0]["type"] : null,
	"presentation" => isset($WorkFlowTaskHandler->getTasksByTag("presentation")[0]["type"]) ? $WorkFlowTaskHandler->getTasksByTag("presentation")[0]["type"] : null,
	"businesslogic" => isset($WorkFlowTaskHandler->getTasksByTag("businesslogic")[0]["type"]) ? $WorkFlowTaskHandler->getTasksByTag("businesslogic")[0]["type"] : null,
	"dataaccess" => isset($WorkFlowTaskHandler->getTasksByTag("dataaccess")[0]["type"]) ? $WorkFlowTaskHandler->getTasksByTag("dataaccess")[0]["type"] : null,
	"db" => isset($WorkFlowTaskHandler->getTasksByTag("db")[0]["type"]) ? $WorkFlowTaskHandler->getTasksByTag("db")[0]["type"] : null,
	"dbdriver" => isset($WorkFlowTaskHandler->getTasksByTag("dbdriver")[0]["type"]) ? $WorkFlowTaskHandler->getTasksByTag("dbdriver")[0]["type"] : null,
);

$choose_bean_layer_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#";
$upload_bean_layer_files_from_file_manager_url = $project_url_prefix . "admin/upload_file?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#";
$choose_dao_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=dao&path=#path#";
$choose_lib_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=lib&path=#path#";
$choose_vendor_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=vendor&path=#path#";
$choose_test_units_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=test_unit&path=#path#";

$validate_template_properties_url = $project_url_prefix . "deployment/validate_template?server=#server#&template_id=#template_id#";
$deploy_template_to_server_url = $project_url_prefix . "deployment/deploy_template_to_server?server=#server#&template_id=#template_id#&deployment_id=#deployment_id#&action=#action#";

$head = $WorkFlowUIHandler->getHeader();
$head .= '
<!-- Add MyTree main JS and CSS files -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/jquerymytree/css/style.min.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerymytree/js/mytree.js"></script>

<!-- Add FileManager JS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/file_manager.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/file_manager.js"></script>

<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add EDIT PHP CODE JS files -->
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/edit_php_code.js"></script>

<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/deployment/index.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/deployment/index.js"></script>';

$head .= $WorkFlowUIHandler->getJS($workflow_path_id);

$head .= '<script>
ServerTaskPropertyObj.template_workflow_html += \'' . str_replace("<script>", "<' + 'script>", str_replace("</script>", "<' + '/script>", str_replace("'", "\\'", str_replace(array("\n", "\r"), "", getTemplateWorklowHtml($SubWorkFlowUIHandler, $project_url_prefix))))) . '\';
ServerTaskPropertyObj.get_layers_tasks_file_url = \'' . $project_url_prefix . 'workflow/get_workflow_file?path=layer\';
ServerTaskPropertyObj.validate_template_properties_url = \'' . $validate_template_properties_url . '\';
ServerTaskPropertyObj.deploy_template_to_server_url = \'' . $deploy_template_to_server_url . '\';
ServerTaskPropertyObj.template_tasks_types_by_tag = ' . (isset($template_tasks_types_by_tag) ? json_encode($template_tasks_types_by_tag) : "null") . ';
ServerTaskPropertyObj.server_time_diff_in_milliseconds = (' . time() . ' * 1000) - (new Date()).getTime();
ServerTaskPropertyObj.on_choose_template_flow_layer_file_callback = onChooseTemplateTaskLayerFile;
ServerTaskPropertyObj.on_choose_template_file_callback = onChooseTemplateFile;
ServerTaskPropertyObj.on_get_layer_wordpress_installations_url_callback = onGetLayerWordPressInstallationsUrl;
ServerTaskPropertyObj.on_choose_test_units_callback = onChooseTemplateActionTestUnit;
ServerTaskPropertyObj.on_open_server_properties_popup_callback = onOpenServerPropertiesPopup;
ServerTaskPropertyObj.on_close_server_properties_popup_callback = onCloseServerPropertiesPopup;
ServerTaskPropertyObj.show_php_obfuscation_option = ' . ($show_php_obfuscation_option ? "true" : "false") . ';
ServerTaskPropertyObj.show_js_obfuscation_option = ' . ($show_js_obfuscation_option ? "true" : "false") . ';
ServerTaskPropertyObj.projects_max_expiration_date = "' . $projects_max_expiration_date . '";
ServerTaskPropertyObj.sysadmin_max_expiration_date = "' . $sysadmin_max_expiration_date . '";
ServerTaskPropertyObj.projects_max_num = "' . $projects_max_num . '";
ServerTaskPropertyObj.users_max_num = "' . $users_max_num . '";
ServerTaskPropertyObj.end_users_max_num = "' . $end_users_max_num . '";
ServerTaskPropertyObj.actions_max_num = "' . $actions_max_num . '";
ServerTaskPropertyObj.allowed_paths = "' . $allowed_paths . '";
ServerTaskPropertyObj.allowed_domains = "' . $allowed_domains . '";
ServerTaskPropertyObj.check_allowed_domains_port = ' . ($check_allowed_domains_port ? "true" : "false") . ';
ServerTaskPropertyObj.allowed_sysadmin_migration = ' . ($allowed_sysadmin_migration ? "true" : "false") . ';

var wordpress_installations_relative_path = "' . $EVC->getCommonProjectName() . '/webroot/' . WordPressUrlsParser::WORDPRESS_FOLDER_PREFIX . '/";

var beans_folders_name = ' . json_encode($beans_folders_name) . ';
';
$head .= WorkFlowPresentationHandler::getPresentationBrokersHtml($presentation_brokers, $choose_bean_layer_files_from_file_manager_url, "", $upload_bean_layer_files_from_file_manager_url);
$head .= WorkFlowPresentationHandler::getBusinessLogicBrokersHtml($business_logic_brokers, $choose_bean_layer_files_from_file_manager_url, "");
$head .= WorkFlowPresentationHandler::getDataAccessBrokersHtml($data_access_brokers, $choose_bean_layer_files_from_file_manager_url);
//$head .= WorkFlowPresentationHandler::getDaoLibAndVendorBrokersHtml($choose_test_units_files_from_file_manager_url, "", "", "");
$head .= WorkFlowPresentationHandler::getDaoLibAndVendorBrokersHtml($choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, "");

//Be sure all nodes have the attribute: file_path otherwise the chooseTemplateTaskLayerFile function won't work!
foreach ($layer_brokers_settings as $k => $layer_brokers)
	if ($k == "data_access_brokers" || $k == "business_logic_brokers" || $k == "presentation_brokers") {
		$t = count($layer_brokers);
		
		for ($i = 0; $i < $t; $i++) {
			$l = $layer_brokers[$i];
			
			if (!empty($l[2])) {
				$head .= '
				if (main_layers_properties.' . $l[2] . ') {
					if (!main_layers_properties.' . $l[2] . '.ui.folder.hasOwnProperty("attributes"))
						main_layers_properties.' . $l[2] . '.ui.folder.attributes = {};
					
					if (!main_layers_properties.' . $l[2] . '.ui.folder.attributes.hasOwnProperty("file_path"))
						main_layers_properties.' . $l[2] . '.ui.folder.attributes.file_path = "#path#";
					
					if (!main_layers_properties.' . $l[2] . '.ui.cms_common.hasOwnProperty("attributes"))
						main_layers_properties.' . $l[2] . '.ui.cms_common.attributes = {};
					
					if (!main_layers_properties.' . $l[2] . '.ui.cms_common.attributes.hasOwnProperty("file_path"))
						main_layers_properties.' . $l[2] . '.ui.cms_common.attributes.file_path = "#path#";
					
					if (!main_layers_properties.' . $l[2] . '.ui.cms_module.hasOwnProperty("attributes"))
						main_layers_properties.' . $l[2] . '.ui.cms_module.attributes = {};
					
					if (!main_layers_properties.' . $l[2] . '.ui.cms_module.attributes.hasOwnProperty("file_path"))
						main_layers_properties.' . $l[2] . '.ui.cms_module.attributes.file_path = "#path#";
					
					if (!main_layers_properties.' . $l[2] . '.ui.cms_program.hasOwnProperty("attributes"))
						main_layers_properties.' . $l[2] . '.ui.cms_program.attributes = {};
					
					if (!main_layers_properties.' . $l[2] . '.ui.cms_program.attributes.hasOwnProperty("file_path"))
						main_layers_properties.' . $l[2] . '.ui.cms_program.attributes.file_path = "#path#";
					
					if (!main_layers_properties.' . $l[2] . '.ui.cms_resource.hasOwnProperty("attributes"))
						main_layers_properties.' . $l[2] . '.ui.cms_resource.attributes = {};
					
					if (!main_layers_properties.' . $l[2] . '.ui.cms_resource.attributes.hasOwnProperty("file_path"))
						main_layers_properties.' . $l[2] . '.ui.cms_resource.attributes.file_path = "#path#";';
			
				if ($k == "presentation_brokers")
					$head .= '
					if (!main_layers_properties.' . $l[2] . '.ui.project_folder.hasOwnProperty("attributes"))
						main_layers_properties.' . $l[2] . '.ui.project_folder.attributes = {};
					
					if (!main_layers_properties.' . $l[2] . '.ui.project_folder.attributes.hasOwnProperty("file_path"))
						main_layers_properties.' . $l[2] . '.ui.project_folder.attributes.file_path = "#path#";
					
					if (!main_layers_properties.' . $l[2] . '.ui.project.hasOwnProperty("attributes"))
						main_layers_properties.' . $l[2] . '.ui.project.attributes = {};
					
					if (!main_layers_properties.' . $l[2] . '.ui.project.attributes.hasOwnProperty("file_path"))
						main_layers_properties.' . $l[2] . '.ui.project.attributes.file_path = "#path#";';
			
				$head .= '
				}';
			}
		}
	}

$head .= '</script>';

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
	"Add new Server" => array(
		"class" => "add_new_server", 
		"html" => '<a onClick="addNewServer();return false;"><i class="icon add"></i> Add new Server</a>',
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
		"html" => '<a onClick="return saveDeploymentDiagram();"><i class="icon save"></i> Save</a>',
	),
);
$WorkFlowUIHandler->setMenus($menus);

$main_content = '
	<div class="top_bar">
		<header>
			<div class="title">Deployment</div>
			<ul>
				<li class="add_server" data-title="Add new Server"><a onClick="addNewServer();return false;"><i class="icon server"></i> Add new Server</a>
				<li class="save" data-title="Save"><a onClick="saveDeploymentDiagram()"><i class="icon save"></i> Save</a></li>
			</ul>
		</header>
	</div>
	
	<div id="choose_template_task_layer_file_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
		<div class="title">Choose a File</div>
		<div class="broker">
			<label>Broker:</label>
			<select onChange="updateTemplateTaskLayerUrlFileManager(this)">';

foreach ($layer_brokers_settings as $k => $layer_brokers)
	if ($k == "data_access_brokers" || $k == "business_logic_brokers" || $k == "presentation_brokers") {
		$t = count($layer_brokers);
		
		for ($i = 0; $i < $t; $i++) {
			$l = $layer_brokers[$i];
			$layer_name = isset($l[0]) ? $l[0] : null;
			$bean_file_name = isset($l[1]) ? $l[1] : null;
			$bean_name = isset($l[2]) ? $l[2] : null;
			$url = str_replace("#bean_file_name#", $bean_file_name, str_replace("#bean_name#", $bean_name, $choose_bean_layer_files_from_file_manager_url));
			$url .= $k == "presentation_brokers" ? "&item_type=presentation" : "";
			
			$main_content .= '<option url="' . $url . '">' . strtolower($layer_name) . '</option>';
		}
	}

$main_content .= '
			</select>
		</div>
		<ul class="mytree">
			<li>
				<label>Layer Root</label>
				<ul></ul>
			</li>
		</ul>
		<div class="button">
			<input type="button" value="Update" onClick="MyDeploymentUIFancyPopup.settings.updateFunction(this)" />
		</div>
	</div>';

$main_content .= '
	<div id="choose_test_units_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
		<div class="title">Choose a Test Unit</div>
		<ul class="mytree">
			<li>
				<label>Test Units</label>
				<ul url="' . $choose_test_units_files_from_file_manager_url . '"></ul>
			</li>
		</ul>
		<div class="button">
			<input type="button" value="Update" onClick="MyDeploymentUIFancyPopup.settings.updateFunction(this)" />
		</div>
	</div>';

$main_content .= WorkFlowPresentationHandler::getChooseFromFileManagerPopupHtml(null, null, $choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $db_brokers, $data_access_brokers, $ibatis_brokers, $hibernate_brokers, $business_logic_brokers, $presentation_brokers);

$main_content .= $WorkFlowUIHandler->getContent();
$main_content .= '<div class="loading_panel"></div>';

function getTemplateWorklowHtml($SubWorkFlowUIHandler, $project_url_prefix) {
	$menus = array(
		"Set Global Vars" => array(
			"class" => "set_global_vars", 
			"html" => '<a onClick="return ServerTaskPropertyObj.openTemplateGlobalVarsOrSettingsPopup(this, \'' . $project_url_prefix . 'phpframework/layer/list_global_vars?popup=1\');"><i class="icon global_vars"></i> Globar Vars</a>',
		),
		"Set Global Settings" => array(
			"class" => "set_global_settings",
			"html" => '<a onClick="return ServerTaskPropertyObj.openTemplateGlobalVarsOrSettingsPopup(this, \'' . $project_url_prefix . 'phpframework/layer/list_global_settings?popup=1&deployment=1\');"><i class="icon global_settings"></i> Global Settings</a>',
		),
	);
	$SubWorkFlowUIHandler->setMenus($menus);
	
	$html = $SubWorkFlowUIHandler->getContent("taskflowchart_#rand#");
	
	return $html;
}
?>
