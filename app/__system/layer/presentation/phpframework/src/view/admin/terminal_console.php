<?php
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
var is_allowed = ' . ($is_allowed ? 1 : 0) . ';
var hide_dir_prefix = "' . CMS_PATH . '";
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

<div class="terminal_console ' . ($popup ? " in_popup" : "") . '">';

if ($is_allowed) {
	$main_content .= '
	<div class="output"></div>
	<div class="input">
		<form class="form" method="GET" onSubmit="sendCommand()">
			<div class="username"></div>
			<div class="hash">#</div>
			<input class="input_text" type="text" name="cmd" autocomplete="off" autofocus>
			<span class="loading"></span>
		</form>
	</div>
	
	<form class="upload" method="POST">
		<input type="file" name="file" class="file_browser" onchange="uploadFile()" />
	</form>
	
	<div class="info">
		Type any command you would like in the shell terminal at the bottom-right corner, then press Enter.<br/>
		Some commands special:
		<ul>
			<li>"upload" to upload files to the current directory.</li>
		</ul>
	</div>';
}
else
	$main_content .= '<div class="error">Error: ' . ShellCmdHandler::FUNCTION_NAME . ' function is disabled. To allow terminal access through this page, please talk with your SysAdmin to enable this function.</div>';

$main_content .= '</div>';
?>
