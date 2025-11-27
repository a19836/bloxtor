<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

include_once $EVC->getUtilPath("WorkFlowBusinessLogicHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

if (!empty($_POST["object"]))
	WorkFlowBusinessLogicHandler::prepareServiceObjectForsaving($_POST["object"]);

$do_not_die_on_save = true;

include $EVC->getEntityPath("admin/save_file_includes");

//delete caches
if (!empty($obj) && is_a($obj, "BusinessLogicLayer") && !empty($_POST) && !empty($status)) 
	CacheHandlerUtil::deleteFolder($obj->getCacheLayer()->getCachedDirPath(), false);

echo isset($status) ? $status : null;
die();
?>
