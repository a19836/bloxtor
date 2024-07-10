<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$file_type = "edit_template";

include $EVC->getEntityPath("presentation/edit");
?>
