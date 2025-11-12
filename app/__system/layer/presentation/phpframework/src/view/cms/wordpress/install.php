<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$selected_project_id = isset($selected_project_id) ? $selected_project_id : null;
$P = isset($P) ? $P : null;
$db_driver = isset($db_driver) ? $db_driver : null;
$msg = isset($msg) ? $msg : null;

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/cms/wordpress/install.css" type="text/css" charset="utf-8" />
';

$msg = !empty($is_installed) ? 'already has the wordpress installed!<br/>If you wish to reinstalled it please click in the button bellow, but all wordpress\'s previous data will be lost...' : 'doesn\'t have the wordpress installed.<br/>To proceed with it installation, please click in the button bellow.<br/>Note that the Wordpress framework has a GPL licence.';

$main_content = '
<div class="top_bar">
	<header>
		<div class="title" title="' . $path . '">Install WordPress in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($selected_project_id, $P) . '</div>
	</header>
</div>
<div class="install_wordpress with_top_bar_section">
	' . (version_compare(PHP_VERSION, '7.2', '>') ? '<div class="php_version">Our current version of WordPress only works with PHP versions 5.6 until 7.2. If you continue, WordPress can be unstable...</div>' : '') . '
	<label>The DB Driver "' . $db_driver . '" ' . $msg . '</label>
	
	<form method="post">
		<input class="button" type="submit" name="install" value="' . (!empty($is_installed) ? 'Reinstall' : 'Install') . ' WordPress in \'' . $db_driver . '\' DB Driver" name="submit" onClick="$(this).parent().prepend(\'<div>Installing...</div>\').find(\'input, p\').hide()">
		
		' . (!empty($is_installed) ? '<input class="button" type="submit" name="hack" value="Re-Hacking WordPress in \'' . $db_driver . '\' DB Driver" name="submit" onClick="$(this).parent().prepend(\'<div>Hacking...</div>\').find(\'input, p\').hide()">' : '') . '
		
		' . (!empty($is_installed) ? '<p>Note that Reinstalling or Re-Hacking WordPress is extremelly inadvisable and imprudent.<br/>Are you really sure, you wish to continue?</p>' : '') . '
	</form>
</div>';

if (!empty($error_message))
	$main_content .= '<script>alert("' . addcslashes($error_message, '"') . '");</script>';
?>
