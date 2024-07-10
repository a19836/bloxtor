<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$file_type = "edit_util";

include $EVC->getEntityPath("presentation/edit");
?>
