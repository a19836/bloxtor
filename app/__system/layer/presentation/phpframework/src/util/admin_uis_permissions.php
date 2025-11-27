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

//prepare admin uis permissions
$is_admin_ui_simple_allowed = $UserAuthenticationHandler->isFilePermissionAllowed("simple", "admin_ui", "access");
$is_admin_ui_citizen_allowed = $UserAuthenticationHandler->isFilePermissionAllowed("citizen", "admin_ui", "access");
$is_admin_ui_advanced_allowed = $UserAuthenticationHandler->isFilePermissionAllowed("advanced", "admin_ui", "access");
$is_admin_ui_expert_allowed = $UserAuthenticationHandler->isFilePermissionAllowed("expert", "admin_ui", "access");

$admin_uis_count = 0;

if ($is_admin_ui_simple_allowed)
	$admin_uis_count++;
	
if ($is_admin_ui_citizen_allowed)
	$admin_uis_count++;

if ($is_admin_ui_advanced_allowed)
	$admin_uis_count++;
	
if ($is_admin_ui_expert_allowed)
	$admin_uis_count++;

$is_switch_admin_ui_allowed = $admin_uis_count > 1;
?>
