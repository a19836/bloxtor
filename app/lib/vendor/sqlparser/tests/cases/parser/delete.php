<?php
require_once(dirname(__FILE__) . "/../../../src/PHPSQLParser.php");
require_once(dirname(__FILE__) . "/../../test-more.php");

$parser = new PHPSQLParser();

$sql = "delete from testA as a where a.id = 1";
$p = $parser->parse($sql);
$expected = getExpectedValue(dirname(__FILE__), 'delete1.serialized');
eq_array($p, $expected, 'simple delete statement');


?>