<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$file_type = "save_entity_advanced";

include $EVC->getEntityPath("presentation/save");
?>
