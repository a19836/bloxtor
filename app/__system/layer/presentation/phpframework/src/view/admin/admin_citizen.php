<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include $EVC->getViewPath("admin/admin_simple");

$db_driver_layer_folder_name = isset($db_driver_layer_folder_name) ? $db_driver_layer_folder_name : null;
$db_driver_bean_name = isset($db_driver_bean_name) ? $db_driver_bean_name : null;
$db_driver_bean_file_name = isset($db_driver_bean_file_name) ? $db_driver_bean_file_name : null;
$db_driver_layer_bean_file_name = isset($db_driver_layer_bean_file_name) ? $db_driver_layer_bean_file_name : null;
$db_driver_layer_bean_name = isset($db_driver_layer_bean_name) ? $db_driver_layer_bean_name : null;
$db_driver_broker_name = isset($db_driver_broker_name) ? $db_driver_broker_name : null;

$head .= '
<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/admin_citizen.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/admin_citizen.js"></script>

<script>
menu_item_properties = ' . (isset($menu_item_properties) ? json_encode($menu_item_properties) : "null") . ';
</script>';

$main_content = '';

if (empty($projects)) 
	$main_content .= '<script>alert("Error: No projects available! Please contact your sysadmin...");</script>';

$main_content .= '
<div id="selected_menu_properties" class="myfancypopup with_title">
	<div class="title">Properties</div>
	<p class="content"></p>
</div>

<div id="top_panel">
	<ul class="left">
		<li class="logo"><a href="' . $project_url_prefix . '"></a></li>
		<li class="icon toggle_side_panel" data-title="Collapse left panel" onClick="toggleLeftpanel(this)"></li>
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
				' . ($layers["others"]["other"] ? '<li><a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . $project_url_prefix . 'phpframework/presentation/list?item_type=other"><i class="icon list_view"></i> Other Files</a></li>' : '') . '
				<li><a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . $project_url_prefix . 'docbook/" title="Go to Doc-Book"><i class="icon list_view"></i> Doc Book</a></li>
				<li class="separator"></li>
				';*/

$main_content .= '
				<!--li class="toggle_theme_layout" title="Toggle Theme"><a onClick="toggleThemeLayout(this)"><i class="icon toggle_theme_layout"></i> <span>Show dark theme</span></a></li>
				<li class="toggle_main_navigator_side" title="Toggle Navigator Side"><a onClick="toggleNavigatorSide(this)"><i class="icon toggle_main_navigator_side"></i> <span>Show navigator on right side</span></a></li>
				<li class="separator"></li-->
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
<div id="side_panel">
	<ul>
		' . (!empty($is_db_layer_allowed) ? '
		<li class="db with_sub_menu">
			<a class="item_header" href="javascript:void(0)" onClick="showSubMenu(this);">
				<i class="selected"></i>
				<span class="fas fa-database logo"></span>
				<label>Data-Base</label>
				<i class="fas fa-chevron-down sub_menu"></i>
			</a>
			
			<ul>
				<li class="diagram">
					<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}db/diagram?layer_bean_folder_name=$db_driver_layer_folder_name&bean_name=$db_driver_bean_name&bean_file_name=$db_driver_bean_file_name" . '">
						<i class="selected"></i>
						<span class="fa fa-project-diagram logo"></span>
						<label>Edit Tables Diagram</label>
					</a>
				</li>
				<li class="list_tables">
					<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/presentation/list?bean_name=$db_driver_layer_bean_name&bean_file_name=$db_driver_layer_bean_file_name&path=$db_driver_bean_name" . '">
						<i class="selected"></i>
						<span class="fa fa-list logo"></span>
						<label>List Tables</label>
					</a>
				</li>
				<li class="edit_table">
					<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}db/edit_table?layer_bean_folder_name=$db_driver_layer_folder_name&bean_name=$db_driver_bean_name&bean_file_name=$db_driver_bean_file_name" . '">
						<i class="selected"></i>
						<span class="fa fa-table logo"></span>
						<label>Add Table</label>
					</a>
				</li>
				<li class="execute_sql">
					<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}db/execute_sql?layer_bean_folder_name=$db_driver_layer_folder_name&bean_name=$db_driver_bean_name&bean_file_name=$db_driver_bean_file_name" . '">
						<i class="selected"></i>
						<span class="fa fa-code logo"></span>
						<label>Execute SQL</label>
					</a>
				</li>
				<li class="db_dump">
					<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}db/db_dump?layer_bean_folder_name=$db_driver_layer_folder_name&bean_name=$db_driver_bean_name&bean_file_name=$db_driver_bean_file_name" . '">
						<i class="selected"></i>
						<span class="fa fa-download logo"></span>
						<label>DB Dump</label>
					</a>
				</li>
				<li class="set_db_settings">
					<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}db/set_db_settings?layer_bean_folder_name=$db_driver_layer_folder_name&bean_name=$db_driver_bean_name&bean_file_name=$db_driver_bean_file_name" . '">
						<i class="selected"></i>
						<span class="fa fa-edit logo"></span>
						<label>Edit DB Credentials</label>
					</a>
				</li>
				<li class="properties">
					<a class="item_header" href="javascript:void(0)" onClick="showProperties(this)" properties_id="' . $db_driver_bean_name . '">
						<i class="selected"></i>
						<span class="fa fa-eye logo"></span>
						<label>Show Properties</label>
					</a>
				</li>
			</ul>
		' : '') . '
		</li>
		' . (!empty($layers["presentation_layers"]) ? '
			<li class="pages">
				<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/presentation/list?element_type=entity&bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$project" . '">
					<i class="selected"></i>
					<span class="fas fa-copy logo"></span>
					<label>Pages</label>
				</a>
			</li>
			<li class="templates with_sub_menu">
				<a class="item_header" href="javascript:void(0)" onClick="showSubMenu(this);">
					<i class="selected"></i>
					<span class="fas fa-th logo"></span>
					<label>Templates</label>
					<i class="fas fa-chevron-down sub_menu"></i>
				</a>
				
				<ul>
					<li class="list_templates">
						<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/presentation/list?element_type=template&bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$project" . '">
							<i class="selected"></i>
							<span class="fa fa-list logo"></span>
							<label>List Templates</label>
						</a>
					</li>
					<li class="install_template">
						<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/presentation/install_template?bean_name=$bean_name&bean_file_name=$bean_file_name&filter_by_layout=$filter_by_layout&path=$project/src/template/" . '">
							<i class="selected"></i>
							<span class="fab fa-instalod logo"></span>
							<label>Install Template</label>
						</a>
					</li>
				</ul>
			</li>
			<li class="webroot">
				<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/presentation/list?element_type=webroot&bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$project" . '">
					<i class="selected"></i>
					<span class="fas fa-images logo"></span>
					<label>Webroot</label>
				</a>
			</li>
			' . (!empty($util_exists) ? '<li class="utils with_sub_menu">
				<a class="item_header" href="javascript:void(0)" onClick="showSubMenu(this);">
					<i class="selected"></i>
					<span class="fas fa-radiation logo"></span>
					<label>Actions</label>
					<i class="fas fa-chevron-down sub_menu"></i>
				</a>
				
				<ul>
					<li class="list_utils">
						<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/presentation/list?element_type=util&bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$project" . '">
							<i class="selected"></i>
							<span class="fa fa-list logo"></span>
							<label>List Actions</label>
						</a>
					</li>
					<li class="crontabs">
						<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/module/workerpool/admin/list_workers?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$project&path_to_filter=$project/utils" . '">
							<i class="selected"></i>
							<span class="fas fa-robot logo"></span>
							<label>Scheduled Jobs</label>
						</a>
					</li>
				</ul>
			</li>' : '') . '
		' : '') . '
		<li class="others with_sub_menu">
			<a class="item_header" href="javascript:void(0)" onClick="showSubMenu(this);">
				<i class="selected"></i>
				<span class="fas fa-feather logo"></span>
				<label>Others</label>
				<i class="fas fa-chevron-down sub_menu"></i>
			</a>
			
			<ul>';
	
	if (!empty($layers["presentation_layers"]))
		$main_content .= '
				<li class="edit_global_variables">
					<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/presentation/edit_project_global_variables?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$project/src/config/pre_init_config.php" . '">
						<i class="selected"></i>
						<span class="fas fa-globe logo"></span>
						<label>Edit Global Variables</label>
					</a>
				</li>
				<li class="edit_config">
					<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/presentation/edit_config?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$project/src/config/config.php" . '">
						<i class="selected"></i>
						<span class="fas fa-edit logo"></span>
						<label>Edit Config</label>
					</a>
				</li>
				<li class="views">
					<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/presentation/list?element_type=view&bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$project" . '">
						<i class="selected"></i>
						<span class="fas fa-list logo"></span>
						<label>List Views</label>
					</a>
				</li>
				<li class="blocks">
					<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/presentation/list?element_type=block&bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$project" . '">
						<i class="selected"></i>
						<span class="fas fa-list logo"></span>
						<label>List Blocks</label>
					</a>
				</li>';
	
	if (!empty($layers)) {
		if (!empty($layers["data_access_layers"]))
			foreach ($layers["data_access_layers"] as $layer_name => $layer) {
				$bn = isset($layer["properties"]["bean_name"]) ? $layer["properties"]["bean_name"] : null;
				$bfn = isset($layer["properties"]["bean_file_name"]) ? $layer["properties"]["bean_file_name"] : null;
				$label = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bfn, $bn, $user_global_variables_file_path);
				
				$main_content .= '
				<li class="data_access_rules">
					<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/presentation/list?element_type=block&bean_name=$bn&bean_file_name=$bfn$filter_by_layout_url_query&selected_db_driver=$db_driver_broker_name" . '">
						<i class="selected"></i>
						<span class="fas fa-list logo"></span>
						<label>List ' . ucwords($label) . ' Rules</label>
					</a>
				</li>';
			}
		
		if (!empty($layers["business_logic_layers"]))
			foreach ($layers["business_logic_layers"] as $layer_name => $layer) {
				$bn = isset($layer["properties"]["bean_name"]) ? $layer["properties"]["bean_name"] : null;
				$bfn = isset($layer["properties"]["bean_file_name"]) ? $layer["properties"]["bean_file_name"] : null;
				$label = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bfn, $bn, $user_global_variables_file_path);
				
				$main_content .= '
				<li class="business_logic_services">
					<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}phpframework/presentation/list?element_type=block&bean_name=$bn&bean_file_name=$bfn$filter_by_layout_url_query&selected_db_driver=$db_driver_broker_name" . '">
						<i class="selected"></i>
						<span class="fas fa-list logo"></span>
						<label>List ' . ucwords($label) . ' Services</label>
					</a>
				</li>';
			}
	}
	
	$main_content .= '
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
?>
