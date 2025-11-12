<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$_GET["item_type"] = "businesslogic";
$_GET["class"] = isset($_GET["service"]) ? $_GET["service"] : null;
$include_annotations = true;

include $EVC->getEntityPath("admin/edit_file_class_method");

if (!empty($obj_data)) {
	include_once $EVC->getUtilPath("WorkFlowBusinessLogicHandler");
	
	$obj_data["is_business_logic_service"] = WorkFlowBusinessLogicHandler::isBusinessLogicService($obj_data);
}
?>
