<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$file_type = "save_page_module_block";

include $EVC->getEntityPath("presentation/save");
?>
