<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$file_type = "save_entity_simple";

include $EVC->getEntityPath("presentation/save");
?>
