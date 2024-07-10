<?php
//http://jplpinto.localhost/__system/test/tests/workflow_code_parser

include_once get_lib("org.phpframework.workflow.WorkFlowTaskCodeParser");

//$task_folder_path = dirname(get_lib("org.phpframework.workflow.task.CallBusinessLogicWorkFlowTask"));

$webroot_cache_folder_path = $EVC->getPresentationLayer()->getSelectedPresentationSetting("presentation_webroot_path") . "__system/cache/";
$webroot_cache_folder_url = $project_url_prefix . "test/__system/cache/";

$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
$WorkFlowTaskHandler->flushCache();
$WorkFlowTaskHandler->initWorkFlowTasks();

$WorkFlowTaskCodeParser = new WorkFlowTaskCodeParser($WorkFlowTaskHandler);
$available_statements = $WorkFlowTaskCodeParser->getAvailableStatements();
//print_r($available_statements);

/*
if
switch
*/
$code = '<?php
/*class C {
	private $x;
	
	public function __construct() {
		$this->x = "1";
	}
	
	public function getX() {return $this->x;}
}

$C = new C();
$y = $C->getX();
*/

//if (($x !== 1)) {
//if ($y == "1" || $y == true && foo()) {
//if (($y == "1" || $x == true) && $x = 12) {
if ($y == "1" && $y == "12" || $y == "0") {
	$str = 1 * 100;
}
elseif (strtolower($y) == "foo") {
	$str = "foo";
}
else {
	$str = "HELLO";
}

echo "str", \':\' . "$str\n";

switch ($x) {
	case "1": $y = 1; break;
	case "2":
	case "3": $y = 3; break;
	default: $y = 0;
}

$x = 0;
?>';

/*$code = '<?php
$x = array(12, "1" => "asd", bar() => foo(), $obj->bar() => $obj->foo());
array(12, "1" => "asd", array(1, 2));
Obj::$x = Obj::foo();
Obj::$x->foo();
date("Y-D-m");
$x = date("Y-D-m");
include("xxx.php");
include($x);
include_once __DIR__ . "/xxx.php";
require "../" . "xxx.php";
require_once substr("as", 0) . "xxx.php";
new Obj(1, 2);
$x = new Obj(1, 2);
define("asd", 123);
Obj::$x = xpto(1, "as", foo());
$Obj->TTT->x = xpto(1, "as", foo());
$x = foo();
foo();
$x = $obj->foo();
$x = $obj->C->foo();
$obj->foo();
$x = Obj::foo();
Obj::foo();
$x = $y;
$x = "hello";
$x = 1;

$x = "hello" . " world";
$x .= "!";
$x = "www." . $domain . ".com";

$x = 1 + 1;
$x = 1 * 1 + 1 % 2;
$x = (int) 1 - 1 / 2;
$x = parseInt(4 / 3);

//PROPERTY
$Obj->TTT->x = xpto();
$Obj->TTT->x = "as";
$Obj->TTT->YYY->x = "as";
$Obj->TTT()->x = 1;
Obj::$TTT->$x = 1;
$this->x = "as";
self::$x = 1;
$i++;
?>';*/

/*$code = '<?php
include "foo.php";
foo();
?>';*/

$tasks = $WorkFlowTaskCodeParser->getParsedCodeAsArray($code);
$xml = $WorkFlowTaskCodeParser->getParsedCodeAsXml($code);

echo "<pre>";print_r($tasks);echo "</pre>";
echo "<textarea style='width:100%; height:500px;'>$xml</textarea>";
die();
?>
