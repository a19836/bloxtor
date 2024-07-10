<?php
include_once $EVC->getUtilPath("AdminMenuUIHandler");

if (!$is_admin_ui_expert_allowed) {
	echo '<script>
		alert("You don\'t have permission to access this Workspace!");
		document.location="' . $project_url_prefix . 'auth/logout";
	</script>';
	die();
}

$filter_by_layout_url_query = $filter_by_layout ? "&filter_by_layout=$filter_by_layout&filter_by_layout_permission=$filter_by_layout_permission" : "";

$head = AdminMenuUIHandler::getHeader($project_url_prefix, $project_common_url_prefix);
$head = '
<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/admin_expert.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/admin_expert.js"></script>
';

$main_content = '
<div id="main_menu">
	<ul class="dropdown">
		<li></li>
	</ul>
</div>
<div id="content">
	<script>
		alert("Sorry but you are not an expert!\nYou will be redirected to the previous screen...");
		history.back();
	</script>
	
	<!--div class="iframe_overlay">
		<div class="iframe_loading">Loading...</div>
	</div>
	<iframe src="' . "{$project_url_prefix}admin/" . ($filter_by_layout ? "admin_home_project?$filter_by_layout_url_query" : "admin_home?selected_layout_project=$filter_by_layout") . '"></iframe-->
</div>';
?>
