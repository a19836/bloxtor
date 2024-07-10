<?php
//http://jplpinto.localhost/__system/test/tests/sql
include_once get_lib("org.phpframework.db.SQLQueryHandler");

$PHPFrameWork->loadBeansFile(SYSTEM_BEAN_PATH . "db_driver.xml");
$DBDriver = $PHPFrameWork->getObject("MySqlDB");
$DBDriver->selectDB("test");

$data = array(
	"type" => "select",
	"main_table" => "user_activity",
	"attributes" => array(
		array("table" => "user_activity", "column" => "user_id", "name" => "uid"),
		array("table" => "user_activity", "column" => "activity_id", "name" => ""),
		array("table" => "", "column" => "values", "name" => ""),
		array("table" => "", "column" => "notes", "name" => ""),
		array("table" => "", "column" => "created_date", "name" => ""),
		array("table" => "", "column" => "modified_date", "name" => ""),
	),
	"conditions" => array(
		array("table" => "user_activity", "column" => "activity_id", "operator" => "=", "value" => "1"),
	),
	"start" => 0,
	"limit" => 1
);
$sql = SQLQueryHandler::create($data);

echo "sql:$sql\n<br><pre>";
print_r($DBDriver->getData($sql));
echo "</pre>";

echo "<br><hr><br>";

$sql = "SELECT 
     `Fornecedor`.`Fornecedor`
 FROM `Caixeiro`
   INNER JOIN `Fornecedor` ON (`Caixeiro`.`fornecedor_id` = `Fornecedor`.`fornecedor_id`)
 WHERE `Caixeiro`.`caixeiro_id` = '#caixeiro_id#' AND #searching_condition#;";
$parsed = SQLQueryHandler::parse($sql);
echo "sql:$sql\n<br><pre>";
print_r($parsed);
echo "</pre>";
?>
