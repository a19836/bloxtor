<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layout_type_id = isset($_GET["layout_type_id"]) ? $_GET["layout_type_id"] : null;

if ($layout_type_id) {
	$layout_type_permissions = $UserAuthenticationHandler->searchLayoutTypePermissions(array("layout_type_id" => $layout_type_id));
}
?>
