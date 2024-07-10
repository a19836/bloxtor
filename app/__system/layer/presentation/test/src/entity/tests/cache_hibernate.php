<?php
//http://jplpinto.localhost/__system/test/tests/cache_hibernate

include $EVC->getUtilPath("util");

echo "<br/>DELETE THE CACHE FOR HIBERNATE:";
$status = CacheHandlerUtil::deleteFolder(LAYER_CACHE_PATH . "sysdataaccess/hibernate/", false);
	
//If you try this $ItemObj, it will work!
//DB_BROKER: 1 ==> DB_BROKER_CLIENT ==> DB_BROKER_SERVER ==> DB_LAYER ==> mysql + pg
//$ItemObj = $EVC->getBroker()->callBusinessLogic("TEST", "get_obj", array("module" => "TEST", "service" => "Item", "options" => array("db_broker" => 1, "db_driver" => "mysql")));
//or
$ItemObj = $EVC->getBroker()->callBusinessLogic("TEST", "get_obj", array("module" => "TEST", "service" => "Item"));

//If you try this $ItemObj, you will get errors because the Postgres driver doesn't support the Hibernate's features: insert, update, findById, etc... 
//DB_BROKER: 1 ==> DB_BROKER_CLIENT ==> DB_BROKER_SERVER ==> DB_LAYER ==> mysql + pg
//$ItemObj = $EVC->getBroker()->callBusinessLogic("TEST", "get_obj", array("module" => "TEST", "service" => "Item", "options" => array("db_broker" => 1, "db_driver" => "pg")));

//If you try this $ItemObj, you will get errors because the Postgres driver doesn't support the Hibernate's features: insert, update, findById, etc... 
//DB_BROKER: 2 ==> DB_BROKER_CLIENT_AUX ==> DB_BROKER_SERVER_AUX ==> DB_LAYER_AUX ==> only pg
//$ItemObj = $EVC->getBroker()->callBusinessLogic("TEST", "get_obj", array("module" => "TEST", "service" => "Item", "options" => array("db_broker" => 2)));

//If you try this $ItemObj, you will get an exception because the DB_DRIVER mysql doesn't exists in the DB_BROKER: DB_BROKER_CLIENT_AUX
//DB_BROKER: 2 ==> DB_BROKER_CLIENT_AUX ==> DB_BROKER_SERVER_AUX ==> DB_LAYER_AUX ==> only pg
//$ItemObj = $EVC->getBroker()->callBusinessLogic("TEST", "get_obj", array("module" => "TEST", "service" => "Item", "options" => array("db_broker" => 2, "db_driver" => "mysql")));

echo "<br/>RELATED SERVICES FOR SELECT ITEM:";
echo "<br/>You should NOT see anything, but only if it is the first time that you are running this. (The cache was deleted so you should NOT see anything)<br/><br/>";
printUnserializedLastDirectoryFilesContent(LAYER_CACHE_PATH."sysdataaccess/hibernate/TEST/");
echo "<hr/>";

echo "<br/>INSERT NEW ITEM:";
echo "<br/>status: ".$ItemObj->insert(array("name" => "hibernate item test X", "status" => 0), $ids);
$new_id = $ItemObj->getInsertedId();
echo "<br/>new_id: {$new_id}";
echo "<hr/>";

echo "<br/>RELATED SERVICES FOR SELECT ITEM:";
echo "<br/>You should still NOT see anything, because the insert_items doesn't have cache active, so no cache is created for the insert_item service.<br/><br/>";
printUnserializedLastDirectoryFilesContent(LAYER_CACHE_PATH."sysdataaccess/hibernate/TEST/");
echo "<hr/>";

echo "<br/>SELECT ITEM {$new_id}:";
$result = $ItemObj->findById($new_id, array(
					"attributes" => array("pk_id", "name", "status"),
					"relationships" => true
					)
				);
echo "<br/>result: <pre>";print_r($result);echo "</pre>";
echo "<hr/>";

sleep(2);

echo "<br/>RELATED SERVICES FOR SELECT ITEM:";
echo "<br/>You should see at least 1 service, this is, Item.findById_ids-mysql<br/>";
printUnserializedLastDirectoryFilesContent(LAYER_CACHE_PATH."sysdataaccess/hibernate/TEST/Item.findById/php/__related/prefix/");

echo "<br>THE CACHED FILES ARE HERE: (you should see 1 file)";
$files = listLastDirectoryFiles(LAYER_CACHE_PATH."sysdataaccess/hibernate/TEST/Item.findById/php/");
echo "<pre>Cached files: \n" . print_r($files, 1) . "</pre>";
echo "<hr/>";

/*Now we can test the cache. After Update is DONE, the cache should delete the Item.findById_ids-* keys */
echo "<br/>UPDATE ITEM:";
echo "<br/>status: ".$ItemObj->update( array("pk_id" => $new_id, "name" => "hibernate item test W", "status" => 1) );
echo "<hr/>";

echo "<br/>RELATED SERVICES FOR SELECT ITEM:";
echo "<br/>You should NOT see any services or cached files<br/>";
printUnserializedLastDirectoryFilesContent(LAYER_CACHE_PATH."sysdataaccess/hibernate/TEST/Item.findById/php/__related/prefix/");

echo "<br>THE CACHED FILES ARE HERE: (you should NOT see any files)";
$files = listLastDirectoryFiles(LAYER_CACHE_PATH."sysdataaccess/hibernate/TEST/Item.findById/php/");
echo "<pre>Cached files: \n" . print_r($files, 1) . "</pre>";
echo "<hr/>";

echo "<br/>SELECT ITEM {$new_id}:";
$result = $ItemObj->findById($new_id, array(
					"attributes" => array("pk_id", "name", "status"),
					"relationships" => true
					)
				);
echo "<br/>result: <pre>";print_r($result);echo "</pre>";
echo "<hr/>";

sleep(2);

echo "<br/>RELATED SERVICES FOR SELECT ITEM:";
echo "<br/>You should STILL see at least 1 service, but now this service should have their cached files again<br/>";
printUnserializedLastDirectoryFilesContent(LAYER_CACHE_PATH."sysdataaccess/hibernate/TEST/Item.findById/php/__related/prefix/");

echo "<br>THE CACHED FILES ARE HERE: (you should see 1 file)";
$files = listLastDirectoryFiles(LAYER_CACHE_PATH."sysdataaccess/hibernate/TEST/Item.findById/php/");
echo "<pre>Cached files: \n" . print_r($files, 1) . "</pre>";

echo "<br>THE CONTENT OF THE CACHED FILES FROM ABOVE:";
printUnserializedLastDirectoryFilesContent(LAYER_CACHE_PATH."sysdataaccess/hibernate/TEST/Item.findById/php/");
echo "<hr/>";
die();
/*
You should see something like:
	FILE: /media/disk/www/test/phpframework/trunk/other/tmp/cache/layer/dataaccess/ibatis/TEST/select_item/php/0fc/9ab/5ed/79e/7ae/select_item_id-769-mysql_includes:

	Array
	(
	    [0] => root.domain.test.ItemTest
	)

	FILE: /media/disk/www/test/phpframework/trunk/other/tmp/cache/layer/dataaccess/ibatis/TEST/select_item/php/cc1/002/0a1/72d/d29/select_item_id-769-mysql:

	Array
	(
	    [0] => __PHP_Incomplete_Class Object
	        (
	            [__PHP_Incomplete_Class_Name] => ItemTest
	            [status:ItemTest:private] => 1
	            [field:protected] => 
	            [data:protected] => Array
	                (
	                    [id] => 769
	                    [title] => ibatis item test X
	                    [status] => 1
	                )

	        )

	)
*/
?>
