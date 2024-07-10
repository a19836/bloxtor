<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$_GET["item_type"] = "businesslogic";
$include_annotations = true;

include $EVC->getEntityPath("admin/edit_file_function");

if ($obj_data) {
	include_once $EVC->getUtilPath("WorkFlowBusinessLogicHandler");
	
	$obj_data["is_business_logic_service"] = WorkFlowBusinessLogicHandler::isBusinessLogicService($obj_data);
}
?>
