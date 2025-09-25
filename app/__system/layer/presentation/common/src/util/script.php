<?php
$app_path = realpath(__DIR__ . "/../../../../../") . "/";
include_once "$app_path/lib/org/phpframework/phpscript/PHPScriptCommandLineHandler.php";

function isCommandLineScript() {
	PHPScriptCommandLineHandler::isCommandLineScript();
}

function prepareCommandLineScript($settings = array()) {
	PHPScriptCommandLineHandler::prepareCommandLineScript($settings);
}
?>
