<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$user_id = $UserAuthenticationHandler->auth && !empty($UserAuthenticationHandler->auth["user_data"]) ? $UserAuthenticationHandler->auth["user_data"]["user_id"] : null;
?>
