<?php
//http://jplpinto.localhost/__system/test/tests/mysql

$PHPFrameWork->loadBeansFile(SYSTEM_BEAN_PATH . "db_driver.xml");
$DBDriver = $PHPFrameWork->getObject("MySqlDB");

$table_attributes = $DBDriver->listTableFields("item");
$table_data = array(
	"table_name" => "item",
	"charset" => "utf8",
	"collation" => "utf8_bin",
	"table_storage_engine" => "MyISAM",
	"attributes" => $table_attributes,
);

echo "<pre>";
echo $DBDriver->getCreateTableStatement($table_data);

print_r($DBDriver->listTables());

print_r($DBDriver->listTableFields("item"));

print_r($DBDriver->getData("show columns from item"));
echo "</pre>";
?>
