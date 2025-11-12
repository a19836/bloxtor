<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include $EVC->getUtilPath("WorkFlowUIHandler");

$WorkFlowUIHandler = new WorkFlowUIHandler($WorkFlowTaskHandler, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url);
$WorkFlowUIHandler->setTasksOrderByTag($tasks_order_by_tag);

$head = $WorkFlowUIHandler->getHeader();
$head .= '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local JS and CSS files -->
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/layer/diagram.js"></script>
';
$head .= $WorkFlowUIHandler->getJS($workflow_path_id);
$head .= '<link rel="stylesheet" href="' . $project_url_prefix . 'css/layer/diagram.css" type="text/css" charset="utf-8" />';

$menus = array(
	"Flush Cache" => array(
		"class" => "flush_cache", 
		"html" => '<a onClick="return flushCache();"><i class="icon flush_cache"></i> Flush Cache</a>',
	),
	0 => array(
		"class" => "separator",
		"title" => " ", 
		"html" => " ", 
	),
	"Set Global Vars" => array(
		"class" => "set_global_vars", 
		"html" => '<a onClick="return openGlobalSettingsAndVarsPopup(\'' . $project_url_prefix . 'phpframework/layer/list_global_vars?popup=1\', {onOpen: onOpenGlobalSettingsAndVars});"><i class="icon global_vars"></i> Globar Vars</a>',
	),
	"Set Global Settings" => array(
		"class" => "set_global_settings", 
		"html" => '<a onClick="return openGlobalSettingsAndVarsPopup(\'' . $project_url_prefix . 'phpframework/layer/list_global_settings?popup=1\', {onOpen: onOpenGlobalSettingsAndVars});"><i class="icon global_settings"></i> Global Settings</a>',
	),
	1 => array(
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
	2 => array(
		"class" => "separator",
		"title" => " ", 
		"html" => " ", 
	),
	"Layers Settings" => array(
		"class" => "layers_settings",
		"html" => '<a onClick="javascript:void(0)"><i class="icon layers_settings"></i> Layers Settings</a>',
		"childs" => array(
			"Expand Presentation Layer" => array(
				"class" => "expand_layer", 
				"html" => '<a onClick="taskFlowChartObj.Container.changeContainerSize(\'layer_presentations\', 0, $(\'.tasks_flow #layer_presentations\').height() + 150);return false;"><i class="icon maximize"></i> Expand Presentation Layer</a>',
			),
			"Shrink Presentation Layer" => array(
				"class" => "shrink_layer", 
				"html" => '<a onClick="taskFlowChartObj.Container.changeContainerSize(\'layer_presentations\', 0, $(\'.tasks_flow #layer_presentations\').height() - 150);return false;"><i class="icon minimize"></i> Shrink Presentation Layer</a>',
			),
			
			"Expand Business-Logic Layer" => array(
				"class" => "expand_layer", 
				"html" => '<a onClick="taskFlowChartObj.Container.changeContainerSize(\'layer_bls\', 0, $(\'.tasks_flow #layer_bls\').height() + 150);return false;"><i class="icon maximize"></i> Expand Business-Logic Layer</a>',
			),
			"Shrink Business-Logic Layer" => array(
				"class" => "shrink_layer", 
				"html" => '<a onClick="taskFlowChartObj.Container.changeContainerSize(\'layer_bls\', 0, $(\'.tasks_flow #layer_bls\').height() - 150);return false;"><i class="icon minimize"></i> Shrink Business-Logic Layer</a>',
			),
			
			"Expand Data-Access Layer" => array(
				"class" => "expand_layer", 
				"html" => '<a onClick="taskFlowChartObj.Container.changeContainerSize(\'layer_dals\', 0, $(\'.tasks_flow #layer_dals\').height() + 150);return false;"><i class="icon maximize"></i> Expand Data-Access Layer</a>',
			),
			"Shrink Data-Access Layer" => array(
				"class" => "shrink_layer", 
				"html" => '<a onClick="taskFlowChartObj.Container.changeContainerSize(\'layer_dals\', 0, $(\'.tasks_flow #layer_dals\').height() - 150);return false;"><i class="icon minimize"></i> Shrink Data-Access Layer</a>',
			),
			
			"Expand DB Layer" => array(
				"class" => "expand_layer", 
				"html" => '<a onClick="taskFlowChartObj.Container.changeContainerSize(\'layer_dbs\', 0, $(\'.tasks_flow #layer_dbs\').height() + 150);return false;"><i class="icon maximize"></i> Expand DB Layer</a>',
			),
			"Shrink DB Layer" => array(
				"class" => "shrink_layer", 
				"html" => '<a onClick="taskFlowChartObj.Container.changeContainerSize(\'layer_dbs\', 0, $(\'.tasks_flow #layer_dbs\').height() - 150);return false;"><i class="icon minimize"></i> Shrink DB Layer</a>',
			),
			
			"Expand DB-Drivers Layer" => array(
				"class" => "expand_layer", 
				"html" => '<a onClick="taskFlowChartObj.Container.changeContainerSize(\'layer_drivers\', 0, $(\'.tasks_flow #layer_drivers\').height() + 150);return false;"><i class="icon maximize"></i> Expand DB-Drivers Layer</a>',
			),
			"Shrink DB-Drivers Layer" => array(
				"class" => "shrink_layer", 
				"html" => '<a onClick="taskFlowChartObj.Container.changeContainerSize(\'layer_drivers\', 0, $(\'.tasks_flow #layer_drivers\').height() - 150);return false;"><i class="icon minimize"></i> Shrink DB-Drivers Layer</a>',
			),
			
		)
	),
	3 => array(
		"class" => "separator",
		"title" => " ", 
		"html" => " ", 
	),
	"Disable Layers Name Auto-Normalization" => array(
		"class" => "layers_name_auto_normalization", 
		"html" => '<a onClick="return toggleLayersNameAutoNormalization(this);"><i class="icon layers_name_auto_normalization"></i> <span>Disable</span> Layers Name Auto-Normalization <input type="checkbox"/></a>',
	),
	4 => array(
		"class" => "separator",
		"title" => " ", 
		"html" => " ", 
	),
	"Manage User Layers Permissions" => array(
		"class" => "manage_user_type_permissions", 
		"html" => '<a href="' . $project_url_prefix . 'user/manage_user_type_permissions"><i class="icon manage_user_type_permissions"></i> Manage User Layers Permissions</a>',
	),
	5 => array(
		"class" => "separator",
		"title" => " ", 
		"html" => " ", 
	),
	"Maximize/Minimize Editor Screen" => array(
		"class" => "tasks_flow_full_screen", 
		"html" => '<a onClick="toggleFullScreen(this);return false;"><i class="icon full_screen"></i> Maximize Editor Screen</a>',
	),
	6 => array(
		"class" => "separator",
		"title" => " ", 
		"html" => " ", 
	),
	"Save" => array(
		"class" => "save", 
		"html" => '<a onClick="return saveLayersDiagram();"><i class="icon save"></i> Save</a>',
	),
);
$WorkFlowUIHandler->setMenus($menus);

$main_content = '
	<div class="top_bar">
		<header>
			<div class="title">Layers Diagram</div>
			<ul>
				<li class="save" data-title="Save and Rebuild"><a onClick="saveLayersDiagram()"><i class="icon save"></i> Save</a></li>
			</ul>
		</header>
	</div>';
$main_content .= $WorkFlowUIHandler->getContent();
$main_content .= '
<script>
	$(".tasks_flow #layer_presentations").html("<span class=\"layer_title\">Presentation Layers</span>");
	$(".tasks_flow #layer_bls").html("<span class=\"layer_title\">Business Logic Layers</span>");
	$(".tasks_flow #layer_dals").html("<span class=\"layer_title\">Data-Access Layers (SQL)</span>");
	$(".tasks_flow #layer_dbs").html("<span class=\"layer_title\">DB Layers</span>");
	$(".tasks_flow #layer_drivers").html("<span class=\"layer_title\">DB Drivers</span>");
	
	//allow_connections_to_multiple_levels = false; //allow connections to only 1 level below.
	
	taskFlowChartObj.TaskFlow.default_connection_line_width = 2;
	taskFlowChartObj.TaskFlow.default_connection_from_target = true;
	//taskFlowChartObj.TaskFlow.default_connection_z_index = 11; //done in css only on hover
	
	//add default function to reset the top positon of the tasksflow panels, if with_top_bar class exists
	taskFlowChartObj.setTaskFlowChartObjOption("on_resize_panels_function", onResizeTaskFlowChartPanels);
</script>';
?>
