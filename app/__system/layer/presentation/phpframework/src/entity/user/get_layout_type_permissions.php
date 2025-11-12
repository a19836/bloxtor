<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layout_type_id = isset($_GET["layout_type_id"]) ? $_GET["layout_type_id"] : null;

if ($layout_type_id) {
	$layout_type_permissions = $UserAuthenticationHandler->searchLayoutTypePermissions(array("layout_type_id" => $layout_type_id));
}
?>
