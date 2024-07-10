<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$file_type = "save_view";

include $EVC->getEntityPath("presentation/save");
?>
