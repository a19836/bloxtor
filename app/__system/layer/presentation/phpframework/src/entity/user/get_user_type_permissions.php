<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$user_type_id = $_GET["user_type_id"];

if ($user_type_id) {
	$user_type_permissions = $UserAuthenticationHandler->searchUserTypePermissions(array("user_type_id" => $user_type_id));
}
?>
