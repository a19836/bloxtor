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
<div class="framework_update">
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
		$main_content .= '<div>Update finished!</div>
		<pre class="code">' . $output . '</pre>
		<div>To refresh the framework please click <a href="javascript:void(0)" onClick="window.parent.document.location=window.parent.document.location;">here</a></div>';
	}
	else if ($step == 1 && !empty($changed_files)) {
		$changed_authdb = false;
		
		foreach ($changed_files as $file)
			if (preg_match("/^other\/authdb\/\w+\.tbl$/", $file)) {
				$changed_authdb = true;
				break;
			}
		$changed_authdb = false;
		
		$main_content .= '
			<div>We detected that the following system files were changed:</div>
			<ul><li>' . implode('</li><li>', $changed_files) . '</li></ul>
			
			<div class="warning"><u>If you continue, all changes will be discarded and replaced with the original files</u>.<br/>If you want to keep your changes and merge them with the new ones, contact your system administrator to perform the update manually.</div>
			' . ($changed_authdb ? '<div class="warning">Please note that your authdb has changed, which means you may have changed/created user credentials, or defined new permissions or user types or something similar...<br/><strong>If you continue you will loose all your changes.</strong></div>' : '') . '
			<div>To proceed to the update, discarding your changes, please click on the button below.</div>
			<input type="submit" name="update" value="Continue updating"/>';
	}
	else
		$main_content .= '
			<div>To update to the latest version of the Framework please click in the following button:</div>
			<div>
				<input type="submit" name="update" value="Update to the latest version"/>
			<div>';

	$main_content .= '</form>';
}
else {
	$cms_path_str = preg_match("/\s/", CMS_PATH) ? '"' . CMS_PATH . '"' : CMS_PATH;
	$main_content .= '<div><strong>Update NOT possible, due to permission issues.</strong><br/>Please contact your system administrator to run the following command on your server as root user:</div>
	<pre class="command">sudo /bin/bash ' . $cms_path_str . 'other/script/update_git_repo.sh ' . $cms_path_str . '</pre>
	<div>If you want to keep your changes and merge them with the new ones, contact your system administrator to perform the update manually.</div>';
}

$main_content .= '</div>';
?>
