<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$popup = $_GET["popup"];
$status = $_GET["status"];
$msg = $_GET["msg"];

if (isset($status)) {
	if ($status)
		$status_message = $msg ? $msg : "Feedback sent successfully!";
	else
		$error_message = $msg ? $msg : "Feedback not sent! Please try again...";
}
?>
