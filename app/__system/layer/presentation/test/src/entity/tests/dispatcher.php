<?php
//http://jplpinto.localhost/__system/test/tests/dispatcher?value=1
// and then execute the cmd: ls -l /tmp/phpframework/cache/layer/syspresentation/dispatcher/test/text/*/*/*/*

echo "Dispatcher time for '".(isset($_GET["value"]) ? $_GET["value"] : null)."':" . date("Y-m-d H:i:s:u");
?>
