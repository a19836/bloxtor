<?php
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

$msg = $is_installed ? 'already has the wordpress installed!<br/>If you wish to reinstalled it please click in the button bellow, but all wordpress\'s previous data will be lost...' : 'doesn\'t have the wordpress installed.<br/>To proceed with it installation, please click in the button bellow.<br/>Note that the Wordpress framework has a GPL licence.';

$main_content = '
<div class="top_bar">
	<header>
		<div class="title" title="' . $path . '">Install WordPress in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($selected_project_id, $P) . '</div>
	</header>
</div>
<div class="install_wordpress with_top_bar_section">
	<label>The DB Driver "' . $db_driver . '" ' . $msg . '</label>
	
	<form method="post">
		<input class="button" type="submit" name="install" value="' . ($is_installed ? 'Reinstall' : 'Install') . ' WordPress in \'' . $db_driver . '\' DB Driver" name="submit" onClick="$(this).parent().prepend(\'<div>Installing...</div>\').find(\'input, p\').hide()">
		
		' . ($is_installed ? '<input class="button" type="submit" name="hack" value="Re-Hacking WordPress in \'' . $db_driver . '\' DB Driver" name="submit" onClick="$(this).parent().prepend(\'<div>Hacking...</div>\').find(\'input, p\').hide()">' : '') . '
		
		' . ($is_installed ? '<p>Note that Reinstalling or Re-Hacking WordPress is extremelly inadvisable and imprudent.<br/>Are you really sure, you wish to continue?</p>' : '') . '
	</form>
</div>';

if ($error_message)
	$main_content .= '<script>alert("' . addcslashes($error_message, '"') . '");</script>';
?>
