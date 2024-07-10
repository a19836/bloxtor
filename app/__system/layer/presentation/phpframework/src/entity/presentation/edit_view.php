<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$file_type = "edit_view";

include $EVC->getEntityPath("presentation/edit");
?>
