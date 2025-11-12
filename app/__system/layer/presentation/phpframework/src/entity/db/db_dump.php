<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.db.DBDumperHandler");
include_once get_lib("org.phpframework.util.MimeTypeHandler");
include_once $EVC->getUtilPath("WorkFlowDBHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = isset($_GET["layer_bean_folder_name"]) ? $_GET["layer_bean_folder_name"] : null;
$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$selected_table = isset($_GET["table"]) ? $_GET["table"] : null;

if ($bean_name) {
	$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	
	$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
	$DBDriver = $WorkFlowDBHandler->getBeanObject($bean_file_name, $bean_name);
	$tables = $DBDriver->listTables();
	//echo "<pre>";print_r($tables);die();
	
	if (!empty($_POST)) {
		//print_r($_POST);
		$selected_tables = isset($_POST["tables"]) ? $_POST["tables"] : null;
		
		if (!$selected_tables || !is_array($selected_tables))
			$error_messsage = "Error: You must select at least 1 table!";
		else {
			if (!DBDumper::isValid($DBDriver))
				$error_messsage = "Error: The '" . $DBDriver->getLabel() . "' driver doesn't allow dumps! Please contact the sysadmin to add this feature...";
			else {
				$settings = isset($_POST["settings"]) ? $_POST["settings"] : null;
				$db_options = $DBDriver->getOptions();
				$dump_file_name = "dbsqldump_data." . (isset($db_options["host"]) ? $db_options["host"] : "") . (!empty($db_options["port"]) ? "-" . $db_options["port"] : "") . "-" . (isset($db_options["db_name"]) ? $db_options["db_name"] : "") . ".sql";
				$compression = DBDumperHandler::NONE;
				
				if (isset($settings["compress"]))
					switch ($settings["compress"]) {
						case "bzip2": 
							$compression = DBDumperHandler::BZIP2; 
							$dump_file_name .= ".bz2";
							break;
						case "gzip": $compression = DBDumperHandler::GZIP; 
							$dump_file_name .= ".gz";
							break;
						case "gzipstream": $compression = DBDumperHandler::GZIPSTREAM; 
							$dump_file_name .= ".gz";
							break;
						case "zip": $compression = DBDumperHandler::ZIP; 
							$dump_file_name .= ".zip";
							break;
					}
				
				$tmp_file = tmpfile();
				$tmp_file_path = stream_get_meta_data($tmp_file);
				$tmp_file_path = isset($tmp_file_path['uri']) ? $tmp_file_path['uri'] : null; // eg: /tmp/phpFx0513a
				
				$dump_settings = array(
					'include-tables' => count($selected_tables) == count($tables) ? array() : $selected_tables,
					'exclude-tables' => array(),
					'include-views' => array(),
					'compress' => $compression,
					'no-data' => !empty($settings["no-data"]) ? true : false,
					'reset-auto-increment' => isset($settings["reset-auto-increment"]) ? $settings["reset-auto-increment"] : null,
					'add-drop-database' => isset($settings["add-drop-database"]) ? $settings["add-drop-database"] : null,
					'add-drop-table' => isset($settings["add-drop-table"]) ? $settings["add-drop-table"] : null,
					'add-drop-trigger' => isset($settings["add-drop-trigger"]) ? $settings["add-drop-trigger"] : null,
					'add-drop-routine' => isset($settings["add-drop-routine"]) ? $settings["add-drop-routine"] : null,
					'add-drop-event' => isset($settings["add-drop-event"]) ? $settings["add-drop-event"] : null,
					'add-locks' => isset($settings["add-locks"]) ? $settings["add-locks"] : null,
					'complete-insert' => isset($settings["complete-insert"]) ? $settings["complete-insert"] : null,
					'databases' => false,
					'default-character-set' => !empty($db_options["encoding"]) ? $db_options["encoding"] : null, //DBDumperHandler::UTF8,
					'disable-keys' => isset($settings["disable-keys"]) ? $settings["disable-keys"] : null,
					'extended-insert' => isset($settings["extended-insert"]) ? $settings["extended-insert"] : null,
					'events' => isset($settings["events"]) ? $settings["events"] : null,
					'hex-blob' => isset($settings["hex-blob"]) ? $settings["hex-blob"] : null,
					'insert-ignore' => isset($settings["insert-ignore"]) ? $settings["insert-ignore"] : null,
					'net_buffer_length' => DBDumperHandler::MAX_LINE_SIZE,
					'no-autocommit' => isset($settings["no-autocommit"]) ? $settings["no-autocommit"] : null,
					'no-create-info' => isset($settings["no-create-info"]) ? $settings["no-create-info"] : null,
					'lock-tables' => isset($settings["lock-tables"]) ? $settings["lock-tables"] : null,
					'routines' => isset($settings["routines"]) ? $settings["routines"] : null,
					'single-transaction' => isset($settings["single-transaction"]) ? $settings["single-transaction"] : null,
					'skip-triggers' => isset($settings["skip-triggers"]) ? $settings["skip-triggers"] : null,
					'skip-tz-utc' => isset($settings["skip-tz-utc"]) ? $settings["skip-tz-utc"] : null,
					'skip-comments' => isset($settings["skip-comments"]) ? $settings["skip-comments"] : null,
					'skip-dump-date' => isset($settings["skip-dump-date"]) ? $settings["skip-dump-date"] : null,
					'skip-definer' => isset($settings["skip-definer"]) ? $settings["skip-definer"] : null,
					'where' => isset($settings["where"]) ? $settings["where"] : null,
				);
				$pdo_settings = !empty($db_options["persistent"]) && empty($db_options["new_link"]) ? array(PDO::ATTR_PERSISTENT => true) : array();
				
				$DBDumperHandler = new DBDumperHandler($DBDriver, $dump_settings, $pdo_settings);
				$DBDumperHandler->connect();
				$DBDumperHandler->run($tmp_file_path);
				$DBDumperHandler->disconnect();
				
				if (!file_exists($tmp_file_path))
					$error_messsage = "Error: Dumper did not created correctly the dumped file! Please try again...";
				else {
					$status = true;
					$old_compressed_internal_file = pathinfo($tmp_file_path, PATHINFO_FILENAME);
					$new_compressed_internal_file = pathinfo($dump_file_name, PATHINFO_FILENAME);
					
					if (isset($settings["compress"]) && $settings["compress"] == "zip" && !ZipHandler::renameFileInZip($tmp_file_path, $old_compressed_internal_file, $new_compressed_internal_file)) {
						$status = false;
						$error_messsage = "Error: Could NOT rename internal file inside of zip file! Please try again...";
					}
					
					if ($status) {
						$mime_type = MimeTypeHandler::getFileMimeType($tmp_file_path);
						$mime_type = $mime_type ? $mime_type : "application/octet-stream";
						
						header('Content-Type: ' . $mime_type);
						header('Content-Length: ' . filesize($tmp_file_path));
						header('Content-Disposition: attachment; filename="' . $dump_file_name . '"');
						
						readfile($tmp_file_path);
						
						unlink($tmp_file_path);
						die();
					}
				}
			}
		}
	}
}
?>
