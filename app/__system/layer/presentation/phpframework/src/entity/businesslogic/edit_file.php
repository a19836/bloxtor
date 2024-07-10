<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

if ($_POST)
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$do_not_die_on_save = true;

include $EVC->getEntityPath("admin/edit_raw_file");

if ($_POST && $ret) {
	if ($obj && is_a($obj, "BusinessLogicLayer") && $ret["status"]) {
		//delete caches
		CacheHandlerUtil::deleteFolder($obj->getCacheLayer()->getCachedDirPath(), false);
	}
	
	echo json_encode($ret);
	die();
}
?>
