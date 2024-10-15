<?php
include_once $EVC->getUtilPath("AdminMenuUIHandler");
include $EVC->getUtilPath("TourGuideUIHandler");

$head = AdminMenuUIHandler::getHeader($project_url_prefix, $project_common_url_prefix);
$head .= '
<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/choose_available_tool.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/choose_available_tool.js"></script>

<script>
var is_popup = ' . ($popup ? 1 : 0) . ';
</script>';

$main_content = '<div class="choose_available_tool">
	<div class="title' . ($popup ? " inside_popup_title" : "") . '">Tools</div>
	<ul>
		' . (!empty($is_switch_admin_ui_allowed) ? '<li class="switch_admin_ui" onClick="return goTo(\'' . $project_url_prefix . 'admin/admin_uis?filter_by_layout=' . $filter_by_layout . '\', event, 1)">
			<div class="photo"></div>
			<label>Switch Workspace</label>
			<div class="description">Switch to other Workspace more fitted to your technical skills.</div>
		</li>' : '') . '
		<li class="switch_project" onClick="return goTo(\'' . $project_url_prefix . 'admin/choose_available_project?redirect_path=admin\', event, 1)">
			<div class="photo"></div>
			<label>Switch Project</label>
			<div class="description">Switch to another project...</div>
		</li>
		<li class="delimiter"></li>
		
		' . (!empty($is_manage_projects_allowed) ? '<li class="manage_projects" onClick="return goTo(\'' . $project_url_prefix . 'phpframework/presentation/manage_projects\', event)">
			<div class="photo"></div>
			<label>Manage Projects</label>
			<div class="description">Create, edit or remove Projects. Set default project...</div>
		</li>' : '') . '
		' . (!empty($is_manage_layers_allowed) ? '<li class="manage_layers" onClick="return goTo(\'' . $project_url_prefix . 'setup?step=3.1&iframe=1&hide_setup=1\', event)">
			<div class="photo"></div>
			<label>Manage Layers</label>
			<div class="description">Edit the framework structure by managing its\' layers</div>
		</li>' : '') . '
		' . (!empty($is_manage_modules_allowed) ? '<li class="manage_modules" onClick="return goTo(\'' . $project_url_prefix . 'phpframework/admin/manage_modules?filter_by_layout=' . $filter_by_layout . '\', event)">
			<div class="photo"></div>
			<label>Manage Modules</label>
			<div class="description">Install, enable, disable and edit modules...</div>
		</li>' : '') . '
		' . (!empty($is_manage_users_allowed) ? '<li class="manage_users" onClick="return goTo(\'' . $project_url_prefix . 'user/manage_users\', event)">
			<div class="photo"></div>
			<label>Manage Permissions/Users</label>
			<div class="description">Manage the framework\'s users</div>
		</li>' : '') . '
		' . (!empty($is_testunits_allowed) ? '<li class="manage_test_units" onClick="return goTo(\'' . $project_url_prefix . 'phpframework/testunit/\', event)">
			<div class="photo"></div>
			<label>Manage Test-Units</label>
			<div class="description">Create and execute your test-units in a batch...</div>
		</li>' : '') . '
		' . (!empty($is_deployment_allowed) ? '<li class="manage_deployments" onClick="return goTo(\'' . $project_url_prefix . 'phpframework/deployment/\', event)">
			<div class="photo"></div>
			<label>Manage Deployments</label>
			<div class="description">Deploy your projects to multiple servers with a single click...</div>
		</li>' : '') . '
		' . (!empty($is_program_installation_allowed) ? '<li class="install_program" onClick="return goTo(\'' . "{$project_url_prefix}phpframework/admin/install_program?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path" . '\', event)">
			<div class="photo"></div>
			<label>Install a Program</label>
			<div class="description">Install a Program and start using it in a few minutes...</div>
		</li>' : '') . '
		' . (!empty($is_diff_files_allowed) ? '<li class="diff_files" onClick="return goTo(\'' . $project_url_prefix . 'phpframework/diff/\', event)">
			<div class="photo"></div>
			<label>Diff Files</label>
			<div class="description">Compare 2 files and check their differences...</div>
		</li>' : '') . '
		<li class="flush_cache" onClick="flushCacheFromAdmin(\'' . $project_url_prefix . 'admin/flush_cache\');">
			<div class="photo"></div>
			<label>Flush Cache</label>
			<div class="description">Delete all saved cache.</div>
		</li>
		<li class="about" onClick="return goTo(\'' . $project_url_prefix . 'phpframework/admin/about\', event)">
			<div class="photo"></div>
			<label>About</label>
			<div class="description">Show framework info.</div>
		</li>
		<li class="logout" onClick="return goTo(\'' . $project_url_prefix . 'auth/logout\', event, 1)">
			<div class="photo"></div>
			<label>Logout</label>
			<div class="description">Logout</div>
		</li>
		<li class="delimiter"></li>
		
		<li class="doc_book" onClick="return goTo(\'' . $project_url_prefix . 'docbook/\', event)">
			<div class="photo"></div>
			<label>Internal Library</label>
			<div class="description">Go to our Library Doc-Book</div>
		</li>';
	
if (!empty($layers)) {
	$filter_by_layout_url_query = $filter_by_layout ? "&filter_by_layout=$filter_by_layout&filter_by_layout_permission=$filter_by_layout_permission" : "";
	
	if (isset($layers["vendors"]["vendor"]))
		$main_content .= '
			<li class="vendor_files" onClick="return goTo(\'' . $project_url_prefix . 'phpframework/presentation/list?item_type=vendor\', event)">
				<div class="photo"></div>
				<label>External Library</label>
				<div class="description">Extend the framework with the upload of external libraries, new workflow tasks, new ui widgets and much more...</div>
			</li>';
	
	if (!empty($layers["others"]["other"]))
		$main_content .= '
		<li class="other_files" onClick="return goTo(\'' . $project_url_prefix . 'phpframework/presentation/list?item_type=other\', event)">
			<div class="photo"></div>
			<label>Other Files</label>
			<div class="description">View, Edit and Upload other files that you may wish to include here...</div>
		</li>';
	
	if (isset($layers["vendors"]["vendor"]))
		$main_content .= '
			<li class="dao_files" onClick="return goTo(\'' . $project_url_prefix . 'phpframework/presentation/list?item_type=dao\', event)">
				<div class="photo"></div>
				<label>DAO Files</label>
				<div class="description">Create, edit and manage your DAO objects here...</div>
			</li>';
	
	$main_content .= '<li class="delimiter"></li>';
	
	if (!empty($layers["db_layers"]))
		foreach ($layers["db_layers"] as $layer_name => $layer) {
			$bn = isset($layer["properties"]["bean_name"]) ? $layer["properties"]["bean_name"] : null;
			$bfn = isset($layer["properties"]["bean_file_name"]) ? $layer["properties"]["bean_file_name"] : null;
			
			$label = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bfn, $bn, $user_global_variables_file_path);
			
			$main_content .= '
			<li class="db_layers" onClick="return goTo(\'' . "{$project_url_prefix}phpframework/presentation/list?bean_name=$bn&bean_file_name=$bfn$filter_by_layout_url_query" . '\', event)">
				<div class="photo"></div>
				<label>' . ucwords($label) . ' DBs</label>
				<div class="description">Manage Data-Bases.</div>
			</li>';
		}
		
	if (!empty($layers["data_access_layers"]))
		foreach ($layers["data_access_layers"] as $layer_name => $layer) {
			$bn = isset($layer["properties"]["bean_name"]) ? $layer["properties"]["bean_name"] : null;
			$bfn = isset($layer["properties"]["bean_file_name"]) ? $layer["properties"]["bean_file_name"] : null;
			
			$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bfn, $user_global_variables_file_path);
			$obj = $WorkFlowBeansFileHandler->getBeanObject($bn);
			$obj_type = $obj->getType();
			$label = WorkFlowBeansFileHandler::getLayerObjFolderName($obj);
			
			$main_content .= '
			<li class="data_access_layers data_access_layers_' . $obj_type . '" onClick="return goTo(\'' . "{$project_url_prefix}phpframework/presentation/list?bean_name=$bn&bean_file_name=$bfn$filter_by_layout_url_query&selected_db_driver=$selected_db_driver" . '\', event)">
				<div class="photo"></div>
				<label>' . ucwords($label) . ' Data Access</label>
				<div class="description">Manage the ' . $obj_type . ' rules.</div>
			</li>';
		}
	
	if (!empty($layers["business_logic_layers"]))
		foreach ($layers["business_logic_layers"] as $layer_name => $layer) {
			$bn = isset($layer["properties"]["bean_name"]) ? $layer["properties"]["bean_name"] : null;
			$bfn = isset($layer["properties"]["bean_file_name"]) ? $layer["properties"]["bean_file_name"] : null;
			
			$label = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bfn, $bn, $user_global_variables_file_path);
			
			$main_content .= '
			<li class="business_logic_layers" onClick="return goTo(\'' . "{$project_url_prefix}phpframework/presentation/list?bean_name=$bn&bean_file_name=$bfn$filter_by_layout_url_query&selected_db_driver=$selected_db_driver" . '\', event)">
				<div class="photo"></div>
				<label>' . ucwords($label) . ' Business Logic</label>
				<div class="description">Manage the business logic services.</div>
			</li>';
		}
}

$main_content .= '
	</ul>
</div>';
$main_content .= TourGuideUIHandler::getHtml($entity, $project_url_prefix, $project_common_url_prefix, $online_tutorials_url_prefix);
?>
