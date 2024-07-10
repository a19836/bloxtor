<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$file_type = "save_util";

include $EVC->getEntityPath("presentation/save");
?>
