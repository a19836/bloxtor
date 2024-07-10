<?php
//http://jplpinto.localhost/__system/test/tests/ibatis?step=1
//http://jplpinto.localhost/__system/test/tests/ibatis?step=2
//http://jplpinto.localhost/__system/test/tests/ibatis?step=3
//http://jplpinto.localhost/__system/test/tests/ibatis?step=4
//http://jplpinto.localhost/__system/test/tests/ibatis?step=5
//http://jplpinto.localhost/__system/test/tests/ibatis?step=6
//http://jplpinto.localhost/__system/test/tests/ibatis?step=7

include $EVC->getUtilPath("util");

$step = isset($_GET["step"]) ? $_GET["step"] : null;

if($step == 1) {//1st step
	echo "<br/>GET SQL FROM DATA ACCESS THAT IS NOT REGISTER IN THE dataaccess/ibatis/xxx/SERVICES.xml";
	$parameters = array("title" => "ibatis item test X");
	echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("test", "get_query_sql", array("module" => "test", "type" => "insert", "service" => "insert_item_not_registered", "parameters" => $parameters));
	//echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("test", "get_query_sql", array("module" => "test.item.xml", "type" => "insert", "service" => "insert_item_not_registered", "parameters" => $parameters));
	//echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("test", "get_query_sql", array("module" => "test/item.xml", "type" => "insert", "service" => "insert_item_not_registered", "parameters" => $parameters));
	//echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("test", "get_query_sql", array("module" => "test.item", "type" => "insert", "service" => "insert_item_not_registered", "parameters" => $parameters));
	//echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("test", "get_query_sql", array("module" => "test/item", "type" => "insert", "service" => "insert_item_not_registered", "parameters" => $parameters));
	echo "<hr/>";
	
	echo "<br/>GET SQL FROM SUB DATA ACCESS THAT IS NOT REGISTER IN THE dataaccess/ibatis/xxx/yyy/SERVICES.xml";
	echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("test", "get_query_sql", array("module" => "test.subtest", "type" => "insert", "service" => "insert_sub_item_not_registered", "parameters" => $parameters));
	echo "<hr/>";

	echo "<br/>INSERT NEW ITEM:";
	$parameters = array("title" => "ibatis item test X");
	echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("test", "get_query_sql", array("module" => "test", "type" => "insert", "service" => "insert_item", "parameters" => $parameters));
	$new_id = $EVC->getBroker()->callBusinessLogic("test", "get_query", array("module" => "test", "type" => "insert", "service" => "insert_item", "parameters" => $parameters));
	echo "<br/>new_id: {$new_id}";
	echo "<hr/>";


	echo "<br/>SELECT ITEM {$new_id}:";
	$parameters = $new_id;//array("item_id" => $new_id);
	echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("test", "get_query_sql", array("module" => "test", "type" => "select", "service" => "select_item", "parameters" => $parameters));
	$result = $EVC->getBroker()->callBusinessLogic("test", "get_query", array("module" => "test", "type" => "select", "service" => "select_item", "parameters" => $parameters));
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
	
	
	setcookie("item_id", $new_id);
	$_COOKIE["item_id"] = $new_id;
}
elseif($step == 2) {//2nd step
	$item_id = isset($_COOKIE["item_id"]) ? $_COOKIE["item_id"] : null;
	
	
	echo "<br/>SELECT ITEM {$item_id}:";
	$parameters = $item_id;
	echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query_sql", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $parameters));
	$result = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $parameters));
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
}
elseif($step == 3) {//3rd step
	$item_id = isset($_COOKIE["item_id"]) ? $_COOKIE["item_id"] : null;
	
	echo "<br/>SELECT CACHED ITEM {$item_id}:";
	$parameters = $item_id;
	echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query_sql", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $parameters));
	$result = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $parameters));
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
	
	
	echo "<br/>UPDATE ITEM:";
	$parameters = array("id" => $item_id, "title" => "ibatis item test Y");
	echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query_sql", array("module" => "TEST", "type" => "update", "service" => "update_item", "parameters" => $parameters));
	echo "<br/>status: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "update", "service" => "update_item", "parameters" => $parameters));
	echo "<hr/>";


	echo "<br/>SELECT CACHED ITEM {$item_id}:";
	$parameters = $item_id;
	echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query_sql", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $parameters));
	$result = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $parameters));
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";


	echo "<br/>SELECT ITEM {$item_id} (no cache):";
	$parameters = $item_id;
	echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query_sql", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $parameters));
	$result = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $parameters, "options" => array("no_cache" => true)));
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";


	echo "<br/>DELETE ITEM:";
	$parameters = $item_id;
	echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query_sql", array("module" => "TEST", "type" => "delete", "service" => "delete_item", "parameters" => $parameters));
	echo "<br/>status: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "delete", "service" => "delete_item", "parameters" => $parameters));
	echo "<hr/>";


	echo "<br/>SELECT CACHED ITEM {$item_id}:";
	$parameters = $item_id;
	echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query_sql", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $parameters));
	$result = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $parameters));
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";


	echo "<br/>SELECT ITEM {$item_id} (no cache):";
	$parameters = $item_id;
	echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query_sql", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $parameters));
	$result = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $parameters, "options" => array("no_cache" => true)));
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";


	echo "<br/>SELECT ITEMS:";
	$parameters = array("row" => "10", "type" => "select");
	echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query_sql", array("module" => "TEST", "type" => "select", "service" => "select_items", "parameters" => $parameters));
	$result = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "select", "service" => "select_items", "parameters" => $parameters));
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
}
elseif($step == 4) {//4th step
	include_once get_lib("vendor.dao.test.ItemTest");
	$ItemTest = new ItemTest();
	$ItemTest->setTitle("Molo AHAH");
	
	/*class X extends ItemTest { 
		public $data;
		public function __construct() {
			$this->data = array("title" => "EHEHE");
		}
	}
	$ItemTest = new X();*/
	
	echo "<br/>PROCEDURE ITEMS:";
	$parameters = $ItemTest;
	echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query_sql", array("module" => "TEST", "type" => "procedure", "service" => "procedure_items", "parameters" => $parameters));
	$result = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "procedure", "service" => "procedure_items", "parameters" => $parameters));
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
}
elseif($step == 5) {//5th step
	include_once get_lib("vendor.dao.test.ItemTest");
	class X extends ItemTest { 
		public function setData($data) {$this->data = $data;}
		public function getData() {return $this->data;}
	}
	$ItemTest = new X();
	$ItemTest->setData("Molo IHIH");
	//$ItemTest->setData(array("Molo AHAH", "BLABLABLA" => "Molo EHEH"));
	//$ItemTest->setData(array("Molo AHAH", "Molo OHOH"));
	
	echo "<br/>PROCEDURE ITEMS:";
	$parameters = $ItemTest;
	echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("TEST", "get_query_sql", array("module" => "TEST", "type" => "procedure", "service" => "procedure_items_class", "parameters" => $parameters));
	$result = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "procedure", "service" => "procedure_items_class", "parameters" => $parameters));
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
}
elseif($step == 6) {//6th step
	$status = CacheHandlerUtil::deleteFolder(LAYER_CACHE_PATH . "sysdataaccess/ibatis/", false);
	echo "\n<br/>status: $status";
	echo "<hr/>";
	
	$no_cache_start = microtime(true);
	for($i = 0; $i < 300; $i++) {
		$result = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $i, "options" => array("no_cache" => true)));
		
		if(!empty($result[0]) && get_class($result[0]) == "ItemTest") {
			$data = $result[0]->getData();
			echo "\n<br/>item ".(isset($data["id"]) ? $data["id"] : null);
		}
	}
	$no_cache_end = microtime(true);
	
	echo "<hr/>";
	
	$cache_start = microtime(true);
	for($i = 0; $i < 300; $i++) {
		$result = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $i));
		
		if(!empty($result[0]) && get_class($result[0]) == "ItemTest") {
			$data = $result[0]->getData();
			echo "\n<br/>item ".(isset($data["id"]) ? $data["id"] : null);
		}
	}
	$cache_end = microtime(true);
	
	echo "<hr/>";
	echo "\n<br/>no cache time: ".($no_cache_end - $no_cache_start)." segs.";
	echo "\n<br/>cache time: ".($cache_end - $cache_start)." segs.";
	echo "<hr/>";
	$files = listLastDirectoryFiles(LAYER_CACHE_PATH."sysdataaccess/ibatis/TEST/select_item/php/");
	echo "<pre>Cached files: \n" . print_r($files, 1) . "</pre>";
}
elseif($step == 7) {//7th step
	$item_id = 3;
	
	$sql = $EVC->getBroker()->callBusinessLogic("TEST", "get_query_sql", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $item_id));
	
	echo "<br/>CONNECT TO THE MYSQL DRIVER AND GET THE ITEM {$item_id}:";	
	echo "<br/>sql: $sql";
	$result = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $item_id, "options" => array("db_driver" => "mysql")));
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
	
	echo "<br/>CONNECT TO PG DRIVER AND GET THE ITEM {$item_id}:";	
	echo "<br/>sql: $sql</pre>";
	$result = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $item_id, "options" => array("db_driver" => "pg")));
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
	
	echo "<br/>CONNECT TO THE DEFAULT DRIVER AND GET THE ITEM {$item_id}:";	
	echo "<br/>sql: $sql";
	$result = $EVC->getBroker()->callBusinessLogic("TEST", "get_query", array("module" => "TEST", "type" => "select", "service" => "select_item", "parameters" => $item_id));
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
}

die();
?>
