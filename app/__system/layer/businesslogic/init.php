<?php
//http://jplpinto.localhost/__system/businesslogic/TEST/get_obj?parameters[module]=TEST&parameters[service]=ItemObjNotRegistered
//http://jplpinto.localhost/__system/businesslogic/test/get_query_sql/?response_type=xml&type=select&module=test&service=select_item&parameters[item_id]=5
//http://jplpinto.localhost/__system/businesslogic/test/get_query/?response_type=xml&type=select&module=test&service=select_item&parameters[item_id]=5
//Note: This values passed in the QUERY STRING are now passed through POST

try {
	define('GLOBAL_SETTINGS_PROPERTIES_FILE_PATH', dirname(dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__)))) . "/config/global_settings.php");//including the app/config/global_settings.php file. __system CANNOT have a global_settings.php.
	define('GLOBAL_VARIABLES_PROPERTIES_FILE_PATH', dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))) . "/config/global_variables.php");

	include dirname(dirname(dirname(__DIR__))) . "/app.php";
	include_once get_lib("org.phpframework.webservice.layer.BusinessLogicLayerWebService");

	define('BEANS_FILE_PATH', SYSTEM_BEAN_PATH . 'business_logic_layer.xml');
	define('BUSINESS_LOGIC_BROKER_SERVER_BEAN_NAME', 'BusinessLogicBrokerServer');

	//echo call_business_logic_layer_web_service();
}
catch(Exception $e) {
	$GlobalExceptionLogHandler->log($e);
}
?>
