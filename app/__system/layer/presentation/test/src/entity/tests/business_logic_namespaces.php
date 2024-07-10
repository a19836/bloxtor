<?php
//http://jplpinto.localhost/__system/test/tests/business_logic_namespaces

echo "<br>";
echo "Testing business logic namespaces: <br/>";
echo "0- " . $EVC->getBroker()->callBusinessLogic("test", "TestService.getQuerySQL", array("module" => "TEST", "type" => "insert", "service" => "insert_item_not_registered", "parameters" => 0), array("no_cache" => true));
echo "<br/>";
echo "1- " . $EVC->getBroker()->callBusinessLogic("test.subtest", "TestService.getQuerySQL", 1, array("no_cache" => true));
echo "<br/>";
echo "2- " . $EVC->getBroker()->callBusinessLogic("test.subtest", "Test\TestService.getQuerySQL", 2, array("no_cache" => true));
echo "<br/>";
echo "3- " . $EVC->getBroker()->callBusinessLogic("test.subtest", "\Test\TestService.getQuerySQL", 3, array("no_cache" => true));
echo "<br/>";
echo "4- ";print_r( $EVC->getBroker()->callBusinessLogic("test.subtest", "TestService.getSQL", 4, array("no_cache" => true)) );
echo "<br/>";
echo "5- ";print_r( $EVC->getBroker()->callBusinessLogic("test.subtest", "\Test\a\TestService.getSQL", 5, array("no_cache" => true)) );
echo "<br/>";
echo "6- ";print_r( $EVC->getBroker()->callBusinessLogic("test.subtest", "Test\a\TestService.getSQL", 6, array("no_cache" => true)) );
echo "<br/>";
echo "7- " . $EVC->getBroker()->callBusinessLogic("test", "get_query", array("module" => "TEST", "type" => "insert", "service" => "insert_item_not_registered", "parameters" => 1));
echo "<br/>";
echo "8- " . $EVC->getBroker()->callBusinessLogic("test", "get_query_sql", array("module" => "TEST", "type" => "insert", "service" => "insert_item_not_registered", "parameters" => 1));
echo "<br/>";
echo "9- " . get_class($EVC->getBroker()->callBusinessLogic("test", "get_obj", array("module" => "TEST", "service" => "Item")));
echo "<br/>";
echo "10- " . $EVC->getBroker()->callBusinessLogic("test", "get_query_sql_namespace", 10, array("no_cache" => true));
echo "<br/>";
echo "11- ";print_r( $EVC->getBroker()->callBusinessLogic("test", "get_query_namespace", 11, array("no_cache" => true)) );
echo "<br/>";
echo "12- ";print_r( $EVC->getBroker()->callBusinessLogic("test", "get_query_relative_namespace", 12, array("no_cache" => true)) );
echo "<br/>";

echo "13- " . $EVC->getBroker()->callBusinessLogic("test", "get_query_sql_namespace2", 13, array("no_cache" => true));
echo "<br/>";
echo "14- ";print_r( $EVC->getBroker()->callBusinessLogic("test", "get_query_namespace3", 14, array("no_cache" => true)) );
echo "<br/>";
echo "15- ";print_r( $EVC->getBroker()->callBusinessLogic("test", "test", 15, array("no_cache" => true)) );
echo "<br/>";

echo "<br/>";
echo "Testing cache now:<br>";
echo "1- ";print_r( $EVC->getBroker()->callBusinessLogic("test", "get_query_namespace3", 111, array("no_cache" => false)) );
echo "<br/>";
echo "2- " . $EVC->getBroker()->callBusinessLogic("test", "TestService.getQuerySQL", array("module" => "TEST", "type" => "insert", "service" => "insert_item_not_registered", "parameters" => 112));
echo "<br/>";
echo "3- " . $EVC->getBroker()->callBusinessLogic("test.subtest", "TestService.getQuerySQL", 113);
echo "<br/>";
echo "4- " . $EVC->getBroker()->callBusinessLogic("test.subtest", "\Test\TestService.getQuerySQL", 114);
echo "<br/>";
echo "5- ";print_r( $EVC->getBroker()->callBusinessLogic("test.subtest", "TestService.getSQL", 115) );
echo "<br/>";
echo "6- ";print_r( $EVC->getBroker()->callBusinessLogic("test.subtest", "\Test\a\TestService.getSQL", 116) );
echo "<br/>";

?>
