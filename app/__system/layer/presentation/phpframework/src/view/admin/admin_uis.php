<?php
include_once $EVC->getUtilPath("AdminMenuUIHandler");
include_once $EVC->getUtilPath("HeatMapHandler");

$head = AdminMenuUIHandler::getHeader($project_url_prefix, $project_common_url_prefix);
$head .= '
<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/admin_uis.css" type="text/css" charset="utf-8" />';

if (!$admin_uis_count) {
	echo '<script>
		alert("This logged user doesn\'t have access to any Workspace. Please contact the sysadmin and ask him to give you permission to at least 1 Workspace.");
		document.location = "' . $project_url_prefix . 'auth/logout";
	</script>';
	die();
}
else if ($admin_uis_count == 1) {
	$default_admin_type = $is_admin_ui_simple_allowed ? "simple" : (
		$is_admin_ui_citizen_allowed ? "citizen" : (
			$is_admin_ui_low_code_allowed ? "low_code" : (
				$is_admin_ui_advanced_allowed ? "advanced" : "expert"
			)
		)
	);
	header("Location: {$project_url_prefix}admin?admin_type=" . $default_admin_type);
}

$head .= HeatMapHandler::getHtml($project_url_prefix);

$main_content = '<div id="title">Please choose your workspace:</div>
<ul>
	' . ($is_admin_ui_simple_allowed ? '<li class="ui simple_admin_ui" onClick="document.location=\'' . $project_url_prefix . 'admin?admin_type=simple&filter_by_layout=' . $filter_by_layout . '\'">
		<div class="icon photo"></div>
		<label class="title">Simple Workspace</label>
		<div class="sub_title">For No-Coders</div>
		<div class="description">Workspace based in No-Code for non-technical people or people with very little technical knowledge.</div>
		<div class="button">Select this Workspace</div>
	</li>' : '') . '
	' . ($is_admin_ui_citizen_allowed ? '<li class="ui citizen_admin_ui" onClick="document.location=\'' . $project_url_prefix . 'admin?admin_type=citizen\'">
		<div class="icon photo"></div>
		<label class="title">Citizen-Dev Workspace</label>
		<div class="sub_title">For Citizen-Developers</div>
		<div class="description">Workspace based in No-Code and Low-Code for all tech-savvy citizens, this is, with some basic technical knowledge.</div>
		<div class="button">Select this Workspace</div>
	</li>' : '') . '
	' . ($is_admin_ui_low_code_allowed ? '<li class="ui low_code_admin_ui" onClick="document.location=\'' . $project_url_prefix . 'admin?admin_type=low_code\'">
		<div class="icon photo"></div>
		<label class="title">Low-Code Workspace</label>
		<div class="sub_title">For Low-Coders.</div>
		<div class="description">Workspace for all professionals who usually work in Low-Code.</div>
		<div class="button">Select this Workspace</div>
	</li>' : '') . '
	' . ($is_admin_ui_advanced_allowed ? '<li class="ui advanced_admin_ui" onClick="document.location=\'' . $project_url_prefix . 'admin?admin_type=advanced\'">
		<div class="icon photo"></div>
		<label class="title">Advanced Workspace</label>
		<div class="sub_title">For Low-Coders or Coders</div>
		<div class="description">Workspace for technical people or programmers.</div>
		<div class="button">Select this Workspace</div>
	</li>' : '') . '
	' . ($is_admin_ui_expert_allowed ? '<li class="ui expert_admin_ui" onClick="document.location=\'' . $project_url_prefix . 'admin?admin_type=expert\'">
		<div class="icon photo"></div>
		<label class="title">Expert Workspace</label>
		<div class="sub_title">For experts and ninjas only</div>
		<div class="description">File manager to edit raw files.</div>
		<div class="button">Select this Workspace</div>
	</li>' : '') . '
</ul>';
?>
