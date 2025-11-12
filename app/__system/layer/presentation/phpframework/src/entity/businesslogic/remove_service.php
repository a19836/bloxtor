<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");

if (!empty($_GET["service"])) {
	$_GET["item_type"] = "businesslogic";
	$_GET["class"] = $_GET["service"];
	$_GET["remove_file_if_no_class"] = true;
	$do_not_die_on_save = true;

	include $EVC->getEntityPath("admin/remove_file_class");
	
	//delete caches
	if (!empty($obj) && is_a($obj, "BusinessLogicLayer") && !empty($_POST) && !empty($status))
		CacheHandlerUtil::deleteFolder($obj->getCacheLayer()->getCachedDirPath(), false);
	
	echo isset($status) ? $status : null;
	die();
}
die();
?>
