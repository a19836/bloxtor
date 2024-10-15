<?php
//Call global settings
@include GLOBAL_SETTINGS_PROPERTIES_FILE_PATH;//Leave the @ here because the __system/ folder will use the global settings from the app/config/global_settings.php file. this happend because the __system requests must have the same global_settings than the other normal requests. Otherwise you will inconsistencies behaviours... The @ symbol, will apply before the setup where there is no app/config/global_settings.php file. In this case the file will not be included and the default settings bellow will be used instead.

//Preparing some default settings if not yet set:
$ignore_undefined_vars_errors = isset($ignore_undefined_vars_errors) ? $ignore_undefined_vars_errors : true; //because of PHP >= 7.3

$default_timezone = !empty($default_timezone) ? $default_timezone : @date_default_timezone_get();
$default_timezone = !empty($default_timezone) ? $default_timezone : "Europe/London";

//Settings $tmp_path if is empty
if (empty($tmp_path)) { 
	$local_installation_name = isset($_SERVER["SCRIPT_NAME"]) ? strstr($_SERVER["SCRIPT_NAME"], "/" . basename(__DIR__) . "/", true) : null;
	$document_root = (!empty($_SERVER["CONTEXT_DOCUMENT_ROOT"]) ? $_SERVER["CONTEXT_DOCUMENT_ROOT"] : (isset($_SERVER["DOCUMENT_ROOT"]) ? $_SERVER["DOCUMENT_ROOT"] : null) ) . "/"; //Use CONTEXT_DOCUMENT_ROOT if exist, instead of DOCUMENT_ROOT, bc if a virtual host has an alias to this folder, the DOCUMENT_ROOT will be the folder of the virtual host and not this folder. Here is an example: Imagine that you have a Virtual host with a DOCUMENT_ROOT /var/www/html/livingroop/ and an Alias: /test/ pointing to /var/www/html/test/. Additionally this file (app.php) is in /var/www/html/test/. According with this requirements the DOCUMENT_ROOT is /var/www/html/livingroop/, but we would like to get /var/www/html/test/. So we must use the CONTEXT_DOCUMENT_ROOT to get the right document root.
	
	//Settings the $tmp_path if the DOCUMENT_ROOT is based in specific domain and the DOCUMENT_ROOT folder contains the app/ and tmp/ folders. 
	//This means, we can have multiple installations with independent $tmp_path, this is: /var/www/html/installation1/app/ /var/www/html/installation2/trunk/app/ /var/www/html/installation3/app/, etc...
	if ($local_installation_name && is_dir($document_root . $local_installation_name . "/tmp/"))
		$tmp_path = $document_root . $local_installation_name . "/tmp/";
	else if (is_dir($document_root . "/tmp/"))
		$tmp_path = $document_root . "/tmp/";
	else //Settings $tmp_path with default system temp folder
		$tmp_path = (sys_get_temp_dir() ? sys_get_temp_dir() : "/tmp") . "/phpframework/";
	
	$tmp_path = preg_replace("/\/\/+/", "/", $tmp_path);

	//echo "SCRIPT_NAME: ".$_SERVER["SCRIPT_NAME"]."\n<br>CONTEXT_DOCUMENT_ROOT: ".$_SERVER["CONTEXT_DOCUMENT_ROOT"]."\n<br>DOCUMENT_ROOT: ".$_SERVER["DOCUMENT_ROOT"]."\n<br>local_installation_name: ".$local_installation_name."\n<br>tmp_path: ".$tmp_path."\n<br>";die();
}
else if (substr($tmp_path, -1) != "/") //adding / to the end of $tmp_path
	$tmp_path .= "/";

//echo "tmp_path:$tmp_path";die();

$die_when_throw_exception = isset($die_when_throw_exception) ? $die_when_throw_exception : true;
$log_level = isset($log_level) && is_numeric($log_level) ? $log_level: 2;//only exceptions and errors
$log_echo_active = isset($log_echo_active) ? $log_echo_active : true;
$log_file_path = !empty($log_file_path) ? $log_file_path : $tmp_path . "phpframework.log";

//Settings default timezone
date_default_timezone_set($default_timezone);

//Creating DEFINED vars
define('CMS_PATH', dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__)) . "/");
define('APP_PATH', CMS_PATH . "app/");
define('VENDOR_PATH', CMS_PATH . "vendor/");
define('OTHER_PATH', CMS_PATH . "other/");
define('LAYER_PATH', APP_PATH . "layer/");
define('LIB_PATH', APP_PATH . "lib/");
define('CONFIG_PATH', APP_PATH . "config/");
define('DAO_PATH', VENDOR_PATH . "dao/");
define('CODE_WORKFLOW_EDITOR_PATH', VENDOR_PATH . "codeworkfloweditor/");
define('CODE_WORKFLOW_EDITOR_TASK_PATH', CODE_WORKFLOW_EDITOR_PATH . "task/");
define('LAYOUT_UI_EDITOR_PATH', VENDOR_PATH . "layoutuieditor/");
define('LAYOUT_UI_EDITOR_WIDGET_PATH', LAYOUT_UI_EDITOR_PATH . "widget/");
define('TEST_UNIT_PATH', VENDOR_PATH . "testunit/");

define('BEAN_PATH', CONFIG_PATH . "bean/");
define('TMP_PATH', $tmp_path);
define('CACHE_PATH', TMP_PATH . "cache/");
define('LAYER_CACHE_PATH', CACHE_PATH . "layer/");

define('SYSTEM_PATH', APP_PATH . "__system/");
define('SYSTEM_LAYER_PATH', SYSTEM_PATH . "layer/");
define('SYSTEM_CONFIG_PATH', SYSTEM_PATH . "config/");
define('SYSTEM_BEAN_PATH', SYSTEM_CONFIG_PATH . "bean/");

//Call other global vars
include GLOBAL_VARIABLES_PROPERTIES_FILE_PATH;

//Start app
include LIB_PATH . "org/phpframework/util/import/lib.php";
include get_lib("org.phpframework.app");

//must be set here bc the ignore_undefined_var_error_handler is defined in lib/org/phpframework/app.php
if ($ignore_undefined_vars_errors)
	set_error_handler("ignore_undefined_var_error_handler", E_WARNING);
else
	set_error_handler("ignore_vendor_undefined_var_error_handler", E_WARNING);

//you can change here the $GlobalErrorHandler and $GlobalExceptionLogHandler configurations.

$PHPFrameWork = new PHPFrameWork();
$PHPFrameWork->init();
$PHPFrameWork->setCacheRootPath(LAYER_CACHE_PATH);
?>
