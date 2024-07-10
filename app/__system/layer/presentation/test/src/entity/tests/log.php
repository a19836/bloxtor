<?php
//http://jplpinto.localhost/__system/test/tests/log

global $GlobalLogHandler;

echo "<h1>WITH EXCEPTION</h1>";
$e = new Exception();
$GlobalLogHandler->setInfoLog("MSG TEST", $e->getTraceAsString());

echo "<h1>WITH MY PRINT STACK TRACE</h1>";
$GlobalLogHandler->setInfoLog("MSG TEST", true);
?>
