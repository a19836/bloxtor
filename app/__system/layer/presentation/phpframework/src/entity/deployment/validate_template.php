<?php
include_once $EVC->getUtilPath("WorkFlowDeploymentHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$server_name = $_GET["server"];
$template_id = $_GET["template_id"];

$li = $EVC->getPresentationLayer()->getPHPFrameWork()->getLicenceInfo();
$status = WorkFlowDeploymentHandler::validateTemplate($server_name, $template_id, $workflow_paths_id, $li, $error_message);

echo $error_message ? $error_message : $status;
die();
?>
