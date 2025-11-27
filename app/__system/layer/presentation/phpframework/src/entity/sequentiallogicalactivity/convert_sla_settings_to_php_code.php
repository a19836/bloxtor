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

include_once $EVC->getUtilPath("SequentialLogicalActivityCodeConverter");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$actions_settings = isset($_POST["actions"]) ? $_POST["actions"] : null;

$code = SequentialLogicalActivityCodeConverter::convertActionsSettingsToCode($EVC, $webroot_cache_folder_path, $webroot_cache_folder_url, $actions_settings);
$obj_code = array("code" => $code);
?>
