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

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");
$is_admin_ui_advanced_allowed = $UserAuthenticationHandler->isFilePermissionAllowed("advanced", "admin_ui", "access");

if (empty($is_admin_ui_advanced_allowed)) {
	echo '<script>
		alert("You don\'t have permission to access this Workspace!");
		document.location="' . $project_url_prefix . 'auth/logout";
	</script>';
	die();
}

$default_page = isset($_GET["default_page"]) ? $_GET["default_page"] : null;
$tree_layout = isset($_GET["tree_layout"]) ? $_GET["tree_layout"] : null;
$advanced_level = isset($_GET["advanced_level"]) ? $_GET["advanced_level"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null;
$filter_by_layout_permission = UserAuthenticationHandler::$PERMISSION_BELONG_NAME;

if ($default_page)
	CookieHandler::setCurrentDomainEternalRootSafeCookie("default_page", $default_page);
else if (!empty($_COOKIE["default_page"]))
	$default_page = $_COOKIE["default_page"];

if (isset($_GET) && array_key_exists("advanced_level", $_GET)) {
	$advanced_level = $advanced_level;
	CookieHandler::setCurrentDomainEternalRootSafeCookie("advanced_level", $advanced_level);
}
else if (!empty($_COOKIE["advanced_level"]))
	$advanced_level = $_COOKIE["advanced_level"];
else
	$advanced_level = "simple_level";

if (isset($_GET) && array_key_exists("tree_layout", $_GET)) {
	$tree_layout = $tree_layout;
	CookieHandler::setCurrentDomainEternalRootSafeCookie("tree_layout", $tree_layout);
}
else if (!empty($_COOKIE["tree_layout"]))
	$tree_layout = $_COOKIE["tree_layout"];
else
	$tree_layout = "left_panel_with_tabs";

if (isset($_GET) && array_key_exists("theme_layout", $_GET)) {
	$_COOKIE["theme_layout"] = $_GET["theme_layout"]; //set cookie directly so it takes efect in the template->body class
	CookieHandler::setCurrentDomainEternalRootSafeCookie("theme_layout", $_GET["theme_layout"]);
}

if (isset($_GET) && array_key_exists("main_navigator_side", $_GET)) {
	$_COOKIE["main_navigator_side"] = $_GET["main_navigator_side"]; //set cookie directly so it takes efect in the template->body class
	CookieHandler::setCurrentDomainEternalRootSafeCookie("main_navigator_side", $_GET["main_navigator_side"]);
}

if (isset($_GET) && array_key_exists("filter_by_layout", $_GET))
	CookieHandler::setCurrentDomainEternalRootSafeCookie("filter_by_layout", $filter_by_layout);
else if (!empty($_COOKIE["filter_by_layout"]))
	$filter_by_layout = $_COOKIE["filter_by_layout"];

$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

include $EVC->getUtilPath("admin_uis_layers_and_permissions");

//echo "<pre>";print_r($layers);die();
//echo "<pre>";print_r($presentation_projects_by_layer_label_and_folders);print_r($non_projects_layout_types);die();
//echo "<pre>";print_r($presentation_projects_by_layer_label);die();
?>
