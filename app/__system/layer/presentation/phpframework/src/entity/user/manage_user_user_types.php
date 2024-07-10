<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$user_user_types = $UserAuthenticationHandler->getAllUserUserTypes();

$user_types = $UserAuthenticationHandler->getAvailableUserTypes();
$user_types = is_array($user_types) ? array_flip($user_types) : array();

$users = $UserAuthenticationHandler->getAvailableUsers();
$users = is_array($users) ? array_flip($users) : array();
?>
