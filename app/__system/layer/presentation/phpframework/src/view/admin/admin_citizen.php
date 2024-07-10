<?php
include_once $EVC->getUtilPath("AdminMenuUIHandler");

if (!$is_admin_ui_citizen_allowed) {
	echo '<script>
		alert("You don\'t have permission to access this Workspace!");
		document.location="' . $project_url_prefix . 'auth/logout";
	</script>';
	die();
}

$switch_project_url = $project_url_prefix . "admin?bean_name=$bean_name&bean_file_name=$bean_file_name&project=#project#";
$logged_name = $UserAuthenticationHandler->auth["user_data"]["name"] ? $UserAuthenticationHandler->auth["user_data"]["name"] : $UserAuthenticationHandler->auth["user_data"]["username"];

$filter_by_layout_url_query = $filter_by_layout ? "&filter_by_layout=$filter_by_layout&filter_by_layout_permission=$filter_by_layout_permission" : "";

$head = AdminMenuUIHandler::getHeader($project_url_prefix, $project_common_url_prefix);
$head .= '
<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/admin_citizen.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/admin_citizen.js"></script>

<script>
menu_item_properties = ' . json_encode($menu_item_properties) . ';
</script>';

$main_content = '';

if (!$projects) 
	$main_content .= '<script>alert("Error: No projects available! Please contact your sysadmin...");</script>';

$main_content .= '
<div id="selected_menu_properties" class="myfancypopup">
	<div class="title">Properties</div>
	<p class="content"></p>
</div>

<div id="left_panel">
	<ul class="dropdown-1">
		<li class="selected_project with_sub_menu">
			<a class="item_header" href="javascript:void(0)" onClick="showSubMenu(this);" title="' . $project . '">
				<label>' . $project . '</label>
				<i class="fas fa-chevron-down sub_menu"></i>
			</a>
			
			<ul>
				<li class="choose_another_project" title="Choose a different project">
					<a class="item_header" href="javascript:void(0)" onClick="chooseAvailableProject(\'' . $project_url_prefix . 'admin/choose_available_project?redirect_path=admin&popup=1\');">
						<i class="selected"></i>
						<span class="fas fa-toggle-on logo"></span>
						<label>Choose another project</label>
					</a>
				</li>
				
				' . getProjectsHtml($projects, $switch_project_url) . '
			</ul>
		</li>
		<li class="dashboard">
			<a class="item_header" href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . "{$project_url_prefix}admin/admin_home?selected_layout_project=$filter_by_layout" . '" title="Go Home">
				<span class="fas fa-tachometer-alt logo"></span>
				<label>Dashboard</label>
			</a>
		</li>
	</ul>
	<ul class="dropdown-2">
		' . ($is_db_layer_allowed ? '
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
		' . ($layers["presentation_layers"] ? '
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
			' . ($util_exists ? '<li class="utils with_sub_menu">
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
	
	if ($layers["presentation_layers"])
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
	
	if ($layers) {
		if ($layers["data_access_layers"])
			foreach ($layers["data_access_layers"] as $layer_name => $layer) {
				$bn = $layer["properties"]["bean_name"];
				$bfn = $layer["properties"]["bean_file_name"];
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
		
		if ($layers["business_logic_layers"])
			foreach ($layers["business_logic_layers"] as $layer_name => $layer) {
				$bn = $layer["properties"]["bean_name"];
				$bfn = $layer["properties"]["bean_file_name"];
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
				<li class="tools">
					<a class="item_header" href="javascript:void(0)" onClick="chooseAvailableTool(\'' . "{$project_url_prefix}admin/choose_available_tool?bean_name=$bean_name&bean_file_name=$bean_file_name&filter_by_layout=$filter_by_layout&path=$project&selected_db_driver=$db_driver_broker_name&popup=1" . '\')">
						<i class="selected"></i>
						<span class="fas fa-tools logo"></span>
						<label>Tools</label>
					</a>
				</li>
				' . ($is_switch_admin_ui_allowed ? '<li class="switch_admin_ui">
					<a class="item_header" href="' . $project_url_prefix . 'admin/admin_uis">
						<i class="selected"></i>
						<span class="fas fa-th-large logo"></span>
						<label>Switch Workspace</label>
					</a>
				</li>' : '') . '
				' . ($is_flush_cache_allowed ? '<li class="flush_cache">
					<a class="item_header" href="javascript:void(0)" onClick="flushCacheFromAdmin(\'' . $project_url_prefix . 'admin/flush_cache\');">
						<i class="selected"></i>
						<span class="fas fa-broom logo"></span>
						<label>Flush Cache</label>
					</a>
				</li>' : '') . '
				<li class="logout">
					<a class="item_header" href="' . $project_url_prefix . 'auth/logout">
						<i class="selected"></i>
						<span class="fas fa-sign-out-alt logo"></span>
						<label>Logout</label>
					</a>
				</li>
				<li class="about">
					<a class="item_header" href="javascript:void(0)" onClick="goTo(this,\'url\', event)" url="' . $project_url_prefix . 'phpframework/admin/about">
						<i class="selected"></i>
						<span class="fas fa-info-circle logo"></span>
						<label>About</label>
					</a>
				</li>
			</ul>
		</li>
	</ul>
</div>
<div id="top_right_panel">
	<span class="icon toggle_left_panel" onClick="toggleLeftpanel(this)"></span>
	<span class="login_info"><i class="icon user"></i>  "' . $logged_name . '"</span>
	<span class="icon go_back" onClick="goBack()" title="Go Back"></span>
	<span class="icon refresh" onClick="refreshIframe()" title="Refresh"></span>
	<span class="icon home" onClick="goTo(this, \'home_url\', event)" home_url="' . "{$project_url_prefix}admin/admin_home?selected_layout_project=$filter_by_layout" . '" title="Go Home"></span>
</div>
<div id="right_panel">
	<iframe src="' . "{$project_url_prefix}admin/" . ($filter_by_layout ? "admin_home_project?$filter_by_layout_url_query" : "admin_home?selected_layout_project=$filter_by_layout") . '"></iframe>
	<div class="iframe_overlay">
		<div class="iframe_loading">Loading...</div>
	</div>
</div>';

function getProjectsHtml($projects, $switch_project_url) {
	$html = '';
	
	if ($projects)
		foreach ($projects as $project_name => $project_props) {
			if ($project_props["is_project"]) {
				$html .= '<li class="project' . ($project_props["is_selected"] ? " shown_project" : "") . '">
							<a class="item_header" href="' . str_replace("#project#", $project_props["element_type_path"], $switch_project_url) . '" title="' . $project_name . '">
								<i class="selected"></i>
								<span class="fas fa-globe logo"></span>
								<label>' . $project_name . '</label>
							</a>
						</li>';
			}
			else {
				$html .= '<li class="project project_folder">
							<div class="item_header" title="' . $project_name . '">
								<span class="fas fa-folder logo"></span>
								<label>' . $project_name . '</label>
							</div>';
				
				if ($project_props)
					$html .= '<ul>' . getProjectsHtml($project_props, $switch_project_url) . '</ul>';
				
				$html .= '</li>';
			}
		}
	
	return $html;
}
?>
