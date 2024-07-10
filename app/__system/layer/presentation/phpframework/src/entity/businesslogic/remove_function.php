<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");

if ($_GET["function"]) {
	$_GET["item_type"] = "businesslogic";
	$do_not_die_on_save = true;

	include $EVC->getEntityPath("admin/remove_file_function");

	//delete caches
	if ($obj && is_a($obj, "BusinessLogicLayer") && $_POST && $status) 
		CacheHandlerUtil::deleteFolder($obj->getCacheLayer()->getCachedDirPath(), false);

	die($status);
}
die();
?>
