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

$edit_type = isset($edit_type) ? $edit_type : null;
$edit_type_label = str_replace("_", " ", $edit_type);

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icon CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/edit_task_source.css" type="text/css" charset="utf-8" />
';

$main_content = '
<div class="top_bar' . ($popup ? " in_popup" : "") . '">
	<header>
		<div class="title">Edit ' . ucwords($edit_type_label) . '</div>
	</header>
</div>

<div class="invalid">Error: Could not detect ' . $edit_type . '\'s path.' . (!empty($error_message) ? "<br/><br/>" . $error_message : "") . '</div>';
?>
