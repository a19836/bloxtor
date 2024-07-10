<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$file_type = "edit_obj";

include $EVC->getEntityPath("dataaccess/edit");
?>
