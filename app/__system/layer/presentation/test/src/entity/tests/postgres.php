<?php
//http://jplpinto.localhost/__system/test/tests/postgres

$PHPFrameWork->loadBeansFile(SYSTEM_BEAN_PATH . "db_driver.xml");
$DBDriver = $PHPFrameWork->getObject("PostgresDB");

$table_attributes = $DBDriver->listTableFields("item");
$table_data = array(
	"table_name" => "item",
	"charset" => "utf8",
	"collation" => "utf8_bin",
	"table_storage_engine" => "fillfactor=70",
	"attributes" => $table_attributes,
);

echo "<pre>";
echo $DBDriver->getCreateTableStatement($table_data);

print_r($DBDriver->listTables());

print_r($DBDriver->listTableFields("products2"));
//print_r($DBDriver->listTableFields("products"));
print_r($DBDriver->listTableFields("item"));

print_r($DBDriver->getData("select * from item limit 2"));
//print_r($DBDriver->getData("SELECT id,title,status FROM item"));

echo "</pre>";
?>
