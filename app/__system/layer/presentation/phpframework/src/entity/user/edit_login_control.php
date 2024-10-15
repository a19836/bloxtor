<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$username = isset($_GET["username"]) ? $_GET["username"] : null;

if ($username) {
	$login_control_data = $UserAuthenticationHandler->getLoginControl($username);
}

if (!empty($_POST)) {
	if ($username && !empty($login_control_data)) {
		if (!empty($_POST["delete"])) {
			$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");

			if ($username && $UserAuthenticationHandler->deleteLoginControl($username)) {
				echo "<script>alert('Username data deleted successfully'); document.location = '$project_url_prefix/user/manage_login_controls';</script>";
				die();
			}
			else {
				$error_message = "There was an error trying to delete this Username data. Please try again...";
			}
		}
		else if (!empty($_POST["reset_failed_login_attempts"])) {
			$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		
			if ($UserAuthenticationHandler->resetFailedLoginAttempts($username)) {
				echo "<script>alert('Username reset successfully'); document.location = '$project_url_prefix/user/manage_login_controls';</script>";
				die();
			}
			else {
				$error_message = "There was an error trying to reset this username. Please try again...";
			}
		}
	}
	else {
		$error_message = "No username data to reset.";
	}
}
?>
