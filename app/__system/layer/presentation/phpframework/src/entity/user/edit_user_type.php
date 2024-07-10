<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$user_type_id = $_GET["user_type_id"];

if ($user_type_id) {
	$user_type_data = $UserAuthenticationHandler->getUserType($user_type_id);
}

if ($_POST["user_type_data"]) {
	$new_user_type_data = $_POST["user_type_data"];
	
	if ($_POST["delete"]) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");

		if ($user_type_id && $UserAuthenticationHandler->deleteUserType($user_type_id)) {
			die("<script>alert('User Type deleted successfully'); document.location = '$project_url_prefix/user/manage_user_types';</script>");
		}
		else {
			$user_type_data = $new_user_type_data;
			$error_message = "There was an error trying to delete this user type. Please try again...";
		}
	}
	else if (empty($new_user_type_data["name"])) {
		$user_type_data = $new_user_type_data;
		$error_message = "Error: Name cannot be undefined";
	}
	else {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

		$new_user_type_data["name"] = strtolower($new_user_type_data["name"]);
		
		if ($user_type_data["name"] != $new_user_type_data["name"]) {
			$results = $UserAuthenticationHandler->searchUserTypes(array("name" => $new_user_type_data["name"]));
			if ($results[0]) {
				$user_type_data = $new_user_type_data;
				$error_message = "Error: Repeated Name";
			}
		}
		
		if (!$error_message) {
			if ($user_type_data) {
				$user_type_data = array_merge($user_type_data, $new_user_type_data);
				
				if ($UserAuthenticationHandler->updateUserType($user_type_data)) {
					$status_message = "User Type updated successfully...";
				}
				else {
					$error_message = "There was an error trying to update this user type. Please try again...";
				}
			}
			else {
				$user_type_data = $new_user_type_data;
				
				$status = $UserAuthenticationHandler->insertUserType($user_type_data);
				
				if ($status) {
					die("<script>alert('User Type inserted successfully'); document.location = '?user_type_id=" . $status . "';</script>");
				}
				else {
					$error_message = "There was an error trying to insert this user type. Please try again...";
				}
			}
		}
	}
}

if (empty($user_type_data)) {
	$user_type_data = array(
		"user_type_id" => $user_type_id,
		"name" => "",
	);
}
?>
