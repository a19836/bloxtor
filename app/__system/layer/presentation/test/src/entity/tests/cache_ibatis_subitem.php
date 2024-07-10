<?php
//http://jplpinto.localhost/__system/test/tests/cache_ibatis_subitem
//http://jplpinto.localhost/__system/test/tests/cache_ibatis_subitem?item_id=1

include $EVC->getUtilPath("util");

$item_id = 1;
//$item_id = isset($_GET["item_id"]) ? $_GET["item_id"] : null;

echo "<br/>DELETE THE CACHE FOR IBATIS:";
CacheHandlerUtil::deleteFolder(LAYER_CACHE_PATH . "sysdataaccess/ibatis/", false);

echo "<br/>RELATED SERVICES FOR SELECT SUB ITEM:";
echo "<br/>You should NOT see anything, but only if it is the first time that you are running this. (The cache was deleted so you should NOT see anything)<br/><br/>";
printUnserializedLastDirectoryFilesContent(LAYER_CACHE_PATH."sysdataaccess/ibatis/test/subtest/select_sub_item/php/__related/prefix/");
echo "<hr/>";

echo "<br/>INSERT NEW SUB ITEM:";
$parameters = array("title" => "ibatis sub item test X", "item_id" => $item_id);
echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query_sql", array("module" => "test/subtest", "type" => "insert", "service" => "insert_sub_item", "parameters" => $parameters));
$new_id = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "test/subtest", "type" => "insert", "service" => "insert_sub_item", "parameters" => $parameters));
echo "<br/>new_id: {$new_id}";
echo "<hr/>";

echo "<br/>RELATED SERVICES FOR SELECT ITEM:";
echo "<br/>You should still NOT see anything, because the insert_items doesn't have cache active, so no cache is created for the insert_item service.<br/><br/>";
printUnserializedLastDirectoryFilesContent(LAYER_CACHE_PATH."sysdataaccess/ibatis/test/subtest/select_sub_item/php/__related/prefix/");
echo "<hr/>";

echo "<br/>SELECT SUB ITEM {$new_id}:";
$parameters = $new_id;
echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query_sql", array("module" => "test/subtest", "type" => "select", "service" => "select_sub_item", "parameters" => $parameters));
$result = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "test/subtest", "type" => "select", "service" => "select_sub_item", "parameters" => $parameters));
echo "<br/>result: <pre>";print_r($result);echo "</pre>";
echo "<hr/>";

sleep(2);

echo "<br/>RELATED SERVICES FOR SELECT ITEM:";
echo "<br/>You should see 2 services, this is, select_sub_item_id-$new_id-mysql and select_sub_item_id-$new_id-mysql_includes<br/>";
printUnserializedLastDirectoryFilesContent(LAYER_CACHE_PATH."sysdataaccess/ibatis/test/subtest/select_sub_item/php/__related/prefix/");

echo "<br>THE CACHED FILES ARE HERE: (you should see 2 files)";
$files = listLastDirectoryFiles(LAYER_CACHE_PATH."sysdataaccess/ibatis/test/subtest/select_sub_item/php/");
echo "<pre>Cached files: \n" . print_r($files, 1) . "</pre>";

echo "<br>THE CONTENT OF THE CACHED FILES FROM ABOVE:";
printUnserializedLastDirectoryFilesContent(LAYER_CACHE_PATH."sysdataaccess/ibatis/test/subtest/select_sub_item/php/");
echo "<hr/>";

die();
/*
You should see something like:
	FILE: /media/disk/www/phpframework/trunk/other/tmp/cache/layer/dataaccess/ibatis/test/subtest/select_sub_item/php/0fc/9ab/5ed/79e/7ae/select_sub_item_id-769-mysql_includes:

	Array
	(
	    [0] => root.domain.test.MySubItem
	)

	FILE: /media/disk/www/phpframework/trunk/other/tmp/cache/layer/dataaccess/ibatis/test/subtest/select_sub_item/php/cc1/002/0a1/72d/d29/select_sub_item_id-769-mysql:

	Array
	(
	    [0] => __PHP_Incomplete_Class Object
	        (
	            [__PHP_Incomplete_Class_Name] => MySubItem
	            [status:ItemTest:private] => 1
	            [field:protected] => 
	            [data:protected] => Array
	                (
	                    [id] => 769
                    	[item_id] => 1
	                    [title] => ibatis sub item test X
	                )

	        )

	)
*/
?>
