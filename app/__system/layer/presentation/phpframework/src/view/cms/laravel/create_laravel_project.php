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

include $EVC->getUtilPath("BreadCrumbsUIHandler");

$folder_path = isset($folder_path) ? $folder_path : null;
$obj = isset($obj) ? $obj : null;

$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($filter_by_layout);

$choose_queries_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?bean_name=#bean_name#&bean_file_name=#bean_file_name#$filter_by_layout_url_query&path=#path#";

$head = '
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

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local JS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/cms/laravel/create_laravel_project.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/cms/laravel/create_laravel_project.js"></script>
';
$head .= LayoutTypeProjectUIHandler::getHeader();

$main_content = "";

if (!empty($_POST["step_1"])) {
	$msg = (!empty($status) ? 'Project created successfully!' : 'Error creating laravel project.') . (!empty($error_message) ? "<br/>$error_message" : "");
	
	$main_content .= '<div class="status">
		<div class="top_bar">
			<header>
				<div class="title" title="' . $path . '">Create Laravel Project in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($folder_path, $obj) . '</div>
			</header>
		</div>
		
		<h1 class="title">Create Laravel Project</h1>
		
		<div class="msg ' . (empty($status) ? 'status_error' : 'status_ok') . '">' . $msg . '</div>
	</div>';
	
	if (!empty($status))
		$main_content .= '<script>
		if (window.parent && typeof window.parent.refreshAndShowLastNodeChilds == "function")
			window.parent.refreshAndShowLastNodeChilds();
		</script>';
}
else {
	$head .= '<script>
var laravel_kit_stacks = ' . (isset($laravel_kit_stacks) ? json_encode($laravel_kit_stacks) : "null") . ';
var laravel_kit_stack_features = ' . (isset($laravel_kit_stack_features) ? json_encode($laravel_kit_stack_features) : "null") . ';
</script>';
	
	$main_content .= '
	<div class="laravel_form">
		<div class="top_bar">
			<header>
				<div class="title" title="' . $path . '">Create Laravel Project in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($folder_path, $obj) . '</div>
				<ul>
					<li class="continue" data-title="Continue"><a onClick="submitForm(this);"><i class="icon continue"></i> Continue</a></li>
				</ul>
			</header>
		</div>
		
		<h1 class="title">Create Laravel Project</h1>
		
		<form method="post" onSubmit="return MyJSLib.FormHandler.formCheck(this);">
			<input type="hidden" name="step_1" value="Continue" />
			
			<div class="project_name">
				<label>Project name: </label>
				<input type="text" name="project_name" value="" allownull="false" validationmessage="Please insert the project name." />
				<div class="info">Project name or path including parent folders...</div>
			</div>
			<div class="project_kit">
				<label>Starter Kit: </label>
				<select name="project_kit" onChange="onProjectKitChange(this)">';
	
	if (!empty($laravel_kits))
		foreach ($laravel_kits as $k => $v)
			$main_content .= '<option value="' . $k . '">' . $v . '</option>';
	
	$main_content .= '
				</select>
			</div>
			<div class="project_testing_framework">
				<label>Testing Framework: </label>
				<select name="project_testing_framework">';
	
	if (!empty($laravel_testing_frameworks))
		foreach ($laravel_testing_frameworks as $k => $v)
			$main_content .= '<option value="' . $k . '">' . $v . '</option>';
	
	$main_content .= '
				</select>
			</div>
			<div class="project_database">
				<label>Default Database Driver: </label>
				<select name="project_database">
					<!--option value="">-- none --</option-->';
	
	if (!empty($laravel_databases))
		foreach ($laravel_databases as $k => $v)
			$main_content .= '<option value="' . $k . '">' . $v . '</option>';
	
	$main_content .= '
				</select>
			</div>
			<div class="project_db_driver">
				<label>Database:</label>
				<select name="project_db_driver" onChange="onChangeProjectWithDB(this)">
					' . ($default_db_driver ? '<option value="0">-- default --</option>' : '') . '
					<option value="1">New DB - User Defined</option>
					<option value="" disabled></option>';
	
	if (!empty($db_drivers_names)) {
		$main_content .= '<optgroup label="Existent DBs">';
		
		foreach ($db_drivers_names as $db_driver_name => $aux)
			$main_content .= '<option value="' . $db_driver_name . '">' . $db_driver_name . '</option>';
		
		$main_content .= '</optgroup>';
	}
	
	$main_content .= '
				</select>
			</div>
			<div class="new_db">
				<div class="db_host">
					<label>Host: </label>
					<input name="db_host">
				</div>
				<div class="db_port">
					<label>Port: </label>
					<input name="db_port" type="number">
				</div>
				<div class="db_name">
					<label>DB Name: </label>
					<input name="db_name">
				</div>
				<div class="db_user">
					<label>DB User: </label>
					<input name="db_user" autocomplete="new-password">
				</div>
				<div class="db_pass">
					<label>DB Password: </label>
					<input name="db_pass" type="password" autocomplete="new-password">
				</div>
			</div>
			<div class="project_stack">
				<label>Stack: </label>
				<select name="project_stack" onChange="onProjectStackChange(this)">
					<option value="">-- none --</option>
				</select>
			</div>
			<div class="project_features">
				<label>Optional Features: </label>
				<ul>
					<li class="empty">No features avaiable for this selection</li>
				</ul>
			</div>
		</form>
	</div>';
}

if (empty($is_shell_cmd_allowed))
	$main_content .= '<div class="warning">PHP "' . ShellCmdHandler::FUNCTION_NAME . '" function is disabled. please enable this function to proceed.</div>';
else if (empty($laravel_bin_path))
	$main_content .= '<div class="warning">Laravel bin path is undefined. Please be sure that laravel is installed globally and your web server has access to it.<br/>The current web server path is: "' . $apache_bin_path . '".</div>';
else if (empty($laravel_cmd_is_installed))
	$main_content .= '<div class="warning">Laravel command is not installed. Please install laravel command line to proceed.</div>';
?>
