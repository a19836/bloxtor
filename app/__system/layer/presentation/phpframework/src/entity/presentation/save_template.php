<?php
include $EVC->getUtilPath("WorkFlowPresentationHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$file_type = "save_template";

/*DEPRECATED with the new UI in edit_template_simple. Note that this file is being used for the edit_template_advanced too.
if ($_POST["object"]["code"]) {
	$_POST["object"]["code"] = WorkFlowPresentationHandler::convertCodeTagsToHtmlTags($_POST["object"]["code"]);
}*/

include $EVC->getEntityPath("presentation/save");
?>
