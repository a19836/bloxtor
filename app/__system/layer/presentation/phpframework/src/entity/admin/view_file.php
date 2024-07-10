<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$readonly = true;

include $EVC->getEntityPath("admin/edit_raw_file");
?>
