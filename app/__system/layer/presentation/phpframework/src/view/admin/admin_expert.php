<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

if ($action == "edit") {
	include $EVC->getViewPath("admin/edit_raw_file");
}
else if ($action == "upload") {
	$upload_url = "?admin_type=expert&path=$path&action=upload";
	include $EVC->getViewPath("admin/upload_file");
}
else if ($action == "get_sub_files") {
	include $EVC->getViewPath("admin/get_sub_files");
}
else {
	include_once $EVC->getUtilPath("AdminMenuUIHandler");
	include_once $EVC->getUtilPath("TourGuideUIHandler");
	include_once $EVC->getUtilPath("HeatMapHandler");

	$logged_name = $UserAuthenticationHandler->auth["user_data"]["name"] ? $UserAuthenticationHandler->auth["user_data"]["name"] : $UserAuthenticationHandler->auth["user_data"]["username"];
	$logged_name_initials = explode(" ", $logged_name);
	$logged_name_initials = strtoupper(substr($logged_name_initials[0], 0, 1) . (isset($logged_name_initials[1]) ? substr($logged_name_initials[1], 0, 1) : ""));

	$notifications_url = $project_url_prefix . "admin/get_notifications";

	$main_layers_properties = getMainLayersProperties();

	$head = AdminMenuUIHandler::getHeader($project_url_prefix, $project_common_url_prefix);
	$head .= '
	<!-- Add Admin Advanced JS and CSS files -->
	<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/admin_advanced.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/admin_advanced.js"></script>

	<!-- Add Local JS and CSS files -->
	<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/admin_expert.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/admin_expert.js"></script>

	<script>
	var path_to_filter = "";
	var notifications_url = "' . $notifications_url . '";

	main_layers_properties = ' . json_encode($main_layers_properties) . ';
	</script>
	';
	$head .= HeatMapHandler::getHtml($project_url_prefix);

	//Context Menus
	$main_content = getcontextMenusHtml();

	//UI
	$main_content .= '
	<div id="top_panel">
		<ul class="left">
			<li class="logo"><a href="' . $project_url_prefix . '"></a></li>
		</ul>
		<ul class="center">
			
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
					';
					
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
					<li><a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . $project_url_prefix . 'docbook/" title="Go to Doc-Book"><i class="icon list_view"></i> Doc Book</a></li>
					<li class="separator"></li>
					';*/

	$main_content .= '	
					<li class="toggle_theme_layout" title="Toggle Theme"><a onClick="toggleThemeLayout(this)"><i class="icon toggle_theme_layout"></i> <span>Show dark theme</span></a></li>
					<li class="toggle_main_navigator_side" title="Toggle Navigator Side"><a onClick="toggleNavigatorSide(this)"><i class="icon toggle_main_navigator_side"></i> <span>Show navigator on right side</span></a></li>
					<li class="separator"></li>
					<li class="console" title="Logs Console"><a onClick="openConsole(\'' . $project_url_prefix . 'admin/logs_console?popup=1\', event);"><i class="icon logs_console"></i> Logs Console</a></li>
					' . ($is_terminal_console_allowed ? '<li class="view_terminal" title="Terminal"><a onClick="goToPopup(this, \'url\', event, \'with_title\')" url="' . $project_url_prefix . 'admin/terminal_console?popup=1"><i class="icon terminal_console"></i> Terminal Console</a></li>' : '') . '
					<!--li class="question" title="Tutorials - How To?"><a onClick="chooseAvailableTutorial(\'' . $project_url_prefix . 'admin/choose_available_tutorial?popup=1\', event);"><i class="icon tutorials"></i> Tutorials - How To?</a></li-->
					<li class="question" title="Tutorials - How To?"><a onClick="openOnlineTutorialsPopup(\'' . $online_tutorials_url_prefix . '\', event);"><i class="icon tutorials"></i> Tutorials - How To?</a></li>
					<li class="question" title="Open Tour Guide"><a onClick="MyTourGuide.restart()"><i class="icon question"></i> Open Tour Guide</a></li>
					<li class="info" title="About"><a onClick="goTo(this, \'url\', event)" url="' . $project_url_prefix . 'admin/about"><i class="icon info"></i> About</a></li>
					<li class="feedback" title="Feedback - Send us your questions"><a onClick="goToPopup(this, \'url\', event, \'with_title\')" url="' . $project_url_prefix . 'admin/feedback?popup=1"><i class="icon chat"></i> Feedback</a></li>
					<li class="framework_update" title="Update to the Latest Version of the Framework"><a onClick="goTo(this, \'url\', event)" url="' . $project_url_prefix . 'admin/framework_update"><i class="icon download"></i> Framework Update</a></li>
					<li class="separator"></li>
					<li class="logout" title="Logout"><a onClick="document.location=this.getAttribute(\'logout_url\')" logout_url="' . $project_url_prefix . 'auth/logout"><i class="icon logout"></i> Logout</a></li>
				</ul>
			</li>
		</ul>
	</div>
	<div id="left_panel">
		<div class="file_tree_root"></div>
		<div id="file_tree" class="mytree hidden">
			<ul>';
		
	$main_content .= !empty($nodes) ? AdminMenuUIHandler::getSubNodes($nodes, $main_layers_properties["expert"]) : "";

	$main_content .= '
			</ul>
		</div>
	</div>
	<div id="hide_panel">
		<div class="button minimize" onClick="toggleLeftPanel(this)"></div>
	</div>
	<div id="right_panel">';

	$iframe_url = !empty($default_page) ? $default_page : $project_url_prefix . 'admin/admin_home';

	$main_content .= '
		<iframe src="' . $iframe_url . '"></iframe>
		<div class="iframe_overlay">
			<div class="iframe_loading">Loading...</div>
		</div>
	</div>';

	$main_content .= TourGuideUIHandler::getHtml($entity, $project_url_prefix, $project_common_url_prefix, $online_tutorials_url_prefix, array("restart_allow" => false));
}

function getMainLayersProperties() {
	$url = '?admin_type=expert&path=#path#&action=#action#&extra=#extra#';
	
	return array(
		"expert" => array(
			"ui" => array(
				"path" => "",
				"item_id" => "",
				"item_type" => "",
				"folder" => array(
					"get_sub_files_url" => "?admin_type=expert&path=#path#&action=get_sub_files",
					"attributes" => array(
						"rename_url" => $url,
						"remove_url" => $url,
						"create_url" => $url,
						"upload_url" => '?admin_type=expert&path=#path#&action=upload',
						"download_url" => '?admin_type=expert&path=#path#&action=download',
						"zip_url" => $url,
						"copy_url" => "[#path#]",
						"cut_url" => "[#path#]",
						"paste_url" => $url,
					)
				),
				"file" => array(
					"attributes" => array(
						"onClick" => "return goTo(this, 'edit_url', event)",
						"rename_url" => $url,
						"remove_url" => $url,
						"edit_url" => '?admin_type=expert&path=#path#&action=edit',
						"download_url" => '?admin_type=expert&path=#path#&action=download',
						"zip_url" => $url,
						"copy_url" => "[#path#]",
						"cut_url" => "[#path#]",
					)
				),
				"zip_file" => array(
					"attributes" => array(
						"rename_url" => $url,
						"remove_url" => $url,
						"download_url" => '?admin_type=expert&path=#path#&action=download',
						"unzip_url" => $url,
						"copy_url" => "[#path#]",
						"cut_url" => "[#path#]",
					)
				),
			)
		)
	);
}

function getcontextMenusHtml() {
	return '
		<div id="selected_menu_properties" class="myfancypopup with_title">
			<div class="title">Properties</div>
			<p class="content"></p>
		</div>
		
		<ul id="folder_context_menu" class="mycontextmenu">
			<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Folder</a></li>
			<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\')">Add File</a></li>
			<li class="line_break"></li>
			<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
			<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
			<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
			<li class="line_break"></li>
			<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
			<li class="line_break"></li>
			<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')">Rename</a></li>
			<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
			<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
			<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
			<li class="line_break"></li>
			<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
			<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
		</ul>

		<ul id="file_context_menu" class="mycontextmenu">
			<li class="edit"><a onClick="return goTo(this, \'edit_url\', event)">Edit File</a></li>
			<li class="line_break"></li>
			<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
			<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
			<li class="line_break"></li>
			<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
			<li class="line_break"></li>
			<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')" allow_upper_case="1">Rename File</a></li>
			<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename_name\')" allow_upper_case="1">Rename Name</a></li>
			<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
			<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
			<li class="line_break"></li>
			<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
		</ul>

		<ul id="zip_file_context_menu" class="mycontextmenu">
			<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
			<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
			<li class="line_break"></li>
			<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
			<li class="line_break"></li>
			<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')">Rename File</a></li>
			<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename_name\')">Rename Name</a></li>
			<li class="unzip"><a onClick="return manageFile(this, \'unzip_url\', \'unzip\')">Unzip</a></li>
			<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
			<li class="line_break"></li>
			<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
		</ul>';
}
?>
