<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$file_type = "save_config";

include $EVC->getEntityPath("presentation/save");
?>
