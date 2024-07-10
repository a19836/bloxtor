<?php
include_once get_lib("org.phpframework.db.DBDumperHandler");
include_once get_lib("org.phpframework.util.MimeTypeHandler");
include_once $EVC->getUtilPath("WorkFlowDBHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = $_GET["layer_bean_folder_name"];
$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$selected_table = $_GET["table"];

if ($bean_name) {
	$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	
	$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
	$DBDriver = $WorkFlowDBHandler->getBeanObject($bean_file_name, $bean_name);
	$tables = $DBDriver->listTables();
	//echo "<pre>";print_r($tables);die();
	
	if ($_POST) {
		//print_r($_POST);
		$selected_tables = $_POST["tables"];
		
		if (!$selected_tables || !is_array($selected_tables))
			$error_messsage = "Error: You must select at least 1 table!";
		else {
			if (!DBDumper::isValid($DBDriver))
				$error_messsage = "Error: The '" . $DBDriver->getLabel() . "' driver doesn't allow dumps! Please contact the sysadmin to add this feature...";
			else {
				$settings = $_POST["settings"];
				$db_options = $DBDriver->getOptions();
				$dump_file_name = "dbsqldump_data." . $db_options["host"] . ($db_options["port"] ? "-" . $db_options["port"] : "") . "-" . $db_options["db_name"] . ".sql";
				$compression = DBDumperHandler::NONE;
				
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
				$tmp_file_path = stream_get_meta_data($tmp_file)['uri']; // eg: /tmp/phpFx0513a
				
				$dump_settings = array(
					'include-tables' => count($selected_tables) == count($tables) ? array() : $selected_tables,
					'exclude-tables' => array(),
					'include-views' => array(),
					'compress' => $compression,
					'no-data' => $settings["no-data"] ? true : false,
					'reset-auto-increment' => $settings["reset-auto-increment"],
					'add-drop-database' => $settings["add-drop-database"],
					'add-drop-table' => $settings["add-drop-table"],
					'add-drop-trigger' => $settings["add-drop-trigger"],
					'add-drop-routine' => $settings["add-drop-routine"],
					'add-drop-event' => $settings["add-drop-event"],
					'add-locks' => $settings["add-locks"],
					'complete-insert' => $settings["complete-insert"],
					'databases' => false,
					'default-character-set' => $db_options["encoding"] ? $db_options["encoding"] : null, //DBDumperHandler::UTF8,
					'disable-keys' => $settings["disable-keys"],
					'extended-insert' => $settings["extended-insert"],
					'events' => $settings["events"],
					'hex-blob' => $settings["hex-blob"],
					'insert-ignore' => $settings["insert-ignore"],
					'net_buffer_length' => DBDumperHandler::MAX_LINE_SIZE,
					'no-autocommit' => $settings["no-autocommit"],
					'no-create-info' => $settings["no-create-info"],
					'lock-tables' => $settings["lock-tables"],
					'routines' => $settings["routines"],
					'single-transaction' => $settings["single-transaction"],
					'skip-triggers' => $settings["skip-triggers"],
					'skip-tz-utc' => $settings["skip-tz-utc"],
					'skip-comments' => $settings["skip-comments"],
					'skip-dump-date' => $settings["skip-dump-date"],
					'skip-definer' => $settings["skip-definer"],
					'where' => $settings["where"],
				);
				$pdo_settings = $db_options["persistent"] && !$db_options["new_link"] ? array(PDO::ATTR_PERSISTENT => true) : array();
				
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
					
					if ($settings["compress"] == "zip" && !ZipHandler::renameFileInZip($tmp_file_path, $old_compressed_internal_file, $new_compressed_internal_file)) {
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
