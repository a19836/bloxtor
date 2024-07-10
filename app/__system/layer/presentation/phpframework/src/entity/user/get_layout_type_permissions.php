<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layout_type_id = $_GET["layout_type_id"];

if ($layout_type_id) {
	$layout_type_permissions = $UserAuthenticationHandler->searchLayoutTypePermissions(array("layout_type_id" => $layout_type_id));
}
?>
