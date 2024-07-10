<?php
//http://jplpinto.localhost/__system/test/tests/cache_ibatis

include $EVC->getUtilPath("util");

echo "<br/>DELETE THE CACHE FOR IBATIS:";
CacheHandlerUtil::deleteFolder(LAYER_CACHE_PATH . "sysdataaccess/ibatis/", false);

echo "<br/>RELATED SERVICES FOR SELECT ITEM:";
echo "<br/>You should NOT see anything, but only if it is the first time that you are running this. (The cache was deleted so you should NOT see anything)<br/><br/>";
printUnserializedLastDirectoryFilesContent(LAYER_CACHE_PATH."sysdataaccess/ibatis/TEST/select_item/php/__related/prefix/");
echo "<hr/>";

echo "<br/>INSERT NEW ITEM:";
$parameters = array("title" => "ibatis item test X");
echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query_sql", array("module" => "TEST", "type" => "insert", "service" => "insert_item", "parameters" => $parameters));
$new_id = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "insert", "service" => "insert_item", "parameters" => $parameters));
echo "<br/>new_id: {$new_id}";
echo "<hr/>";

echo "<br/>RELATED SERVICES FOR SELECT ITEM:";
echo "<br/>You should still NOT see anything, because the insert_items doesn't have cache active, so no cache is created for the insert_item service.<br/><br/>";
printUnserializedLastDirectoryFilesContent(LAYER_CACHE_PATH."sysdataaccess/ibatis/TEST/select_item/php/__related/prefix/");
echo "<hr/>";

echo "<br/>SELECT ITEM {$new_id}:";
$parameters = $new_id;
echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query_sql", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $parameters));
$result = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $parameters));
echo "<br/>result: <pre>";print_r($result);echo "</pre>";
echo "<hr/>";

sleep(2);

echo "<br/>RELATED SERVICES FOR SELECT ITEM:";
echo "<br/>You should see 2 services, this is, select_item_id-$new_id-mysql and select_item_id-$new_id-mysql_includes<br/>";
printUnserializedLastDirectoryFilesContent(LAYER_CACHE_PATH."sysdataaccess/ibatis/TEST/select_item/php/__related/prefix/");

echo "<br>THE CACHED FILES ARE HERE: (you should see 2 files)";
$files = listLastDirectoryFiles(LAYER_CACHE_PATH."sysdataaccess/ibatis/TEST/select_item/php/");
echo "<pre>Cached files: \n" . print_r($files, 1) . "</pre>";
echo "<hr/>";

sleep(2);

/*Now we can test the cache. After Update is DONE, the cache should delete the select_items_id-* keys */
echo "<br/>UPDATE ALL ITEMS:";
$parameters = array("status" => "1");
echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query_sql", array("module" => "TEST", "type" => "update", "service" => "update_all_items", "parameters" => $parameters));
echo "<br/>status: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "update", "service" => "update_all_items", "parameters" => $parameters));
echo "<hr/>";

sleep(2);

echo "<br/>RELATED SERVICES FOR SELECT ITEM:";
echo "<br/>You should STILL see 2 services, but this 2 services should not have any cached files<br/>";
printUnserializedLastDirectoryFilesContent(LAYER_CACHE_PATH."sysdataaccess/ibatis/TEST/select_item/php/__related/prefix/");

echo "<br>THE CACHED FILES ARE HERE: (you should NOT see any files)";
$files = listLastDirectoryFiles(LAYER_CACHE_PATH."sysdataaccess/ibatis/TEST/select_item/php/");
echo "<pre>Cached files: \n" . print_r($files, 1) . "</pre>";
echo "<hr/>";

sleep(2);

echo "<br/>SELECT ITEM {$new_id}:";
$parameters = $new_id;
echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query_sql", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $parameters));
$result = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $parameters));
echo "<br/>result: <pre>";print_r($result);echo "</pre>";
echo "<hr/>";

echo "<br/>RELATED SERVICES FOR SELECT ITEM:";
echo "<br/>You should STILL see 2 services, but now this 2 services should have their cached files again<br/>";
printUnserializedLastDirectoryFilesContent(LAYER_CACHE_PATH."sysdataaccess/ibatis/TEST/select_item/php/__related/prefix/");

echo "<br>THE CACHED FILES ARE HERE: (you should see 1 file)";
$files = listLastDirectoryFiles(LAYER_CACHE_PATH."sysdataaccess/ibatis/TEST/select_item/php/");
echo "<pre>Cached files: \n" . print_r($files, 1) . "</pre>";

echo "<br>THE CONTENT OF THE CACHED FILES FROM ABOVE:";
printUnserializedLastDirectoryFilesContent(LAYER_CACHE_PATH."sysdataaccess/ibatis/TEST/select_item/php/");
echo "<hr/>";

die();
/*
You should see something like:
	FILE: /media/disk/www/phpframework/trunk/other/tmp/cache/layer/dataaccess/ibatis/TEST/select_item/php/0fc/9ab/5ed/79e/7ae/select_item_id-769-mysql_includes:

	Array
	(
	    [0] => root.domain.test.ItemTest
	)

	FILE: /media/disk/www/phpframework/trunk/other/tmp/cache/layer/dataaccess/ibatis/TEST/select_item/php/cc1/002/0a1/72d/d29/select_item_id-769-mysql:

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
