<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$permission_id = isset($_GET["permission_id"]) ? $_GET["permission_id"] : null;

if ($permission_id) {
	$permission_data = $UserAuthenticationHandler->getPermission($permission_id);
}

if (!empty($_POST["permission_data"])) {
	$new_permission_data = isset($_POST["permission_data"]) ? $_POST["permission_data"] : null;
	
	if (!empty($_POST["delete"])) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");

		if ($permission_id && !in_array($permission_id, $UserAuthenticationHandler->getReservedPermissions()) && $UserAuthenticationHandler->deletePermission($permission_id)) {
			echo "<script>alert('Permission deleted successfully'); document.location = '$project_url_prefix/user/manage_permissions';</script>";
			die();
		}
		else {
			$permission_data = $new_permission_data;
			$error_message = "There was an error trying to delete this permission. Please try again...";
		}
	}
	else if (empty($new_permission_data["name"])) {
		$permission_data = $new_permission_data;
		$error_message = "Error: Name cannot be undefined";
	}
	else {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		
		$permission_name = isset($permission_data["name"]) ? $permission_data["name"] : null;
		$new_permission_data["name"] = strtolower($new_permission_data["name"]);
		
		if ($permission_name != $new_permission_data["name"]) {
			$results = $UserAuthenticationHandler->searchPermissions(array("name" => $new_permission_data["name"]));
			
			if (!empty($results[0])) {
				$permission_data = $new_permission_data;
				$error_message = "Error: Repeated Name";
			}
		}
		
		if (empty($error_message)) {
			if (!empty($permission_data)) {
				$permission_data = array_merge($permission_data, $new_permission_data);
				
				if (in_array($permission_id, $UserAuthenticationHandler->getReservedPermissions())) {
					$error_message = "This is a reserved permission and you cannot edit it.";
				}
				else if ($UserAuthenticationHandler->updatePermission($permission_data)) {
					$status_message = "Permission updated successfully...";
				}
				else {
					$error_message = "There was an error trying to update this permission. Please try again...";
				}
			}
			else {
				$permission_data = $new_permission_data;
				
				$status = $UserAuthenticationHandler->insertPermission($permission_data);
				
				if ($status) {
					echo "<script>alert('Permission inserted successfully'); document.location = '?permission_id=" . $status . "';</script>";
					die();
				}
				else {
					$error_message = "There was an error trying to insert this permission. Please try again...";
				}
			}
		}
	}
}

if (empty($permission_data)) {
	$permission_data = array(
		"permission_id" => $permission_id,
		"name" => "",
	);
}
?>
