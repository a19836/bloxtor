<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

if (!empty($_POST))
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$file_type = "edit_config";

include $EVC->getEntityPath("presentation/edit");
?>
