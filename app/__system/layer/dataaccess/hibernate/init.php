<?php
//http://jplpinto.localhost/__system/dataaccess/hibernate/TEST/Item?result_type=xml
//http://jplpinto.localhost/__system/dataaccess/hibernate/TEST/Item/findById/?args[]=1&result_type=xml
//http://jplpinto.localhost/__system/dataaccess/hibernate/TEST/Item/callQuery/?args[]=select&args[]=select_all_by_status&result_type=xml&data=1
try {
	define('GLOBAL_SETTINGS_PROPERTIES_FILE_PATH', dirname(dirname(dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))))) . "/config/global_settings.php");//including the app/config/global_settings.php file. __system CANNOT have a global_settings.php.
	define('GLOBAL_VARIABLES_PROPERTIES_FILE_PATH', dirname(dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__)))) . "/config/global_variables.php");

	include dirname(dirname(dirname(dirname(__DIR__)))) . "/app.php";
	include_once get_lib("org.phpframework.webservice.layer.HibernateDataAccessLayerWebService");

	define('BEANS_FILE_PATH', SYSTEM_BEAN_PATH . 'hibernate_data_access_layer.xml');
	define('HIBERNATE_DATA_ACCESS_BROKER_SERVER_BEAN_NAME', 'HibernateDataAccessBrokerServer');

	//echo call_hibernate_data_access_layer_web_service();
}
catch(Exception $e) {
	$GlobalExceptionLogHandler->log($e);
}
?>
