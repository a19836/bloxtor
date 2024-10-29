<?php
$file_created_time = isset($file_created_time) ? $file_created_time : null;
$file_pointer = isset($file_pointer) ? $file_pointer : null;
$output = isset($output) ? $output : null;

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
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/terminal_console.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/terminal_console.js"></script>

<script>
var is_popup = ' . ($popup ? 1 : 0) . ';
</script>';

$main_content = '
<div class="top_bar' . ($popup ? " in_popup" : "") . '">
	<header>
		<div class="title">Terminal Console</div>
		<ul>
			<li class="info" data-title="Info"><a onClick="$(\'.terminal_console\').toggleClass(\'show_info\');"><i class="icon info"></i> Info</a></li>
			<li class="refresh" data-title="Refresh"><a onClick="refresh()"><i class="icon refresh"></i> Refresh</a></li>
		</ul>
	</header>
</div>

<div class="terminal_console ' . ($popup ? " in_popup" : "") . '">
	<div class="output"></div>
	<div class="input">
		<form class="form" method="GET" onSubmit="sendCommand()">
			<div class="username"></div>
			<input class="input_text" type="text" name="cmd" autocomplete="off" autofocus>
		</form>
	</div>
	
	<form class="upload" method="POST">
		<input type="file" name="file" class="file_browser" onchange="uploadFile()" />
	</form>
	
	<div class="info">
		Type any command that you would type in your shell terminal.<br/>
		Some commands special:
		<ul>
			<li>"upload" to upload files to the current directory.</li>
		</ul>
	</div>
</div>';
?>
