<?php
//http://jplpinto.localhost/__system/dbdata/getdbdriversname
//http://jplpinto.localhost/__system/dbdata/getfunction/listtablefields?data=item
//http://jplpinto.localhost/__system/dbdata/getdata/?data=select * from item limit 2
//http://jplpinto.localhost/__system/dbdata/setdata/?data=insert into item (title)values('from db_layer')
//http://jplpinto.localhost/__system/dbdata/setdata/getinsertedid/?data=insert into item (title)values('from db_layer')
//http://jplpinto.localhost/__system/dbdata/getinsertedid
//Note: This values passed in the QUERY STRING are now passed through POST

try {
	define('GLOBAL_SETTINGS_PROPERTIES_FILE_PATH', dirname(dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__)))) . "/config/global_settings.php");//including the app/config/global_settings.php file. __system CANNOT have a global_settings.php.
	define('GLOBAL_VARIABLES_PROPERTIES_FILE_PATH', dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))) . "/config/global_variables.php");
	
	include dirname(dirname(dirname(__DIR__))) . "/app.php";
	include_once get_lib("org.phpframework.webservice.layer.DBLayerWebService");
	
	define('BEANS_FILE_PATH', SYSTEM_BEAN_PATH . 'db_layer.xml');
	define('DB_BROKER_SERVER_BEAN_NAME', 'DBBrokerServer');
	
	//echo call_db_layer_web_service();
}
catch(Exception $e) {
	$GlobalExceptionLogHandler->log($e);
}
?>
