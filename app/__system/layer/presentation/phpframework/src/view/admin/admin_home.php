<?php
include $EVC->getViewPath("admin/choose_available_project");
$projects_head = $head;
$projects_main_content = $main_content;

include $EVC->getViewPath("admin/choose_available_tutorial");
$tutorials_head = $head;
$tutorials_main_content = $main_content;

$logged_name = $UserAuthenticationHandler->auth["user_data"]["name"] ? $UserAuthenticationHandler->auth["user_data"]["name"] : $UserAuthenticationHandler->auth["user_data"]["username"];

$head = $projects_head . $tutorials_head . '
<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/admin_home.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/admin_home.js"></script>

<script>
var is_popup = 1; //must be 1 so when we choose the project, it refresh the main panel.
</script>
';

$main_content = '
<div class="admin_panel">
	<div class="title">Welcome to Bloxtor, ' . $logged_name . '</div>
	
	<ul>
		<li><a href="#projs">All Projects</a></li>
		<li><a href="#tutorials">Video Tutorials</a></li>
		<li><a onClick="openOnlineTutorialsPopup(\'' . $online_tutorials_url_prefix . '\')">How it works?</a></li>
	</ul>
	
	<div id="projs" class="projs">
		' . $projects_main_content . '
	</div>
	
	<div id="tutorials" class="tutorials">
		' . $tutorials_main_content . '
	</div>
</div>';
?>
