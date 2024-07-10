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
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/logs_console.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/logs_console.js"></script>

<script>
var is_popup = ' . ($popup ? 1 : 0) . ';
</script>';

$main_content = '
<div class="top_bar' . ($popup ? " in_popup" : "") . '">
	<header>
		<div class="title">Logs Console</div>
		<ul>
			<li class="refresh" data-title="Refresh"><a onClick="refresh()"><i class="icon refresh"></i> Refresh</a></li>
		</ul>
	</header>
</div>

<div class="logs_console ' . ($popup ? " in_popup" : "") . '">
	<div class="logs" file_created_time="' . $file_created_time . '" file_pointer="' . $file_pointer . '">
		<textarea readonly>' . str_replace('\n', "\n", htmlentities($output)) . '</textarea>
	</div>
</div>';
?>
