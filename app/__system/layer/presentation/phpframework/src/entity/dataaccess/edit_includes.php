<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$file_type = "edit_includes";

include $EVC->getEntityPath("dataaccess/edit");
?>
