<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$file_type = "save_query";

include $EVC->getEntityPath("dataaccess/save");
?>
