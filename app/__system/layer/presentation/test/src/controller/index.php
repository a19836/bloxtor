<?php
echo "<br/>url: ";print_r($_GET);
echo "<br/>parameters: ";print_r($parameters);
echo "<br/><hr/><br/>";

include $EVC->getControllerPath("index", $EVC->getCommonProjectName());
?>
