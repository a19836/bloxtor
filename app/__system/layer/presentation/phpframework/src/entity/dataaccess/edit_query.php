<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$file_type = "edit_query";

include $EVC->getEntityPath("dataaccess/edit");
?>
