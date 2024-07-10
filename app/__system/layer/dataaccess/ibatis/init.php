<?php
//http://jplpinto.localhost/__system/dataaccess/ibatis/TEST/select/select_item/?response_type=xml&item_id=5
//http://jplpinto.localhost/__system/dataaccess/ibatis/TEST/select-sql/select_item/?response_type=xml&item_id=5
//http://jplpinto.localhost/__system/dataaccess/ibatis/TEST/select/select_item_simple/?item_id=5
//http://jplpinto.localhost/__system/dataaccess/ibatis/getdata/?data=select * from item limit 2
//http://jplpinto.localhost/__system/dataaccess/ibatis/getinsertedid
try {
	define('GLOBAL_SETTINGS_PROPERTIES_FILE_PATH', dirname(dirname(dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))))) . "/config/global_settings.php");//including the app/config/global_settings.php file. __system CANNOT have a global_settings.php.
	define('GLOBAL_VARIABLES_PROPERTIES_FILE_PATH', dirname(dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__)))) . "/config/global_variables.php");

	include dirname(dirname(dirname(dirname(__DIR__)))) . "/app.php";
	include_once get_lib("org.phpframework.webservice.layer.IbatisDataAccessLayerWebService");

	define('BEANS_FILE_PATH', SYSTEM_BEAN_PATH . 'ibatis_data_access_layer.xml');
	define('IBATIS_DATA_ACCESS_BROKER_SERVER_BEAN_NAME', 'IbatisDataAccessBrokerServer');

	//echo call_ibatis_data_access_layer_web_service();
}
catch(Exception $e) {
	$GlobalExceptionLogHandler->log($e);
}
?>
