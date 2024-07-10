<?php
include_once $EVC->getUtilPath("WorkFlowBusinessLogicHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

if ($_POST["object"])
	WorkFlowBusinessLogicHandler::prepareObjectIfIsBusinessLogicService($_POST["object"]);

$do_not_die_on_save = true;

include $EVC->getEntityPath("admin/save_file_function");

//delete caches
if ($obj && is_a($obj, "BusinessLogicLayer") && $_POST && $status) 
	CacheHandlerUtil::deleteFolder($obj->getCacheLayer()->getCachedDirPath(), false);

die($status);
?>
