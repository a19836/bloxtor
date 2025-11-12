<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once $EVC->getUtilPath("PHPVariablesFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;
$deployment = isset($_GET["deployment"]) ? $_GET["deployment"] : null;

if (isset($_POST["data"])) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

	$content = '<?php
//[GENERAL SETTINGS]
$default_timezone = "' . (isset($_POST["data"]["default_timezone"]) ? $_POST["data"]["default_timezone"] : "") . '";

//[EXCEPTION SETTINGS]
$die_when_throw_exception = ' . (isset($_POST["data"]["die_when_throw_exception"]) ? $_POST["data"]["die_when_throw_exception"] : "") . ';

//[LOG SETTINGS]
$log_level = ' . (isset($_POST["data"]["log_level"]) ? $_POST["data"]["log_level"] : "") . ';
$log_echo_active = ' . (isset($_POST["data"]["log_echo_active"]) ? $_POST["data"]["log_echo_active"] : "") . ';
$log_file_path = "' . (isset($_POST["data"]["log_file_path"]) ? $_POST["data"]["log_file_path"] : "") . '";

//[TMP SETTINGS]
$tmp_path = "' . (isset($_POST["data"]["tmp_path"]) ? $_POST["data"]["tmp_path"] : "") . '";
?>';
	
	if (file_put_contents($user_global_settings_file_path, $content)) {
		$status_message = "Settings saved successfully";
	}
	else {
		$error_message = "There was an error trying to save settings. Please try again...";
	}
}

$vars = PHPVariablesFileHandler::getVarsFromFileContent($user_global_settings_file_path);

$vars["default_timezone"] = !empty($vars["default_timezone"]) ? $vars["default_timezone"] : (isset($GLOBALS["default_timezone"]) ? $GLOBALS["default_timezone"] : null);
$vars["die_when_throw_exception"] = !empty($vars["die_when_throw_exception"]) ? $vars["die_when_throw_exception"] : (!empty($GLOBALS["die_when_throw_exception"]) ? "true" : "false");
$vars["log_level"] = isset($vars["log_level"]) && is_numeric($vars["log_level"]) ? $vars["log_level"] : (isset($GLOBALS["log_level"]) ? $GLOBALS["log_level"] : null);
$vars["log_echo_active"] = !empty($vars["log_echo_active"]) ? $vars["log_echo_active"] : (!empty($GLOBALS["log_echo_active"]) ? "true" : "false");
$vars["log_file_path"] = !empty($vars["log_file_path"]) ? $vars["log_file_path"] : (isset($GLOBALS["log_file_path"]) ? $GLOBALS["log_file_path"] : null);
$vars["tmp_path"] = !empty($vars["tmp_path"]) ? $vars["tmp_path"] : (isset($GLOBALS["tmp_path"]) ? $GLOBALS["tmp_path"] : null);
//echo "<pre>";print_r($vars);die();
?>
