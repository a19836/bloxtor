<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;
$status = isset($_GET["status"]) ? $_GET["status"] : null;
$msg = isset($_GET["msg"]) ? $_GET["msg"] : null;

if ($status)
	$status_message = $msg ? $msg : "Feedback sent successfully!";
else
	$error_message = $msg ? $msg : "Feedback not sent! Please try again...";
?>
