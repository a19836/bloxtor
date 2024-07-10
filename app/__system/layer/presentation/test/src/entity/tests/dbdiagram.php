<?php
//http://jplpinto.localhost/__system/test/tests/dbdiagram

include_once get_lib("org.phpframework.dbdiagram.DBDiagramHandler");

$file_path = get_lib("org.phpframework.dbdiagram.test.tables_schema_for_test", "xml");
$sql = DBDiagramHandler::parseFile($file_path);

echo "<h1>PRINTED SQL</h1>";
echo "<pre>\n" . $sql . "\n</pre>";
?>
