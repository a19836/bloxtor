<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$file_type = "create_page_module_block";

include $EVC->getEntityPath("presentation/edit");
?>
