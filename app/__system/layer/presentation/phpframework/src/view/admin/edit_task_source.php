<?php
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
