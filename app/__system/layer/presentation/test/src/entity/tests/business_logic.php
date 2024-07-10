<?php
//http://jplpinto.localhost/__system/test/tests/business_logic
include $EVC->getUtilPath("util");

echo "Testing subtest business logic function (without class. Independent function): <br/>";
//echo $EVC->getBroker()->callBusinessLogic("test", "test", 123, array("no_cache" => false));
echo "<br/>";
echo $EVC->getBroker()->callBusinessLogic("test/subtest", "foo", "value xxx", array("no_cache" => false));
//echo $EVC->getBroker()->callBusinessLogic("test.subtest.IndependentFunctionsServices.php", "foo", "value xxx", array("no_cache" => false));
//echo $EVC->getBroker()->callBusinessLogic("test/subtest/IndependentFunctionsServices.php", "foo", "value xxx", array("no_cache" => false));
//echo $EVC->getBroker()->callBusinessLogic("test/subtest/IndependentFunctionsServices", "foo", "value xxx", array("no_cache" => false));
//echo $EVC->getBroker()->callBusinessLogic("test.subtest.IndependentFunctionsServices", "foo", "value xxx", array("no_cache" => false));
echo "<br/>";
echo $EVC->getBroker()->callBusinessLogic("test.subtest", "bar", "value bar", array("no_cache" => false));
echo "<hr/>";

echo "Testing subtest business logic: ";
echo $EVC->getBroker()->callBusinessLogic("test.subtest", "SubTestService.executeBusinessLogicSubTest", null, array("no_cache" => false/*, "no_annotations" => true*/));
//echo $EVC->getBroker()->callBusinessLogic("test.subtest.SubTestService.php", "SubTestService.executeBusinessLogicSubTest", null, array("no_cache" => false/*, "no_annotations" => true*/));
//echo $EVC->getBroker()->callBusinessLogic("test/subtest/SubTestService.php", "SubTestService.executeBusinessLogicSubTest", null, array("no_cache" => false/*, "no_annotations" => true*/));
echo "<hr/>";

echo "Testing subsubtest business logic: ";
echo $EVC->getBroker()->callBusinessLogic("test.subtest.subsubtest", "SubSubTestService.executeBusinessLogicSubSubTest", "sub sub test", array("no_cache" => false));
echo "<hr/>";

echo "Testing TestExtendCommonServiceWithDiferentName business logic (simple constructor in the SERVICES.xml, which extends the CommonService constructor): <br/>";
echo $EVC->getBroker()->callBusinessLogic("test", "TestExtendCommonServiceWithDiferentName.getQuerySQL", array("module" => "TEST", "type" => "insert", "service" => "insert_item_not_registered", "parameters" => 1), array("no_cache" => false));
echo "<hr/>";

echo "Testing TestExtendCommonService business logic (no constructor in the SERVICES.xml, but extends the CommonService constructor): <br/>";
echo $EVC->getBroker()->callBusinessLogic("test", "TestExtendCommonService.getQuerySQL", array("module" => "TEST", "type" => "insert", "service" => "insert_item_not_registered", "parameters" => 1), array("no_cache" => false));
echo "<hr/>";

echo "Testing TestExtendCommonService.getBusinessLogicLayer service (no constructor in the SERVICES.xml, but extends the CommonService constructor and the getBusinessLogicLayer service only exists in the CommonService. It does NOT exist in the TestExtendCommonService): <br/>";
echo "TestExtendCommonService.getBusinessLogicLayer object type: " . get_class( $EVC->getBroker()->callBusinessLogic("test", "TestExtendCommonService.getBusinessLogicLayer", false, array("no_cache" => false)) );
echo "<hr/>";

echo "Testing TestExtendCommonServiceWithDiferentName.getBusinessLogicLayer service (simple constructor in the SERVICES.xml, which extends the CommonService constructor and the getBusinessLogicLayer service only exists in the CommonService. It does NOT exist in the TestExtendCommonServiceWithDiferentName or in the SERVICES.xml): <br/>";
echo "TestExtendCommonServiceWithDiferentName.getBusinessLogicLayer object type: " . get_class( $EVC->getBroker()->callBusinessLogic("test", "TestExtendCommonService.getBusinessLogicLayer", false, array("no_cache" => false)) );
echo "<hr/>";

$status = CacheHandlerUtil::deleteFolder(LAYER_CACHE_PATH . "sysbusinesslogic/", false);
echo "\n<br/>status: $status";
echo "<hr/>";
echo $EVC->getBroker()->callBusinessLogic("TEST", "TestService.executeBusinessLogicTest", "first test", array("no_cache" => false));
echo "<hr/>";

$no_cache_start = microtime(true);
for($i = 0; $i < 300; $i++) {
	$result = $EVC->getBroker()->callBusinessLogic("TEST", "exec_business_logic_test", $i, array("no_cache" => true));
	echo "\n<br/>business_logic_test $i: ".$result;
}
$no_cache_end = microtime(true);

echo "<hr/>";

$cache_start = microtime(true);
for($i = 0; $i < 300; $i++) {
	$result = $EVC->getBroker()->callBusinessLogic("TEST", "exec_business_logic_test", $i);
	echo "\n<br/>business_logic_test $i: ".$result;
}
$cache_end = microtime(true);

echo "<hr/>";
echo "\n<br/>no cache time: ".($no_cache_end - $no_cache_start)." segs.";
echo "\n<br/>cache time: ".($cache_end - $cache_start)." segs.";
echo "<hr/>";
$files = listLastDirectoryFiles(LAYER_CACHE_PATH."sysbusinesslogic/TEST/exec_business_logic_test/php/");
echo "<pre>Cached files: \n" . print_r($files, 1) . "</pre>";
echo "<hr/>";

$result = $EVC->getBroker()->callBusinessLogic("TEST", "del_business_logic_test_cache", $i);
$files = listLastDirectoryFiles(LAYER_CACHE_PATH."sysbusinesslogic/TEST/exec_business_logic_test/php/");
echo "<pre>Cached files: \n" . print_r($files, 1) . "</pre>";
?>
