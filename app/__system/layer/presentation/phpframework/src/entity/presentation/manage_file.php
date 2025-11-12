<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

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
