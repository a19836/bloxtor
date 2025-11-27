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

$file_created_time = isset($file_created_time) ? $file_created_time : null;
$file_pointer = isset($file_pointer) ? $file_pointer : null;
$output = isset($output) ? $output : null;

$manage_ai_action_url = $openai_encryption_key ? $project_url_prefix . "phpframework/ai/manage_ai_action" : null;

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Filemanager CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/file_manager.css" type="text/css" charset="utf-8" />

<!-- Icons CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS and JS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/logs_console.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/logs_console.js"></script>

<script>
var is_popup = ' . ($popup ? 1 : 0) . ';
var manage_ai_action_url = "' . $manage_ai_action_url . '";
</script>';

$main_content = '
<div class="top_bar' . ($popup ? " in_popup" : "") . '">
	<header>
		<div class="title">Logs Console</div>
		<ul>
			<li class="ai" data-title="Explain Logs through AI"><a onClick="explainLogs()"><i class="icon ai"></i> AI</a></li>
			<li class="refresh" data-title="Refresh"><a onClick="refresh()"><i class="icon refresh"></i> Refresh</a></li>
		</ul>
	</header>
</div>

<div class="logs_console ' . ($popup ? " in_popup" : "") . '">
	<div class="logs" file_created_time="' . $file_created_time . '" file_pointer="' . $file_pointer . '">
		<textarea readonly>' . str_replace('\n', "\n", htmlentities($output)) . '</textarea>
	</div>
	<div class="ai_replies">
		<span class="icon close" onClick="closeLogsExplanation()"></span>
		<ul></ul>
	</div>
</div>';
?>
