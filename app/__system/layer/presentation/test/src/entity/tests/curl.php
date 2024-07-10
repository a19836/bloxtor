<?php
//http://jplpinto.localhost/__system/test/tests/curl

//MyCurl test
include_once get_lib("org.phpframework.util.web.MyCurl");

//$url = "http://jplpinto.localhost/__system/phpframework/presentation/get_presentation_tables_ui_props_automatically?bean_name=Presentation&bean_file_name=presentation_pl.xml&path=test/src/entity/admin/&db_layer=Mysql&db_layer_file=mysql_dbl.xml&db_driver=mysql&type=db";
$url = "http://jplpinto.localhost/__system/phpframework/presentation/get_presentation_tables_ui_props_automatically?bean_name=Presentation&bean_file_name=presentation_pl.xml&path=test/src/entity/test/forms/events/&db_layer=Dbdata&db_layer_file=dbdata_dbl.xml&db_driver=test&type=diagram";

$ab = array("iorm" => 1, "horm" => 1);

$MyCurl = new MyCurl();
$MyCurl->initSingle(
	array(
		"url" => $url, 
		"post" => array(
			"ab" => $ab,
			"st" => array("activity")
		),
		"cookie" => $_COOKIE,
		"settings" => array(
			"referer" => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null,
			"follow_location" => 1,
			"connection_timeout" => 10,
			"header" => 1,
			"http_auth" => !empty($_SERVER["AUTH_TYPE"]) ? $_SERVER["AUTH_TYPE"] : null,
			"user_pwd" => !empty($_SERVER["PHP_AUTH_USER"]) ? $_SERVER["PHP_AUTH_USER"] . ":" . (isset($_SERVER["PHP_AUTH_PW"]) ? $_SERVER["PHP_AUTH_PW"] : "") : null,
		)
	)
);
$MyCurl->get_contents();
$content = $MyCurl->getData();
echo "<pre>";print_r($content);echo "</pre>";
?>
