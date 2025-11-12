<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;
$status = isset($_GET["status"]) ? $_GET["status"] : null;
$msg = isset($_GET["msg"]) ? $_GET["msg"] : null;

if (isset($status)) {
	if ($status)
		$status_message = $msg ? $msg : "Feedback sent successfully!";
	else
		$error_message = $msg ? $msg : "Feedback not sent! Please try again...";
}
?>
