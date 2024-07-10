<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");

if ($_GET["service"]) {
	$_GET["item_type"] = "businesslogic";
	$_GET["class"] = $_GET["service"];
	$_GET["remove_file_if_no_class"] = true;
	$do_not_die_on_save = true;

	include $EVC->getEntityPath("admin/remove_file_class");
	
	//delete caches
	if ($obj && is_a($obj, "BusinessLogicLayer") && $_POST && $status) 
		CacheHandlerUtil::deleteFolder($obj->getCacheLayer()->getCachedDirPath(), false);
	
	die($status);
}
die();
?>
