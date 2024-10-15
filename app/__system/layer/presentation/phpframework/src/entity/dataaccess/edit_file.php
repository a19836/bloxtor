<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

if (!empty($_POST))
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$do_not_die_on_save = true;

include $EVC->getEntityPath("admin/edit_raw_file");

if (!empty($_POST) && !empty($ret)) {
	if (!empty($obj) && is_a($obj, "DataAccessLayer") && !empty($ret["status"])) {
		//delete caches
		$cache_path = $obj->getCacheLayer()->getCachedDirPath() . "/" . (is_a($obj, "IbatisDataAccessLayer") ? IBatisClientCache::CACHE_DIR_NAME : HibernateClientCache::CACHE_DIR_NAME);
		CacheHandlerUtil::deleteFolder($cache_path, false);
	}
	
	echo json_encode($ret);
	die();
}
?>
