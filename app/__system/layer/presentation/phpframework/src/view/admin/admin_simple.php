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

include_once $EVC->getUtilPath("AdminMenuUIHandler");
include_once $EVC->getUtilPath("TourGuideUIHandler");
include_once $EVC->getUtilPath("HeatMapHandler");

$filter_by_layout = isset($filter_by_layout) ? $filter_by_layout : null;
$filter_by_layout_permission = isset($filter_by_layout_permission) ? $filter_by_layout_permission : null;
$project = isset($project) ? $project : null;
$presentation_projects_by_layer_label_and_folders = isset($presentation_projects_by_layer_label_and_folders) ? $presentation_projects_by_layer_label_and_folders : null;

$logged_name = $UserAuthenticationHandler->auth["user_data"]["name"] ? $UserAuthenticationHandler->auth["user_data"]["name"] : $UserAuthenticationHandler->auth["user_data"]["username"];
$logged_name_initials = explode(" ", $logged_name);
$logged_name_initials = strtoupper(substr($logged_name_initials[0], 0, 1) . (isset($logged_name_initials[1]) ? substr($logged_name_initials[1], 0, 1) : ""));

$filter_by_layout_url_query = $filter_by_layout ? "&filter_by_layout=$filter_by_layout&filter_by_layout_permission=$filter_by_layout_permission" : "";
$admin_home_project_page_url = $project_url_prefix . "admin/admin_home_project?filter_by_layout=#filter_by_layout#";
$admin_home_projects_page_url = "";
$notifications_url = $project_url_prefix . "admin/get_notifications";

$head = AdminMenuUIHandler::getHeader($project_url_prefix, $project_common_url_prefix);
$head .= '
<!-- Add Admin Advanced JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/admin_advanced.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/admin_advanced.js"></script>

<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/admin_simple.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/admin_simple.js"></script>

<script>
var admin_home_project_page_url = "' . $admin_home_project_page_url . '";
var admin_home_projects_page_url = "' . $admin_home_projects_page_url . '";
var notifications_url = "' . $notifications_url . '";
</script>
';
$head .= HeatMapHandler::getHtml($project_url_prefix);

$main_content = '
<div id="top_panel">
	<ul class="left">
		<li class="logo"><a href="' . $project_url_prefix . '"></a></li>
	</ul>
	<ul class="center">
		<li class="sub_menu filter_by_layout" data-title="Selected project" current_selected_project="' . $filter_by_layout . '">
			<!--label>Project: </label-->
			<span class="selected_project" onClick="openFilterByLayoutSubMenu(this)">
				<span>' . ($filter_by_layout ? basename($filter_by_layout) : '') . '</span>
				<i class="icon dropdown_arrow"></i>
			</span>
			
			<ul>
				<div class="triangle_up"></div>
				
				<li class="scroll">
					<ul>
						<li class="label"><a>Select a Project:</a></li>';
	
	$selected_project_name = "";
	$is_single_presentation_layer = is_array($presentation_projects_by_layer_label_and_folders) && count($presentation_projects_by_layer_label_and_folders) == 1;
	
	foreach ($presentation_projects_by_layer_label_and_folders as $layer_label => $projs) {
		if (!$is_single_presentation_layer) //only show presentation layer if is not the only one.
			$main_content .= '<li class="projects_group">
							<a><i class="icon project_folder"></i> <span>' . $layer_label . '</span></a>
							<ul>';
		
		$layer_bean_folder_name = isset($presentation_bean_folder_name_by_layer_label[$layer_label]) ? $presentation_bean_folder_name_by_layer_label[$layer_label] : null;
		$main_content .= getProjectsHtml($projs, $filter_by_layout, $layer_bean_folder_name . "/" . $EVC->getCommonProjectName());
		
		if (!$is_single_presentation_layer) //only show presentation layer if is not the only one.
			$main_content .= '	</ul>
						</li>';
		
		if ($filter_by_layout && !empty($presentation_projects_by_layer_label[$layer_label][$filter_by_layout]))
			$selected_project_name = $presentation_projects_by_layer_label[$layer_label][$filter_by_layout];
	}

	foreach ($non_projects_layout_types as $lname => $lid)
		$main_content .= '<li class="project' . ($filter_by_layout == $lname ? ' selected' : '') . '">
							<a value="' . $lname . '" onClick="filterByLayout(this)"><i class="icon project"></i> <span>' . $lname . '</span></a>
						</li>';
	
	$main_content .= '	</ul>
				</li>	
			</ul>
			
			<!--span class="icon project" onClick="chooseAvailableProject(\'' . $project_url_prefix . 'admin/choose_available_project?redirect_path=admin&popup=1\');" data-title="' . ($selected_project_name ? 'Selected Project: \'' . $selected_project_name . '\'. ' : '') . 'Please click here to choose another project."></span-->
			<!--a class="got_to_project_home" onClick="goTo(this, \'url\', event)" url="' . $project_url_prefix . 'admin/admin_home_project?filter_by_layout=' . $filter_by_layout . '" data-title="Go to project homepage"><span class="icon project_home"></span></a-->
		</li>
		
		' . (!empty($layers["presentation_layers"]) ? '
		<li class="separator">|</li>
		<li class="pages link" onClick="goTo(this, \'url\', event)" url="' . str_replace("#filter_by_layout#", $filter_by_layout, $admin_home_project_page_url) . '">Pages</li>' : '') . '
	</ul>
	<ul class="right">
		<li class="icon go_back" onClick="goBack()" data-title="Go Back"></li>
		<li class="icon go_forward" onClick="goForward()" data-title="Go Forward"></li>
		<li class="separator">|</li>
		
		' . (!empty($is_flush_cache_allowed) ? '<li class="icon flush_cache" data-title="Flush Cache" onClick="flushCacheFromAdmin(\'' . $project_url_prefix . 'admin/flush_cache\')"></li>' : '') . '
		<li class="icon refresh" onClick="refreshIframe()" data-title="Refresh"></li>
		<li class="icon full_screen" data-title="Toggle Full Screen" onClick="toggleFullScreen(this)"></li>
		<li class="separator">|</li>
		
		<li class="sub_menu sub_menu_notifications" data-title="Notifications" onClick="openSubmenu(this)">
			<span class="icon notification"></span>
			<i class="icon dropdown_arrow"></i>
			
			<ul>
				<div class="triangle_up"></div>
				
				<li class="empty_notification"><div>There are no notifications</div></li>
				<!--li class="notification"><div>test</div></li-->
			</ul>
		</li>
		<li class="separator">|</li>
		
		<li class="icon tools" onClick="chooseAvailableTool(\'' . "{$project_url_prefix}admin/choose_available_tool?element_type=util&bean_name=$bean_name&bean_file_name=$bean_file_name&filter_by_layout=$filter_by_layout&path=$project&popup=1" . '\')" data-title="Tools"></li>
		<li class="icon home" data-title="Home" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}admin/admin_home?selected_layout_project=$filter_by_layout" . '"></li>
		<li class="separator">|</li>
		
		<li class="sub_menu sub_menu_user" data-title="Others" onClick="openSubmenu(this)">
			<span class="logged_user_icon">' . $logged_name_initials . '</span>
			<i class="icon dropdown_arrow"></i>
			<!--i class="icon user"></i-->
			
			<ul>
				<div class="triangle_up"></div>
				
				<li class="login_info" title="Logged as \'' . $logged_name . '\' user."><a><span class="logged_user_icon">' . $logged_name_initials . '</span> Logged in as "' . $logged_name . '"</a></li>
				<li class="separator"></li>
				
				' . (!empty($layers["presentation_layers"]) ? '
					<li><a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/presentation/list?element_type=webroot&bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$project" . '"><i class="icon list_view"></i> Manage Webroot Files</a></li>
					<li class="separator"></li>
					<li><a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/presentation/install_template?bean_name=$bean_name&bean_file_name=$bean_file_name&filter_by_layout=$filter_by_layout&path=$project/src/template/" . '"><i class="icon install_template"></i> Install New Template</a></li>
					<li><a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/presentation/list?element_type=template&bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$project" . '"><i class="icon list_view"></i> List Templates</a></li>
					<li><a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/presentation/list?element_type=block&bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$project" . '"><i class="icon list_view"></i> List Blocks</a></li>
					<li><a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/presentation/list?element_type=util&bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$project" . '"><i class="icon list_view"></i> List Actions</a></li>
					<li class="separator"></li>
					
				' : '');
				
/*$main_content .= ($is_switch_admin_ui_allowed ? '<li><a href="' . $project_url_prefix . 'admin/admin_uis"><i class="icon toggle_theme_layout"></i> Switch Workspace</a></li>' : '') . '
				<li><a href="javascript:void(0)" onClick="chooseAvailableProject(\'' . $project_url_prefix . 'admin/choose_available_project?redirect_path=admin&popup=1\')"><i class="icon project"></i> Switch Project</a></li>
				<li class="separator"></li>
				';*/

/*$main_content .= '	
				' . ($is_manage_projects_allowed ? '<li><a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . $project_url_prefix . 'phpframework/presentation/manage_projects"><i class="icon list_view"></i> Manage Projects</a></li>' : '') . '
				' . ($is_manage_layers_allowed ? '<li><a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . $project_url_prefix . 'setup?step=3.1&iframe=1&hide_setup=1"><i class="icon list_view"></i> Manage Layers</a></li>' : '') . '
				' . ($is_manage_modules_allowed ? '<li><a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . $project_url_prefix . 'phpframework/admin/manage_modules"><i class="icon list_view"></i> Manage Modules</a></li>' : '') . '
				' . ($is_manage_users_allowed ? '<li><a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . $project_url_prefix . 'user/manage_users"><i class="icon list_view"></i> Manage Users</a></li>' : '') . '
				' . ($is_testunits_allowed ? '<li><a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . $project_url_prefix . 'phpframework/testunit/"><i class="icon list_view"></i> Test-Units Management</a></li>' : '') . '
				' . ($is_deployment_allowed ? '<li><a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . $project_url_prefix . 'phpframework/deployment/"><i class="icon list_view"></i> Deployments Management</a></li>' : '') . '
				' . ($is_program_installation_allowed ? '<li><a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/admin/install_program?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$project" . '"><i class="icon list_view"></i> Install a Program</a></li>' : '') . '
				' . ($is_diff_files_allowed ? '<li><a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . $project_url_prefix . 'phpframework/diff/"><i class="icon list_view"></i> Diff Files</a></li>' : '') . '
				' . ($layers["others"]["other"] ? '<li><a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . $project_url_prefix . 'phpframework/presentation/list?item_type=other"><i class="icon list_view"></i> Other Files</a></li>' : '') . '
				<li><a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . $project_url_prefix . 'docbook/" title="Go to Doc-Book"><i class="icon list_view"></i> Doc Book</a></li>
				<li class="separator"></li>
				';*/

$main_content .= '	
				<li class="toggle_theme_layout" title="Toggle Theme"><a onClick="toggleThemeLayout(this)"><i class="icon toggle_theme_layout"></i> <span>Show dark theme</span></a></li>
				<!--li class="toggle_main_navigator_side" title="Toggle Navigator Side"><a onClick="toggleNavigatorSide(this)"><i class="icon toggle_main_navigator_side"></i> <span>Show navigator on right side</span></a></li-->
				<li class="separator"></li>
				<li class="console" title="Logs Console"><a onClick="openConsole(\'' . $project_url_prefix . 'admin/logs_console?popup=1\', event);"><i class="icon logs_console"></i> Logs Console</a></li>
				<!--li class="question" title="Tutorials - How To?"><a onClick="chooseAvailableTutorial(\'' . $project_url_prefix . 'admin/choose_available_tutorial?popup=1\', event);"><i class="icon tutorials"></i> Tutorials - How To?</a></li-->
				<li class="question" title="Tutorials - How To?"><a onClick="openOnlineTutorialsPopup(\'' . $online_tutorials_url_prefix . '\', event);"><i class="icon tutorials"></i> Tutorials - How To?</a></li>
				<li class="question" title="Open Tour Guide"><a onClick="MyTourGuide.restart()"><i class="icon question"></i> Open Tour Guide</a></li>
				<li class="info" title="About"><a onClick="goTo(this, \'url\', event)" url="' . $project_url_prefix . 'admin/about"><i class="icon info"></i> About</a></li>
				<li class="feedback" title="Feedback - Send us your questions"><a onClick="goToPopup(this, \'url\', event, \'with_title\')" url="' . $project_url_prefix . 'admin/feedback?popup=1"><i class="icon chat"></i> Feedback</a></li>
				<!--li class="framework_update" title="Update to the Latest Version of the Framework"><a onClick="goTo(this, \'url\', event)" url="' . $project_url_prefix . 'admin/framework_update"><i class="icon download"></i> Framework Update</a></li-->
				<li class="separator"></li>
				<li class="logout" title="Logout"><a onClick="document.location=this.getAttribute(\'logout_url\')" logout_url="' . $project_url_prefix . 'auth/logout"><i class="icon logout"></i> Logout</a></li>
			</ul>
		</li>
	</ul>
</div>
<div id="right_panel">';

$iframe_url = $default_page ? $default_page : (
	$project_url_prefix . 'admin/' . ($filter_by_layout ? "admin_home_project?$filter_by_layout_url_query" : "admin_home?selected_layout_project=$filter_by_layout")
);

$main_content .= '
	<iframe src="' . $iframe_url . '"></iframe>
	<div class="iframe_overlay">
		<div class="iframe_loading">Loading...</div>
	</div>
</div>';

$main_content .= TourGuideUIHandler::getHtml($entity, $project_url_prefix, $project_common_url_prefix, $online_tutorials_url_prefix, array("restart_allow" => false));

function getProjectsHtml($projs, $filter_by_layout, $common_project_id = null) {
	$html = "";
	//echo "<pre>$common_project_id";print_r($projs);die();
	
	if (is_array($projs)) {
		if (isset($projs[$common_project_id]))
			$html .= '<li class="project project_common' . ($filter_by_layout == $common_project_id ? ' selected' : '') . '">
						<a value="' . $common_project_id . '" onClick="filterByLayout(this)"><i class="icon project"></i> <span>' . $projs[$common_project_id] . '</span></a>
					</li>';
		
		foreach ($projs as $proj_id => $proj_name) {
			if (is_array($proj_name))
				$html .= '<li class="projects_group">
							<a><i class="icon project_folder"></i> <span>' . $proj_id . '</span></a>
							<ul>
							' . getProjectsHtml($proj_name, $filter_by_layout) . '
							</ul>
						</li>';
			else if ($proj_id != $common_project_id)
				$html .= '<li class="project' . ($filter_by_layout == $proj_id ? ' selected' : '') . '">
							<a value="' . $proj_id . '" onClick="filterByLayout(this)"><i class="icon project"></i> <span>' . $proj_name . '</span></a>
						</li>';
		}
	}
	
	return $html;
}
?>
