<?php
//http://jplpinto.localhost/__system/test/tests/webservice

$prefix_url = "http://jplpinto.localhost/";

//MyCurl test
include_once get_lib("org.phpframework.util.web.MyCurl");
include_once get_lib("org.phpframework.encryption.CryptoKeyHandler");

$MyCurl = new MyCurl();

/*** CALL BUSINESS LOGIC SERVICE ***/

$MyCurl->initSingle(
	array(
		"url" => "{$prefix_url}soa/sample/test/TestService.getAll", //check app/lib/org/phpframework/broker/server/rest/RESTBusinessLogicBrokerServer.php for more details
		"post" => array(
			"response_type" => "json", //xml, json or empty for php serialize
			//"rest_auth_user" => "", //string with username
			//"rest_auth_pass" => "", //string with password
			//"no_cache" => true, //true or false
			"data" => array(
				"parameters" => array(),
				"options" => array(
					//"db_driver" => "", //string with db_driver name
					//"no_cache" => true, //true or false
					//"sort" => array("column_name" => "asc"), //asc or desc
				),
			),
			"gv" => array( //global_variables
				"default_db_driver" => "test"
			)
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
$data = $MyCurl->getData();
//echo "<pre>";print_r($data);echo "</pre>";

echo "*** BUSINESS LOGIC ***<br>";
echo "result: " . $data[0]["content"] . "<br>";
echo "result in json: <pre>" . print_r(json_decode($data[0]["content"], true), 1) . "</pre><br>";

/*** CALL IBATIS RULE ***/

$data = array(
	"parameters" => array(
		"table_name" => "item"
	),
	"options" => array(
		//"db_driver" => "", //string with db_driver name
		//"no_cache" => true, //true or false
		//"sort" => array("column_name" => "asc"), //asc or desc
	),
);
$key = CryptoKeyHandler::hexToBin("e3372580dc1e2801fc0aba77f4b342b2");
$cipher_bin = CryptoKeyHandler::encryptSerializedObject($data, $key);
$data_cipher = CryptoKeyHandler::binToHex($cipher_bin);

$global_variables = array( //global_variables
	"default_db_driver" => "test"
);
$cipher_bin = CryptoKeyHandler::encryptSerializedObject($global_variables, $key);
$global_variables_cipher = CryptoKeyHandler::binToHex($cipher_bin);

$MyCurl->initSingle(
	array(
		"url" => "{$prefix_url}iorm/sample/test/select/get_item_items", //check app/lib/org/phpframework/broker/server/rest/RESTIbatisDataAccessBrokerServer.php for more details
		//"url" => "{$prefix_url}iorm/findobjects",
		"post" => array(
			"response_type" => "json", //xml, json or empty for php serialize
			"rest_auth_user" => password_hash("test", PASSWORD_DEFAULT), //string with username
			"rest_auth_pass" => password_hash("test", PASSWORD_DEFAULT), //string with password
			//"no_cache" => true, //true or false
			"data" => $data_cipher,
			"gv" => $global_variables_cipher
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
$data = $MyCurl->getData();
//echo "<pre>";print_r($data);echo "</pre>";

$key = CryptoKeyHandler::hexToBin("5b6d71b3e03e7540478d277666f08948");
$cipher_bin = CryptoKeyHandler::hexToBin($data[0]["content"]);
$content = CryptoKeyHandler::decryptText($cipher_bin, $key);

echo "*** IBATIS RULE 1 ***<br>";
echo "result: " . $content . "<br>";
echo "result in json: <pre>" . print_r(json_decode($content, true), 1) . "</pre><br>";

$data["parameters"] = "select * from item";
$key = CryptoKeyHandler::hexToBin("e3372580dc1e2801fc0aba77f4b342b2");
$cipher_bin = CryptoKeyHandler::encryptSerializedObject($data, $key);
$data_cipher = CryptoKeyHandler::binToHex($cipher_bin);
$MyCurl->initSingle(
	array(
		"url" => "{$prefix_url}iorm/getdata",
		"post" => array(
			"response_type" => "json", //xml, json or empty for php serialize
			"rest_auth_user" => password_hash("test", PASSWORD_DEFAULT), //string with username
			"rest_auth_pass" => password_hash("test", PASSWORD_DEFAULT), //string with password
			//"no_cache" => true, //true or false
			"data" => $data_cipher,
			"gv" => $global_variables_cipher
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
$data = $MyCurl->getData();
//echo "<pre>";print_r($data);echo "</pre>";

$key = CryptoKeyHandler::hexToBin("5b6d71b3e03e7540478d277666f08948");
$cipher_bin = CryptoKeyHandler::hexToBin($data[0]["content"]);
$content = CryptoKeyHandler::decryptText($cipher_bin, $key);

echo "*** IBATIS RULE 2 ***<br>";
echo "result: " . $content . "<br>";
echo "result in json: <pre>" . print_r(json_decode($content, true), 1) . "</pre><br>";

/*** CALL HIBERNATE OBJ ***/

$MyCurl->initSingle(
	array(
		//"url" => "{$prefix_url}horm/findobjects", //check app/lib/org/phpframework/broker/server/rest/RESTHibernateDataAccessBrokerServer.php for more details
		"url" => "{$prefix_url}horm/sample/test/Item/find", 
		"post" => array(
			"response_type" => "json", //xml, json or empty for php serialize
			//"rest_auth_user" => "", //string with username
			//"rest_auth_pass" => "", //string with password
			//"no_cache" => true, //true or false
			"data" => array(
				"parameters" => array(
					"table_name" => "item",
				),
				"options" => array(
					//"db_driver" => "", //string with db_driver name
					//"no_cache" => true, //true or false
					//"sort" => array("column_name" => "asc"), //asc or desc
				),
			),
			"gv" => array( //global_variables
				"default_db_driver" => "test"
			)
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
$data = $MyCurl->getData();
//echo "<pre>";print_r($data);echo "</pre>";

echo "*** HIBERNATE 1 ***<br>";
echo "result: " . $data[0]["content"] . "<br>";
echo "result in json: <pre>" . print_r(json_decode($data[0]["content"], true), 1) . "</pre><br>";

$MyCurl->initSingle(
	array(
		"url" => "{$prefix_url}horm/sample/test/Item/callobject", 
		"post" => array(
			"response_type" => "", //xml, json or empty for php serialize
			//"rest_auth_user" => "", //string with username
			//"rest_auth_pass" => "", //string with password
			//"no_cache" => true, //true or false
			"data" => array(
				"parameters" => array(),
				"options" => array(
					//"db_driver" => "", //string with db_driver name
					//"no_cache" => true, //true or false
					//"sort" => array("column_name" => "asc"), //asc or desc
				),
			),
			"gv" => array( //global_variables
				"default_db_driver" => "test"
			)
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
$data = $MyCurl->getData();
//echo "<pre>";print_r($data);echo "</pre>";

$results = callHibernateMethod($EVC, $data, $obj_class);
echo "*** HIBERNATE 2 ***<br>";
echo "obj class: " . $obj_class . "<br>";
echo "result in json: <pre>" . print_r($results, 1) . "</pre><br>";

//simulate that the returned object is inside of the project layers, by loading all the PresentationPLayer layer and all its dependencies
function callHibernateMethod($EVC, $data, &$obj_class) {
	$P = $EVC->getPresentationLayer();
	$project_id = $P->getSelectedPresentationId();
	$P->setSelectedPresentationId("phpframework");
	
	include $EVC->getConfigPath("config");
	include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
	
	$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
	$PHPVariablesFileHandler->startUserGlobalVariables();
	
	$GLOBALS["default_db_driver"] = "test";
	$bean_objs = WorkFlowBeansFileHandler::getAllBeanObjects($user_global_variables_file_path, $user_beans_folder_path);
	//echo "<pre>" . print_r(array_keys($bean_objs), 1) . "</pre>";die();
	//echo "<pre>" . print_r($bean_objs["Test"]->getData("SELECT `id`, `status`, `title`, `sub_item_id` FROM `item`"), 1) . "</pre>";die();
	
	$obj_path = $data[0]["header"]["Response-Object-Lib"];

	if ($obj_path && file_exists($obj_path))
		include_once $obj_path;
	
	$content = unserialize($data[0]["content"]);
	//echo "<pre>Content:";print_r($data[0]["content"]);echo "</pre>";
	//echo "<pre>Content:";print_r($content);echo "</pre>";
	
	$obj = $content["result"];
	$obj_class = get_class($obj);
	
	//when cache is deleted, we are getting a warning saying: Warning: mysqli_query(): Couldn't fetch mysqli in /var/www/html/phpframework/trunk/app/lib/org/phpframework/db/driver/MySqlDB.php on line 540. To ignore this error just add the '@' before the '$obj->find()' code.
	//This happens because the system didn't reference the DB connection with the Response-Object-Lib class. So the first time we need to reconnect the DB.
	$active = $obj->getFunction("ping");
	
	if (!$active)
		$obj->getFunction("connect");
	
	$results = $obj->find();
	
	$PHPVariablesFileHandler->endUserGlobalVariables();
	
	$P->setSelectedPresentationId($project_id);
	
	return $results;
}
?>
