<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$user_type_id = isset($_GET["user_type_id"]) ? $_GET["user_type_id"] : null;

if ($user_type_id) {
	$user_type_data = $UserAuthenticationHandler->getUserType($user_type_id);
}

if (!empty($_POST["user_type_data"])) {
	$new_user_type_data = isset($_POST["user_type_data"]) ? $_POST["user_type_data"] : null;
	
	if (!empty($_POST["delete"])) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");

		if ($user_type_id && $UserAuthenticationHandler->deleteUserType($user_type_id)) {
			echo "<script>alert('User Type deleted successfully'); document.location = '$project_url_prefix/user/manage_user_types';</script>";
			die();
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
		
		$user_type_name = isset($user_type_data["name"]) ? $user_type_data["name"] : null;
		$new_user_type_data["name"] = isset($new_user_type_data["name"]) ? strtolower($new_user_type_data["name"]) : "";
		
		if ($user_type_name != $new_user_type_data["name"]) {
			$results = $UserAuthenticationHandler->searchUserTypes(array("name" => $new_user_type_data["name"]));
			
			if (!empty($results[0])) {
				$user_type_data = $new_user_type_data;
				$error_message = "Error: Repeated Name";
			}
		}
		
		if (empty($error_message)) {
			if (!empty($user_type_data)) {
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
					echo "<script>alert('User Type inserted successfully'); document.location = '?user_type_id=" . $status . "';</script>";
					die();
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
