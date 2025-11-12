<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once $EVC->getUtilPath("SequentialLogicalActivitySettingsCodeCreator");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$selected_project_id = isset($_GET["project"]) ? $_GET["project"] : null;
$default_extension = isset($_GET["default_extension"]) ? $_GET["default_extension"] : null;
$object = isset($_POST["object"]) ? $_POST["object"] : null;

if (!empty($object["sla_settings"]) && empty($object["sla_settings_code"]))
	$object["sla_settings_code"] = SequentialLogicalActivitySettingsCodeCreator::getActionsCode($webroot_cache_folder_path, $webroot_cache_folder_url, $object["sla_settings"], "\t");

$code = CMSPresentationLayerHandler::createEntityCode($object, $selected_project_id, $default_extension);
?>
