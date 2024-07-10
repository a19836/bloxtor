<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$_GET["item_type"] = "businesslogic";

include $EVC->getEntityPath("admin/edit_file_includes");
?>
