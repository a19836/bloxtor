<?php
//http://jplpinto.localhost/__system/test/tests/parse_php_code

//MyXMLArray test
include_once get_lib("org.phpframework.phpscript.PHPCodePrintingHandler");
include_once get_lib("org.phpframework.phpscript.PHPCodeObfuscator");

$tmp_path = (TMP_PATH ? TMP_PATH : "/tmp/");

//$file_path = get_lib("org.phpframework.db.IDB");
//$file_path = get_lib("org.phpframework.db.DB");
$file_path = get_lib("org.phpframework.db.driver.MySqlDB");
$tmp_file = $tmp_path . "php_code_tmp_file.php";
$obfuscate = false;

//copy $file_path to $tmp_file
copy($file_path, $tmp_file);

//obfuscate file for testing
if ($obfuscate) {
	$opts = array(
		"strip_eol" => 1,
		"strip_comments" => 1,
		"strip_doc_comments" => 1,
		"copyright" => '/*
 * Copyright (c) 2007 PHPMyFrameWork - Joao Pinto
 * AUTHOR: Joao Paulo Lopes Pinto -- http://jplpinto.com
 */'
	);
	
	$files_settings = array(
		$tmp_file => array(
			1 => array(
				"save_path" => $tmp_file,
				"all_functions" => array("obfuscate_code" => 1),
				"all_properties" => array("obfuscate_name_private" => 1),
				"all_methods" => array("obfuscate_code" => 1, "obfuscate_name_private" => 1),
			),
		)
	);
	
	$PHPCodeObfuscator = new PHPCodeObfuscator($files_settings);
	$PHPCodeObfuscator->obfuscateFiles($opts);
}

//For any file
//$result = PHPCodePrintingHandler::removeIncludesFromFile($tmp_file);
//$result = PHPCodePrintingHandler::addIncludesToFile($tmp_file, array('$vars["business_logic_modules_service_common_file_path"]', '"/tmp/php_code_tmp_file.php"'));
//$result = PHPCodePrintingHandler::getIncludesFromFile($tmp_file);
//$result = PHPCodePrintingHandler::addClassToFile($tmp_file, array("name" => "foo", "abstract" => 1, "extends" => "bar"));
//$result = PHPCodePrintingHandler::addFunctionToFile($tmp_file, array("name" => "FOO", "code" => "return true;"));
//$result = PHPCodePrintingHandler::removeFunctionFromFile($tmp_file, "foo");
//$result = PHPCodePrintingHandler::editFunctionCommentsFromFile($tmp_file, "FOO", "//Some comments\n/*Bla ble*/");

//In case of MySqlDB or obfuscated file
$result = PHPCodePrintingHandler::getPHPClassesFromFile($tmp_file);
//$result = PHPCodePrintingHandler::renameClassFromFile($tmp_file, "mysqldb", "test");
//$result = PHPCodePrintingHandler::renameFunctionFromFile($tmp_file, "getLoadTableDataFromFileStatement", "test", "mysqldb");
//$result = PHPCodePrintingHandler::removeClassFromFile($tmp_file, "mysqlDB");
//$result = PHPCodePrintingHandler::addFunctionToFile($tmp_file, array("name" => "getBar", "static" => true, "arguments" => array("\$a" => null, "\$b" => "true", "\$c" => "aaaaa", "\$d" => "123"), "code" => "return true;"), "mysqldb");
//$result = PHPCodePrintingHandler::removeFunctionFromFile($tmp_file, "getLoadTableDataFromFileStatement", "mysqldb");
//$result = PHPCodePrintingHandler::editClassFromFile($tmp_file, array("name" => "mysqldb"), array("name" => "foo", "abstract" => 1, "extends" => "bar", "code" => "private \$x = 12;\npublic \$y='asd';\n//TODO"));
//$result = PHPCodePrintingHandler::editFunctionFromFile($tmp_file, array("name"=>"getLoadTableDataFromFileStatement"), array("name" => "getBar", "static" => true, "type" => "private", "arguments" => array("\$a" => null, "\$b" => "true", "\$c" => "aaaaa", "\$d" => "123"), "code" => "//TODO: XXX"), "mysqldb");
//$result = PHPCodePrintingHandler::getFunctionCodeFromFile($tmp_file, "getLoadTableDataFromFileStatement", "mysqldb");
//$result = PHPCodePrintingHandler::getFunctionCodeFromFile($tmp_file, "getLoadTableDataFromFileStatement", "mysqldb", true);
//$result = PHPCodePrintingHandler::removeClassPropertiesFromFile($tmp_file, "mysqldb");
//$result = PHPCodePrintingHandler::addClassPropertiesToFile($tmp_file, "mysqldb", "private \$x = 12;\npublic \$y='asd';");
//$result = PHPCodePrintingHandler::replaceFunctionCodeFromFile($tmp_file, "getLoadTableDataFromFileStatement", "echo 'HELLO';\nreturn 1;", "mysqldb");
//$result = PHPCodePrintingHandler::getClassPropertiesFromFile($tmp_file, "mysqldb");
//$result = PHPCodePrintingHandler::getClassPropertyFromFile($tmp_file, "mysqldb", "to_db_encodings");
//$result = PHPCodePrintingHandler::editClassCommentsFromFile($tmp_file, "mysqldb", "//Some comments\n/*Bla ble*/");
//$result = PHPCodePrintingHandler::editFunctionCommentsFromFile($tmp_file, "errno", "//Some comments\n/*Bla ble*/", "mysqldb");
//$result = PHPCodePrintingHandler::editFunctionCommentsFromFile($tmp_file, "getLoadTableDataFromFileStatement", "//Some comments\n/*Bla ble*/", "mysqldb");

//In case of IDB
//$result = PHPCodePrintingHandler::getPHPClassesFromFile($tmp_file);
//$result = PHPCodePrintingHandler::renameClassFromFile($tmp_file, "IDB", "test");
//$result = PHPCodePrintingHandler::renameFunctionFromFile($tmp_file, "getdata", "test", "IDB");
//$result = PHPCodePrintingHandler::removeClassFromFile($tmp_file, "IDB");
//$result = PHPCodePrintingHandler::addFunctionToFile($tmp_file, array("name" => "getBar", "static" => true, "arguments" => array("\$a" => null, "\$b" => "true", "\$c" => "aaaaa", "\$d" => "123"), "code" => "return true;"), "idb");
//$result = PHPCodePrintingHandler::removeFunctionFromFile($tmp_file, "getdata", "idb");
//$result = PHPCodePrintingHandler::editClassFromFile($tmp_file, array("name" => "idb"), array("name" => "foo", "abstract" => 1, "extends" => "bar", "code" => "private \$x = 12;\npublic \$y='asd';\n//TODO"));
//$result = PHPCodePrintingHandler::editFunctionFromFile($tmp_file, array("name"=>"getdata"), array("name" => "getBar", "static" => true, "type" => "private", "arguments" => array("\$a" => null, "\$b" => "true", "\$c" => "aaaaa", "\$d" => "123"), "code" => "//TODO: XXX"), "idb");
//$result = PHPCodePrintingHandler::getFunctionCodeFromFile($tmp_file, "getdata", "idb");
//$result = PHPCodePrintingHandler::addClassPropertiesToFile($tmp_file, "idb", "private \$x = 12;\npublic \$y='asd';");
//$result = PHPCodePrintingHandler::removeClassPropertiesFromFile($tmp_file, "idb");
//$result = PHPCodePrintingHandler::replaceFunctionCodeFromFile($tmp_file, "getdata", "echo 'HELLO';\nreturn 1;", "idb");
//$result = PHPCodePrintingHandler::getClassPropertiesFromFile($tmp_file, "idb");
//$result = PHPCodePrintingHandler::editClassCommentsFromFile($tmp_file, "idb", "//Some comments\n/*Bla ble*/");
//$result = PHPCodePrintingHandler::editFunctionCommentsFromFile($tmp_file, "getdata", "//Some comments\n/*Bla ble*/", "idb");

echo "<pre>" .  print_r($result, 1) . "</pre>";
echo "<br>";
echo '<textarea style="width:100%; height:200px;">' .  print_r($result, 1) . "</textarea>";
echo "<br><br>";
echo '<textarea style="width:100%; height:90%; min-height:300px;">' .  htmlspecialchars(file_get_contents($tmp_file), ENT_NOQUOTES) . "</textarea>";

unlink($tmp_file);
?>
