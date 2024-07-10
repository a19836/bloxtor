<?php
include_once get_lib("org.phpframework.util.web.MyCurl");
include_once get_lib("org.phpframework.compression.ZipHandler");
include_once get_lib("org.phpframework.encryption.CryptoKeyHandler");
include_once get_lib("org.phpframework.cms.wordpress.WordPressInstallationHandler");
include_once get_lib("org.phpframework.cms.wordpress.WordPressUrlsParser");
include_once $EVC->getUtilPath("WorkFlowDBHandler");
include_once $EVC->getUtilPath("BreadCrumbsUIHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$db_driver = $_GET["db_driver"];

$path = str_replace("../", "", $path);//for security reasons

if ($bean_name && $bean_file_name && $db_driver) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$P = $PEVC->getPresentationLayer();
		$selected_project_id = $P->getSelectedPresentationId();
		$common_project_name = $PEVC->getCommonProjectName();
		
		$wordpress_folder_suffix = WordPressUrlsParser::WORDPRESS_FOLDER_PREFIX . "/$db_driver/";
		$wordpress_folder_path = $PEVC->getWebrootPath($common_project_name) . $wordpress_folder_suffix;
		$is_installed = file_exists($wordpress_folder_path . "index.php");
		
		if ($_POST) {
			$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
			
			$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
			$PHPVariablesFileHandler->startUserGlobalVariables();
			
			//get available db drivers
			$db_drivers = WorkFlowBeansFileHandler::getLayerDBDrivers($user_global_variables_file_path, $user_beans_folder_path, $P, true);
			$db_driver_props = $db_drivers[$db_driver];
			$db_driver_bean_file_name = $db_driver_props[1];
			$db_driver_bean_name = $db_driver_props[2];
			
			$hack_wordpress_installation = true;
			
			if ($_POST["install"]) {
				$zipped_file_path = $EVC->getWebrootPath() . "vendor/wordpress.zip";
				$download_zip_file = !file_exists($zipped_file_path) && $dependency_wordpress_zip_file_url;
				
				//download wordpress.zip from store dependencies
				if ($download_zip_file) {
					$downloaded_file = MyCurl::downloadFile($dependency_wordpress_zip_file_url, $fp);
					
					if (!$downloaded_file) {
						launch_exception(new Exception("Error: Could not download file: $zip_url. Please try again..."));
						die();
					}
					else if (stripos($downloaded_file["type"], "zip") === false) {
						launch_exception(new Exception("Error: Downloaded file from $zip_url, is not a zip file. Please try again..."));
						die();
					}
					else if (!rename($downloaded_file["tmp_name"], $zipped_file_path)) {
						launch_exception(new Exception("Error: Could not move downloaded file to vendor/wordpress.zip. Please try again..."));
						die();
					}
				}
				
				if (file_exists($zipped_file_path)) {
					$DBDriver = null;
					
					if ($db_driver && $db_driver_bean_name && $db_driver_bean_file_name) {
						$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
						$DBDriver = $WorkFlowDBHandler->getBeanObject($db_driver_bean_file_name, $db_driver_bean_name);
					}
					
					if ($DBDriver) {
						$layer_path = $P->getLayerPathSetting();
						$wordpress_relative_folder_path = substr($wordpress_folder_path, strlen($layer_path));
						
						if ($is_installed) {
							//delete wordpress folder
							CacheHandlerUtil::deleteFolder($wordpress_folder_path, false);
							
							//remove file just in case someone messed up the system...
							if (file_exists($wordpress_folder_path) && !is_dir($wordpress_folder_path)) 
								unlink($wordpress_folder_path);
							
							//delete all wordpress tables from the $db_driver
							$tables = $DBDriver->listTables();
							
							if ($tables)
								foreach ($tables as $table)
									if (substr($table["table_name"], 0, 3) == "wp_") { //use table_name instead of name bc the "name" contains the schema and table_name doesn't!
										$sql = $DBDriver->getDropTableStatement($table["name"], $DBDriver->getOptions());
										
										if (!$DBDriver->setData($sql))
											$error_message = "Could not delete all the existent WordPress tables from the '$db_driver' DB Driver. Please try again...";
									}
						}
						
						if (!$error_message) {
							//create folder $wordpress_folder_path
							if (!file_exists($wordpress_folder_path) && !mkdir($wordpress_folder_path, 0755, true)) 
								$error_message = "Could not create folder: '$wordpress_relative_folder_path'. Please try again...";
							
							if (!$error_message) {
								//unzip zip to $wordpress_folder_path
								if (ZipHandler::unzip($zipped_file_path, $wordpress_folder_path)) {
									//move unzipped file to right place
									$wordpress_sub_folder_path = $wordpress_folder_path . "wordpress/";
									$files = array_diff(scandir($wordpress_sub_folder_path), array('.', '..'));
									$moved = true;
									
									foreach ($files as $file)
										if (!rename($wordpress_sub_folder_path . $file, $wordpress_folder_path . $file))
											$moved = false;
									
									if ($moved)
										rmdir($wordpress_sub_folder_path);
									else
										$error_message = "There was a problem trying to move the wordpress sub-folder into  '$wordpress_relative_folder_path' folder. Please try again...";
								}
								else
									$error_message = "Wordpress zipped file could not be unzipped to '$wordpress_relative_folder_path'. Please try again...";
							}
						}
					}
					else
						$error_message = "DBDriver object could not be created for db driver '$db_driver'. Please contact the sysadmin...";
				}
				else {
					launch_exception(new Exception("wordpress.zip file not found. Please talk with sysadmin!"));
					die();
				}
				
				if ($error_message)
					$hack_wordpress_installation = false;
				
				if ($download_zip_file && $fp)
					fclose($fp);
			}
			
			if ($hack_wordpress_installation) {
				//get db driver credentials
				$DBDriverWorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $db_driver_bean_file_name, $user_global_variables_file_path);
				$DBDriverWorkFlowBeansFileHandler->init();
				$db_settings = $DBDriverWorkFlowBeansFileHandler->getDBSettings($db_driver_bean_name);
				
				//prepare wordpress base url
				$wordpress_url = getProjectCommonUrlPrefix($PEVC, $selected_project_id ? $selected_project_id : $common_project_name) . $wordpress_folder_suffix;
				
				//get current user credentials
				$user_id = $UserAuthenticationHandler->auth["user_data"]["user_id"];
				$user_data = $UserAuthenticationHandler->getUser($user_id);
				
				//make changes in the wordpress files - hack wordpress
				WordPressInstallationHandler::hackWordPress($EVC, $db_driver, $db_settings, $wordpress_folder_path, $wordpress_url, $user_data, $error_message);
				
				if (!$error_message) {
					$url = $project_url_prefix . "phpframework/cms/wordpress/admin_login?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path&db_driver=$db_driver";
					
					echo '<script>
					alert("Instalation successfully.\nYou will be now redirected to the WordPress page...");
					document.location = "' . $url . '";
					</script>';
				}
			}
				
			$PHPVariablesFileHandler->endUserGlobalVariables();
		}
	}
	else {
		launch_exception(new Exception("PEVC doesn't exists!"));
		die();
	}
}
else {
	launch_exception(new Exception("Undefined bean or db_driver!"));
	die();
}

function getProjectCommonUrlPrefix($EVC, $selected_project_id) {
	include $EVC->getConfigPath("config", $selected_project_id);
	return $project_common_url_prefix;
}
?>
