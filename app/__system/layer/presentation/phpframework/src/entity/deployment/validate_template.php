<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once $EVC->getUtilPath("WorkFlowDeploymentHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$server_name = isset($_GET["server"]) ? $_GET["server"] : null;
$template_id = isset($_GET["template_id"]) ? $_GET["template_id"] : null;

$li = $EVC->getPresentationLayer()->getPHPFrameWork()->getLicenceInfo();
$error_message = isset($error_message) ? $error_message : null;
$status = WorkFlowDeploymentHandler::validateTemplate($server_name, $template_id, $workflow_paths_id, $li, $error_message);

echo $error_message ? $error_message : $status;
die();
?>
