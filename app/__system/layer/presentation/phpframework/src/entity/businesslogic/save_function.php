<?php
include_once $EVC->getUtilPath("WorkFlowBusinessLogicHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

if (!empty($_POST["object"]))
	WorkFlowBusinessLogicHandler::prepareObjectIfIsBusinessLogicService($_POST["object"]);

$do_not_die_on_save = true;

include $EVC->getEntityPath("admin/save_file_function");

//delete caches
if (!empty($obj) && is_a($obj, "BusinessLogicLayer") && !empty($_POST) && !empty($status))
	CacheHandlerUtil::deleteFolder($obj->getCacheLayer()->getCachedDirPath(), false);

echo isset($status) ? $status : null;
die();
?>
