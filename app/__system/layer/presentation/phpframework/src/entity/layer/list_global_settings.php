<?php
include_once $EVC->getUtilPath("PHPVariablesFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$popup = $_GET["popup"];
$deployment = $_GET["deployment"];

if (isset($_POST["data"])) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

	$content = '<?php
//[GENERAL SETTINGS]
$default_timezone = "' . $_POST["data"]["default_timezone"] . '";

//[EXCEPTION SETTINGS]
$die_when_throw_exception = ' . $_POST["data"]["die_when_throw_exception"] . ';

//[LOG SETTINGS]
$log_level = ' . $_POST["data"]["log_level"] . ';
$log_echo_active = ' . $_POST["data"]["log_echo_active"] . ';
$log_file_path = "' . $_POST["data"]["log_file_path"] . '";

//[TMP SETTINGS]
$tmp_path = "' . $_POST["data"]["tmp_path"] . '";
?>';
	
	if (file_put_contents($user_global_settings_file_path, $content)) {
		$status_message = "Settings saved successfully";
	}
	else {
		$error_message = "There was an error trying to save settings. Please try again...";
	}
}

$vars = PHPVariablesFileHandler::getVarsFromFileContent($user_global_settings_file_path);

$vars["default_timezone"] = $vars["default_timezone"] ? $vars["default_timezone"] : $GLOBALS["default_timezone"];
$vars["die_when_throw_exception"] = $vars["die_when_throw_exception"] ? $vars["die_when_throw_exception"] : ($GLOBALS["die_when_throw_exception"] ? "true" : "false");
$vars["log_level"] = is_numeric($vars["log_level"]) ? $vars["log_level"] : $GLOBALS["log_level"];
$vars["log_echo_active"] = $vars["log_echo_active"] ? $vars["log_echo_active"] : ($GLOBALS["log_echo_active"] ? "true" : "false");
$vars["log_file_path"] = $vars["log_file_path"] ? $vars["log_file_path"] : $GLOBALS["log_file_path"];
$vars["tmp_path"] = $vars["tmp_path"] ? $vars["tmp_path"] : $GLOBALS["tmp_path"];
//echo "<pre>";print_r($vars);die();
?>
