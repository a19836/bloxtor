<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$_GET["item_type"] = "businesslogic";
$_GET["class"] = $_GET["service"];

include $EVC->getEntityPath("admin/edit_file_class");
?>
