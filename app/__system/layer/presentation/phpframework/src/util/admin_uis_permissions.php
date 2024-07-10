<?php
//prepare admin uis permissions
$is_admin_ui_simple_allowed = $UserAuthenticationHandler->isFilePermissionAllowed("simple", "admin_ui", "access");
$is_admin_ui_citizen_allowed = $UserAuthenticationHandler->isFilePermissionAllowed("citizen", "admin_ui", "access");
$is_admin_ui_low_code_allowed = $UserAuthenticationHandler->isFilePermissionAllowed("low_code", "admin_ui", "access");
$is_admin_ui_advanced_allowed = $UserAuthenticationHandler->isFilePermissionAllowed("advanced", "admin_ui", "access");
$is_admin_ui_expert_allowed = $UserAuthenticationHandler->isFilePermissionAllowed("expert", "admin_ui", "access");

$admin_uis_count = 0;

if ($is_admin_ui_simple_allowed)
	$admin_uis_count++;
	
if ($is_admin_ui_citizen_allowed)
	$admin_uis_count++;
	
if ($is_admin_ui_low_code_allowed)
	$admin_uis_count++;

if ($is_admin_ui_advanced_allowed)
	$admin_uis_count++;
	
if ($is_admin_ui_expert_allowed)
	$admin_uis_count++;

$is_switch_admin_ui_allowed = $admin_uis_count > 1;
?>
