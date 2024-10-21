<?php
$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icon CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/framework_update.css" type="text/css" charset="utf-8" />
';

$main_content = '
<div class="git_update">
	<div class="top_bar">
		<header>
			<div class="title">Git Update</div>
		</header>
	</div>';

if ($is_remote_update_allowed) {	
	$main_content .= '
	<form method="post">
		<input type="hidden" name="step" value="' . ($step + 1) . '" />';

	if ($step == 2 || ($step == 1 && empty($changed_files))) {
		$output = !empty($output) ? implode("\n", $output) : "No output detected.<br/>Please talk with your sysadmin to be sure everything runned as planned.";
		$main_content .= '<label>Update finished!</label>
		<pre class="code">' . $output . '</pre>';
	}
	else if ($step == 1 && !empty($changed_files)) {
		$main_content .= '
			<label>We detected that the following system files were changed:</label>
			<ul><li>' . implode('</li><li>', $changed_files) . '</li></ul>
			
			<div>If you continue, all the changes will be discarded and replaced with the original files.<br/>To continue with the update, please click on the button below.</div>
			<input type="submit" name="update" value="Continue updating"/>';
	}
	else
		$main_content .= '
			<label>To update to the latest version of the Framework please click in the following button:</label>
			<div>
				<input type="submit" name="update" value="Update to the latest version"/>
			<div>';

	$main_content .= '</form>';
}
else
	$main_content .= '<label>The update failed due to permission issues.<br/>Please contact your system administrator to run the following command on your server as root user:</label>
	<pre class="command">sudo /bin/bash "' . CMS_PATH . '"other/script/update_git_repo.sh "' . CMS_PATH . '"</pre>';

$main_content .= '</div>';
?>
