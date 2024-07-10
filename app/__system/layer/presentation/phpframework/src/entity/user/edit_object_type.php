<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$object_type_id = $_GET["object_type_id"];

if ($object_type_id) {
	$object_type_data = $UserAuthenticationHandler->getObjectType($object_type_id);
}

if ($_POST["object_type_data"]) {
	$new_object_type_data = $_POST["object_type_data"];
	
	if ($_POST["delete"]) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");

		if ($object_type_id && in_array($object_type_id, $UserAuthenticationHandler->getReservedObjectTypes())) {
			$object_type_data = $new_object_type_data;
			$error_message = "This is a reserved object type and is not editable!";
		}
		else if ($object_type_id && $UserAuthenticationHandler->deleteObjectType($object_type_id)) {
			die("<script>alert('Object Type deleted successfully'); document.location = '$project_url_prefix/user/manage_object_types';</script>");
		}
		else {
			$object_type_data = $new_object_type_data;
			$error_message = "There was an error trying to delete this object type. Please try again...";
		}
	}
	else if (empty($new_object_type_data["name"])) {
		$object_type_data = $new_object_type_data;
		$error_message = "Error: Name cannot be undefined";
	}
	else {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		
		$new_object_type_data["name"] = strtolower($new_object_type_data["name"]);
		
		if ($object_type_data["name"] != $new_object_type_data["name"]) {
			$results = $UserAuthenticationHandler->searchObjectTypes(array("name" => $new_object_type_data["name"]));
			if ($results[0]) {
				$object_type_data = $new_object_type_data;
				$error_message = "Error: Repeated Name";
			}
		}
		
		if (!$error_message) {
			if ($object_type_data) {
				$object_type_data = array_merge($object_type_data, $new_object_type_data);
				
				if (in_array($object_type_id, $UserAuthenticationHandler->getReservedObjectTypes())) {
					$error_message = "This is a reserved object type and is not editable!";
				}
				else if ($UserAuthenticationHandler->updateObjectType($object_type_data)) {
					$status_message = "Object Type updated successfully...";
				}
				else {
					$error_message = "There was an error trying to update this object type. Please try again...";
				}
			}
			else {
				$object_type_data = $new_object_type_data;
				
				$status = $UserAuthenticationHandler->insertObjectType($object_type_data);
				
				if ($status) {
					die("<script>alert('Object Type inserted successfully'); document.location = '?object_type_id=" . $status . "';</script>");
				}
				else {
					$error_message = "There was an error trying to insert this object type. Please try again...";
				}
			}
		}
	}
}

if (empty($object_type_data)) {
	$object_type_data = array(
		"object_type_id" => $object_type_id,
		"name" => "",
	);
}
?>
