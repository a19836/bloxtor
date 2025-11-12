<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$head = '
<!-- Ace Editor file -->
<script src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ace.js"></script>
<script src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ext-language_tools.js"></script>

<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Icons CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/layout.js"></script>

<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/db/create_diagram_sql.css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/db/create_diagram_sql.js"></script>';

$main_content = '<div class="create_diagram_sql">
	<div class="top_bar' . ($popup ? " in_popup" : "") . '">
		<header>
			<div class="title">DB Diagram\'s SQL for DB: \'' . $bean_name . '\'</div>
			<ul>
				<li class="execute" data-title="Execute SQL"><a onClick="execute()"><i class="icon continue"></i> Execute</a></li>
			</ul>
		</header>
	</div>';

if (!empty($_POST)) {
	$main_content .= '
	<div class="status_' . (!empty($status) ? 'ok' : 'error') . '">' . (!empty($status) ? 'SQL executed successfully' : 'SQL executed unssuccessfully') . '</div>
	';
}

if (empty($_POST) || empty($status)) {
	$main_content .= '
	<div class="sql_text_area">
		<textarea>' . "\n" . (isset($sql) ? htmlspecialchars($sql, ENT_NOQUOTES) : "") . '</textarea>
	</div>';
}

$main_content .= '</div>';
?>
