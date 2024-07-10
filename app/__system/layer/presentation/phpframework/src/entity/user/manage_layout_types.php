<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layout_types = $UserAuthenticationHandler->getAllLayoutTypes();
$available_types = UserAuthenticationHandler::$AVAILABLE_LAYOUTS_TYPES;
?>
