<?php
include_once $EVC->getUtilPath("SequentialLogicalActivityCodeConverter");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$actions_settings = $_POST["actions"];

$code = SequentialLogicalActivityCodeConverter::convertActionsSettingsToCode($EVC, $webroot_cache_folder_path, $webroot_cache_folder_url, $actions_settings);
$obj_code = array("code" => $code);
?>
