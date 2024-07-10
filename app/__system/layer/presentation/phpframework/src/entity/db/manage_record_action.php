<?php
include_once get_lib("org.phpframework.util.text.TextValidator");
include_once get_lib("org.phpframework.util.MimeTypeHandler");
include_once $EVC->getUtilPath("WorkFlowDBHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$status = false;

$layer_bean_folder_name = $_GET["layer_bean_folder_name"];
$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$table = $_GET["table"];

$download = $_GET["download"];

if ($bean_name && $table) {
	$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	
	$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
	$DBDriver = $WorkFlowDBHandler->getBeanObject($bean_file_name, $bean_name);
	
	$existent_tables = $DBDriver->listTables();
	$table_exists = $DBDriver->isTableInNamesList($existent_tables, $table);
	
	if ($table_exists) {
		if ($download) {
			$attr_name = $_GET["attr_name"];
			$pks = $_GET["pks"];
			
			if ($attr_name && $pks) {
				$results = $DBDriver->findObjects($table, $attr_name, $pks);
				$content = $results[0][$attr_name];
				
				$finfo = new finfo(FILEINFO_MIME_TYPE);
				$mime_type = $finfo->buffer($field_value);
				
				$types = MimeTypeHandler::getAvailableTypesByMimeType($mime_type);
				$extension = $types ? $types[0]["extension"] : "";
				$extension = $extension ? explode(" ", str_replace(array(",", ";"), " ", $extension)) : "";
				
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="' . $table . "_" . $attr_name . "_file" . ($extension ? ".$extension" : "") . '"');
				header('Content-Length: ' . mb_strlen($content));
				echo $content;
				exit;
			}
		}
		else if ($_POST) {
			$table_fields = $DBDriver->listTableFields($table);
			
			if ($table_fields) {
				$pks = array();
				$auto_increment_pks = array();
				
				$action = $_POST["action"];
				$attributes = $_POST["attributes"];
				$conditions = $_POST["conditions"];
				
				//prepare pks
				foreach ($table_fields as $field_name => $field) {
					if ($field["primary_key"])
						$pks[] = $field_name;
					
					if ($field["auto_increment"])
						$auto_increment_pks[] = $field_name;
				}
				//echo "<pre>";print_r($pks);die();
				
				//add _FILES to attributes
				//echo "<pre>";print_r($_FILES);die();
				if ($_FILES) {
					$blob_types = $DBDriver->getDBColumnBlobTypes();
					
					foreach ($table_fields as $field_name => $field) {
						$field_type = $field["type"];
						
						if (in_array($field_type, $blob_types) && preg_match("/blob/i", $field_type) && $_FILES[$field_name] && $_FILES[$field_name]["tmp_name"]) {
							$uploaded_file = $_FILES[$field_name]["tmp_name"];
							$attributes[$field_name] = file_exists($uploaded_file) ? file_get_contents($uploaded_file) : "";
						}
					}
				}
				
				//prepare attributes
				if ($attributes) {
					$numeric_types = $DBDriver->getDBColumnNumericTypes();
					
					foreach ($attributes as $k => $v) { //filter attributes by table fields
						if (!array_key_exists($k, $table_fields))
							unset($attributes[$k]);
						else {
							$field_props = $table_fields[$k];
							
							if (in_array($field_props["type"], $numeric_types) && !is_numeric($v) && empty($v)) {
								if (in_array($k, $auto_increment_pks)) {
									unset($attributes[$k]);
									continue 1;
								}
								else
									$attributes[$k] = $field_props["null"] ? null : 0;
							}
							else if (in_array($field_props["type"], $numeric_types) && is_numeric($v) && is_string($v)) //convert string to numeric
								$attributes[$k] += 0; //convert string to real numeric value. This is very important, bc in the insert and update primitive actions of the DBSQLConverter, the sql must be created with numeric values and without quotes, otherwise the DB server gives a sql error.
						}
					}
				}
				//echo "<pre>";print_r($attributes);die();
				
				//prepare conditions
				if ($conditions)
					foreach ($conditions as $k => $v) //filter conditions by pks
						if (!in_array($k, $pks))
							unset($conditions[$k]);
				
				switch($action) {
					case "insert":
						//prepare options
						$options = array();
						foreach ($attributes as $k => $v)
							if (in_array($k, $auto_increment_pks)) {
								$options["hard_coded_ai_pk"] = true;
								break;
							}
						
						//insert record
						if ($attributes && $DBDriver->insertObject($table, $attributes, $options)) {
							$pks_values = array();
							
							foreach ($pks as $k) {
								//return latest inserted pk
								if (in_array($k, $auto_increment_pks))
									$pks_values[$k] = $DBDriver->getInsertedId();
								else
									$pks_values[$k] = $attributes[$k];
							}
							
							$status = json_encode($pks_values);
						}
						break;
					
					case "update":
						//prepare attributes: remove repeated attributes that are the same that the conditions.
						foreach ($attributes as $k => $v)
							foreach ($conditions as $ck => $cv)
								if ($k == $ck) {
									if ($v == $cv)
										unset($attributes[$k]);
									
									break 1;
								}
						
						//prepare options
						$options = array();
						foreach ($attributes as $k => $v)
							if (in_array($k, $auto_increment_pks)) {
								$options["hard_coded_ai_pk"] = true;
								break;
							}
						
						if ($attributes && $conditions && $DBDriver->updateObject($table, $attributes, $conditions, $options))
							$status = true;
						break;
					
					case "delete":
						if ($conditions && $DBDriver->deleteObject($table, $conditions))
							$status = true;
						break;
					
					case "get":
						if ($conditions) {
							$results = $DBDriver->findObjects($table, null, $conditions);
							$t = count($results);
							
							for ($i = 0; $i < $t; $i++) {
								$item = $results[$i];
								
								foreach ($item as $field_name => $field_value) 
									if (TextValidator::isBinary($field_value)) {
										$binary_fields[$i][$field_name] = true;
										
										$finfo = new finfo(FILEINFO_MIME_TYPE);
										$mime_type = $finfo->buffer($field_value);
										
										if (MimeTypeHandler::isImageMimeType($mime_type))
											$new_field_value = "<img src=\"data:$mime_type;base64, " . base64_encode($field_value) . "\" />";
										else if (!MimeTypeHandler::isTextMimeType($mime_type))
											$new_field_value = "<a onClick=\"downloadFile(this, '$field_name')\">Download File</a>";
										
										$results[$i][$field_name] = $new_field_value;
									}
							}
							
							$status = json_encode($results[0] ? $results[0] : array());
						}
						break;
				}
			}
		}
	}
}

echo $status;
die();
?>
