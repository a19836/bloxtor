<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$user_id = $_GET["user_id"];
$user_type_id = $_GET["user_type_id"];

if ($user_id && $user_type_id) {
	$user_user_type_data = $UserAuthenticationHandler->getUserUserType($user_id, $user_type_id);
}

if ($_POST) {
	if ($_POST["delete"]) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");

		if ($user_id && $user_type_id && $UserAuthenticationHandler->deleteUserUserType($user_id, $user_type_id)) {
			die("<script>alert('User User Type deleted successfully'); document.location = '$project_url_prefix/user/manage_user_user_types';</script>");
		}
		else {
			$error_message = "There was an error trying to delete this user user type. Please try again...";
		}
	}
	else {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		
		if (!$user_user_type_data) {
			$user_user_type_data = $_POST["user_user_type_data"];
			
			if ($user_user_type_data) {
				$status = $UserAuthenticationHandler->insertUserUserType($user_user_type_data);
			
				if ($status) {
					die("<script>alert('User User Type inserted successfully'); document.location = '?user_id=" . $user_user_type_data["user_id"] . "&user_type_id=" . $user_user_type_data["user_type_id"] . "';</script>");
				}
				else {
					$error_message = "There was an error trying to insert this user user type. Please try again...";
				}
			}
			else {
				$error_message = "There was an error trying to insert this user user type. Please try again...";
			}
		}
	}
}

if (empty($user_user_type_data)) {
	$user_user_type_data = array(
		"user_id" => $user_id,
		"user_type_id" => $user_type_id,
	);
}

$users = $UserAuthenticationHandler->getAvailableUsers();
$users_options = array();
$available_users = array();
foreach ($users as $name => $u_id) {
	$users_options[] = array("value" => $u_id, "label" => $name);
	$available_users[$u_id] = $name;
}

$user_types = $UserAuthenticationHandler->getAvailableUserTypes();
$user_types_options = array();
$available_user_types = array();
foreach ($user_types as $name => $u_type_id) {
	$user_types_options[] = array("value" => $u_type_id, "label" => $name);
	$available_user_types[$u_type_id] = $name;
}
?>
