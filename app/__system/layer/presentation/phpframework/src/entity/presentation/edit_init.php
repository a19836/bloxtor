<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

if (!empty($_POST))
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

include $EVC->getEntityPath("admin/edit_raw_file");
?>
