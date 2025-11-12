<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$user_type_id = isset($_GET["user_type_id"]) ? $_GET["user_type_id"] : null;

if ($user_type_id) {
	$user_type_permissions = $UserAuthenticationHandler->searchUserTypePermissions(array("user_type_id" => $user_type_id));
}
?>
