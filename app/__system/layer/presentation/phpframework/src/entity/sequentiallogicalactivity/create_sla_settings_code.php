<?php
include_once $EVC->getUtilPath("SequentialLogicalActivitySettingsCodeCreator");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$actions_settings = $_POST["actions"];

$code = SequentialLogicalActivitySettingsCodeCreator::getActionsCode($webroot_cache_folder_path, $webroot_cache_folder_url, $actions_settings);
$obj_code = array("code" => $code);
?>
