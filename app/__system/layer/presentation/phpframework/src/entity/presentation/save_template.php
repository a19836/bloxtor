<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include $EVC->getUtilPath("WorkFlowPresentationHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$file_type = "save_template";

/*DEPRECATED with the new UI in edit_template_simple. Note that this file is being used for the edit_template_advanced too.
if (!empty($_POST["object"]["code"])) {
	$_POST["object"]["code"] = WorkFlowPresentationHandler::convertCodeTagsToHtmlTags($_POST["object"]["code"]);
}*/

include $EVC->getEntityPath("presentation/save");
?>
