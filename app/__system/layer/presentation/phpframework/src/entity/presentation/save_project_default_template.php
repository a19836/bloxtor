<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$file_type = "save_project_default_template";

include $EVC->getEntityPath("presentation/save");
?>
