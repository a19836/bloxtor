<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$_GET["item_type"] = "businesslogic";
$_GET["class"] = isset($_GET["service"]) ? $_GET["service"] : null;

include $EVC->getEntityPath("admin/edit_file_class");
?>
