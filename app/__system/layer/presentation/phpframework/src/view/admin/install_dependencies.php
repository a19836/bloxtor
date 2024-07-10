<?php
$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icon CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/install_dependencies.css" type="text/css" charset="utf-8" />';

$main_content = '<div class="install_dependencies">
	<form method="post">
		<div class="title">Install Dependencies</div>
		<div class="info">
			This framework utilizes external libraries with GPL and LGPL licenses. To access its full functionality, you should install them.<br/>
			Please make your choice by clicking one of the buttons below.<br/>
			We strongly recommend installing the dependencies.
		</div>
		
		<input type="submit" name="install" value="Install dependencies" />
		<input type="submit" name="continue" value="Continue without dependencies" />
	</form>
</div>';
?>
