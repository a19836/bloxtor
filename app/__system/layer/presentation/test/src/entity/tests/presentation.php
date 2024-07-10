<?php
//http://jplpinto.localhost/__system/test/tests/presentation?value=1
//http://jplpinto.localhost/__system/test/tests/presentation?value=2
//http://jplpinto.localhost/__system/test/tests/presentation?value=3
// and then execute the cmd: ls -l /tmp/phpframework/cache/layer/syspresentation/test/tests/text/*/*/*/*

$value = isset($_GET["value"]) ? $_GET["value"] : null;

echo "Presentation time for '".$value."':" . date("Y-m-d H:i:s:u");
echo "<br/>";

//This is only to show that is possible to call the DATA ACCESS LAYER from the PRESENTATION LAYER
$sql = $EVC->getBroker("ibatis")->callQuerySQL("TEST", "select", "select_item", $value);
echo "<br/>CONNECT TO THE MYSQL DRIVER AND GET THE ITEM {$value}:";	
echo "<br/>sql: $sql";
?>
