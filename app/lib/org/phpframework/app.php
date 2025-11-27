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

include get_lib("org.phpframework.exception.ExceptionLogHandler");
include get_lib("org.phpframework.log.LogHandler");
include get_lib("org.phpframework.error.ErrorHandler");
include_once get_lib("org.phpframework.PHPFrameWork");

$GlobalErrorHandler = new ErrorHandler();
$GlobalLogHandler = new LogHandler();
$GlobalLogHandler->setLogLevel($log_level);
$GlobalLogHandler->setEchoActive($log_echo_active);
$GlobalLogHandler->setRootPath(CMS_PATH);
$GlobalExceptionLogHandler = new ExceptionLogHandler($GlobalLogHandler, $die_when_throw_exception);

function error_handler($errno, $errstr, $errfile, $errline) {
	//if error code is included in error_reporting
	if (error_reporting() & $errno) //error_reporting() & $errno => checks if bits are set in both $error_reporting and $errno are set. 
		return false; //false to let error fall through to the standard PHP error handler, since is included in error_reporting
	
	//Don't execute PHP internal error handler, this is, ignore it, bc the error_reporting does NOT include this error, which means that the error_reporting is defined to ignore this error.
	return true; //true to ignore error
}

function is_undefined_var_error($errstr) {
	return preg_match('/^(Undefined array key|Undefined global variable|Undefined variable|Trying to access array offset on value of type null|Trying to access array offset on null)/', $errstr);
}

function ignore_undefined_var_error_handler($errno, $errstr, $errfile, $errline) {
	if (is_undefined_var_error($errstr))
		return true; //true to ignore error. Don't execute PHP internal error handler, this is, ignore it.
	else
		return error_handler($errno, $errstr, $errfile, $errline);
}

function ignore_vendor_undefined_var_error_handler($errno, $errstr, $errfile, $errline) {
	if (is_undefined_var_error($errstr) && strpos($errfile, "/vendor/") !== false)
		return true; //true to ignore error. Don't execute PHP internal error handler, this is, ignore it.
	else if (strpos($errfile, "/webroot/cms/wordpress/") !== false) //ignore all wordpress installations warnings also
		return true; //true to ignore error. Don't execute PHP internal error handler, this is, ignore it.
	else
		return error_handler($errno, $errstr, $errfile, $errline);
}

function normalize_windows_path_to_linux($path) { //This function will be used everytime that we use the php code: __FILE__ and __DIR__
	return DIRECTORY_SEPARATOR != "/" ? str_replace(DIRECTORY_SEPARATOR, "/", $path) : $path;
}

function launch_exception(Throwable $exception) {
	global $GlobalErrorHandler;
	
	$GlobalErrorHandler->stop();
	
	if (!empty($exception->file_not_found))
		echo '<h1 style="text-align:center; margin:0 0 35px; padding:50px 0 35px; line-height:40px; border-bottom:1px solid #ccc;">404<br/>File not found.</h1>';
	
	throw $exception;
	
	return false;
}

function set_log_handler_settings() {
	global $GlobalLogHandler, $PHPFrameWork, $log_level, $log_echo_active, $log_file_path;
	
	$LogHandler = $PHPFrameWork->getObject("LogHandler");
	if (isset($LogHandler) && $LogHandler instanceof ILogHandler) {
		$GlobalLogHandler = $LogHandler;
	}
	
	$log_vars = $PHPFrameWork->getObject("log_vars");
	
	$ll = isset($log_vars["log_level"]) ? $log_vars["log_level"] : null;
	if (isset($ll) && $ll > 0) {
		$ll = (int)$ll;
		
		$GlobalLogHandler->setLogLevel($ll);
		$log_level = $ll;
	}
	
	$lea = isset($log_vars["log_echo_active"]) ? $log_vars["log_echo_active"] : null;
	if (isset($lea)) {
		$lea = $lea == "0" || $lea == "false" ? false : true;
		
		$GlobalLogHandler->setEchoActive($lea);
		$log_echo_active = $lea;
	}
	
	//This is only for testing purposes because the system is creating a file inside of the webroot folders called "&lt;?php echo $GLOBALS["log_file_path"]; ?&gt;" and I don't know why. This happens bc the php in the $log_vars was not parsed correctly, so this code bellow will detect this bug. After this error be fixed, we can remove these lines...
	$lfp = isset($log_vars["log_file_path"]) ? $log_vars["log_file_path"] : null;
	
	if (strpos($lfp, "log_file_path") !== false || strpos($lfp, "&lt;") !== false) {
		echo "<textarea>".print_r($log_vars, 1)."</textarea>";
		debug_print_backtrace();
		die();
	}
	
	if (isset($lfp) && $lfp) {
		$GlobalLogHandler->setFilePath($lfp);
		$log_file_path = $lfp;
	}
	
	$log_css = isset($log_vars["log_css"]) ? $log_vars["log_css"] : null;
	if (isset($log_css) && $log_css) {
		$GlobalLogHandler->setCSS($log_css);
	}
}

function debug_log_function($func, $args, $log_type = "debug") {
	$message = $func . "(" . LogHandler::getArgsInString($args) . ")";
	debug_log($message);
}

function debug_log($message, $log_type = "debug") {
	global $GlobalLogHandler;
	
	$log_level = $GlobalLogHandler->getLogLevel();

	if ($log_level >= 1) {
		$echo_active = $GlobalLogHandler->getEchoActive();
		$GlobalLogHandler->setEchoActive(false);
		
		switch (strtolower($log_type)) {
			case "exception":
				$GlobalLogHandler->setExceptionLog($message);
				break;
			case "error":
				$GlobalLogHandler->setErrorLog($message);
				break;
			case "info":
				$GlobalLogHandler->setInfoLog($message);
				break;
			default: //debug
				$GlobalLogHandler->setDebugLog($message);
		}
		
		$GlobalLogHandler->setEchoActive($echo_active);
	}
}

function call_presentation_layer_web_service($settings = false) {
	global $PHPFrameWork;
	
	$WebService = new PresentationLayerWebService($PHPFrameWork, $settings);
	return $WebService->callWebServicePage();
}

function call_business_logic_layer_web_service($settings = false) {
	global $PHPFrameWork;
	
	$WebService = new BusinessLogicLayerWebService($PHPFrameWork, $settings);
	return $WebService->callWebService();
}

function call_ibatis_data_access_layer_web_service($settings = false) {
	global $PHPFrameWork;
	
	$WebService = new IbatisDataAccessLayerWebService($PHPFrameWork, $settings);
	return $WebService->callWebService();
}

function call_hibernate_data_access_layer_web_service($settings = false) {
	global $PHPFrameWork;
	
	$WebService = new HibernateDataAccessLayerWebService($PHPFrameWork, $settings);
	return $WebService->callWebService();
}

function call_db_layer_web_service($settings = false) {
	global $PHPFrameWork;
	
	$WebService = new DBLayerWebService($PHPFrameWork, $settings);
	return $WebService->callWebService();
}
?>
