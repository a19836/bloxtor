<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$reserved_db_table_name_id = isset($_GET["reserved_db_table_name_id"]) ? $_GET["reserved_db_table_name_id"] : null;

if ($reserved_db_table_name_id)
	$reserved_db_table_name_data = $UserAuthenticationHandler->getReservedDBTableName($reserved_db_table_name_id);

if (!empty($_POST["reserved_db_table_name_data"])) {
	$new_reserved_db_table_name_data = isset($_POST["reserved_db_table_name_data"]) ? $_POST["reserved_db_table_name_data"] : null;
	
	if (!empty($_POST["delete"])) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");

		if ($reserved_db_table_name_id && $UserAuthenticationHandler->deleteReservedDBTableName($reserved_db_table_name_id)) {
			echo "<script>alert('Reserved db table name deleted successfully'); document.location = '$project_url_prefix/user/manage_reserved_db_table_names';</script>";
			die();
		}
		else {
			$reserved_db_table_name_data = $new_reserved_db_table_name_data;
			$error_message = "There was an error trying to delete this reserved db table name. Please try again...";
		}
	}
	else if (empty($new_reserved_db_table_name_data["name"])) {
		$reserved_db_table_name_data = $new_reserved_db_table_name_data;
		$error_message = "Error: Name cannot be undefined";
	}
	else {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		
		$reserved_db_table_name = isset($reserved_db_table_name_data["name"]) ? $reserved_db_table_name_data["name"] : null;
		$new_reserved_db_table_name_data["name"] = strtolower($new_reserved_db_table_name_data["name"]);
		
		if ($reserved_db_table_name != $new_reserved_db_table_name_data["name"]) {
			$results = $UserAuthenticationHandler->searchReservedDBTableNames(array("name" => $new_reserved_db_table_name_data["name"]));
			
			if (!empty($results[0])) {
				$reserved_db_table_name_data = $new_reserved_db_table_name_data;
				$error_message = "Error: Repeated Name";
			}
		}
		
		if (empty($error_message)) {
			if (!empty($reserved_db_table_name_data)) {
				$reserved_db_table_name_data = array_merge($reserved_db_table_name_data, $new_reserved_db_table_name_data);
				
				if ($UserAuthenticationHandler->updateReservedDBTableName($reserved_db_table_name_data)) {
					$status_message = "Reserved db table name updated successfully...";
				}
				else {
					$error_message = "There was an error trying to update this reserved db table name. Please try again...";
				}
			}
			else {
				$reserved_db_table_name_data = $new_reserved_db_table_name_data;
				
				$status = $UserAuthenticationHandler->insertReservedDBTableName($reserved_db_table_name_data);
				
				if ($status) {
					echo "<script>alert('Reserved db table name inserted successfully'); document.location = '?reserved_db_table_name_id=" . $status . "';</script>";
					die();
				}
				else {
					$error_message = "There was an error trying to insert this reserved db table name. Please try again...";
				}
			}
		}
	}
}

//prepare empty data
if (empty($reserved_db_table_name_data)) {
	$reserved_db_table_name_data = array(
		"reserved_db_table_name_id" => $reserved_db_table_name_id,
		"name" => "",
	);
}
?>
