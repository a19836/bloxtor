<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$file_type = "save_includes";

include $EVC->getEntityPath("dataaccess/save");
?>
