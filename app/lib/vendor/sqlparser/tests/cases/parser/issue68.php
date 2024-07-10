<?php
require_once(dirname(__FILE__) . "/../../../src/PHPSQLParser.php");
require_once(dirname(__FILE__) . "/../../test-more.php");

// TODO: there is an error within no_quotes
$sql = "select a.`admin_id` FROM admins a WHERE a.admin_username=? AND a.admin_password=?";
$parser = new PHPSQLParser($sql, true);
$p = $parser->parsed;
$expected = getExpectedValue(dirname(__FILE__), 'issue68.serialized');
eq_array($p, $expected, 'Parameter alias ? should not fail.');

?>