<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

if (isset($_GET["action"]) && $_GET["action"] == "remove")
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");
else { //create_folder, create_file, rename, upload, paste, paste_and_remove
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
	
	if (isset($_GET["action"]) && $_GET["action"] == "paste_and_remove")
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");
}

include $EVC->getEntityPath("admin/manage_file");
?>
