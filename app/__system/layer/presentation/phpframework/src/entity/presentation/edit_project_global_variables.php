<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$file_type = "edit_project_global_variables";

include $EVC->getEntityPath("presentation/edit");
?>
