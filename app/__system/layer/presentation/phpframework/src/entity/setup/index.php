<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$step = isset($_GET["step"]) ? $_GET["step"] : null;
$is_inside_of_iframe = !empty($_GET["iframe"]);

switch ($step) {
	case 1: $page = "/setup/terms_and_conditions"; break;
	case 2: $page = "/setup/project_name"; break;
	case 3: $page = "/setup/db"; break;
	case 3.1: $page = "/setup/layers"; break;
	case 4: $page = "/setup/end"; break;
	default: $page = "/setup/terms_and_conditions";
}

$entity_path = $EVC->getEntityPath($page);
include $entity_path;
?>
