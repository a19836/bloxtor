<?php
//http://jplpinto.localhost/__system/test/tests/hibernate?step=1
//http://jplpinto.localhost/__system/test/tests/hibernate?step=2
//http://jplpinto.localhost/__system/test/tests/hibernate?step=3
//http://jplpinto.localhost/__system/test/tests/hibernate?step=4
//http://jplpinto.localhost/__system/test/tests/hibernate?step=5
//http://jplpinto.localhost/__system/test/tests/hibernate?step=6
//http://jplpinto.localhost/__system/test/tests/hibernate?step=7
//http://jplpinto.localhost/__system/test/tests/hibernate?step=8
//http://jplpinto.localhost/__system/test/tests/hibernate?step=9
//http://jplpinto.localhost/__system/test/tests/hibernate?step=10

include_once get_lib("vendor.dao.test.MyItemHbnModel"); //if broker is REST, php does not have this class yet included
include $EVC->getUtilPath("util");

$step = isset($_GET["step"]) ? $_GET["step"] : null;

echo "<br/>GET ITEM OBJ FROM AN OBJECT WHICH IS NOT REGISTER IN THE dataaccess/hibernate/xxx/SERVICES.xml:";
$ItemObj = $EVC->getBroker()->callBusinessLogic("TEST", "get_obj", array("module" => "TEST", "service" => "ItemObjNotRegistered"));
$ItemObj = is_a($ItemObj, "MyItemHbnModel") ? $ItemObj : ObjectHandler::objectToObject($ItemObj, "MyItemHbnModel"); //if broker is REST, we must convert the unserilized object to MyItemHbnModel, otherwise the $ItemObj is __PHP_Incomplete_Class 
echo "<br/>obj class: ".get_class($ItemObj);
echo "<hr/>";

echo "<br/>GET SUB ITEM OBJ FROM AN OBJECT WHICH IS NOT REGISTER IN THE dataaccess/hibernate/xxx/yyy/SERVICES.xml:";
$ItemObj = $EVC->getBroker()->callBusinessLogic("TEST", "get_obj", array("module" => "TEST.subtest", "service" => "SubItemObjNotRegistered"));
$ItemObj = is_a($ItemObj, "MyItemHbnModel") ? $ItemObj : ObjectHandler::objectToObject($ItemObj, "MyItemHbnModel"); //if broker is REST, we must convert the unserilized object to MyItemHbnModel, otherwise the $ItemObj is __PHP_Incomplete_Class 
echo "<br/>obj class: ".get_class($ItemObj);
echo "<hr/>";

echo "<br/>GET ITEM OBJ:";
$ItemObj = $EVC->getBroker()->callBusinessLogic("TEST", "get_obj", array("module" => "TEST", "service" => "Item"));
$ItemObj = is_a($ItemObj, "MyItemHbnModel") ? $ItemObj : ObjectHandler::objectToObject($ItemObj, "MyItemHbnModel"); //if broker is REST, we must convert the unserilized object to MyItemHbnModel, otherwise the $ItemObj is __PHP_Incomplete_Class 
echo "<br/>obj class: ".get_class($ItemObj);
echo "<hr/>";

if($step == 1) {//1st step
	echo "<br/>ITEM OBJ SETTINGS:";
	echo "<br/>getIds: <pre>";print_r($ItemObj->getIds());echo "</pre>";
	echo "<br/>getManyToOne: <pre>";print_r($ItemObj->getManyToOne());echo "</pre>";
	echo "<br/>getManyToMany: <pre>";print_r($ItemObj->getManyToMany());echo "</pre>";
	echo "<br/>getOneToMany: <pre>";print_r($ItemObj->getOneToMany());echo "</pre>";
	echo "<br/>getOneToOne: <pre>";print_r($ItemObj->getOneToOne());echo "</pre>";
	echo "<br/>getQueries: <pre>";print_r($ItemObj->getQueries());echo "</pre>";
	echo "<br/>getTableAttributes: <pre>";print_r($ItemObj->getTableAttributes());echo "</pre>";
	echo "<br/>getPropertiesToAttributes: <pre>";print_r($ItemObj->getPropertiesToAttributes());echo "</pre>";
	echo "<hr/>";
}
elseif($step == 2) {//2nd step
	echo "<br/>INSERT NEW ITEM:";
	echo "<br/>status: ".$ItemObj->insert(array("name" => "hibernate item test X", "status" => 0), $ids);
	$new_id = $ItemObj->getInsertedId();
	echo "<br/>new_id: {$new_id}";
	echo "<br/>news_ids: <pre>";print_r($ids);echo "</pre>";
	echo "<hr/>";
	
	echo "<br/>SELECT ITEM {$new_id} with separated objects:";
	$result = $ItemObj->findById($new_id, array(
						"attributes" => array("pk_id", "name", "status"),
						"relationships" => true
					),
					array(
						"separated_by_objects" => true
					)
				);
	echo "<br/>findById result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
	
	
	setcookie("item_id", $new_id);
	$_COOKIE["item_id"] = $new_id;
}
elseif($step == 3) {//3rd step
	$item_id = isset($_COOKIE["item_id"]) ? (int)$_COOKIE["item_id"] : null;
	
	echo "<br/>UPDATE AND INSERT ITEMS:";
	echo "<br/>status: ".$ItemObj->insertOrUpdateAll(
						array(
							array("pk_id" => $item_id, "name" => "hibernate item test W", "status" => 1),
							array("name" => "hibernate item test T", "status" => 1)
						),
						$statuses,
						$ids
				      );
	$new_id = isset($ids[1]["pk_id"]) ? $ids[1]["pk_id"] : null;
	echo "<br/>updated_id: {$item_id}";
	echo "<br/>new_id: {$new_id}";
	echo "<br/>news_ids: <pre>";print_r($ids);echo "</pre>";
	echo "<br/>statuses: <pre>";print_r($statuses);echo "</pre>";
	echo "<hr/>";
	
	
	echo "<br/>SELECT NEW AND UPDATED ITEM ($new_id, $item_id):";
	$result = $ItemObj->getData("select * from item where id in ($item_id, $new_id)");
	//$result = $ItemObj->getData("select * from item where id in ($item_id, $new_id)", array("limit" => 2, "start" => 0));
	echo "<br/>getData result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
	$result = $ItemObj->find(array(
			"conditions" => array("pk_id" => array($item_id, $new_id)),
			"conditions_join" => "or"
		)
	);
	echo "<br/>find result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
}
elseif($step == 4) {//4th step
	$item_id = isset($_COOKIE["item_id"]) ? $_COOKIE["item_id"] : null;
	
	echo "<br/>GET SUBITEM OBJ:";
	$SubItemObj = $EVC->getBroker()->callBusinessLogic("TEST", "get_obj", array("module" => "TEST", "service" => "SubItem"));
	echo "<br/>obj class: ".get_class($SubItemObj);
	echo "<hr/>";
	
	
	echo "<br/>INSERT NEW SUBITEM IN ITEM $item_id:";
	echo "<br/>status: ".$SubItemObj->insert(array("item_id" => $item_id, "title" => "hibernate subitem of item $item_id"));
	$new_id = $SubItemObj->getInsertedId();
	echo "<br/>new_id: {$new_id}";
	echo "<hr/>";
	
	
	echo "<br/>SELECT RELATIONSHIPS FOR ITEM $item_id:";
	$result = $ItemObj->findRelationships(array("pk_id" => $item_id));
	echo "<br/>findRelationships result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
	
	
	echo "<br/>SELECT RELATIONSHIPS FOR ITEM $item_id:";
	$result = $ItemObj->findRelationships(array("pk_id" => $item_id), array("no_cache" => true));
	echo "<br/>findRelationships result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
	
	
	echo "<br/>SELECT SUBITEMS FROM ITEM $item_id:";
	echo "<br/>sql: ".$SubItemObj->callSelectSQL("select_all_by_item", array("item_id" => $item_id));
	$result = $SubItemObj->callSelect("select_all_by_item", array("item_id" => $item_id));
	echo "<br/>findRelationships result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
	
	
	echo "<br/>SELECT RELATIONSHIP 'sub_items' FOR ITEM $item_id:";
	$result = $ItemObj->findRelationship("sub_items", array("pk_id" => $item_id));
	echo "<br/>findRelationship 'sub_items' result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
	
	
	echo "<br/>SELECT FIRST 5 ITEMS WITH STATUS 1:";
	$result = $ItemObj->find(array(
					"CONDITIONS" =>  array("status" => 1),
					"sort" =>  array(array("COLUMN" => "pk_id", "ORDER" => "DESC"), array("column" => "status", "order" => "asc")),
					"start" =>  0,
					"LIMIT" =>  5,
					"attributes" => array("name", "pk_id", "status"),
					"RELATIONSHIPS" => true
				    )
				);
	//$result = $ItemObj->find();
	//$result = $ItemObj->find(array("LIMIT" =>  5, "start" =>  4));
	echo "<br/>find result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
}
elseif($step == 5) {//5th step
	$item_id = isset($_COOKIE["item_id"]) ? $_COOKIE["item_id"] : null;
	
	echo "<br/>UPDATE ITEM:";
	echo "<br/>status: ".$ItemObj->update(array("pk_id" => $item_id, "name" => "hibernate item test Y", "status" => 1));
	echo "<hr/>";
	
	echo "<br/>SELECT LAST 3 ITEMS:";
	$result = $ItemObj->find(array(
					"sort" =>  array(array("column" => "pk_id", "ORDER" => "DESC"), array("COLUMN" => "status", "order" => "desc")),
					"start" =>  0,
					"LIMIT" =>  3
				)
			);
	echo "<br/>find result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
}
elseif($step == 6) {//6th step
	$item_id = isset($_COOKIE["item_id"]) ? $_COOKIE["item_id"] : null;
	
	echo "<br/>SELECT ITEM $new_id BEFORE DELETION:";
	$result = $ItemObj->find(array("CONDITIONS" =>  array("pk_id" => $item_id) ) );
	echo "<br/>find result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
	
	
	echo "<br/>DELETE ITEM $item_id:";
	echo "<br/>status: ".$ItemObj->delete($item_id);
	echo "<hr/>";
	
	
	echo "<br/>SELECT CACHED ITEM $item_id AFTER DELETION:";
	$result = $ItemObj->find(array("CONDITIONS" =>  array("pk_id" => $item_id) ) );
	echo "<br/>find result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
	
	
	echo "<br/>SELECT ITEM $item_id AFTER DELETION:";
	$result = $ItemObj->find(array("CONDITIONS" =>  array("pk_id" => $item_id) ), array("no_cache" => true) );
	echo "<br/>find result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
}
elseif($step == 7) {//7th step
	include_once get_lib("vendor.dao.test.ItemTest");
	$ItemTest = new ItemTest();
	$ItemTest->setTitle("Molo EHEHE...");
	
	/*class X { 
		public $data;
		public function __construct() {
			$this->data = array("title" => "EHEHE");
		}
	}
	$ItemTest = new X();*/
	
	echo "<br/>PROCEDURE ITEMS:";
	$parameters = $ItemTest;
	echo "<br/>sql: ".$ItemObj->callProcedureSQL("procedure_items", $parameters);
	$result = $ItemObj->callProcedure("procedure_items", $parameters);
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
}
elseif($step == 8) {//8th step
	$status = CacheHandlerUtil::deleteFolder(LAYER_CACHE_PATH . "sysdataaccess/hibernate/", false);
	echo "\n<br/>status: $status";
	echo "<hr/>";
	
	$no_cache_start = microtime(true);
	for($i = 0; $i < 300; $i++) {
		$result = $ItemObj->findById($i, false, array("no_cache" => true));
				
		if(!empty($result["ItemObj"]["pk_id"])) {
			echo "\n<br/>item ".$result["ItemObj"]["pk_id"];
		}
	}
	$no_cache_end = microtime(true);
	
	echo "<hr/>";
	
	$cache_start = microtime(true);
	for($i = 0; $i < 300; $i++) {
		$result = $ItemObj->findById($i);
				
		if(!empty($result["ItemObj"]["pk_id"])) {
			echo "\n<br/>item ".$result["ItemObj"]["pk_id"];
		}
	}
	$cache_end = microtime(true);
	
	echo "<hr/>";
	echo "\n<br/>no cache time: ".($no_cache_end - $no_cache_start)." segs.";
	echo "\n<br/>cache time: ".($cache_end - $cache_start)." segs.";
	echo "<hr/>";
	$files = listLastDirectoryFiles(LAYER_CACHE_PATH."sysdataaccess/hibernate/TEST/Item.findById/php/");
	echo "<pre>Cached files: \n" . print_r($files, 1) . "</pre>";
}
elseif($step == 9) {//9th step
	$sql = "DESCRIBE item";
	echo "<br/>CONNECT TO THE DEFAULT DRIVER AND GET THE AVAILABLE ITEM COLUMNS:";	
	echo "<br/>sql: $sql";
	$result = $ItemObj->getData($sql);
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
	
	$sql = "SELECT
			a.attnum,
			a.attname AS field,
			t.typname AS type,
			a.attlen AS length,
			a.atttypmod AS lengthvar,
			a.attnotnull AS notnull
		FROM
			pg_class c,
			pg_attribute a,
			pg_type t
		WHERE
			c.relname = 'item'
			and a.attnum > 0
			and a.attrelid = c.oid
			and a.atttypid = t.oid
		ORDER BY a.attnum";
	echo "<br/>CONNECT TO OTHER DRIVER AND GET THE AVAILABLE ITEM COLUMNS:";	
	echo "<br/>sql: <pre>$sql</pre>";
	$result = $ItemObj->getData($sql, array("db_driver" => "pg"));
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
	
	$sql = "DESCRIBE item";
	echo "<br/>CONNECT TO THE PREVIOUS DRIVER AND GET THE AVAILABLE ITEM COLUMNS:";	
	echo "<br/>sql: $sql";
	$result = $ItemObj->getData($sql);
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
}
elseif($step == 10) {//10th step
	echo "<br/>GET MYSQL ITEM OBJ:";
	$MySQLItemObj = $EVC->getBroker()->callBusinessLogic("TEST", "get_obj", array("module" => "TEST", "service" => "Item"));
	echo "<br/>obj class: ".get_class($MySQLItemObj);
	echo "<hr/>";
	
	echo "<br/>GET PG ITEM OBJ:";
	$PGItemObj = $EVC->getBroker()->callBusinessLogic("TEST", "get_obj", array("module" => "TEST", "service" => "Item", "options" => array("db_driver" => "pg")));
	echo "<br/>obj class: ".get_class($PGItemObj);
	echo "<hr/>";
	
	$sql = "SELECT * FROM item limit 10";
	
	echo "<br/>SHOW MYSQL ITEMS:";	
	echo "<br/>sql: $sql";
	$result = $MySQLItemObj->getData($sql);
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
	
	echo "<br/>SHOW PG COLUMNS:";	
	echo "<br/>sql: $sql";
	$result = $PGItemObj->getData($sql);
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
	
	echo "<br/>SHOW MYSQL ITEMS AGAIN:";	
	echo "<br/>sql: $sql";
	$result = $MySQLItemObj->getData($sql);
	echo "<br/>result: <pre>";print_r($result);echo "</pre>";
	echo "<hr/>";
}

die();
?>
