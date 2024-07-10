<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$file_type = "edit_entity";

include $EVC->getEntityPath("presentation/edit");
?>
