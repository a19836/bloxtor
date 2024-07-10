<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$_GET["item_type"] = "businesslogic";
$_GET["class"] = $_GET["service"];
$include_annotations = true;

include $EVC->getEntityPath("admin/edit_file_class_method");

if ($obj_data) {
	include_once $EVC->getUtilPath("WorkFlowBusinessLogicHandler");
	
	$obj_data["is_business_logic_service"] = WorkFlowBusinessLogicHandler::isBusinessLogicService($obj_data);
}
?>
