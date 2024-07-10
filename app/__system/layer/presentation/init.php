<?php
//http://jplpinto.localhost/__system/test/pages/entity1/?name=joao

try {
	define('GLOBAL_SETTINGS_PROPERTIES_FILE_PATH', dirname(dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__)))) . "/config/global_settings.php");//including the app/config/global_settings.php file. __system CANNOT have a global_settings.php.
	define('GLOBAL_VARIABLES_PROPERTIES_FILE_PATH', dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))) . "/config/global_variables.php");

	include dirname(dirname(dirname(__DIR__))) . "/app.php";
	include_once get_lib("org.phpframework.webservice.layer.PresentationLayerWebService");

	define('BEANS_FILE_PATH', SYSTEM_BEAN_PATH . 'presentation_layer.xml');
	define('PRESENTATION_DISPATCHER_CACHE_HANDLER_BEAN_NAME', 'PresentationDispatcherCacheHandler');
	define('PRESENTATION_LAYER_BEAN_NAME', 'PresentationLayer');
	define('EVC_DISPATCHER_BEAN_NAME', 'EVCDispatcher');
	define('EVC_BEAN_NAME', 'EVC');

	echo call_presentation_layer_web_service(array(
		"presentation_id" => isset($presentation_id) ? $presentation_id : null, 
		"external_vars" => isset($external_vars) ? $external_vars : null, 
		"includes" => isset($includes) ? $includes : null, 
		"includes_once" => isset($includes_once) ? $includes_once : null, 
	));
}
catch(Exception $e) {
	$GlobalExceptionLogHandler->log($e);
}
?>
