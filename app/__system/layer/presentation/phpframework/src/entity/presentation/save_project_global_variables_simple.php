<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$file_type = "save_project_global_variables_simple";

include $EVC->getEntityPath("presentation/save");
?>
