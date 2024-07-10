<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$file_type = "save_block_simple";

include $EVC->getEntityPath("presentation/save");
?>
