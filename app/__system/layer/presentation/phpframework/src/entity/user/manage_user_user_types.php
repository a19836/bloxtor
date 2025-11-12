<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$user_user_types = $UserAuthenticationHandler->getAllUserUserTypes();

$user_types = $UserAuthenticationHandler->getAvailableUserTypes();
$user_types = is_array($user_types) ? array_flip($user_types) : array();

$users = $UserAuthenticationHandler->getAvailableUsers();
$users = is_array($users) ? array_flip($users) : array();
?>
