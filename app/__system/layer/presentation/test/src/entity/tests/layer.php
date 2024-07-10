<?php
//http://jplpinto.localhost/__system/test/tests/layer

echo "<p>
Test if we can call a business logic or other layer (presentation && data_access) typing the folder name directly, this means, if the module_id is not register in the modules.xml file, we can call him by typing the correspondent folder name. 
<br/>
<br/>
eg: Both module_id 'TEST' or 'test' will work, because the 'TEST' id is register in the modules.xml 'file' and test is the folder name.
</p>";
echo "<hr/>";
echo "<br/>BUSINESS LOGIC FUNCTION:";
echo "<br/>sql: ".$EVC->getBroker()->callBusinessLogic("test", "get_query_sql", array("module" => "TEST", "type" => "insert", "service" => "insert_item", "parameters" => array("title" => "some title xpto")));
?>
