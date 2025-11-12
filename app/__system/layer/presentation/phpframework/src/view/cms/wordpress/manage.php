<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include $EVC->getUtilPath("BreadCrumbsUIHandler");

$selected_project_id = isset($selected_project_id) ? $selected_project_id : null;
$P = isset($P) ? $P : null;
$selected_db_driver = isset($selected_db_driver) ? $selected_db_driver : null;

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/cms/wordpress/manage.css" type="text/css" charset="utf-8" />
';

$main_content = '
<div class="top_bar">
	<header>
		<div class="title" title="' . $path . '">Manage WordPress in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($selected_project_id, $P) . '</div>
	</header>
</div>
<div class="manage_wordpress with_top_bar_section">
	<form method="post">';

if (version_compare(PHP_VERSION, '7.2', '>'))
	$main_content .= '<div class="php_version">Our current version of WordPress only works with PHP versions 5.6 until 7.2. If you continue, WordPress can be unstable...</div>';

if (isset($projects) && is_array($projects)) {
	$main_content .= '
		<div class="project">
			<label>Please choose a Project:</label>
			<select name="project">';
	
	foreach ($projects as $project)
		$main_content .= '<option>' . $project . '</option>';
	
	$main_content .= '
			</select>
		</div>';
}
$main_content .= '
		<div class="db_driver">
			<label>Please choose a DB Driver:</label>
			<select name="db_driver">';

if (!empty($layer_db_drivers)) {
	$installed_options = $non_installed_options = "";
	
	foreach ($layer_db_drivers as $db_driver_name => $db_driver_props) {
		$is_installed = in_array($db_driver_name, $installed_wordpress_folders_name);
		
		$option = '<option value="' . $db_driver_name . '"' . ($selected_db_driver == $db_driver_name ? ' selected' : '') . '>' . $db_driver_name . ($db_driver_props ? '' : ' (Rest)') . '</option>';
		
		if (!empty($is_installed))
			$installed_options .= $option;
		else
			$non_installed_options .= $option;
	}
	
	foreach ($installed_wordpress_folders_name as $folder_name)
		if (!array_key_exists($folder_name, $layer_db_drivers))
			$installed_options .= '<option value="' . $folder_name . '"' . ($selected_db_driver == $folder_name ? ' selected' : '') . '>' . ucwords(str_replace("_", " ", $folder_name)) . ' - INACCESSIBLE DB DRIVER</option>';
	
	if ($installed_options)
		$main_content .= "<optgroup label=\"Installed\">$installed_options</optgroup>";
	
	if ($non_installed_options)
		$main_content .= "<optgroup label=\"Not Installed yet\">$non_installed_options</optgroup>";
}
	
$main_content .= '
			</select>
		</div>
		
		<div class="buttons">
			<input type="submit" name="go_to_wordpress" value="Go to WordPress" />
			<input type="submit" name="install_wordpress" value="Install WordPress" />
		</div>
	</form>
</div>';
?>
