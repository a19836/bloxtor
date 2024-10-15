<?php
include_once get_lib("org.phpframework.compression.ZipHandler");
include_once get_lib("org.phpframework.util.web.SSHHandler");
include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once get_lib("org.phpframework.cms.wordpress.WordPressUrlsParser");
include_once get_lib("org.phpframework.db.DBDumperHandler");
include_once $EVC->getUtilPath("WorkFlowBeansConverter");
include_once $EVC->getUtilPath("WorkFlowDBHandler");
include_once $EVC->getUtilPath("WorkFlowTestUnitHandler");
include_once $EVC->getUtilPath("CMSObfuscatePHPFilesHandler");
include_once $EVC->getUtilPath("CMSObfuscateJSFilesHandler");
include_once $EVC->getUtilPath("CMSDeploymentSecurityHandler");

class CMSDeploymentHandler {
	
	private $workflow_paths_id;
	private $deployments_temp_folder_path;
	private $user_beans_folder_path;
	private $user_global_variables_file_path;
	private $user_global_settings_file_path;
	private $licence_data;
	
	private $deployment_tasks;
	private $layer_tasks;
	private $layer_tasks_by_label;
	private $deployments_files;
	private $template_tasks_types_by_tag;
	private $this_file_uid;
	
	private $DeploymentWorkFlowTasksFileHandler;
	private $LayerWorkFlowTasksFileHandler;
	
	private static $obfuscate_php_files_options = "strip_comments=1&strip_eol=1";
	private static $obfuscate_js_files_options = "encoding=Normal&fast_decode=1&special_chars=0&remove_semi_colons=1&allowed_domains=#allowed_domains#&check_allowed_domains_port=#check_allowed_domains_port#";
	
	private static $invalid_files = array(".", "..", ".git", ".gitignore", ".htpasswd", ".svn");
	
	public function __construct($workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $deployments_temp_folder_path, $user_beans_folder_path, $user_global_variables_file_path, $user_global_settings_file_path, $licence_data) {
		$this->workflow_paths_id = $workflow_paths_id;
		$this->deployments_temp_folder_path = $deployments_temp_folder_path;
		$this->user_beans_folder_path = $user_beans_folder_path;
		$this->user_global_variables_file_path = $user_global_variables_file_path;
		$this->user_global_settings_file_path = $user_global_settings_file_path;
		$this->licence_data = $licence_data;
		
		$this->deployments_files = array();
		
		$workflow_path = WorkFlowTasksFileHandler::getTaskFilePathByPath($this->workflow_paths_id, "deployment");
		$this->DeploymentWorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($workflow_path);
		$this->DeploymentWorkFlowTasksFileHandler->init();
		$this->deployment_tasks = $this->DeploymentWorkFlowTasksFileHandler->getWorkflowData();
		
		$workflow_path = WorkFlowTasksFileHandler::getTaskFilePathByPath($this->workflow_paths_id, "layer");
		$this->LayerWorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($workflow_path);
		$this->LayerWorkFlowTasksFileHandler->init();
		$this->layer_tasks = $this->LayerWorkFlowTasksFileHandler->getWorkflowData();
		
		$this->layer_tasks_by_label = self::getTasksByLabel($this->layer_tasks);
		
		$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
		$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
		$WorkFlowTaskHandler->setAllowedTaskFolders(array("layer/"));
		$WorkFlowTaskHandler->initWorkFlowTasks();
		
		$dbdriver_task = $WorkFlowTaskHandler->getTasksByTag("dbdriver");
		$db_task = $WorkFlowTaskHandler->getTasksByTag("db");
		$dataaccess_task = $WorkFlowTaskHandler->getTasksByTag("dataaccess");
		$businesslogic_task = $WorkFlowTaskHandler->getTasksByTag("businesslogic");
		$presentation_task = $WorkFlowTaskHandler->getTasksByTag("presentation");
		
		$this->template_tasks_types_by_tag = array(
			"dbdriver" => isset($dbdriver_task[0]["type"]) ? $dbdriver_task[0]["type"] : null,
			"db" => isset($db_task[0]["type"]) ? $db_task[0]["type"] : null,
			"dataaccess" => isset($dataaccess_task[0]["type"]) ? $dataaccess_task[0]["type"] : null,
			"businesslogic" => isset($businesslogic_task[0]["type"]) ? $businesslogic_task[0]["type"] : null,
			"presentation" => isset($presentation_task[0]["type"]) ? $presentation_task[0]["type"] : null,
		);
		
		$this->this_file_uid = fileowner(__FILE__);
	}
	
	public function __destruct() {
		$this->removeDeploymentsFiles();
	}
	
	public function executeServerAction($server_name, $template_id, $deployment_id, $action) {
		$ret = array();
		$error_messages = array();
		
		if ($server_name && is_numeric($template_id) && is_numeric($deployment_id) && $action) {
			//echo "<pre>";
			//print_r($this->layer_tasks);
			//print_r($this->deployment_tasks);
			//print_r($this->layer_tasks_by_label);
			
			$server_template = self::getServerTaskTemplate($this->deployment_tasks, $server_name, $template_id);
			//print_r($server_template);
			
			if ($server_template) {
				$server_installation_folder_path = isset($server_template["properties"]["server_installation_folder_path"]) ? $server_template["properties"]["server_installation_folder_path"] : null;
				
				if (!$server_installation_folder_path)
					$error_messages[] = "Error: server_installation_folder_path cannot be undefined!";
				else if (self::validateServerTemplateLicenceData($server_template, $this->licence_data, $error_messages)) {
					//get server details
					$server = self::getServerTask($this->deployment_tasks, $server_name);
					$server_properties = isset($server["properties"]) ? $server["properties"] : null;
					//print_r($server_properties);
					
					//connect to remote server
					$SSHHandler = new SSHHandler();
					$SSHHandler->setSSHAuthKeyTmpFolderPath($this->deployments_temp_folder_path);
					
					$ssh_settings = array(
						"host" => isset($server_properties["host"]) ? $server_properties["host"] : null,
						"port" => isset($server_properties["port"]) ? $server_properties["port"] : null,
						"username" => isset($server_properties["username"]) ? $server_properties["username"] : null,
						"fingerprint" => isset($server_properties["server_fingerprint"]) ? $server_properties["server_fingerprint"] : null,
					);
					
					$authentication_type = isset($server_properties["authentication_type"]) ? $server_properties["authentication_type"] : null;
					
					switch ($authentication_type) {
						case "key_files":
							$ssh_settings["ssh_auth_pub_file"] = isset($server_properties["ssh_auth_pub_file"]) ? $server_properties["ssh_auth_pub_file"] : null;
							$ssh_settings["ssh_auth_priv_file"] = isset($server_properties["ssh_auth_pri_file"]) ? $server_properties["ssh_auth_pri_file"] : null;
							$ssh_settings["ssh_auth_passphrase"] = isset($server_properties["ssh_auth_passphrase"]) ? $server_properties["ssh_auth_passphrase"] : null;
							break;
						
						case "key_strings":
							$ssh_settings["ssh_auth_pub_string"] = isset($server_properties["ssh_auth_pub"]) ? $server_properties["ssh_auth_pub"] : null;
							$ssh_settings["ssh_auth_priv_string"] = isset($server_properties["ssh_auth_pri"]) ? $server_properties["ssh_auth_pri"] : null;
							$ssh_settings["ssh_auth_passphrase"] = isset($server_properties["ssh_auth_passphrase"]) ? $server_properties["ssh_auth_passphrase"] : null;
							break;
						
						default:
							$ssh_settings["password"] = isset($server_properties["password"]) ? $server_properties["password"] : null;
					}
					
					$connected = $SSHHandler->connect($ssh_settings);
					
					if (!$connected) 
						$error_messages[] = "Error: Server not connected!";
					else {
						switch($action) {
							case "deploy":
								$deployment_created = false;
								
								$this->deploy($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages, $deployment_created);
								
								$ret["deployment_created"] = $deployment_created;
								break;
							
							case "redeploy":
								$redeployed_deployment_id = null;
								$this->redeploy($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages, $deployment_created, $redeployed_deployment_id);
								
								$ret["deployment_created"] = $deployment_created;
								$ret["redeployed_deployment_id"] = $redeployed_deployment_id;
								break;
							
							case "rollback":
								$rollbacked_deployment_id = null;
								$this->rollback($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages, $rollbacked_deployment_id);
								
								$ret["rollbacked_deployment_id"] = $rollbacked_deployment_id;
								break;
							
							case "clean":
							case "cleantemps":
							case "clean_temps":
								$this->cleanTemps($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages);
								break;
						
							case "delete":
								$this->delete($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages);
								break;
							
							default:
								$error_messages[] = "Error: Invalid deployment action: '$action'!";
						}
					}
					
					//close server connection
					$SSHHandler->disconnect(); //call disconnect even if not connected
				}
			}
			else
				$error_messages[] = "Error: Template '$template_id' in '$server_name' server does not exists!";
		}
		else
			$error_messages[] = "Wrong inputs. Please check your request and confirm that server_name, template_id, deployment_id and action are not blank fields.";
		
		$ret["status"] = empty($error_messages);
		$ret["error_message"] = $error_messages ? implode("\n", $error_messages) : "";
		
		return $ret;
	}
	
	/*
	 * Delete all files of a deployment from remote server
	 */
	public function delete($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, &$error_messages = null) {
		//clean temporary files
		$this->cleanTemps($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages);
		
		//prepare properties
		$server_installation_folder_path = isset($server_template["properties"]["server_installation_folder_path"]) ? $server_template["properties"]["server_installation_folder_path"] : null;
		$server_backups_folder_path = "$server_installation_folder_path/.backups/backup_{$template_id}_$deployment_id/";
		
		//remove folder $server_backups_folder_path
		if (!$SSHHandler->removeRemoteFile($server_backups_folder_path))
			$error_messages[] = "Error: '$server_backups_folder_path' could not be removed in the remote server.";
	}
	
	/*
	 * Clean temporary files created by the deployment and rollback actions
	 */
	public function cleanTemps($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, &$error_messages = null) {
		$this->executeCleanTempsLocalAction($server_name, $template_id, $deployment_id, $error_messages);
		$this->executeCleanTempsRemoteAction($template_id, $deployment_id, $server_template, $SSHHandler, $error_messages);
	}
	
	/*
	 * NOTE: THIS LOGIC IS DEPRECATED! CHANGS HAD BEEN APPLYIED!
	 * 
	 * inside the server:
	 * - check if backup zip file exists
	 * - unzip it to a temp folder
	 * - delete all files from main folder (except .backups/ folder)
	 * - copy all files from temp folder to main folder
	 * - delete temp folder
	 */
	public function rollback($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, &$error_messages = null, &$rollbacked_deployment_id = null) {
		//prepare deployment_folder_path
		$deployment_folder_path = $this->getDeploymentFolderPath("rollback", $server_name, $template_id, $deployment_id);
		$this->deployments_files[] = $deployment_folder_path;
		
		$this->executeRollbackLocalAction($deployment_folder_path, $error_messages);
		$this->executeRollbackRemoteAction($deployment_folder_path, $server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages, $rollbacked_deployment_id);
		
		$this->removeFile($deployment_folder_path);
	}
	
	/*
	 * NOTE: THIS LOGIC IS DEPRECATED! CHANGS HAD BEEN APPLYIED!
	 * 
	 * inside the server:
	 * - check if backup zip file exists
	 * - unzip it to a temp folder
	 * - delete all files from main folder (except .backups/ folder)
	 * - copy all files from temp folder to main folder
	 * - delete temp folder
	 */
	public function redeploy($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, &$error_messages = null, &$deployment_created = null, &$redeployed_deployment_id = null) {
		$server_installation_folder_path = isset($server_template["properties"]["server_installation_folder_path"]) ? $server_template["properties"]["server_installation_folder_path"] : null;
		$server_backups_folder_path = "$server_installation_folder_path/.backups/backup_{$template_id}_$deployment_id/";
		$actions = isset($server_template["properties"]["actions"]) ? $server_template["properties"]["actions"] : null;
		
		if ($actions) {
			$this->prepareArray($actions);
		
			foreach ($actions as $item)
				foreach ($item as $action_type => $action)
					if (!empty($action["active"]) && empty($stop))
						switch($action_type) {
							case "run_test_units":
								//Do nothing bc it doesn't apply to redeploy action! The reddeploy action only works with files in the server
								break;
							
							case "migrate_dbs":
								$deployment_created = true;
								
								//execute php migrate_dbs file in server if exists
								$remote_file = "$server_backups_folder_path/migrate_dbs.php";
								
								if (!$SSHHandler->getFileInfo($remote_file))
									$error_messages[] = "Error: Cannot execute '$remote_file' file because does not exists in server!";
								else {
									$response = $SSHHandler->exec("php '$remote_file'");
									
									if (trim($response) !== "1")
										$error_messages[] = "Error: '$remote_file' script not executed in remote server!" . ($response ? "\n" . $response : "");
								}
								break;
							
							case "copy_layers":
								$deployment_created = true;
								
								//execute php copy_layers file in server if exists
								$remote_file = "$server_backups_folder_path/copy_layers.php";
								
								if (!$SSHHandler->getFileInfo($remote_file))
									$error_messages[] = "Error: Cannot execute '$remote_file' file because does not exists in server!";
								else {
									$remote_relative_files_to_remove = array(
										'tmp/cache/',
										'tmp/workflow/',
										'tmp/program/',
										'tmp/deployment/',
										'app/__system/layer/presentation/phpframework/webroot/__system/cache/',
										'app/__system/layer/presentation/test/webroot/__system/cache/',
										'app_old/',
										'other_old/',
										'vendor_old/',
									);
									
									//flush cache first to remove old temp folders
									$this->fushCacheOnRemoteServer($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $remote_relative_files_to_remove, $error_messages);
									
									$response = $SSHHandler->exec("php '$remote_file'");
									
									if (trim($response) !== "1")
										$error_messages[] = "Error: '$remote_file' script not executed in remote server!" . ($response ? "\n" . $response : "");
									
									//flush cache last to remove old temp folders
									$this->fushCacheOnRemoteServer($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $remote_relative_files_to_remove, $error_messages);
								}
								break;
							
							case "copy_files":
								$deployment_created = true;
								$this->executeCopyFilesAction($action, $server_template, $SSHHandler, $error_messages);
								break;
							
							case "execute_shell_cmds":
								$deployment_created = true;
								$this->executeShelCmdsAction($action, $SSHHandler, $error_messages);
								break;
						}
		}
		
		if ($deployment_created) {
			$this->executeSetVersionAction($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages);
			
			$this->executeUpdateWordPressInstallationsSettings($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages);
		}
		
		//getting real server version
		$redeployed_deployment_id = $this->executeGetVersionAction($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages);
	}
	
	/*
	 * NOTE: THIS LOGIC IS DEPRECATED! CHANGS HAD BEEN APPLYIED!
	 * 
	 * in this local computer:
	 * - if there is the action "Copy Layers":
	 * 	- create new CMS folder (don't forget to copy the .htaccess and other hidden files)
	 * 	- create new APP folder inside of the CMS folder (don't forget to copy the .htaccess and other hidden files)
	 * 	- if "copy vendor folder" is active, copy vendor folder to CMS folder
	 * 	- if "copy sysadmin folder" is active, copy sysadmin folder to APP folder and other/authdb and other/workflow to the CMS path
	 * 	- create tmp folder in CMS folder
	 * 	- remove app/setup.php
	 * 	- set all folders permissions
	 * 	- for all active layers copy them to the APP folder (don't forget to copy the .htaccess and other hidden files)
	 * 	- zip CMS folder
	 * 	- get the DBs schema with or without data accordingly with migrate_db_data option is checked
	 * - then ssh to server
	 * 
	 * inside the server:
	 * - backup folder: $server_template["properties"]["server_installation_folder_path"] to $server_template["properties"]["server_installation_folder_path"]/.backups/xxx.version1
	 * 	Basically zip main folder but ignore the .backups folder
	 * - go to all active DBs and mysqldump to .backups folder
	 * - execute actions
	 * 	- when the action is "copy layers":
	 * 		- create temp folder
	 * 		- copy CMS Zip file to server and to temp folder
	 * 		- unzip it
	 * 		- mv old app folder to app_old
	 * 		- mv temp/app to app
	 * 		- copy app_old/.htaccess to app/.htaccess and others hidden files
	 * 		- mv old vendor folder to vendor_old
	 * 		- mv temp/vendor to vendor
	 * 		- copy vendor_old/.htaccess to vendor/.htaccess and others hidden files
	 *	
	 *	- when the action is "migrate DBs", for each active DB
	 * 		- check if option "migrate db schema" is selected and if it is:
	 *			- get the DB schema from the local computer
	 * 			- get the DB schema from server
	 * 			- if DB not exists create it
	 * 			- if DB exists
	 * 				- compare both and get the differences
	 * 				- add or modify new attributes
	 * 				- if not successfully, undo changes by typing the undo sql command
	 * 				- if undo command was not successfully, dump all the correspondent DB dump which is saved in the .backups folder and stop deployment
	 * 				- if the option "remove old attributes" is selected:
	 * 					- check for old attributes and remove them from DB
	 * 		- check if the option "migrate db data" is selected and if it is: copy data to DB
	 *  
	 */
	public function deploy($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, &$error_messages = null, &$deployment_created = null) {
		$server_installation_folder_path = isset($server_template["properties"]["server_installation_folder_path"]) ? $server_template["properties"]["server_installation_folder_path"] : null;
		$actions = isset($server_template["properties"]["actions"]) ? $server_template["properties"]["actions"] : null;
		$stop = false;
		
		if ($actions) {
			$this->prepareArray($actions);
			
			foreach ($actions as $item)
				foreach ($item as $action_type => $action)
					if ($action && !empty($action["active"]) && !$stop)
						switch($action_type) {
							case "run_test_units":
								$this->executeRunTestUnitsAction($action, $error_messages, $stop);
								break;
							
							case "migrate_dbs":
								$deployment_created = true;
								$this->executeBackupAction($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages);
								
								$this->executeMigrateDBsAction($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages);
								break;
							
							case "copy_layers":
								$deployment_created = true;
								$this->executeBackupAction($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages);
								
								$this->executeCopyLayersAction($server_name, $template_id, $deployment_id, $action, $server_template, $SSHHandler, $error_messages);
								break;
							
							case "copy_files":
								$deployment_created = true;
								$this->executeBackupAction($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages);
								
								$this->executeCopyFilesAction($action, $server_template, $SSHHandler, $error_messages);
								break;
							
							case "execute_shell_cmds":
								$deployment_created = true;
								$this->executeBackupAction($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages);
								
								$this->executeShelCmdsAction($action, $SSHHandler, $error_messages);
								break;
						}
		}
		
		if ($deployment_created) {
			$this->executeSetVersionAction($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages);
			
			$this->executeUpdateWordPressInstallationsSettings($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages);
		}
	}
	
	/*
	 * Makes a curl request to the remote server via HTTP to flush the cache meanwhile created from remote apache... This must be done via curl, bc the cached files will be with the www-data user and the SSH connection is done with another user, which means we cannot delete the cached files from ssh. They must be deleted via http.
	 * So we first make a curl request to change all permissions from apache files and then we remove all files via command line with ssh user.
	 */
	private function fushCacheOnRemoteServer($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $remote_relative_files_to_remove, &$error_messages = null) {
		$deployment_folder_path = $this->getDeploymentFolderPath("flush_cache", $server_name, $template_id, $deployment_id);
		$this->deployments_files[] = $deployment_folder_path;
		
		$this->executeServerFlushCacheLocalAction($deployment_folder_path, $error_messages);
		$this->executeServerFlushCacheRemoteAction($deployment_folder_path, $template_id, $deployment_id, $server_template, $SSHHandler, $remote_relative_files_to_remove, $error_messages);
		
		$this->removeFile($deployment_folder_path);
	}
	
	/* FLUSH CACHE ON REMOTE SERVER - ACTION UTILS */
	
	private function executeServerFlushCacheLocalAction($deployment_folder_path, &$error_messages = null) {	
		//create new CMS folder
		if (!$this->createFolder($deployment_folder_path)) {
			$error_messages[] = "Error: Could not create '" . $deployment_folder_path . "' folder!";
			return false;
		}
	}
	
	private function executeServerFlushCacheRemoteAction($deployment_folder_path, $template_id, $deployment_id, $server_template, $SSHHandler, $remote_relative_files_to_remove, &$error_messages = null) {	
		$server_installation_url = isset($server_template["properties"]["server_installation_url"]) ? $server_template["properties"]["server_installation_url"] : null;
		$server_installation_folder_path = isset($server_template["properties"]["server_installation_folder_path"]) ? $server_template["properties"]["server_installation_folder_path"] : null;
		
		if ($server_installation_folder_path && substr($server_installation_folder_path, -1) != "/")
			$server_installation_folder_path .= "/";
		
		$remove_php_file_name = "remove_cache_on_this_server_{$template_id}_{$deployment_id}_" . rand(1000000, 9999999) . ".php";
		$remove_php_file = $deployment_folder_path . $remove_php_file_name;
		$remove_remote_file_path = $server_installation_folder_path . $remove_php_file_name;
		
		$permissions_php_file_name = "set_cache_permissions_on_this_server_{$template_id}_{$deployment_id}_" . rand(1000000, 9999999) . ".php";
		$permissions_php_file = $deployment_folder_path . $permissions_php_file_name;
		$permissions_remote_file_path = $server_installation_folder_path . $permissions_php_file_name;
		
		if (!$this->createRemoteServerRemoveCachePHPFile($server_installation_folder_path, $remove_php_file, $remote_relative_files_to_remove, $error_messages)) //create file
			$error_messages[] = "Error: Could not create flush cache php file!";
		else if (!$this->createRemoteServerSetCachePermissionsPHPFile($server_installation_folder_path, $permissions_php_file, $remote_relative_files_to_remove, $error_messages)) //create file
			$error_messages[] = "Error: Could not create flush cache php file!";
		else if (!$SSHHandler->copyLocalToRemoteFile($remove_php_file, $remove_remote_file_path, true)) //copy server to remote server
			$error_messages[] = "Error: Could not scp '$remove_php_file_name' to remote server file: '$remove_remote_file_path'!";
		else if (!$SSHHandler->copyLocalToRemoteFile($permissions_php_file, $permissions_remote_file_path, true)) //copy server to remote server
			$error_messages[] = "Error: Could not scp '$permissions_php_file_name' to remote server file: '$permissions_remote_file_path'!";
		else { //sets 777 permission to all files that have apache as owner, via curl with the apache user
			$server_installation_url = (strpos($server_installation_url, "://") === false ? "http://" : "") . $server_installation_url;
			$parsed_url = parse_url($server_installation_url);
			$username = isset($parsed_url["user"]) ? $parsed_url["user"] : null;
			$password = isset($parsed_url["pass"]) ? $parsed_url["pass"] : null;
			unset($parsed_url["user"]);
			unset($parsed_url["pass"]);
			
			$parsed_url["path"] = isset($parsed_url["path"]) ? $parsed_url["path"] : null;
			$parsed_url["path"] .= !empty($parsed_url["path"]) && substr($parsed_url["path"], -1) != "/" ? "/" : "";
			$parsed_url["path"] .= $permissions_php_file_name;
			
			$url = $this->unparseUrl($parsed_url);
			
			$settings = array(
				"url" => $url, 
				"settings" => array(
					"follow_location" => 1,
					"connection_timeout" => 60,
				)
			);
			
			if ($username || $password) {
				$settings["settings"]["http_auth"] = !empty($_SERVER["AUTH_TYPE"]) ? $_SERVER["AUTH_TYPE"] : "basic";
				$settings["settings"]["user_pwd"] = $username . ":" . $password;
			}
			
			$MyCurl = new MyCurl();
			$MyCurl->initSingle($settings);
			$MyCurl->get_contents();
			$content = $MyCurl->getData();
			
			//error_log("[util/CMSDeploymentHandler::fushCacheOnRemoteServer] curl: " . print_r($content, 1) . "\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
			//debug_log("[util/CMSDeploymentHandler::fushCacheOnRemoteServer] curl: " . print_r($content, true));
			
			$http_code = isset($content[0]["info"]["http_code"]) ? $content[0]["info"]["http_code"] : null;
			
			if (substr($http_code, 0, 1) != 2) //checks if status code starts with 2, like if it is 200.
				$error_messages[] = "Error: Could not flush remote cache with url: '$url'";
			
			//then removes remote files, executing file via command line with the ssh user
			$response = $SSHHandler->exec("php '$remove_remote_file_path'");
			
			if (trim($response) !== "1") 
				$error_messages[] = "Error: '$remove_php_file_name' script not executed in remote server!" . ($response ? "\n" . $response : "");
		}
		
		//error_log("[util/CMSDeploymentHandler::fushCacheOnRemoteServer] remote_relative_files_to_remove: " . print_r($remote_relative_files_to_remove, 1) . "\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
		
		//remove remote file and similar files that were not removed before
		$dir = dirname($remove_remote_file_path) . "/";
		$sub_files = $SSHHandler->scanRemoteDir($dir);
		$sub_files = $sub_files ? array_diff($sub_files, self::$invalid_files) : null;
		
		$start_remove_file_name = explode("_", pathinfo($remove_php_file_name, PATHINFO_FILENAME));
		array_pop($start_remove_file_name);
		$start_remove_file_name = implode("_", $start_remove_file_name) . "_";
		
		$start_permissions_file_name = explode("_", pathinfo($permissions_php_file_name, PATHINFO_FILENAME));
		array_pop($start_permissions_file_name);
		$start_permissions_file_name = implode("_", $start_permissions_file_name) . "_";
		
		//check if there are sub files that are not hidden, and if so, return false bc is not an empty deployment.
		if ($sub_files)
			foreach ($sub_files as $sub_file)
				if ($sub_file && (substr($sub_file, 0, strlen($start_remove_file_name)) == $start_remove_file_name || substr($sub_file, 0, strlen($start_permissions_file_name)) == $start_permissions_file_name))
					$SSHHandler->removeRemoteFile($dir . $sub_file);
	}
	
	/* RUN TEST-UNITS - ACTION UTILS */
	
	private function executeRunTestUnitsAction($action, &$error_messages = null, &$stop = null) {	
		$responses = array();
		$files = isset($action["files"]) ? $action["files"] : null;
		$files = $files && !is_array($files) ? array($files) : $files;
		
		$WorkFlowTestUnitHandler = new WorkFlowTestUnitHandler($this->user_global_variables_file_path, $this->user_beans_folder_path);
		$WorkFlowTestUnitHandler->initBeanObjects();
		
		if (!$files)
			$WorkFlowTestUnitHandler->executeTest("", $responses);
		else
			foreach ($files as $file)
				$WorkFlowTestUnitHandler->executeTest($file, $responses);
		
		$errors = "";
		
		foreach ($responses as $file => $response)
			if (empty($response["status"]))
				$errors .= "\n- " . ($file ? $file . ": " : "") . (isset($response["error"]) ? $response["error"] : null);
		
		if ($errors) {
			$error_messages[] = "Error executing the following Test-Units: " . $errors;
			$stop = true;
		}
	}
	
	/* SHELL CMDS - ACTION UTILS */
	
	private function executeShelCmdsAction($action, $SSHHandler, &$error_messages = null) {	
		$shell_script = isset($action["cmds"]) ? $action["cmds"] : null;
		$response = trim( $SSHHandler->exec($shell_script) );
		
		if ($response)
			$error_messages[] = "Error: executing shell script: " . $response;
	}
	
	/* COPY FILES - ACTION UTILS */
	
	private function executeCopyFilesAction($action, $server_template, $SSHHandler, &$error_messages = null) {	
		$server_installation_folder_path = isset($server_template["properties"]["server_installation_folder_path"]) ? $server_template["properties"]["server_installation_folder_path"] : null;
		$server_relative_folder_path = isset($action["server_relative_folder_path"]) ? $action["server_relative_folder_path"] : null;
		$remote_folder_path = "$server_installation_folder_path/$server_relative_folder_path/";
		
		$files = isset($action["files"]) ? $action["files"] : null;
		$files = $files && !is_array($files) ? array($files) : $files;
		
		if ($files)
			foreach ($files as $file) 
				if ($file && !$SSHHandler->copyLocalToRemoteFile(CMS_PATH . $file, $remote_folder_path . basename($file), true))
					$error_messages[] = "Error: Could not scp '$file' to remote server folder: '$server_relative_folder_path'!";
	}
	
	/* CLEAN TEMPS - ACTION UTILS */
	
	private function executeCleanTempsLocalAction($server_name, $template_id, $deployment_id, &$error_messages = null) {	
		$rollback_path = $this->getDeploymentFolderPath("rollback", $server_name, $template_id, $deployment_id);
		$backup_path = $this->getDeploymentFolderPath("backup", $server_name, $template_id, $deployment_id);
		$copy_layers_path = $this->getDeploymentFolderPath("copy_layers", $server_name, $template_id, $deployment_id);
		$migrate_dbs_path = $this->getDeploymentFolderPath("migrate_dbs", $server_name, $template_id, $deployment_id);
		$version_path = $this->getDeploymentFolderPath("version", $server_name, $template_id, $deployment_id);
		
		$rollback_zip_path = $this->deployments_temp_folder_path . "/" . basename($rollback_path) . ".zip";
		$backup_zip_path = $this->deployments_temp_folder_path . "/" . basename($backup_path) . ".zip";
		$copy_layers_zip_path = $this->deployments_temp_folder_path . "/" . basename($copy_layers_path) . ".zip";
		$migrate_dbs_zip_path = $this->deployments_temp_folder_path . "/" . basename($migrate_dbs_path) . ".zip";
		
		$paths = array($rollback_path, $backup_path, $copy_layers_path, $migrate_dbs_path, $version_path, $rollback_zip_path, $backup_zip_path, $copy_layers_zip_path, $migrate_dbs_zip_path);
		
		$not_removed = array();
		
		foreach ($paths as $path) 
			if (!$this->removeFile($path))
				$not_removed[] = $path;
		
		if ($not_removed)
			$error_messages[] = "Error: There were some files that could not be removed. Files: \n- " . implode("\n- ", $not_removed);
	}
	
	private function executeCleanTempsRemoteAction($template_id, $deployment_id, $server_template, $SSHHandler, &$error_messages = null) {	
		//prepare properties
		$server_installation_folder_path = isset($server_template["properties"]["server_installation_folder_path"]) ? $server_template["properties"]["server_installation_folder_path"] : null;
		$server_backups_folder_path = "$server_installation_folder_path/.backups/backup_{$template_id}_$deployment_id/";
		
		//Create folder $server_backups_folder_path and all parent's folders
		if ($SSHHandler->isDir($server_backups_folder_path)) {
			$paths = array($server_backups_folder_path . "rollbacking/", $server_backups_folder_path . "deploying/");
			
			$not_removed = array();
			
			foreach ($paths as $path) 
				if (!$SSHHandler->removeRemoteFile($path))
					$not_removed[] = $path;
			
			if ($not_removed)
				$error_messages[] = "Error: There were some files that could not be removed in the remote server. Files: " . implode("\n- ", $not_removed);
		}
	}
	
	/* ROLLBACK - ACTION UTILS */
	
	private function executeRollbackLocalAction($deployment_folder_path, &$error_messages = null) {	
		//prepare deployment_folder_name
		$deployment_folder_name = basename($deployment_folder_path);
		
		//create new CMS folder
		if (!$this->createFolder($deployment_folder_path)) {
			$error_messages[] = "Error: Could not create '$deployment_folder_name' folder!";
			return false;
		}
		
		//FOLDER LIB - copy DB folder
		if (!$this->copyFolder(CMS_PATH . "app/lib/org/phpframework/db", $deployment_folder_path . "lib/org/phpframework/db/")) {
			$error_messages[] = "Error: Could not copy app/lib/org/phpframework/db/ folder!";
			return false;
		}
		
		//FOLDER LIB - copy TextSanitizer file => DB/SQLQueryHandler.php class and others will use this
		if (!$this->copyFile(CMS_PATH . "app/lib/org/phpframework/util/text/TextSanitizer.php", $deployment_folder_path . "lib/org/phpframework/util/text/TextSanitizer.php")) {
			$error_messages[] = "Error: Could not copy app/lib/org/phpframework/util/text/TextSanitizer.php file!";
			return false;
		}
		
		//FOLDER LIB - copy compression folder => DB/DBDump.php class will use this and other will use the ZipHandler class too
		if (!$this->copyFolder(CMS_PATH . "app/lib/org/phpframework/compression/", $deployment_folder_path . "lib/org/phpframework/compression/")) {
			$error_messages[] = "Error: Could not copy app/lib/org/phpframework/compression/ folder!";
			return false;
		}
		
		//FOLDER LIB - copy sqlparser folder => DB/SQLQueryHandler.php class will use this
		if (!$this->copyFolder(CMS_PATH . "app/lib/vendor/sqlparser/", $deployment_folder_path . "lib/vendor/sqlparser/")) {
			$error_messages[] = "Error: Could not copy app/lib/vendor/sqlparser/ folder!";
			return false;
		}
		
		//FILE ZIP - zip CMS folder
		$zip_file_path = $this->deployments_temp_folder_path . "/$deployment_folder_name.zip";
		$this->deployments_files[] = $zip_file_path;
		
		if (!ZipHandler::zip($deployment_folder_path, $zip_file_path)) {
			$error_messages[] = "Error: Could not create zip '$deployment_folder_name.zip' file!";
			return false;
		}
		
		if (!ZipHandler::renameFileInZip($zip_file_path, $deployment_folder_name, "rollback")) {
			$error_messages[] = "Error: Could not rename '$deployment_folder_name' to 'rollback' in zip file: '$deployment_folder_name.zip'!";
			return false;
		}
		
		$aux = $zip_file_path;
		$zip_file_path = $deployment_folder_path . "/rollback.zip";
		
		if (!rename($aux, $zip_file_path)) {
			$error_messages[] = "Error: Could not move '$deployment_folder_name.zip' file to $deployment_folder_name/rollback.zip!";
			return false;
		}
	}
	
	private function executeRollbackRemoteAction($deployment_folder_path, $server_name, $template_id, $deployment_id, $server_template, $SSHHandler, &$error_messages = null, &$rollbacked_deployment_id = null) {	
		if ($error_messages)
			return false;
		
		//prepare properties
		$server_installation_folder_path = isset($server_template["properties"]["server_installation_folder_path"]) ? $server_template["properties"]["server_installation_folder_path"] : null;
		$tasks = isset($server_template["properties"]["task"]) ? $server_template["properties"]["task"] : null;
		$tasks_props = self::getTasksPropsByLabel($tasks);
		$server_backups_folder_path = "$server_installation_folder_path/.backups/backup_{$template_id}_$deployment_id/";
		$zip_file_path = $deployment_folder_path . "/rollback.zip";
		
		//Create folder $server_backups_folder_path and all parent's folders
		if (!$SSHHandler->isDir($server_backups_folder_path))
			$error_messages[] = "Error: '$server_backups_folder_path' folder does NOT exist in remote server!";
		else {
			$php_file = "$deployment_folder_path/rollback.php";
			
			//create php deploy file with logic code
			if (!$this->createRemoteServerRollbackPHPFile($template_id, $deployment_id, $server_template, $server_installation_folder_path, $tasks_props, $php_file, $error_messages))
				$error_messages[] = "Error: Could not create rollback deployment php file!";
			else {
				//scp php file to server
				if (!$SSHHandler->copyLocalToRemoteFile($php_file, $server_backups_folder_path . basename($php_file), true, 0640))
					$error_messages[] = "Error: Could not scp rollback deployment php file to remote server!";
				else {
					//scp phpframework zip file to server
					if (!$SSHHandler->copyLocalToRemoteFile($zip_file_path, $server_backups_folder_path . basename($zip_file_path), true, 0640))
						$error_messages[] = "Error: Could not scp rollback.zip file to remote server!";
					else {
						//flush cache first to remove old temp folders
						$sub_files = $SSHHandler->scanRemoteDir($server_installation_folder_path);
						$sub_files = $sub_files ? array_diff($sub_files, self::$invalid_files) : array();
						$remote_relative_files_to_remove = array();
						
						foreach ($sub_files as $sub_file)
							if (substr(pathinfo($sub_file, PATHINFO_FILENAME), -4) == "_old")
								$remote_relative_files_to_remove[] = $sub_file;
						
						$this->fushCacheOnRemoteServer($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $remote_relative_files_to_remove, $error_messages);
						
						//execute php deploy file in server
						$remote_file = "$server_backups_folder_path/rollback.php";
						$response = $SSHHandler->exec("php '$remote_file'");
						$get_version = true; //always get the version number even if file does not exists. See bellow...
						
						if (trim($response) !== "1") 
							$error_messages[] = "Error: '$remote_file' script not executed in remote server!" . ($response ? "\n" . $response : "");
						else { //if rollback script executed correctly
							$get_version = !$this->isServerEmptyDeployment($SSHHandler, $server_installation_folder_path);
							$rollbacked_deployment_id = 0;
						}
						
						//flush cache last to remove old temp folders. Get new list of remote old files
						$sub_files = $SSHHandler->scanRemoteDir($server_installation_folder_path);
						$sub_files = $sub_files ? array_diff($sub_files, self::$invalid_files) : array();
						$remote_relative_files_to_remove = array("tmp/cache/");
						
						foreach ($sub_files as $sub_file) 
							if (substr(pathinfo($sub_file, PATHINFO_FILENAME), -4) == "_old")
								$remote_relative_files_to_remove[] = $sub_file;
						
						$this->fushCacheOnRemoteServer($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $remote_relative_files_to_remove, $error_messages);
						
						//get current installed version
						if ($get_version)
							$this->executeGetVersionRemoteAction($deployment_folder_path, $template_id, $deployment_id, $server_template, $SSHHandler, $rollbacked_deployment_id, $error_messages);
					}
				}
			}
		}
	}
	
	/* DEPLOY - ACTION UTILS */
	
	private function executeBackupAction($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, &$error_messages = null) {
		//prepare deployment_folder_path
		$deployment_folder_path = $this->getDeploymentFolderPath("backup", $server_name, $template_id, $deployment_id);
		$this->deployments_files[] = $deployment_folder_path;
		
		$this->executeBackupLocalAction($deployment_folder_path, $error_messages);
		$this->executeBackupRemoteAction($deployment_folder_path, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages);
		
		$this->removeFile($deployment_folder_path);
	}
	
	private function executeBackupLocalAction($deployment_folder_path, &$error_messages = null) {
		//prepare deployment_folder_name
		$deployment_folder_name = basename($deployment_folder_path);
		
		//create new CMS folder
		if (!$this->createFolder($deployment_folder_path)) {
			$error_messages[] = "Error: Could not create '$deployment_folder_name' folder!";
			return false;
		}
		
		//FOLDER LIB - copy ZipHandler file
		if (!$this->copyFile(CMS_PATH . "app/lib/org/phpframework/compression/ZipHandler.php", $deployment_folder_path . "lib/org/phpframework/compression/ZipHandler.php")) {
			$error_messages[] = "Error: Could not copy app/lib/org/phpframework/compression/ZipHandler.php file!";
			return false;
		}
		
		//FILE ZIP - zip CMS folder
		$zip_file_path = $this->deployments_temp_folder_path . "/$deployment_folder_name.zip";
		$this->deployments_files[] = $zip_file_path;
		
		if (!ZipHandler::zip($deployment_folder_path, $zip_file_path)) {
			$error_messages[] = "Error: Could not create zip '$deployment_folder_name.zip' file!";
			return false;
		}
		
		if (!ZipHandler::renameFileInZip($zip_file_path, $deployment_folder_name, "backup")) {
			$error_messages[] = "Error: Could not rename '$deployment_folder_name' to 'backup' in zip file: '$deployment_folder_name.zip'!";
			return false;
		}
		
		$aux = $zip_file_path;
		$zip_file_path = $deployment_folder_path . "/backup.zip";
		
		if (!rename($aux, $zip_file_path)) {
			$error_messages[] = "Error: Could not move '$deployment_folder_name.zip' file to $deployment_folder_name/backup.zip!";
			return false;
		}
	}
	
	private function executeBackupRemoteAction($deployment_folder_path, $template_id, $deployment_id, $server_template, $SSHHandler, &$error_messages = null) {
		if ($error_messages)
			return false;
		
		//prepare properties
		$server_installation_folder_path = isset($server_template["properties"]["server_installation_folder_path"]) ? $server_template["properties"]["server_installation_folder_path"] : null;
		$server_backups_folder_path = "$server_installation_folder_path/.backups/backup_{$template_id}_$deployment_id/";
		$zip_file_path = $deployment_folder_path . "/backup.zip";
		
		//Create folder $server_backups_folder_path and all parent's folders
		if (!$SSHHandler->createRemoteFolder($server_backups_folder_path, 0755, true))
			$error_messages[] = "Error: Could not create '$server_backups_folder_path' folder in remote server!";
		else {
			$php_file = "$deployment_folder_path/backup.php";
			
			//create php deploy file with logic code
			if (!$this->createRemoteServerBackupPHPFile($template_id, $deployment_id, $server_installation_folder_path, $php_file, $error_messages))
				$error_messages[] = "Error: Could not create backup deployment php file!";
			else {
				//scp php file to server
				if (!$SSHHandler->copyLocalToRemoteFile($php_file, $server_backups_folder_path . basename($php_file), true, 0640))
					$error_messages[] = "Error: Could not scp backup deployment php file to remote server!";
				else {	
					//scp phpframework zip file to server
					if (!$SSHHandler->copyLocalToRemoteFile($zip_file_path, $server_backups_folder_path . basename($zip_file_path), true, 0640))
						$error_messages[] = "Error: Could not scp backup.zip file to remote server!";
					else {
						//execute php deploy file in server
						$remote_file = "$server_backups_folder_path/backup.php";
						$response = $SSHHandler->exec("php '$remote_file'");
						
						if (trim($response) !== "1")
							$error_messages[] = "Error: '$remote_file' script not executed in remote server!" . ($response ? "\n" . $response : "");
					}
				}
			}
		}
		
		if (file_exists($zip_file_path))
			unlink($zip_file_path);
	}
	
	private function executeCopyLayersAction($server_name, $template_id, $deployment_id, $action, $server_template, $SSHHandler, &$error_messages = null) {
		//prepare deployment_folder_path
		$deployment_folder_path = $this->getDeploymentFolderPath("copy_layers", $server_name, $template_id, $deployment_id);
		$this->deployments_files[] = $deployment_folder_path;
		
		$this->executeCopyLayersLocalAction($deployment_folder_path, $action, $server_template, $error_messages);
		$this->executeCopyLayersRemoteAction($deployment_folder_path, $server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages);
		
		$this->removeFile($deployment_folder_path);
	}
	
	private function executeCopyLayersLocalAction($deployment_folder_path, $action, $server_template, &$error_messages = null) {
		//prepare deployment_folder_name
		$deployment_folder_name = basename($deployment_folder_path);
		
		//prepare properties
		$connections = isset($server_template["properties"]["connection"]) ? $server_template["properties"]["connection"] : null;
		$connections_props = self::getConnectionsPropsByTaskLabels($connections);
		$tasks_props = self::getTasksPropsByLabel($server_template["properties"]["task"]);
		$global_settings = isset($server_template["properties"]["global_settings"]) ? $server_template["properties"]["global_settings"] : null;
		$global_vars = isset($server_template["properties"]["global_vars"]) ? $server_template["properties"]["global_vars"] : null;
		
		$has_sysadmin = isset($action["sysadmin"]) ? $action["sysadmin"] : null;
		$has_vendor = isset($action["vendor"]) ? $action["vendor"] : null;
		$has_dao = isset($action["dao"]) ? $action["dao"] : null;
		$has_modules = isset($action["modules"]) ? $action["modules"] : null;
		$obfuscate_proprietary_php_files = isset($action["obfuscate_proprietary_php_files"]) ? $action["obfuscate_proprietary_php_files"] : null;
		$obfuscate_proprietary_js_files = isset($action["obfuscate_proprietary_js_files"]) ? $action["obfuscate_proprietary_js_files"] : null;
		$allowed_domains = isset($action["allowed_domains"]) ? $action["allowed_domains"] : null;
		$check_allowed_domains_port = isset($action["check_allowed_domains_port"]) ? $action["check_allowed_domains_port"] : null;
		$create_licence = isset($action["create_licence"]) ? $action["create_licence"] : null;
		
		//check if sysadmin migration is allowed
		if (!$this->licence_data["asm"] && $has_sysadmin)
			$has_sysadmin = false;
		
		/*** LOCAL SERVER ***/
		
		//create new CMS folder
		if (!$this->createFolder($deployment_folder_path)) {
			$error_messages[] = "Error: Could not create '$deployment_folder_name' folder!";
			return false;
		}
		
		//copy the .htaccess and other hidden files
		if (!$this->copyFolderHiddenFiles(CMS_PATH, $deployment_folder_path)) {
			$error_messages[] = "Error: Could not copy hidden files for root folder!";
			return false;
		}
		
		//copy the README.md , LICENSE.md and others md files, if exist...
		$files = preg_grep('/\.(md|txt)$/', scandir(CMS_PATH));
		
		if ($files)
			foreach ($files as $file) 
				$this->copyFile(CMS_PATH . $file, $deployment_folder_path . $file);
		
		//FOLDER APP - create new APP folder inside of the CMS folder
		if (!$this->createFolder($deployment_folder_path . "app")) {
			$error_messages[] = "Error: Could not create '{$deployment_folder_name}/app' folder!";
			return false;
		}
		
		//FOLDER APP - set same permissions
		if (!$this->setSameFilePermissions(CMS_PATH . "app", $deployment_folder_path . "app")) {
			$error_messages[] = "Error: Could not set same permissions for app folder!";
			return false;
		}
		
		//FOLDER APP - copy the .htaccess and other hidden files
		if (!$this->copyFolderHiddenFiles(CMS_PATH . "app", $deployment_folder_path . "app")) {
			$error_messages[] = "Error: Could not copy hidden files for root folder!";
			return false;
		}
		
		//FOLDER APP - copy the app/app.php. Note: Do not copy setup.php
		if (!$this->copyFile(CMS_PATH . "app/app.php", $deployment_folder_path . "app/app.php")) {
			$error_messages[] = "Error: Could not copy app/app.php file!";
			return false;
		}
		
		//FOLDER APP/LIB - copy lib folder - Note that even if $has_copy_layers is false, we must copy the LIB folder bc the deploy.php file uses some files inside of the LIB folder.
		if (!$this->copyFolder(CMS_PATH . "app/lib", $deployment_folder_path . "app/lib")) {
			$error_messages[] = "Error: Could not copy app/lib folder!";
			return false;
		}
		
		//create new layers diagram file: layers.xml
		//prepare $diagram_path to be used by the sysadmin and config folders sections bellow...
		$diagram_path = $deployment_folder_path . "layers.xml";
		
		if (!$this->setLayersBeansDiagram($diagram_path, $tasks_props, $connections_props)) {
			$error_messages[] = "Error: Could not create layers diagram file in: other/workflow/layer/layers.xml!";
			return false;
		}
		
		//FOLDER APP/__SYSTEM - if "copy sysadmin folder" is active, copy sysadmin folder to APP folder and other/authdb and other/workflow to the CMS path
		if ($has_sysadmin) {
			//copy app/__system
			if (!$this->copyFolder(CMS_PATH . "app/__system", $deployment_folder_path . "app/__system")) {
				$error_messages[] = "Error: Could not copy app/__system folder!";
				return false;
			}
			
			//create other folder
			if (!$this->createFolder($deployment_folder_path . "other")) {
				$error_messages[] = "Error: Could not create other folder!";
				return false;
			}
			
			//set same permissions
			if (!$this->setSameFilePermissions(CMS_PATH . "other", $deployment_folder_path . "other")) {
				$error_messages[] = "Error: Could not set same permissions for other folder!";
				return false;
			}
				
			//copy the .htaccess and other hidden files
			if (!$this->copyFolderHiddenFiles(CMS_PATH . "other", $deployment_folder_path . "other")) {
				$error_messages[] = "Error: Could not copy hidden files for other folder!";
				return false;
			}
			
			//copy other/authdb
			if (!$this->copyFolder(CMS_PATH . "other/authdb", $deployment_folder_path . "other/authdb")) {
				$error_messages[] = "Error: Could not copy other/authdb folder!";
				return false;
			}
			
			//copy other/workflow
			if (!$this->copyFolder(CMS_PATH . "other/workflow", $deployment_folder_path . "other/workflow")) {
				$error_messages[] = "Error: Could not copy other/workflow folder!";
				return false;
			}
			
			//backup original layers.xml file
			//$this->copyFile(CMS_PATH . "other/workflow/layer/layers.xml", $deployment_folder_path . "other/workflow/layer/layers_orig.xml"); //Do not copy the layers.xml original file, bc of security reasons. If the user only choose a specific DB Drivers, it means he doesn't want the other drivers to be deploy (including it's credentials). SO WE SHOULD NOT COPY THE LAYERS.XML ORIGINAL FILE.
			$this->copyFile($diagram_path, $deployment_folder_path . "other/workflow/layer/layers.xml");
			
			//Delete system cached files if any...
			$this->removeFile($deployment_folder_path . "app/__system/layer/presentation/phpframework/webroot/__system/cache/");
			$this->removeFile($deployment_folder_path . "app/__system/layer/presentation/test/webroot/__system/cache/");
			
			//Remove modules sub-folders. Must remove only the sub-folders, because of the setup.php
			if (!$has_modules) {
				if (!$this->removeFolderFiles($deployment_folder_path . "app/__system/layer/presentation/common/src/module/")) 
					$error_messages[] = "Error: Could not delete 'app/__system/layer/presentation/common/src/module/' sub-folders!";
				
				if (!$this->removeFolderFiles($deployment_folder_path . "app/__system/layer/presentation/common/webroot/module/")) 
					$error_messages[] = "Error: Could not delete 'app/__system/layer/presentation/common/webroot/module/' sub-folders!";
			}
		}
		else { //if no sysadmin panel
			//Creating deployment package without the workflow/task lib files, bc there are no needed in the projects side.
			//Delete lib/org/phpframework/workflow/task folder
			//Cannot delete the lib/org/phpframework/workflow/ folder because the lib/org/phpframework/phpscript/phpparser/PHPParserPrettyPrinter.php and lib/org/phpframework/phpscript/phpparser/PHPParserTraverserNodeVisitor.php are used in other lib files.
			if (!$this->removeFile($deployment_folder_path . "app/lib/org/phpframework/workflow/task/")) 
				$error_messages[] = "Error: Could not delete 'app/lib/org/phpframework/workflow/task/' folder!";
		}
		
		//FOLDER VENDOR - if "copy vendor folder" is active, copy vendor folder to CMS folder
		if ($has_vendor) {
			if (!$this->copyFolder(CMS_PATH . "vendor", $deployment_folder_path . "vendor")) {
				$error_messages[] = "Error: Could not copy vendor folder!";
				return false;
			}
			
			//remove testunit folder if there is no __system panel bc is not needed in the deployed folder
			if (!$has_sysadmin)
				$this->removeFile($deployment_folder_path . "vendor/testunit");
		}
		
		//FOLDER DAO - if "copy dao folder" is active, copy dao folder to vendor folder
		if ($has_dao) {
			if (!$this->copyFolder(CMS_PATH . "vendor/dao", $deployment_folder_path . "vendor/dao")) {
				$error_messages[] = "Error: Could not copy vendor folder!";
				return false;
			}
			
			if (!$has_vendor && !$this->copyParentsFolderHiddenFiles(CMS_PATH . "vendor", $deployment_folder_path . "vendor", CMS_PATH . "vendor"))
				$error_messages[] = "Error: Could not copy hidden files for vendor/dao parent folder, this is, for vendor folder!";
		}
		else if (!$this->removeFile($deployment_folder_path . "vendor/dao"))
			$error_messages[] = "Error: Could not delete vendor/dao folder!";
		
		//FOLDER TMP - create tmp folder in CMS folder
		if (!$this->createFolder($deployment_folder_path . "tmp")) {
			$error_messages[] = "Error: Could not create tmp folder!";
			return false;
		}
		
		//FOLDER TMP - set same permissions
		if (!$this->setSameFilePermissions(CMS_PATH . "tmp", $deployment_folder_path . "tmp")) {
			$error_messages[] = "Error: Could not set same permissions for tmp folder!";
			return false;
		}
		
		//FOLDER TMP - copy the .htaccess and other hidden files
		if (!$this->copyFolderHiddenFiles(CMS_PATH . "tmp", $deployment_folder_path . "tmp")) {
			$error_messages[] = "Error: Could not copy hidden files for tmp folder!";
			return false;
		}
		
		//FOLDER APP/LAYER - create app/layer folder - must do this before the $this->setLayersBeans(...) method, bc this method uses these folders
		if (!$this->createFolder($deployment_folder_path . "app/layer")) {
			$error_messages[] = "Error: Could not create '{$deployment_folder_name}/app/layer' folder!";
			return false;
		}
		
		//FOLDER APP/LAYER - set same permissions
		if (!$this->setSameFilePermissions(CMS_PATH . "app/layer", $deployment_folder_path . "app/layer")) {
			$error_messages[] = "Error: Could not set same permissions for app/layer folder!";
			return false;
		}
		
		//FOLDER APP/LAYER - copy the .htaccess and other hidden files
		if (!$this->copyFolderHiddenFiles(CMS_PATH . "app/layer", $deployment_folder_path . "app/layer")) {
			$error_messages[] = "Error: Could not copy hidden files for app/layer folder!";
			return false;
		}
		
		//FOLDER APP/CONFIG - create config folder
		if (!$this->createFolder($deployment_folder_path . "app/config")) {
			$error_messages[] = "Error: Could not create app/config folder!";
			return false;
		}
		
		//FOLDER APP/CONFIG - set same permissions
		if (!$this->setSameFilePermissions(CMS_PATH . "app/config", $deployment_folder_path . "app/config")) {
			$error_messages[] = "Error: Could not set same permissions for app/config folder!";
			return false;
		}
		
		//FOLDER APP/CONFIG - copy the .htaccess and other hidden files
		if (!$this->copyFolderHiddenFiles(CMS_PATH . "app/config", $deployment_folder_path . "app/config")) {
			$error_messages[] = "Error: Could not copy hidden files for app/config folder!";
			return false;
		}
		
		//FOLDER APP/CONFIG - set global_settings
		$new_user_global_settings_file_path = $deployment_folder_path . "app/config/" . pathinfo($this->user_global_settings_file_path, PATHINFO_BASENAME);
		if (!$this->setGlobalSettings($new_user_global_settings_file_path, $global_settings)) {
			$error_messages[] = "Error: Could not set global settings file!";
			return false;
		}
		
		//FOLDER APP/CONFIG - set global_variables
		$new_user_global_variables_file_path = $deployment_folder_path . "app/config/" . pathinfo($this->user_global_variables_file_path, PATHINFO_BASENAME);
		if (!$this->setGlobalVariables($new_user_global_variables_file_path, $global_vars)) {
			$error_messages[] = "Error: Could not set global variables file!";
			return false;
		}
		
		//FOLDER APP/CONFIG/BEAN - create beans folder
		if (!$this->createFolder($deployment_folder_path . "app/config/bean")) {
			$error_messages[] = "Error: Could not create app/config/bean folder!";
			return false;
		}
		
		//FOLDER APP/CONFIG/BEAN - set same permissions
		if (!$this->setSameFilePermissions(CMS_PATH . "app/config/bean", $deployment_folder_path . "app/config/bean")) {
			$error_messages[] = "Error: Could not set same permissions for app/config/bean folder!";
			return false;
		}
		
		//FOLDER APP/CONFIG/BEAN - copy the .htaccess and other hidden files
		if (!$this->copyFolderHiddenFiles(CMS_PATH . "app/config/bean", $deployment_folder_path . "app/config/bean")) {
			$error_messages[] = "Error: Could not copy hidden files for app/config/bean folder!";
			return false;
		}
		
		//FOLDER APP/CONFIG/BEAN - set layers beans with new DBDrivers and Brokers settings
		if (!$this->setLayersBeans($deployment_folder_path, $new_user_global_variables_file_path, $new_user_global_settings_file_path, $diagram_path)) {
			$error_messages[] = "Error: Could not create config-beans files!";
			return false;
		}
		
		//FOLDER APP/LAYER - create layers folder
		//for all active layers copy them to the APP LAYER folder (don't forget to copy the .htaccess and other hidden files)
		//must be after the setLayersBeans(...) method so it can cleans the layer's folders
		if ($tasks_props) {
			$dbdriver_task_type = isset($this->template_tasks_types_by_tag["dbdriver"]) ? $this->template_tasks_types_by_tag["dbdriver"] : null;
			$presentation_task_type = isset($this->template_tasks_types_by_tag["presentation"]) ? $this->template_tasks_types_by_tag["presentation"] : null;
			
			foreach ($tasks_props as $layer_name => $task_props) {
				if ($task_props && !empty($task_props["active"])) {
					$task = $this->layer_tasks_by_label[$layer_name];
					$task_type = isset($task["type"]) ? $task["type"] : null;
					
					if (in_array($task_type, $this->template_tasks_types_by_tag) && $task_type != $dbdriver_task_type) {
						$layer_folder_name = WorkFlowBeansConverter::getFileNameFromRawLabel($layer_name); 
						$layer_folder_path = $deployment_folder_path . "app/layer/$layer_folder_name/";
						
						$files = isset($task_props["files"]) ? $task_props["files"] : null;
						$files = $files ? (is_array($files) ? $files : array($files)) : null;
						
						$orig_layer_path = CMS_PATH . "app/layer/$layer_folder_name";
						$this->prepareLayerTypeFiles($task_type, $orig_layer_path, $files);
						
						$copy_all_folder = empty($files);
						
						if ($files) {
							$files_exists = false;
							
							foreach ($files as $file)
								if (!empty(trim($file))) {
									$files_exists = true;
									break;
								}
							
							if (!$files_exists)
								$copy_all_folder = true;
						}
						
						//create layer folder and copy correspondent files
						if (!$copy_all_folder) {
							if ($this->createFolder($layer_folder_path)) {
								//set same permissions
								if (!$this->setSameFilePermissions($orig_layer_path, $layer_folder_path))
									$error_messages[] = "Error: Could not set same permissions for app/layer/$layer_folder_name folder!";
								
								//copy the .htaccess and other hidden files
								if (!$this->copyFolderHiddenFiles($orig_layer_path, $layer_folder_path))
									$error_messages[] = "Error: Could not copy hidden files for app/layer/$layer_folder_name folder!";
								
								foreach ($files as $file)
									if (!empty(trim($file))) {
										$file_src = CMS_PATH . "app/layer/$layer_folder_name/$file";
										$file_dst = $deployment_folder_path . "app/layer/$layer_folder_name/$file";
										
										if (!$this->copyFile($file_src, $file_dst))
											$error_messages[] = "Error: Could not copy app/layer/$layer_folder_name/$file!";
										
										if (!$this->copyParentsFolderHiddenFiles($file_src, $file_dst, $orig_layer_path))
											$error_messages[] = "Error: Could not copy hidden files for app/layer/$layer_folder_name/$file parent folders!";
									}
							}
							else
								$error_messages[] = "Error: Could not create '{$deployment_folder_name}/app/layer/$layer_folder_name' folder!";
						}
						//copy all layer folder
						else if (!$this->copyFolder($orig_layer_path, $layer_folder_path))
							$error_messages[] = "Error: Could not copy app/layer/$layer_folder_name folder!";
						
						//Remove modules sub-folders. Must remove only the sub-folders, because of the setup.php
						if (!$has_modules && !$this->removeFolderFiles($layer_folder_path . "module/"))
							$error_messages[] = "Error: Could not delete 'app/layer/$layer_folder_name/module/' sub-folders!";
						
						//prepare presentation layer task
						if ($task_type == $presentation_task_type) {
							//set default project if presentation layer task
							$default_project = isset($task_props["default_project"]) ? trim($task_props["default_project"]) : "";
							
							if ($default_project) {
								$htaccess_path = $layer_folder_path . ".htaccess";
								
								if (!file_exists($htaccess_path)) 
									$error_messages[] = "Error: presentation .htaccess file does not exists in '$htaccess_path'";
								else {
									$contents = file_get_contents($htaccess_path);
									//'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
									$contents = preg_replace("/(RewriteRule\s*\\^\\$\s*)([\w\-\+]+)(\\/webroot\\/)/u", "$1" . $default_project . "$3", $contents);
									$contents = preg_replace("/(RewriteRule\s*\\(\\.\\*\\)\s*)([\w\-\+]+)(\\/webroot\\/\\$1)/u", "$1" . $default_project . "$3", $contents);
									
									if (file_put_contents($htaccess_path, $contents) === false)
										$error_messages[] = "Error: trying to save default project in presentation layer: '$layer_name' into file '$htaccess_path'!";
								}
							}
							
							//set wordpress installations - delete the wordpress installations that were not selected
							$wordpress_installations = isset($task_props["wordpress_installations"]) ? $task_props["wordpress_installations"] : null;
							$wordpress_installations = $wordpress_installations ? (is_array($wordpress_installations) ? $wordpress_installations : array($wordpress_installations)) : array();
							
							$wordpress_installations_folder_path = "$layer_folder_path/common/webroot/" . WordPressUrlsParser::WORDPRESS_FOLDER_PREFIX . "/";
							$wordpress_files = array_diff(scandir($wordpress_installations_folder_path), self::$invalid_files);
							
							foreach ($wordpress_files as $file)
								if (is_dir("$wordpress_installations_folder_path$file") && !in_array($file, $wordpress_installations))
									if (!CacheHandlerUtil::deleteFolder("$wordpress_installations_folder_path$file", true))
										$error_messages[] = "Error: trying to delete wordpress installation folder: 'app/layer/$layer_folder_name/common/webroot/" . WordPressUrlsParser::WORDPRESS_FOLDER_PREFIX . "/$file/'!";
							
							//prepare the new wordpress db credentials
							$this->prepareWordPressInstallationsCredentials($tasks_props, $wordpress_installations_folder_path, $wordpress_installations, $error_messages);
						}
					}
				}
			}
		}
		
		//execute security files action. Always happens no matter what. This must always to happen!
		CMSDeploymentSecurityHandler::setSecureFiles($deployment_folder_path, $error_messages);
		
		//create licence
		if ($create_licence)
			$this->createAppLicence($deployment_folder_path, $action, $error_messages);
		
		//obfuscate files
		if ($obfuscate_proprietary_php_files || $obfuscate_proprietary_js_files) {
			//These methods bellow must run before the obfuscateProprietaryPHPFiles and obfuscateProprietaryJSFiles bc they empty some classes methods before it happens the php obfuscation.
			$php_files_settings = $this->getObfuscateProprietaryDefaultPHPFilesSettings($deployment_folder_path, $error_messages);
			$js_files_settings = $this->getObfuscateProprietaryDefaultJSFilesSettings($deployment_folder_path, $error_messages);
			
			//obfuscate php files: 
			if ($obfuscate_proprietary_php_files)
				$this->obfuscateProprietaryPHPFiles($deployment_folder_path, $php_files_settings, $error_messages);
			
			//obfuscate js files with allowed_domains
			if ($obfuscate_proprietary_js_files)
				$this->obfuscateProprietaryJSFiles($deployment_folder_path, $allowed_domains, $check_allowed_domains_port, $js_files_settings, $error_messages);
		}
		
		//save perms tracking to be set remotely
		if (!$this->setDeploymentFolderFilesPermissions(CMS_PATH, $deployment_folder_path))
			$error_messages[] = "Error: Could not set folder files perms for '$deployment_folder_name' folder!!";
		
		if ($error_messages)
			return false;
		
		//remove temporary layers.xml ($diagram_path)
		$this->removeFile($diagram_path);
		
		//FILE ZIP - zip CMS folder
		$zip_file_path = $this->deployments_temp_folder_path . "/$deployment_folder_name.zip";
		$this->deployments_files[] = $zip_file_path;
		
		if (!ZipHandler::zip($deployment_folder_path, $zip_file_path)) {
			$error_messages[] = "Error: Could not create zip '$deployment_folder_name.zip' file!";
			return false;
		}
		
		if (!ZipHandler::renameFileInZip($zip_file_path, $deployment_folder_name, "phpframework")) {
			$error_messages[] = "Error: Could not rename '$deployment_folder_name' to 'phpframework' in zip file: '$deployment_folder_name.zip'!";
			return false;
		}
		
		$aux = $zip_file_path;
		$zip_file_path = $deployment_folder_path . "/phpframework.zip";
		
		if (!rename($aux, $zip_file_path)) {
			$error_messages[] = "Error: Could not move '$deployment_folder_name.zip' file to $deployment_folder_name/phpframework.zip!";
			return false;
		}
	}
	
	/*
	 * Checks if the correspondent DB Driver is the same than the wordpress installation folder name and if the DB DRIVER HOST, PORT and DB NAME are the same than the wordpress DB credentials and:
	 * If true, updates the wordpress config file to have the new DB DRIVER credentials.
	 */
	private function prepareWordPressInstallationsCredentials($tasks_props, $wordpress_installations_folder_path, $wordpress_installations, &$error_messages) {
		$status = true;
		
		if ($wordpress_installations) {
			$dbdriver_task_type = isset($this->template_tasks_types_by_tag["dbdriver"]) ? $this->template_tasks_types_by_tag["dbdriver"] : null;
			
			foreach ($wordpress_installations as $wordpress_installation_name) {
				foreach ($tasks_props as $layer_name => $task_props) 
					if ($layer_name == $wordpress_installation_name && $task_props && !empty($task_props["active"])) {
						$task = isset($this->layer_tasks_by_label[$layer_name]) ? $this->layer_tasks_by_label[$layer_name] : null;
						$task_type = isset($task["type"]) ? $task["type"] : null;
						
						if ($task_type == $dbdriver_task_type && !empty($task["properties"])) {
							//get wordpress installation DB credentials
							$wp_config_fp = $wordpress_installations_folder_path . "$wordpress_installation_name/wp-config.php";
							
							if (file_exists($wp_config_fp)) {
								$contents = file_get_contents($wp_config_fp);
								$db_name = $orig_db_host = "";
								
								//get db name
								if (preg_match("/define\s*\(\s*('|\")DB_NAME('|\")\s*,\s*'([^']*)'\s*\)\s*;/", $contents, $match, PREG_OFFSET_CAPTURE))
									$db_name = $match[3][0];
								else if (preg_match("/define\s*\(\s*('|\")DB_NAME('|\")\s*,\s*\"([^\"]*)\"\s*\)\s*;/", $contents, $match, PREG_OFFSET_CAPTURE))
									$db_name = $match[3][0];
								
								//get db host
								if (preg_match("/define\s*\(\s*('|\")DB_HOST('|\")\s*,\s*'([^']*)'\s*\)\s*;/", $contents, $match, PREG_OFFSET_CAPTURE))
									$orig_db_host = $match[3][0];
								else if (preg_match("/define\s*\(\s*('|\")DB_HOST('|\")\s*,\s*\"([^\"]*)\"\s*\)\s*;/", $contents, $match, PREG_OFFSET_CAPTURE))
									$orig_db_host = $match[3][0];
								
								//get db port
								$parts = explode(":", $orig_db_host);
								$db_host = $parts[0];
								$db_port = isset($parts[1]) ? $parts[1] : null;
								
								//compare if original db driver task is the same than wordpress installation, and if true, changes config file with new credentials
								$task_host = isset($task["properties"]["host"]) ? $task["properties"]["host"] : null;
								$task_port = isset($task["properties"]["port"]) ? $task["properties"]["port"] : null;
								$task_db_name = isset($task["properties"]["db_name"]) ? $task["properties"]["db_name"] : null;
								
								if ($task_host == $db_host && $task_port == $db_port && $task_db_name == $db_name) {
									//set new db name
									$task_props_db_name = isset($task_props["db_name"]) ? $task_props["db_name"] : null;
									
									if ($db_name != $task_props_db_name)
										$contents = preg_replace("/define\s*\(\s*('|\")DB_NAME('|\")\s*,\s*('|\")$db_name('|\")\s*\)\s*;/", "define('DB_NAME', '" . $task_props_db_name . "');", $contents);
									
									//set new db host
									$task_props_host = isset($task_props["host"]) ? $task_props["host"] : null;
									$task_props_port = isset($task_props["port"]) ? $task_props["port"] : null;
									
									if ($db_host != $task_props_host || $db_port != $task_props_port)
										$contents = preg_replace("/define\s*\(\s*('|\")DB_HOST('|\")\s*,\s*('|\")$orig_db_host('|\")\s*\)\s*;/", "define('DB_HOST', '" . $task_props_host . (is_numeric($task_props_port) ? ":" . $task_props_port : "") . "');", $contents);
									
									//save new credentials to wp-config.php file
									if (file_put_contents($wp_config_fp, $contents) === false) {
										$error_messages[] = "Could not update credentials to wordpress installation '$wordpress_installation_name'.";
										$status = false;
									}
								}
							}
							
							break;
						}
					}
			}
		}
		
		return $status;
	}
	
	private function executeCopyLayersRemoteAction($deployment_folder_path, $server_name, $template_id, $deployment_id, $server_template, $SSHHandler, &$error_messages = null) {
		if ($error_messages)
			return false;
		
		//prepare properties
		$server_installation_folder_path = isset($server_template["properties"]["server_installation_folder_path"]) ? $server_template["properties"]["server_installation_folder_path"] : null;
		$server_backups_folder_path = "$server_installation_folder_path/.backups/backup_{$template_id}_$deployment_id/";
		$zip_file_path = $deployment_folder_path . "/phpframework.zip";
		
		//Create folder $server_backups_folder_path and all parent's folders
		if (!$SSHHandler->createRemoteFolder($server_backups_folder_path, 0755, true))
			$error_messages[] = "Error: Could not create '$server_backups_folder_path' folder in remote server!";
		else {
			$php_file = "$deployment_folder_path/copy_layers.php";
			
			//create php deploy file with logic code
			if (!$this->createRemoteServerCopyLayersPHPFile($template_id, $deployment_id, $server_installation_folder_path, $php_file, $error_messages))
				$error_messages[] = "Error: Could not create layers deployment php file!";
			else {
				//scp php file to server
				if (!$SSHHandler->copyLocalToRemoteFile($php_file, $server_backups_folder_path . basename($php_file), true, 0640))
					$error_messages[] = "Error: Could not scp layers deployment php file to remote server!";
				else {	
					//scp phpframework zip file to server
					if (!$SSHHandler->copyLocalToRemoteFile($zip_file_path, $server_backups_folder_path . basename($zip_file_path), true, 0640))
						$error_messages[] = "Error: Could not scp phpframework.zip file to remote server!";
					else {
						$remote_relative_files_to_remove = array(
							'tmp/cache/',
							'tmp/workflow/',
							'tmp/program/',
							'tmp/deployment/',
							'app/__system/layer/presentation/phpframework/webroot/__system/cache/',
							'app/__system/layer/presentation/test/webroot/__system/cache/',
							'app_old/',
							'other_old/',
							'vendor_old/',
						);
						
						//flush cache first to remove old temp folders
						$this->fushCacheOnRemoteServer($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $remote_relative_files_to_remove, $error_messages);
						
						//execute php deploy file in server
						$remote_file = "$server_backups_folder_path/copy_layers.php";
						$response = $SSHHandler->exec("php '$remote_file'");
						
						if (trim($response) !== "1")
							$error_messages[] = "Error: '$remote_file' script not executed in remote server!" . ($response ? "\n" . $response : "");
						
						//flush cache last to remove temp folders
						$this->fushCacheOnRemoteServer($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, $remote_relative_files_to_remove, $error_messages);
					}
				}
			}
		}
		
		if (file_exists($zip_file_path))
			unlink($zip_file_path);
	}
	
	private function executeMigrateDBsAction($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, &$error_messages = null) { 
		//prepare deployment_folder_path
		$deployment_folder_path = $this->getDeploymentFolderPath("migrate_dbs", $server_name, $template_id, $deployment_id);
		$this->deployments_files[] = $deployment_folder_path;
		
		$this->executeMigrateDBsLocalAction($deployment_folder_path, $server_template, $error_messages);
		$this->executeMigrateDBsRemoteAction($deployment_folder_path, $template_id, $deployment_id, $server_template, $SSHHandler, $error_messages);
		
		$this->removeFile($deployment_folder_path);
	}
	
	private function executeMigrateDBsLocalAction($deployment_folder_path, $server_template, &$error_messages = null) { 
		//prepare deployment_folder_name
		$deployment_folder_name = basename($deployment_folder_path);
		
		//prepare properties
		$tasks = isset($server_template["properties"]["task"]) ? $server_template["properties"]["task"] : null;
		$tasks_props = self::getTasksPropsByLabel($tasks);
		
		//create new CMS folder
		if (!$this->createFolder($deployment_folder_path)) {
			$error_messages[] = "Error: Could not create '$deployment_folder_name' folder!";
			return false;
		}
		
		//FOLDER LIB - copy DB folder
		if (!$this->copyFolder(CMS_PATH . "app/lib/org/phpframework/db", $deployment_folder_path . "lib/org/phpframework/db/")) {
			$error_messages[] = "Error: Could not copy app/lib/org/phpframework/db/ folder!";
			return false;
		}
		
		//FOLDER LIB - copy TextSanitizer file => DB/SQLQueryHandler.php class and others will use this
		if (!$this->copyFile(CMS_PATH . "app/lib/org/phpframework/util/text/TextSanitizer.php", $deployment_folder_path . "lib/org/phpframework/util/text/TextSanitizer.php")) {
			$error_messages[] = "Error: Could not copy app/lib/org/phpframework/util/text/TextSanitizer.php file!";
			return false;
		}
		
		//FOLDER LIB - copy compression folder => DB/DBDump.php class will use this and other will use the ZipHandler class too
		if (!$this->copyFolder(CMS_PATH . "app/lib/org/phpframework/compression/", $deployment_folder_path . "lib/org/phpframework/compression/")) {
			$error_messages[] = "Error: Could not copy app/lib/org/phpframework/compression/ folder!";
			return false;
		}
		
		//FOLDER LIB - copy sqlparser folder => DB/SQLQueryHandler.php class will use this
		if (!$this->copyFolder(CMS_PATH . "app/lib/vendor/sqlparser/", $deployment_folder_path . "lib/vendor/sqlparser/")) {
			$error_messages[] = "Error: Could not copy app/lib/vendor/sqlparser/ folder!";
			return false;
		}
		
		//FOLDER DBS_BACKUPS - get the DBs schema
		if (!$this->setDBsBackups($deployment_folder_path . "dbsbackup/", $tasks_props, $error_messages)) {
			$error_messages[] = "Error: Could not create DBs backups!";
			return false;
		}
		
		//FILE ZIP - zip CMS folder
		$zip_file_path = $this->deployments_temp_folder_path . "/$deployment_folder_name.zip";
		$this->deployments_files[] = $zip_file_path;
		
		if (!ZipHandler::zip($deployment_folder_path, $zip_file_path)) {
			$error_messages[] = "Error: Could not create zip '$deployment_folder_name.zip' file!";
			return false;
		}
		
		if (!ZipHandler::renameFileInZip($zip_file_path, $deployment_folder_name, "migrate_dbs")) {
			$error_messages[] = "Error: Could not rename '$deployment_folder_name' to 'migrate_dbs' in zip file: '$deployment_folder_name.zip'!";
			return false;
		}
		
		$aux = $zip_file_path;
		$zip_file_path = $deployment_folder_path . "/migrate_dbs.zip";
		
		if (!rename($aux, $zip_file_path)) {
			$error_messages[] = "Error: Could not move '$deployment_folder_name.zip' file to $deployment_folder_name/migrate_dbs.zip!";
			return false;
		}
	}
	
	private function executeMigrateDBsRemoteAction($deployment_folder_path, $template_id, $deployment_id, $server_template, $SSHHandler, &$error_messages = null) { 
		if ($error_messages)
			return false;
		
		//prepare properties
		$server_installation_folder_path = isset($server_template["properties"]["server_installation_folder_path"]) ? $server_template["properties"]["server_installation_folder_path"] : null;
		$tasks = isset($server_template["properties"]["task"]) ? $server_template["properties"]["task"] : null;
		$tasks_props = self::getTasksPropsByLabel($tasks);
		$server_backups_folder_path = "$server_installation_folder_path/.backups/backup_{$template_id}_$deployment_id/";
		$zip_file_path = $deployment_folder_path . "/migrate_dbs.zip";
		
		//Create folder $server_backups_folder_path and all parent's folders
		if (!$SSHHandler->createRemoteFolder($server_backups_folder_path, 0755, true)) 
			$error_messages[] = "Error: Could not create '$server_backups_folder_path' folder in remote server!";
		else {
			$php_file = "$deployment_folder_path/migrate_dbs.php";
			
			//create php deploy file with logic code
			if (!$this->createRemoteServerMigrateDBsPHPFile($template_id, $deployment_id, $server_template, $server_installation_folder_path, $tasks_props, $php_file, $error_messages))
				$error_messages[] = "Error: Could not create dbs deployment php file!";
			else {
				//scp php file to server
				if (!$SSHHandler->copyLocalToRemoteFile($php_file, $server_backups_folder_path . basename($php_file), true, 0640))
					$error_messages[] = "Error: Could not scp dbs deployment php file to remote server!";
				else {
					//scp phpframework zip file to server
					if (!$SSHHandler->copyLocalToRemoteFile($zip_file_path, $server_backups_folder_path . basename($zip_file_path), true, 0640))
						$error_messages[] = "Error: Could not scp phpframework.zip file to remote server!";
					else {
						//execute php deploy file in server
						$remote_file = "$server_backups_folder_path/migrate_dbs.php";
						$response = $SSHHandler->exec("php '$remote_file'");
						
						if (trim($response) !== "1")
							$error_messages[] = "Error: '$remote_file' script not executed in remote server!" . ($response ? "\n" . $response : "");
					}
				}
			}
		}
		
		if (file_exists($zip_file_path))
			unlink($zip_file_path);
	}
	
	private function executeUpdateWordPressInstallationsSettings($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, &$error_messages = null) {
		//prepare deployment_folder_path
		$deployment_folder_path = $this->getDeploymentFolderPath("wordpress", $server_name, $template_id, $deployment_id);
		$this->deployments_files[] = $deployment_folder_path;
		
		$em = array(); //avoids conflits with previous errors
		$this->executeUpdateWordPressInstallationsSettingsLocalAction($deployment_folder_path, $server_template, $em);
		$this->executeUpdateWordPressInstallationsSettingsRemoteAction($deployment_folder_path, $template_id, $deployment_id, $server_template, $SSHHandler, $em);
		
		$error_messages = array_merge($error_messages, $em);
		
		$this->removeFile($deployment_folder_path);
	}
	
	private function executeUpdateWordPressInstallationsSettingsLocalAction($deployment_folder_path, $server_template, &$error_messages = null) {
		//create new CMS folder
		if (!$this->createFolder($deployment_folder_path)) {
			$error_messages[] = "Error: Could not create '" . basename($deployment_folder_path) . "' folder!";
			return false;
		}
		
		//create php file
		$php_file = $deployment_folder_path . "update_wordpress_settings.php";
		
		if (!$this->createRemoteServerUpdateWordPressSettingsPHPFile($server_template, $php_file, $error_messages))
			$error_messages[] = "Error: Could not create 'update_wordpress_settings.php' file!";
	}
	
	private function executeUpdateWordPressInstallationsSettingsRemoteAction($deployment_folder_path, $template_id, $deployment_id, $server_template, $SSHHandler, &$error_messages = null) {
		if ($error_messages)
			return false;
		
		$server_installation_url = isset($server_template["properties"]["server_installation_url"]) ? $server_template["properties"]["server_installation_url"] : null;
		
		if ($server_installation_url) {
			$tasks = isset($server_template["properties"]["task"]) ? $server_template["properties"]["task"] : null;
			$tasks_props = self::getTasksPropsByLabel($tasks);
			$pres_layers_with_wordpress_installations = array();
			
			if ($tasks_props) {
				$presentation_task_type = isset($this->template_tasks_types_by_tag["presentation"]) ? $this->template_tasks_types_by_tag["presentation"] : null;
				
				foreach ($tasks_props as $layer_name => $task_props) 
					if ($task_props && !empty($task_props["active"])) {
						$task = isset($this->layer_tasks_by_label[$layer_name]) ? $this->layer_tasks_by_label[$layer_name] : null;
						$task_type = isset($task["type"]) ? $task["type"] : null;
						
						if ($task_type == $presentation_task_type && !empty($task_props["wordpress_installations"])) {
							$wordpress_installations = $task_props["wordpress_installations"];
							$wordpress_installations = $wordpress_installations ? (is_array($wordpress_installations) ? $wordpress_installations : array($wordpress_installations)) : array();
							
							foreach ($wordpress_installations as $wordpress_installation)
								if ($wordpress_installation) {
									$pres_layers_with_wordpress_installations[] = $layer_name;
									break;
								}
						}
					}
			}
			
			if ($pres_layers_with_wordpress_installations) {
				$php_file = $deployment_folder_path . "update_wordpress_settings.php";
				
				$server_installation_folder_path = isset($server_template["properties"]["server_installation_folder_path"]) ? $server_template["properties"]["server_installation_folder_path"] : null;
				$server_backups_folder_path = "$server_installation_folder_path/.backups/backup_{$template_id}_$deployment_id/";
				$remote_file = $server_backups_folder_path . "update_wordpress_settings.php";
				
				if (!file_exists($php_file))
					$error_messages[] = "Error: update_wordpress_settings.php file does not exist in local server!";
				else if (!$SSHHandler->copyLocalToRemoteFile($php_file, $remote_file, true, 0640)) //scp php file to server
					$error_messages[] = "Error: Could not upload update_wordpress_settings.php file to remote server!";
				else { //executing php file
					foreach ($pres_layers_with_wordpress_installations as $layer_name) {
						$layer_folder_name = WorkFlowBeansConverter::getFileNameFromRawLabel($layer_name); 
						
						$wordpress_installations_folder_path = "$server_installation_folder_path/app/layer/$layer_folder_name/common/webroot/" . WordPressUrlsParser::WORDPRESS_FOLDER_PREFIX . "/";
						$wordpress_installations = $SSHHandler->scanRemoteDir($wordpress_installations_folder_path);
						$wordpress_installations = is_array($wordpress_installations) ? array_diff($wordpress_installations, self::$invalid_files) : array();
						
						foreach ($wordpress_installations as $wordpress_installation) 
							if ($SSHHandler->isDir($wordpress_installations_folder_path . $wordpress_installation)) {
								$response = $SSHHandler->exec("php '$remote_file' '$layer_folder_name' '$wordpress_installation'");
								
								if (trim($response) !== "1")
									$error_messages[] = "Error: '$remote_file' script not executed in remote server!" . ($response ? "\n" . $response : "");
							}
					}
				}
			}
		}
	}
	
	private function executeSetVersionAction($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, &$error_messages = null) {
		//prepare deployment_folder_path
		$deployment_folder_path = $this->getDeploymentFolderPath("version", $server_name, $template_id, $deployment_id);
		$this->deployments_files[] = $deployment_folder_path;
		
		$em = array(); //avoids conflits with previous errors
		$this->executeSetVersionLocalAction($deployment_folder_path, $em);
		$this->executeSetVersionRemoteAction($deployment_folder_path, $template_id, $deployment_id, $server_template, $SSHHandler, $em);
		
		$error_messages = array_merge($error_messages, $em);
		
		$this->removeFile($deployment_folder_path);
	}
	
	private function executeSetVersionLocalAction($deployment_folder_path, &$error_messages = null) {
		//prepare deployment_folder_name
		$deployment_folder_name = basename($deployment_folder_path);
		
		//create new CMS folder
		if (!$this->createFolder($deployment_folder_path)) {
			$error_messages[] = "Error: Could not create '$deployment_folder_name' folder!";
			return false;
		}
	}
	
	private function executeSetVersionRemoteAction($deployment_folder_path, $template_id, $deployment_id, $server_template, $SSHHandler, &$error_messages = null) {
		if ($error_messages)
			return false;
		
		//prepare properties
		$server_installation_folder_path = isset($server_template["properties"]["server_installation_folder_path"]) ? $server_template["properties"]["server_installation_folder_path"] : null;
		$txt_file = "$deployment_folder_path/version.txt";
		$text = "template_id:$template_id\ndeployment_id:$deployment_id\ntime:" . time() . "\ndate:" . date("Y-m-d H:i:s");
		
		//create txt file
		if (file_put_contents($txt_file, $text) === false)
			$error_messages[] = "Error: Could not create deployment version txt file!";
		//scp txt file to server
		else if (!$SSHHandler->copyLocalToRemoteFile($txt_file, $server_installation_folder_path . "/" . basename($txt_file), true, 0640)) 
			$error_messages[] = "Error: Could not scp backup deployment version txt file to remote server!";
	}
	
	private function executeGetVersionAction($server_name, $template_id, $deployment_id, $server_template, $SSHHandler, &$error_messages = null) {
		//prepare deployment_folder_path
		$deployment_folder_path = $this->getDeploymentFolderPath("version", $server_name, $template_id, $deployment_id);
		$this->deployments_files[] = $deployment_folder_path;
		
		$this->executeGetVersionLocalAction($deployment_folder_path, $error_messages);
		$this->executeGetVersionRemoteAction($deployment_folder_path, $template_id, $deployment_id, $server_template, $SSHHandler, $current_deployment_id, $error_messages);
		
		$this->removeFile($deployment_folder_path);
		
		return $deployment_id;
	}
	
	private function executeGetVersionLocalAction($deployment_folder_path, &$error_messages = null) {
		//prepare deployment_folder_name
		$deployment_folder_name = basename($deployment_folder_path);
		
		//create new CMS folder
		if (!$this->createFolder($deployment_folder_path)) {
			$error_messages[] = "Error: Could not create '$deployment_folder_name' folder!";
			return false;
		}
	}
	
	private function executeGetVersionRemoteAction($deployment_folder_path, $template_id, $deployment_id, $server_template, $SSHHandler, &$current_deployment_id, &$error_messages = null) {
		//prepare properties
		$server_installation_folder_path = isset($server_template["properties"]["server_installation_folder_path"]) ? $server_template["properties"]["server_installation_folder_path"] : null;
		
		//get current installed version
		$txt_file = "$deployment_folder_path/version.txt";
		$current_deployment_id = null;
		
		//scp txt file to server
		if (!$SSHHandler->copyRemoteToLocalFile($server_installation_folder_path . "/version.txt", $txt_file) || !file_exists($txt_file))
			$error_messages[] = "Error: Could not get version file from server!";
		//parseing txt file
		else {
			$contents = file_get_contents($txt_file);
			preg_match("/deployment_id:([0-9]+)/", $contents, $matches, PREG_OFFSET_CAPTURE);
			$current_deployment_id = $matches ? $matches[1][0] : -1;
		}
	}
	
	private function isServerEmptyDeployment($SSHHandler, $server_installation_folder_path) {
		$version_file_exists = $SSHHandler->exists($server_installation_folder_path . "/version.txt"); //only get version number if exists, bc the file may not exists since it could be an rollback from the 1st version to NULL or empty version. This means that the server folder will be empty.
		
		if ($version_file_exists)
			return false;
			
		$sub_files = $SSHHandler->scanRemoteDir($server_installation_folder_path);
		$sub_files = $sub_files ? array_diff($sub_files, self::$invalid_files) : null;
		$sub_files = $sub_files ? array_diff($sub_files, array(".backups")) : null;
		
		//check if there are sub files that are not hidden, and if so, return false bc is not an empty deployment.
		if ($sub_files)
			foreach ($sub_files as $sub_file)
				if ($sub_file && substr($sub_file, 0, 1) != ".")
					return false;
		
		return true;
	}
	
	private function getDeploymentFolderPath($prefix, $server_name, $template_id, $deployment_id) {
		$deployment_folder_name = $prefix . "_" . $server_name . "_" . $template_id . "_" . $deployment_id;
		
		return $this->deployments_temp_folder_path . "/$deployment_folder_name/";
	}
	
	/* REMOTE SERVER UTILS */
	
	private function createRemoteServerRemoveCachePHPFile($server_installation_folder_path, $php_file, $remote_relative_files_to_remove, $error_messages) {
		$code = "<?php
\$installation_folder_path = \"" . addcslashes($server_installation_folder_path, '"') . "/\";

\$files_to_remove = array(";

		if ($remote_relative_files_to_remove)
			foreach ($remote_relative_files_to_remove as $f)
				$code .= "
	\"" . addcslashes($f, '"') . "\",";

$code .= "
);

\$status = true;

foreach (\$files_to_remove as \$file) {
	\$fp = \$installation_folder_path . \$file;
	
	if (!removeFile(\$fp))
		\$status = false;
}

if (\$status)
	exitScript(); //terminate script without errors
else 
	exitScript('Error: trying to flush cache by removing files');

" . $this->getRemoteServerExitScriptPHPCode() . "
" . $this->getRemoteServerRemoveFileFunctionPHPCode() . "
?>";
		
		return file_put_contents($php_file, $code) !== false;
	}
	
	private function createRemoteServerSetCachePermissionsPHPFile($server_installation_folder_path, $php_file, $remote_relative_files_to_remove, $error_messages) {
		$code = "<?php
\$installation_folder_path = \"" . addcslashes($server_installation_folder_path, '"') . "/\";

\$files_to_remove = array(";

		if ($remote_relative_files_to_remove)
			foreach ($remote_relative_files_to_remove as $f)
				$code .= "
	\"" . addcslashes($f, '"') . "\",";

$code .= "
);

\$status = true;

foreach (\$files_to_remove as \$file) {
	\$fp = \$installation_folder_path . \$file;
	
	if (!setFilePermissions(\$fp))
		\$status = false;
}

clearstatcache();

if (\$status)
	exitScript(); //terminate script without errors
else 
	exitScript('Error: trying to flush cache by setting permissions');

" . $this->getRemoteServerExitScriptPHPCode() . "

function setFilePermissions(\$path) {
	\$status = true;
	
	if (\$path && file_exists(\$path)) {
		if (is_dir(\$path)) {
			\$files = array_diff(scandir(\$path), " . self::getInvalidFilesVarExport() . ");
			
			foreach (\$files as \$file)
				if (!setFilePermissions(\"\$path/\$file\"))
					\$status = false;
		}
		
		if (is_writable(\$path) && !chmod(\$path, 0777))
			\$status = false;
	}
	return \$status;
}
?>";
		
		return file_put_contents($php_file, $code) !== false;
	}
	
	private function createRemoteServerRollbackPHPFile($template_id, $deployment_id, $server_template, $server_installation_folder_path, $tasks_props, $php_file, $error_messages) {
		$code = "<?php
\$installation_folder_path = \"" . addcslashes($server_installation_folder_path, '"') . "/\";
\$backups_folder_path = \$installation_folder_path . \".backups/backup_{$template_id}_$deployment_id/\";
\$rollbacking_folder_path = \$backups_folder_path . \"rollbacking/\";
\$db_backups_folder_path = \$backups_folder_path . \"dbsbackup/\";
\$zip_file_path = \$backups_folder_path . \"rollback.zip\";
\$old_zip_file_path = \$backups_folder_path . \"old_phpframework.zip\";
\$old_perms_file_path = \$backups_folder_path . \"old_perms.json\";
\$lib_folder_path = \$rollbacking_folder_path . \"rollback/lib/\";

//creating rollbacking folder
if (is_dir(\$rollbacking_folder_path))
	removeFile(\$rollbacking_folder_path);

if (!is_dir(\$rollbacking_folder_path))
	mkdir(\$rollbacking_folder_path, 0755, true);

if (!is_dir(\$rollbacking_folder_path)) 
	exitScript(\"Error: Could not create '\$rollbacking_folder_path' folder\");

if (!file_exists(\$old_zip_file_path))
	exitScript(\"Error: '\$old_zip_file_path' file does NOT exist\");

//unzip old_phpframework.zip to rollbacking folder
\$ZipArchive = new ZipArchive();
\$status = \$ZipArchive->open(\$old_zip_file_path) === true;

if (\$status) {
	\$status = \$ZipArchive->extractTo(\$rollbacking_folder_path);
	\$ZipArchive->close();
}

if (!\$status)
	exitScript(\"Error: Could not unzip '\$old_zip_file_path' file\");

//getting permissions from old_perms_file_path and setting them to the new files 
if (!file_exists(\$old_perms_file_path))
	exitScript(\"Error: Old permissions file does not exists. File should be in: '\$old_perms_file_path'!\");

\$perms = json_decode(file_get_contents(\$old_perms_file_path), true);
if (\$perms)
	foreach (\$perms as \$file => \$perm) 
		if (\$perm) {
			\$fp = \$rollbacking_folder_path . \"phpframework/\$file\";
			
			if (file_exists(\$fp)) {
				\$p = octdec(\$perm);
				
				if (!chmod(\$fp, \$p))
					exitScript(\"Error: Could not set permission '\$perm' to '\$file' file!\");
			}
		}

//move files from rollbacking_folder_path to installation_folder_path
\$files = scandir(\$rollbacking_folder_path . \"phpframework\");
\$old_files_to_remove = array();
\$moved = true;

if (\$files)
	foreach (\$files as \$file)
		if (!in_array(\$file, " . self::getInvalidFilesVarExport() . ") && \$file != \".backups\") {
			\$fp = \$installation_folder_path . \$file;
			
			if (file_exists(\$fp)) {
				\$pathinfo = pathinfo(\$fp);
				\$fp_old = \$pathinfo[\"dirname\"] . \"/\" . \$pathinfo[\"filename\"] . \"_old\" . (!empty(\$pathinfo[\"extension\"]) ? \".\" . \$pathinfo[\"extension\"] : \"\");
				
				if (!rename(\$fp, \$fp_old))
					exitScript(\"Error: Could not rename file: '\$file' to  '\" . basename(\$fp_old) . \"'!\");
				
				\$old_files_to_remove[] = \$fp_old;
			}
			
			if (!rename(\$rollbacking_folder_path . \"phpframework/\$file\", \$fp))
				\$moved = false;
		}

//DO NOT remove old files, bc they still contains files with the apache user owner. These folders will be removed via CMSDeploymentHandler::fushCacheOnRemoteServer method.
//foreach (\$old_files_to_remove as \$file)
//	if (!removeFile(\$file))
//		exitScript(\"Error: Could not remove file: '\$file'!\");

if (!\$moved)
	exitScript(\"Error: Could not move all files from '\$rollbacking_folder_path/phpframework/' folder to '\$installation_folder_path' folder\");

//settings permission to installation_folder_path
if (\$perms && !empty(\$perms[\"/\"])) {
	\$perm = \$perms[\"/\"];
	\$p = octdec(\$perm);
	
	if (!chmod(\$installation_folder_path, \$p))
		exitScript(\"Error: Could not set permission '\$perm' to '\$installation_folder_path' folder!\");
}
";
		
		//go to all active DBs and check if there is any DB to migrate (schema or data) and if yes
		if ($tasks_props && $this->layer_tasks && !empty($this->layer_tasks["tasks"])) {
			$global_vars = isset($server_template["properties"]["global_vars"]) ? $server_template["properties"]["global_vars"] : null;
			$global_variables = $this->convertGlobalVariables($global_vars);
			$global_variables_code = trim( PHPVariablesFileHandler::getVarsCode($global_variables, false) );
			$global_variables_code = trim( substr($global_variables_code, 5, -2) );
			
			$code .= "
//unzip zip_file_path to deploying folder
\$ZipArchive = new ZipArchive();
\$status = \$ZipArchive->open(\$zip_file_path) === true;

if (\$status) {
	\$status = \$ZipArchive->extractTo(\$rollbacking_folder_path);
	\$ZipArchive->close();
}

if (!\$status)
	exitScript(\"Error: Could not unzip '\$zip_file_path' file\");

include get_lib(\"org.phpframework.db.DB\");

//setting global variables - bc some of them may be in the DBDrivers props
" . $global_variables_code . "
";
			
			$dbdriver_task_type = isset($this->template_tasks_types_by_tag["dbdriver"]) ? $this->template_tasks_types_by_tag["dbdriver"] : null;
			
			foreach ($this->layer_tasks["tasks"] as $task_id => $task) {
				$task_label = isset($task["label"]) ? $task["label"] : null;
				$task_props = isset($tasks_props[$task_label]) ? $tasks_props[$task_label] : null;
				$task_type = isset($task["type"]) ? $task["type"] : null;
				$active = $task_props && !empty($task_props["active"]);
				
				if ($active && $task_type == $dbdriver_task_type) {
					$task_props_type = isset($task_props["type"]) ? $task_props["type"] : null;
					
					$code .= "
\$dump_file_path = \$db_backups_folder_path . \"prev_mysqldump.$task_label.sql\";

if (file_exists(\$dump_file_path)) {
	//*** DB $task_label ***
	//connect to " . $task_props_type . " DB: '$task_label' and create DB if not exists yet
	\$db_type = \"" . $task_props_type . "\";
	\$DBDriver = DB::createDriverByType(\$db_type);

	\$db_options = array(
		\"extension\" => \"" . (isset($task_props["extension"]) ? $task_props["extension"] : "") . "\",
		\"host\" => \"" . (isset($task_props["host"]) ? $task_props["host"] : "") . "\",
		\"db_name\" => \"" . (isset($task_props["db_name"]) ? $task_props["db_name"] : "") . "\",
		\"username\" => \"" . (isset($task_props["username"]) ? $task_props["username"] : "") . "\",
		\"password\" => \"" . (isset($task_props["password"]) ? $task_props["password"] : "") . "\",
		\"port\" => \"" . (isset($task_props["port"]) ? $task_props["port"] : "") . "\",
		\"persistent\" => \"" . (isset($task_props["persistent"]) ? $task_props["persistent"] : "") . "\",
		\"new_link\" => \"" . (isset($task_props["new_link"]) ? $task_props["new_link"] : "") . "\",
		\"encoding\" => \"" . (isset($task_props["encoding"]) ? $task_props["encoding"] : "") . "\",
		\"schema\" => \"" . (isset($task_props["schema"]) ? $task_props["schema"] : "") . "\",
		\"odbc_data_source\" => \"" . (isset($task_props["odbc_data_source"]) ? $task_props["odbc_data_source"] : "") . "\",
		\"odbc_driver\" => \"" . (isset($task_props["odbc_driver"]) ? $task_props["odbc_driver"] : "") . "\",
		\"extra_dsn\" => \"" . (isset($task_props["extra_dsn"]) ? $task_props["extra_dsn"] : "") . "\",
	);
	\$DBDriver->setOptions(\$db_options);

	\$exception = null;

	try {
		\$connected = @\$DBDriver->connect();
	}
	catch (Exception \$e) {
		\$exception = \$e;
	}

	//tryies to create DB if not exists yet
	if (!\$connected || \$exception) {
		\$exception = null;
	
		try {
			\$db_name = isset(\$db_options[\"db_name\"]) ? \$db_options[\"db_name\"] : null;
			\$created = \$DBDriver->createDB(\$db_name);
			\$connected = \$created && \$DBDriver->isDBSelected() && \$DBDriver->getSelectedDB() == \$db_name;
		}
		catch (Exception \$e) {
			\$exception = \$e;
		}
	}

	if (!\$connected || \$exception) {
		\$msg = \$exception ? (!empty(\$exception->problem) ? \$exception->problem . PHP_EOL : \"\") . \$exception->getMessage() : \"\";
		exitScript(\"Error (1): Could not connect to \$db_type DB Driver: '$task_label'!\" . (\$msg ? \"\\n\" . \$msg : \"\"));
	}
	
	//import schema
	\$contents = file_get_contents(\$dump_file_path);
	\$imported = true;
	\$msg = \"\";

	try {
		\$imported = \$DBDriver->setData(\$contents, array(\"remove_comments\" => true)); //This must be executed in a batch (this is, all sqls together) bc we may have store procedures or other sql commands that can only take effect if executed together in the same sql session. SO PLEASE DO NOT SPLIT THE SQL STATEMENTS!
	}
	catch(Exception \$e) {
		\$exception = \$e;
	}
	
	if (!\$imported || \$exception) {
		\$msg = \$exception ? (!empty(\$exception->problem) ? \$exception->problem . PHP_EOL : \"\") . \$exception->getMessage() : \"\";
		exitScript(\"Error: Could not import schema to DB '$task_label'!\" . (\$msg ? \"\\n\" . \$msg : \"\"));
	}
	
	\$DBDriver->disconnect();
}
";
				}
			}
		}
		
		$code .= "
//remove rollbacking folder
removeFile(\$rollbacking_folder_path);

exitScript(); //terminate script without errors

" . $this->getRemoteServerExitScriptPHPCode() . "
" . $this->getRemoteServerGetLibFunctionPHPCode() . "
" . $this->getRemoteServerGetLaunchExceptionFunctionPHPCode() . "
" . $this->getRemoteServerGetDebugLogFunctionFunctionPHPCode() . "
" . $this->getRemoteServerGetDebugLogFunctionPHPCode() . "
" . $this->getRemoteServerRemoveFileFunctionPHPCode() . "
?>";
		
		return file_put_contents($php_file, $code) !== false;
	}
	
	private function createRemoteServerBackupPHPFile($template_id, $deployment_id, $server_installation_folder_path, $php_file, $error_messages) {
		$code = "<?php
\$installation_folder_path = \"" . addcslashes($server_installation_folder_path, '"') . "/\";
\$installation_folder_name = basename(\$installation_folder_path);
\$backups_folder_path = \$installation_folder_path . \".backups/backup_{$template_id}_$deployment_id/\";
\$deploying_folder_path = \$backups_folder_path . \"deploying/\";
\$zip_file_path = \$backups_folder_path . \"backup.zip\";
\$old_zip_file_path = \$backups_folder_path . \"old_phpframework.zip\";
\$old_perms_file_path = \$backups_folder_path . \"old_perms.json\";

//creating deploying folder
if (is_dir(\$deploying_folder_path))
	removeFile(\$deploying_folder_path);

if (!is_dir(\$deploying_folder_path))
	mkdir(\$deploying_folder_path, 0755, true);

if (!is_dir(\$deploying_folder_path))
	exitScript(\"Error: Could not create '\$deploying_folder_path' folder\");

//unzip backup.zip to deploying folder
\$ZipArchive = new ZipArchive();
\$status = \$ZipArchive->open(\$zip_file_path) === true;

if (\$status) {
	\$status = \$ZipArchive->extractTo(\$deploying_folder_path);
	\$ZipArchive->close();
}

if (!\$status)
	exitScript(\"Error: Could not unzip '\$zip_file_path' file\");

//zip installation_folder_path folder (.ignore .backups folder) and put it in .backups with version number
include \$deploying_folder_path . \"backup/lib/org/phpframework/compression/ZipHandler.php\";

if (!file_exists(\$old_zip_file_path)) {
	if (!ZipHandler::zip(\$installation_folder_path, \$old_zip_file_path, array(\"exclude_files\" => \$installation_folder_path . \".backups\")))
		exitScript(\"Error: Could not create backup zip file for '\$installation_folder_path'\");
	
	if (!ZipHandler::renameFileInZip(\$old_zip_file_path, \$installation_folder_name, \"phpframework\"))
		exitScript(\"Error: Could not rename '\$installation_folder_name' to 'phpframework' in '\$installation_folder_path' file\");
}

//getting files permissions and save them to old_perms_file_path
\$perms = getFolderFilesPermissions(\$installation_folder_path);
if (!file_put_contents(\$old_perms_file_path, json_encode(\$perms)))
	exitScript(\"Error: creating '\$old_perms_file_path' file\");

//remove deploying folder 
removeFile(\$deploying_folder_path);

exitScript(); //terminate script without errors

" . $this->getRemoteServerExitScriptPHPCode() . "
" . $this->getRemoteServerRemoveFileFunctionPHPCode() . "
" . $this->getRemoteServerGetFolderFilesPermissionsFunctionPHPCode() . "
?>";
		
		return file_put_contents($php_file, $code) !== false;
	}
	
	private function createRemoteServerCopyLayersPHPFile($template_id, $deployment_id, $server_installation_folder_path, $php_file, $error_messages) {
		$code = "<?php
\$installation_folder_path = \"" . addcslashes($server_installation_folder_path, '"') . "/\";
\$backups_folder_path = \$installation_folder_path . \".backups/backup_{$template_id}_$deployment_id/\";
\$deploying_folder_path = \$backups_folder_path . \"deploying/\";
\$zip_file_path = \$backups_folder_path . \"phpframework.zip\";
\$old_zip_file_path = \$backups_folder_path . \"old_phpframework.zip\";

//creating deploying folder
if (is_dir(\$deploying_folder_path))
	removeFile(\$deploying_folder_path);

if (!is_dir(\$deploying_folder_path))
	mkdir(\$deploying_folder_path, 0755, true);

if (!is_dir(\$deploying_folder_path))
	exitScript(\"Error: Could not create '\$deploying_folder_path' folder\");

//unzip zip_file_path to deploying folder
\$ZipArchive = new ZipArchive();
\$status = \$ZipArchive->open(\$zip_file_path) === true;

if (\$status) {
	\$status = \$ZipArchive->extractTo(\$deploying_folder_path);
	\$ZipArchive->close();
}

if (!\$status)
	exitScript(\"Error: Could not unzip '\$zip_file_path' file\");

//set phpframework files perms
\$perms_file_path = \$deploying_folder_path . \"phpframework/perms.json\";

if (!file_exists(\$perms_file_path))
	exitScript(\"Error: Permissions file does not exists. File should be in: '\$perms_file_path'!\");

\$perms = json_decode(file_get_contents(\$perms_file_path), true);
if (\$perms)
	foreach (\$perms as \$file => \$perm) 
		if (\$perm) {
			\$fp = \$deploying_folder_path . \"phpframework/\$file\";
			
			if (file_exists(\$fp)) {
				\$p = octdec(\$perm);
				
				if (!chmod(\$fp, \$p))
					exitScript(\"Error: Could not set permission '\$perm' to '\$file' file!\");
			}
		}

//add \$installation_folder_path/.htaccess if not exists
if (!file_exists(\$installation_folder_path . \".htaccess\") && !rename(\$deploying_folder_path . \"phpframework/.htaccess\", \$installation_folder_path . \".htaccess\"))
	exitScript(\"Error: Could not move '\$deploying_folder_path/phpframework/.htaccess' to '\$installation_folder_path/.htaccess'!\");

//add \$installation_folder_path/LICENSE.md if not exists
if (!file_exists(\$installation_folder_path . \"LICENSE.md\") && !rename(\$deploying_folder_path . \"phpframework/LICENSE.md\", \$installation_folder_path . \"LICENSE.md\"))
	exitScript(\"Error: Could not move '\$deploying_folder_path/phpframework/LICENSE.md' to '\$installation_folder_path/LICENSE.md'!\");

//remove old folder in case it exists. It should already have been removed via the CMSDeploymentHandler::fushCacheOnRemoteServer method, but just in case do it again
removeFile(\$installation_folder_path . \"app_old\"); 

//mv old app folder to app_old
if (file_exists(\$installation_folder_path . \"app\") && !rename(\$installation_folder_path . \"app\", \$installation_folder_path . \"app_old\"))
	exitScript(\"Error: Could not move '\$installation_folder_path/app' to '\$installation_folder_path/app_old'!\");

//mv \$deploying_folder_path/phpframework/app to app
if (!file_exists(\$deploying_folder_path . \"phpframework/app\"))
	exitScript(\"Error: '\$deploying_folder_path/phpframework/app' folder does not exists!\");

if (!rename(\$deploying_folder_path . \"phpframework/app\", \$installation_folder_path . \"app\"))
	exitScript(\"Error: Could not move '\$deploying_folder_path/phpframework/app' to '\$installation_folder_path/app'!\");

//copy app_old/.htaccess to app/.htaccess and others hidden files
if (file_exists(\$installation_folder_path . \"app_old/.htaccess\")) {
	removeFile(\$installation_folder_path . \"app/.htaccess\");
	
	if (!rename(\$installation_folder_path . \"app_old/.htaccess\", \$installation_folder_path . \"app/.htaccess\"))
		exitScript(\"Error: Could not move '\$installation_folder_path/app_old/.htaccess' to '\$installation_folder_path/app/.htaccess'!\");
}

//remove old folder in case it exists. It should already have been removed via the CMSDeploymentHandler::fushCacheOnRemoteServer method, but just in case do it again
removeFile(\$installation_folder_path . \"vendor_old\"); //remove old folder in case it exists

//mv old vendor folder to vendor_old
if (file_exists(\$installation_folder_path . \"vendor\") && !rename(\$installation_folder_path . \"vendor\", \$installation_folder_path . \"vendor_old\"))
	exitScript(\"Error: Could not move '\$installation_folder_path/vendor' to '\$installation_folder_path/vendor_old'!\");

//prepare new vendor folder
if (file_exists(\$deploying_folder_path . \"phpframework/vendor\")) {
	//mv \$deploying_folder_path/phpframework/vendor to vendor
	if (!rename(\$deploying_folder_path . \"phpframework/vendor\", \$installation_folder_path . \"vendor\"))
		exitScript(\"Error: Could not move '\$deploying_folder_path/phpframework/vendor' to '\$installation_folder_path/vendor'!\");

	//copy vendor_old/.htaccess to vendor/.htaccess and others hidden files
	if (file_exists(\$installation_folder_path . \"vendor_old/.htaccess\")) {
		removeFile(\$installation_folder_path . \"vendor/.htaccess\");
		
		if (!rename(\$installation_folder_path . \"vendor_old/.htaccess\", \$installation_folder_path . \"vendor/.htaccess\"))
			exitScript(\"Error: Could not move '\$installation_folder_path/vendor_old/.htaccess' to '\$installation_folder_path/vendor/.htaccess'!\");
	}
}

//remove old folder in case it exists. It should already have been removed via the CMSDeploymentHandler::fushCacheOnRemoteServer method, but just in case do it again
removeFile(\$installation_folder_path . \"other_old\"); //remove old folder in case it exists

//mv old other folder to other_old
if (file_exists(\$installation_folder_path . \"other\") && !rename(\$installation_folder_path . \"other\", \$installation_folder_path . \"other_old\"))
	exitScript(\"Error: Could not move '\$installation_folder_path/other' to '\$installation_folder_path/other_old'!\");

//prepare new other folder
if (file_exists(\$deploying_folder_path . \"phpframework/other\")) {
	//mv \$deploying_folder_path/phpframework/other to other
	if (!rename(\$deploying_folder_path . \"phpframework/other\", \$installation_folder_path . \"other\"))
		exitScript(\"Error: Could not move '\$deploying_folder_path/phpframework/other' to '\$installation_folder_path/other'!\");

	//copy other_old/.htaccess to other/.htaccess and others hidden files
	if (file_exists(\$installation_folder_path . \"other_old/.htaccess\")) {
		removeFile(\$installation_folder_path . \"other/.htaccess\");
		
		if (!rename(\$installation_folder_path . \"other_old/.htaccess\", \$installation_folder_path . \"other/.htaccess\"))
			exitScript(\"Error: Could not move '\$installation_folder_path/other_old/.htaccess' to '\$installation_folder_path/other/.htaccess'!\");
	}
}

//add \$installation_folder_path/tmp folder if not exists
if (!file_exists(\$installation_folder_path . \"tmp\") && !rename(\$deploying_folder_path . \"phpframework/tmp\", \$installation_folder_path . \"tmp\"))
	exitScript(\"Error: Could not move '\$deploying_folder_path/phpframework/tmp' to '\$installation_folder_path/tmp'!\");

//remove deploying folder 
removeFile(\$deploying_folder_path);

//DO NOT remove old folder in case it exists, bc they still contains files with the apache user owner. These folders will be removed via CMSDeploymentHandler::fushCacheOnRemoteServer method.
//removeFile(\$installation_folder_path . \"tmp/cache\");
//removeFile(\$installation_folder_path . \"app_old\");
//removeFile(\$installation_folder_path . \"vendor_old\");
//removeFile(\$installation_folder_path . \"other_old\");

exitScript(); //terminate script without errors

" . $this->getRemoteServerExitScriptPHPCode() . "
" . $this->getRemoteServerRemoveFileFunctionPHPCode() . "
?>";
		
		return file_put_contents($php_file, $code) !== false;
	}
	
	private function createRemoteServerMigrateDBsPHPFile($template_id, $deployment_id, $server_template, $server_installation_folder_path, $tasks_props, $php_file, $error_messages) {
		$code = "<?php";
		
		//go to all active DBs and check if there is any DB to migrate (schema or data) and if yes
		if ($tasks_props && $this->layer_tasks && !empty($this->layer_tasks["tasks"])) {
			$global_vars = isset($server_template["properties"]["global_vars"]) ? $server_template["properties"]["global_vars"] : null;
			$global_variables = $this->convertGlobalVariables($global_vars);
			$global_variables_code = trim( PHPVariablesFileHandler::getVarsCode($global_variables, false) );
			$global_variables_code = trim( substr($global_variables_code, 5, -2) );
			
			$code .= "
\$installation_folder_path = \"" . addcslashes($server_installation_folder_path, '"') . "/\";
\$backups_folder_path = \$installation_folder_path . \".backups/backup_{$template_id}_$deployment_id/\";
\$db_backups_folder_path = \$backups_folder_path . \"dbsbackup/\";
\$deploying_folder_path = \$backups_folder_path . \"deploying/\";
\$zip_file_path = \$backups_folder_path . \"migrate_dbs.zip\";
\$lib_folder_path = \$deploying_folder_path . \"migrate_dbs/lib/\";

//creating deploying folder
if (is_dir(\$deploying_folder_path))
	removeFile(\$deploying_folder_path);

if (!is_dir(\$deploying_folder_path))
	mkdir(\$deploying_folder_path, 0755, true);

if (!is_dir(\$deploying_folder_path))
	exitScript(\"Error: Could not create '\$deploying_folder_path' folder\");

//creating dbs backup folder
if (!is_dir(\$db_backups_folder_path))
	mkdir(\$db_backups_folder_path, 0755, true);

if (!is_dir(\$db_backups_folder_path))
	exitScript(\"Error: Could not create '\$db_backups_folder_path' folder\");

//unzip zip_file_path to deploying folder
\$ZipArchive = new ZipArchive();
\$status = \$ZipArchive->open(\$zip_file_path) === true;

if (\$status) {
	\$status = \$ZipArchive->extractTo(\$deploying_folder_path);
	\$ZipArchive->close();
}

if (!\$status)
	exitScript(\"Error: Could not unzip '\$zip_file_path' file\");

include get_lib(\"org.phpframework.db.DB\");
include get_lib(\"org.phpframework.db.DBDumperHandler\");

//setting global variables - bc some of them may be in the DBDrivers props
" . $global_variables_code . "
";
			
			$dbdriver_task_type = isset($this->template_tasks_types_by_tag["dbdriver"]) ? $this->template_tasks_types_by_tag["dbdriver"] : null;
			
			foreach ($this->layer_tasks["tasks"] as $task_id => $task) {
				$task_label = isset($task["label"]) ? $task["label"] : null;
				$task_props = isset($tasks_props[$task_label]) ? $tasks_props[$task_label] : null;
				$active = $task_props && !empty($task_props["active"]);
				$migrate_db_schema = $task_props && !empty($task_props["migrate_db_schema"]);
				$migrate_db_data = $task_props && !empty($task_props["migrate_db_data"]);
				$remove_deprecated_tables_and_attributes = $task_props && !empty($task_props["remove_deprecated_tables_and_attributes"]);
				$task_type = isset($task["type"]) ? $task["type"] : null;
				
				if ($active && $task_type == $dbdriver_task_type && ($migrate_db_schema || $migrate_db_data)) {
					$task_props_type = isset($task_props["type"]) ? $task_props["type"] : null;
					$dsn = DB::getDSNByType($task_props_type, $task_props);
					
					$code .= "
//*** DB $task_label ***
//connect to " . $task_props_type . " DB: '$task_label' and create DB if not exists yet
\$db_type = \"" . $task_props_type . "\";
\$DBDriver = DB::createDriverByType(\$db_type);

\$db_options = array(
	\"extension\" => \"" . (isset($task_props["extension"]) ? $task_props["extension"] : "") . "\",
	\"host\" => \"" . (isset($task_props["host"]) ? $task_props["host"] : "") . "\",
	\"db_name\" => \"" . (isset($task_props["db_name"]) ? $task_props["db_name"] : "") . "\",
	\"username\" => \"" . (isset($task_props["username"]) ? $task_props["username"] : "") . "\",
	\"password\" => \"" . (isset($task_props["password"]) ? $task_props["password"] : "") . "\",
	\"port\" => \"" . (isset($task_props["port"]) ? $task_props["port"] : "") . "\",
	\"persistent\" => \"" . (isset($task_props["persistent"]) ? $task_props["persistent"] : "") . "\",
	\"new_link\" => \"" . (isset($task_props["new_link"]) ? $task_props["new_link"] : "") . "\",
	\"encoding\" => \"" . (isset($task_props["encoding"]) ? $task_props["encoding"] : "") . "\",
	\"schema\" => \"" . (isset($task_props["schema"]) ? $task_props["schema"] : "") . "\",
	\"odbc_data_source\" => \"" . (isset($task_props["odbc_data_source"]) ? $task_props["odbc_data_source"] : "") . "\",
	\"odbc_driver\" => \"" . (isset($task_props["odbc_driver"]) ? $task_props["odbc_driver"] : "") . "\",
	\"extra_dsn\" => \"" . (isset($task_props["extra_dsn"]) ? $task_props["extra_dsn"] : "") . "\",
);
\$DBDriver->setOptions(\$db_options);

\$sql_options = array(\"schema\" => \$DBDriver->getOption(\"schema\"));
\$exception = null;
\$is_db_previously_created = true;

try {
	\$connected = @\$DBDriver->connect();
}
catch (Exception \$e) {
	\$exception = \$e;
}

//tryies to create DB if not exists yet
if (!\$connected || \$exception) {
	\$is_db_previously_created = false;
	\$exception = null;
	
	try {
		\$db_name = isset(\$db_options[\"db_name\"]) ? \$db_options[\"db_name\"] : null;
		\$created = \$DBDriver->createDB(\$db_name);
		\$connected = \$created && \$DBDriver->isDBSelected() && \$DBDriver->getSelectedDB() == \$db_name;
	}
	catch (Exception \$e) {
		\$exception = \$e;
	}
}

if (!\$connected || \$exception) {
	\$msg = \$exception ? (!empty(\$exception->problem) ? \$exception->problem . PHP_EOL : \"\") . \$exception->getMessage() : \"\";
	exitScript(\"Error (2): Could not connect to \$db_type DB Driver: '$task_label'!\" . (\$msg ? \"\\n\" . \$msg : \"\"));
}

\$DBDriver->disconnect();

//backup $task_label DB to \$db_backups_folder_path folder
\$pdo_settings = " . (!empty($task_props["persistent"]) && empty($task_props["new_link"]) ? "array(PDO::ATTR_PERSISTENT => true)" : "array()") . ";

if (\$is_db_previously_created) {
	//backup DB
	\$dump_file_path = \$db_backups_folder_path . \"prev_mysqldump.$task_label.sql\";

	if (!file_exists(\$dump_file_path)) { //if file exists it means that the backup already happend previously and we don't want to overwrite it, bc we need the first backup with the original changes!
		\$dump_settings = array(
			'include-tables' => array(),
			'exclude-tables' => array(),
			'include-views' => array(),
			'compress' => DBDumperHandler::NONE,
			'no-data' => false,
			'reset-auto-increment' => false,
			'add-drop-database' => false,
			'add-drop-table' => true,
			'add-drop-trigger' => false,
			'add-drop-routine' => true,
			'add-drop-event' => false,
			'add-locks' => true,
			'complete-insert' => true, //must be complete-insert bc postgres gives an error when dumping insert queries without column names.
			'databases' => false,
			'default-character-set' => \"" . (!empty($task_props["encoding"]) ? $task_props["encoding"] : DBDumperHandler::UTF8) . "\",
			'disable-keys' => true,
			'extended-insert' => false,
			'events' => false,
			'hex-blob' => false, //faster than escaped content
			'insert-ignore' => false, 
			'net_buffer_length' => DBDumperHandler::MAX_LINE_SIZE,
			'no-autocommit' => false,
			'no-create-info' => false,
			'lock-tables' => true,
			'routines' => true, //for store procedure
			'single-transaction' => false,
			'skip-triggers' => false,
			'skip-tz-utc' => true,
			'skip-comments' => true,
			'skip-dump-date' => false,
			'skip-definer' => false,
			'where' => '',
		);

		\$DBDumperHandler = new DBDumperHandler(\$DBDriver, \$dump_settings, \$pdo_settings);
		\$DBDumperHandler->connect();
		\$DBDumperHandler->run(\$dump_file_path);

		if (!file_exists(\$dump_file_path))
			exitScript(\"Error: Could not create '\$dump_file_path' file\");
		
		\$DBDumperHandler->disconnect();
	}
}

//connects to DB
try {
	\$connected = \$DBDriver->connect();
}
catch (Exception \$e) {
	\$exception = \$e;
}

if (!\$connected || \$exception) {
	\$msg = \$exception ? (!empty(\$exception->problem) ? \$exception->problem . PHP_EOL : \"\") . \$exception->getMessage() : \"\";
	exitScript(\"Error (3): Could not connect to \$db_type DB Driver: '$task_label'!\" . (\$msg ? \"\\n\" . \$msg : \"\"));
}
";
					if ($migrate_db_schema && !$migrate_db_data) {
						$code .= "
//get the DB schema from server
\$old_tables = \$DBDriver->listTables();

if (\$old_tables) {
	//save tables structure
	foreach (\$old_tables as \$idx => \$table) {
		\$table_name = isset(\$table[\"name\"]) ? \$table[\"name\"] : null;
		\$old_tables[\$idx][\"attributes\"] = \$DBDriver->listTableFields(\$table_name);
	}
	
	//TODO: get views, indexes, triggers, procedures and save them too...
}

//get the DB schema from the local computer
\$new_tables_path = \$deploying_folder_path . \"migrate_dbs/dbsbackup/db_structure.$task_label.json\";
\$new_tables = file_exists(\$new_tables_path) ? json_decode(file_get_contents(\$new_tables_path), true) : null;

//compare both tables arrays, get the differences and add and remove differences
\$new_tables_name = array();
\$dump_backup = false;

if (\$new_tables) {
	\$new_tables_fks = array();
	
	foreach (\$new_tables as \$new_table) {
		\$tn = isset(\$new_table[\"name\"]) ? \$new_table[\"name\"] : null;
		\$fks = isset(\$new_table[\"foreign_keys\"]) ? \$new_table[\"foreign_keys\"] : null;
		\$new_tables_name[] = \$tn;
		\$old_table_detected = null;
		
		foreach (\$old_tables as \$old_table) {
			\$old_table_name = isset(\$old_table[\"name\"]) ? \$old_table[\"name\"] : null;
			
			if (\$DBDriver->isTheSameTableName(\$old_table_name, \$tn)) {
				\$old_table_detected = \$old_table;
				break;
			}
		}
		
		//create new table
		if (!\$old_table_detected) { 
			\$msg = null;
			
			try {
				\$sql = \$DBDriver->getDropTableForeignKeysStatement(\$tn, \$sql_options);
				\$DBDriver->setData(\$sql, array(\"remove_comments\" => true));
				
				\$sql = \$DBDriver->getDropTableStatement(\$tn, \$sql_options);
				\$DBDriver->setData(\$sql, array(\"remove_comments\" => true));
				
				//remove foreign_keys, bc they will be added later.
				unset(\$new_table[\"foreign_keys\"]);
				\$sql = \$DBDriver->getCreateTableStatement(\$new_table, \$sql_options);
				\$created = \$DBDriver->setData(\$sql, array(\"remove_comments\" => true));
				
				//prepare foreign keys to be added later
				if (\$created && \$fks) 
					\$new_tables_fks[\$tn] = \$fks;
			}
			catch(Exception \$e) {
				\$msg = \$e->getMessage();
			}
			
			if (!\$created)
				echo \"\\nError: Could not create table '\$tn'!\" . (\$msg ? \"\\n\" . \$msg : \"\");
		}
		else {
			//compare table attributes
			\$new_attributes = isset(\$new_table[\"attributes\"]) ? \$new_table[\"attributes\"] : null;
			\$old_attributes = isset(\$old_table_detected[\"attributes\"]) ? \$old_table_detected[\"attributes\"] : null;
			\$new_table_attributes_name = array();
			\$attributes_to_add = array();
			\$attributes_to_modify = array();
			\$change_pks = false;
			\$pks = \$pks_attrs = \$auto_increment_pks = array();
			
			foreach (\$new_attributes as \$new_attribute) {
				\$tan = isset(\$new_attribute[\"name\"]) ? \$new_attribute[\"name\"] : null;
				\$new_table_attributes_name[] = \$tan;
				\$old_tan_detected = null;
				
				if (!empty(\$new_attribute[\"primary_key\"])) {
					\$pks[] = \$tan;
					\$pks_attrs[] = \$new_attribute;
				}
				
				foreach (\$old_attributes as \$old_attribute) {
					\$old_attribute_name = isset(\$old_attribute[\"name\"]) ? \$old_attribute[\"name\"] : null;
					
					if (\$old_attribute_name == \$tan) {
						\$old_tan_detected = \$old_attribute;
						break;
					}
				}
				
				//create new attribute
				if (!\$old_tan_detected)
					\$attributes_to_add[] = \$new_attribute;
				else {
					\$new_attribute_type = isset(\$new_attribute[\"type\"]) ? \$new_attribute[\"type\"] : null;
					\$new_attribute_length = isset(\$new_attribute[\"length\"]) ? \$new_attribute[\"length\"] : null;
					\$new_attribute_null = isset(\$new_attribute[\"null\"]) ? \$new_attribute[\"null\"] : null;
					\$new_attribute_unique = isset(\$new_attribute[\"unique\"]) ? \$new_attribute[\"unique\"] : null;
					\$new_attribute_unsigned = isset(\$new_attribute[\"unsigned\"]) ? \$new_attribute[\"unsigned\"] : null;
					\$new_attribute_default = isset(\$new_attribute[\"default\"]) ? \$new_attribute[\"default\"] : null;
					\$new_attribute_charset = isset(\$new_attribute[\"charset\"]) ? \$new_attribute[\"charset\"] : null;
					\$new_attribute_collation = isset(\$new_attribute[\"collation\"]) ? \$new_attribute[\"collation\"] : null;
					\$new_attribute_extra = isset(\$new_attribute[\"extra\"]) ? \$new_attribute[\"extra\"] : null;
					\$new_attribute_comment = isset(\$new_attribute[\"comment\"]) ? \$new_attribute[\"comment\"] : null;
					
					\$old_tan_detected_type = isset(\$old_tan_detected[\"type\"]) ? \$old_tan_detected[\"type\"] : null;
					\$old_tan_detected_length = isset(\$old_tan_detected[\"length\"]) ? \$old_tan_detected[\"length\"] : null;
					\$old_tan_detected_null = isset(\$old_tan_detected[\"null\"]) ? \$old_tan_detected[\"null\"] : null;
					\$old_tan_detected_unique = isset(\$old_tan_detected[\"unique\"]) ? \$old_tan_detected[\"unique\"] : null;
					\$old_tan_detected_unsigned = isset(\$old_tan_detected[\"unsigned\"]) ? \$old_tan_detected[\"unsigned\"] : null;
					\$old_tan_detected_default = isset(\$old_tan_detected[\"default\"]) ? \$old_tan_detected[\"default\"] : null;
					\$old_tan_detected_charset = isset(\$old_tan_detected[\"charset\"]) ? \$old_tan_detected[\"charset\"] : null;
					\$old_tan_detected_collation = isset(\$old_tan_detected[\"collation\"]) ? \$old_tan_detected[\"collation\"] : null;
					\$old_tan_detected_extra = isset(\$old_tan_detected[\"extra\"]) ? \$old_tan_detected[\"extra\"] : null;
					\$old_tan_detected_comment = isset(\$old_tan_detected[\"comment\"]) ? \$old_tan_detected[\"comment\"] : null;
					
					//compare type, length, null, primary_key, unique, unsigned, default, charset, collation, extra, comment
					\$is_at_diff = \$new_attribute_type != \$old_tan_detected_type || \$new_attribute_length != \$old_tan_detected_length || \$new_attribute_null != \$old_tan_detected_null || \$new_attribute_unique != \$old_tan_detected_unique || \$new_attribute_unsigned != \$old_tan_detected_unsigned || \$new_attribute_default != \$old_tan_detected_default || \$new_attribute_charset != \$old_tan_detected_charset || \$new_attribute_collation != \$old_tan_detected_collation || \$new_attribute_extra != \$old_tan_detected_extra || \$new_attribute_comment != \$old_tan_detected_comment;
					
					//add table attribute to modify
					if (\$is_at_diff)
						\$attributes_to_modify[] = \$new_attribute;
					
					//check if PK was changed
					\$new_attribute_primary_key = isset(\$new_attribute[\"primary_key\"]) ? \$new_attribute[\"primary_key\"] : null;
					\$old_tan_detected_primary_key = isset(\$old_tan_detected[\"primary_key\"]) ? \$old_tan_detected[\"primary_key\"] : null;
					
					if (\$new_attribute_primary_key != \$old_tan_detected_primary_key) {
						\$change_pks = true;
						
						//check if old attribute is auto increment pk
						if (\$old_tan_detected_primary_key && (!empty(\$old_tan_detected[\"auto_increment\"]) || stripos(\$old_tan_detected_extra, \"auto_increment\") !== false)) {
							\$old_tan_detected_name = isset(\$old_tan_detected[\"name\"]) ? \$old_tan_detected[\"name\"] : null;
							
							\$auto_increment_pks[\$old_tan_detected_name] = \$old_tan_detected;
						}
					}
				}
			}
			
			\$attrs_with_auto_increment_to_modify = array();
			\$pks_dropped = false;
";
						
						//drop pks before modify attributes bc the drop will remove the sequences contraints and the modify will add them again
						//THIS IS VERY IMPORTANT TO BE HERE BEFORE THE getModifyTableAttributeStatement, OTHERWISE IN POSTGRES, WHEN CHANGING PKS, WE WILL LOOSE THE AUTO_INCREMENT SEQUENCES. 
						//The getDropTablePrimaryKeysStatement should be also before the getAddTableAttributeStatement because this may contain a primary key too, and then we are erasing the that primary key, which is wrong.
						if ($remove_deprecated_tables_and_attributes)
							$code .= "
			//remove PKs
			if (\$change_pks) {
				//check if old PKs exists
				\$exists_pks = false;
				
				/*foreach (\$new_attributes as \$new_attribute) {
					\$new_attribute_name = isset(\$new_attribute[\"name\"]) ? \$new_attribute[\"name\"] : null;
					
					foreach (\$old_attributes as \$old_attribute) {
						\$old_attribute_name = isset(\$old_attribute[\"name\"]) ? \$old_attribute[\"name\"] : null;
						\$old_attribute_primary_key = isset(\$old_attribute[\"primary_key\"]) ? \$old_attribute[\"primary_key\"] : null;
						
						if (\$old_attribute_name == \$new_attribute_name && \$old_attribute_primary_key) {
							\$exists_pks = true;
							break;
						}
					}
					
					if (\$exists_pks)
						break;
				}*/
				foreach (\$old_attributes as \$old_attribute) {
					\$old_attribute_primary_key = isset(\$old_attribute[\"primary_key\"]) ? \$old_attribute[\"primary_key\"] : null;
					
					if (\$old_attribute_primary_key) {
						\$exists_pks = true;
						break;
					}
				}
				
				//drop old PKs if exists
				if (\$exists_pks) {
					//Before removing the pks we must first delete all the auto increments keys, bc I cannot remove pk if there are auto_increment keys in Mysql. To remove the auto_increment key, we only need to execute a modify statement. For more info please check: https://www.techbrothersit.com/2019/01/how-to-drop-or-disable-autoincrement.html
					if (\$auto_increment_pks)
						foreach (\$auto_increment_pks as \$attr_name => \$attr) {
							\$attr[\"auto_increment\"] = false;
							\$attr[\"extra\"] = isset(\$attr[\"extra\"]) ? preg_replace(\"/(^|\s)auto_increment($|\s)/i\", \"\", \$attr[\"extra\"]) : null;
							
							\$sql = \$DBDriver->getModifyTableAttributeStatement(\$tn, \$attr);
							
							if (!\$DBDriver->setData(\$sql))
								echo \"\\nError: Could not remove auto_increment prop from primary keys in table: '\$tn'!\";
						}
					
					\$sql = \$DBDriver->getDropTablePrimaryKeysStatement(\$tn, \$sql_options);
					
					if (!\$DBDriver->setData(\$sql))
						echo \"\\nError: Could not drop table primary keys for table: '\$tn'!\";
					
					\$pks_dropped = true;
				}
			}
";
						
						$code .= "
			//add table attribute
			foreach (\$attributes_to_add as \$new_attribute) {
				\$new_attribute_extra = isset(\$new_attribute[\"extra\"]) ? \$new_attribute[\"extra\"] : null;
				
				//remove auto_increment property bc it can only be added to a KEY (primary key or other key)
				if (!empty(\$new_attribute[\"auto_increment\"]) || stripos(\$new_attribute_extra, \"auto_increment\") !== false) {
					\$attrs_with_auto_increment_to_modify[] = \$new_attribute;
					
					\$new_attribute[\"extra\"] = \$new_attribute_extra ? preg_replace(\"/(^|\s)auto_increment(\s|$)/i\", \" \", \$new_attribute[\"extra\"]) : null;
					\$new_attribute[\"auto_increment\"] = false;
				}
				
				\$tan = isset(\$new_attribute[\"name\"]) ? \$new_attribute[\"name\"] : null;
				\$sql = \$DBDriver->getAddTableAttributeStatement(\$tn, \$new_attribute, \$sql_options);
				
				if (!\$DBDriver->setData(\$sql))
					echo \"\\nError: Could not add table attribute '\$tn.\$tan'!\";
			}
			
			//modify table attribute
			foreach (\$attributes_to_modify as \$new_attribute) {
				\$new_attribute_extra = isset(\$new_attribute[\"extra\"]) ? \$new_attribute[\"extra\"] : null;
				
				//remove auto_increment property bc it can only be added to a KEY (primary key or other key)
				if (\$pks_dropped && (!empty(\$new_attribute[\"auto_increment\"]) || stripos(\$new_attribute_extra, \"auto_increment\") !== false)) {
					\$attrs_with_auto_increment_to_modify[] = \$new_attribute;
					
					\$new_attribute[\"extra\"] = \$new_attribute_extra ? preg_replace(\"/(^|\s)auto_increment(\s|$)/i\", \" \", \$new_attribute[\"extra\"]) : null;
					\$new_attribute[\"auto_increment\"] = false;
				}
				
				\$tan = isset(\$new_attribute[\"name\"]) ? \$new_attribute[\"name\"] : null;
				\$sql = \$DBDriver->getModifyTableAttributeStatement(\$tn, \$new_attribute, \$sql_options);
				
				if (!\$DBDriver->setData(\$sql))
					echo \"\\nError: Could not modify table attribute '\$tn.\$tan'!\";
			}
			
			//add new PKs if exists
			if (\$change_pks && \$pks) {
				\$sql = \$DBDriver->getAddTablePrimaryKeysStatement(\$tn, \$pks_attrs, \$sql_options);
				
				if (!\$DBDriver->setData(\$sql))
					echo \"\\nError: Could not add table primary keys for table: '\$tn' with attributes: '\" . implode(\"', '\", \$pks) . \"'!\";
			}
			
			//add auto_increment to attrs after the getAddTablePrimaryKeysStatement gets executed
			if (\$attrs_with_auto_increment_to_modify)
				foreach (\$attrs_with_auto_increment_to_modify as \$attr) {
					\$sql = \$DBDriver->getModifyTableAttributeStatement(\$tn, \$attr, \$sql_options);
					
					if (!\$DBDriver->setData(\$sql))
						echo \"\\nError: Could not modify table attribute '\$tn.\$tan' adding auto_increment property!\";
				}
";
						if ($remove_deprecated_tables_and_attributes)
							$code .= "			
			//remove old table attributes
			foreach (\$old_attributes as \$old_attribute)
				if (!empty(\$old_attribute[\"name\"]) && !in_array(\$old_attribute[\"name\"], \$new_table_attributes_name)) {
					//remove old attribute
					\$sql = \$DBDriver->getDropTableAttributeStatement(\$tn, \$old_attribute[\"name\"], \$sql_options);
					
					if (!\$DBDriver->setData(\$sql))
						echo \"\\nError: Could not drop table attribute '\$tn.\" . \$old_attribute[\"name\"] . \"'!\";
				}
";	
						
						$code .= "			
		}
			
	}
	
	//add foreign keys if exists any...
	foreach (\$new_tables_fks as \$tn => \$fks) 
		if (\$fks) {
			foreach (\$fks as \$fk) {
				\$msg = null;
				\$created = true;
				
				try {
					\$sql = \$DBDriver->getAddTableForeignKeyStatement(\$tn, \$fk, \$sql_options);
					
					if (!\$DBDriver->setData(\$sql, array(\"remove_comments\" => true)))
						\$created = false;
				}
				catch(Exception \$e) {
					\$msg = \$e->getMessage();
				}
				
				if (!\$created)
					echo \"\\nError: Could not add foreign key to table '\$tn'!\" . (\$msg ? \"\\n\" . \$msg : \"\");
			}
		}
";	
						if ($remove_deprecated_tables_and_attributes)
							$code .= "
	
//remove old tables
foreach (\$old_tables as \$old_table) 
	if (!empty(\$old_table[\"name\"]) && !\$DBDriver->isTableInNamesList(\$new_tables_name, \$old_table[\"name\"])) {
		\$sql_1 = \$DBDriver->getDropTableForeignKeysStatement(\$old_table[\"name\"], \$sql_options);
		\$sql_2 = \$DBDriver->getDropTableStatement(\$old_table[\"name\"], \$sql_options);
		
		if (!\$DBDriver->setData(\$sql_1) || !\$DBDriver->setData(\$sql_2))
			echo \"\\nError: Table '\" . \$old_table[\"name\"] . \"' in '$task_label' DB could not be removed!\";
	}

";	
					}
					else if ($migrate_db_data) {
						if ($migrate_db_schema)
							$code .= "
//remove old tables since we will dump the new schema
/* DO NOT EXECUTE THIS CODE, bc if a table has a foreign key to another table and we try to remove it, it won't work and we will get a DB error, if that Foreign Key is restrict on delete. The dbsqldump_schema.$task_label.sql file already contains the proper code to remove this tables, this is, first removes the foreign keys and then remove the table. This means that this code is obsulete and deprecated!
\$old_tables = \$DBDriver->listTables();
if (\$old_tables)
	foreach (\$old_tables as \$old_table) 
		if (!empty(\$old_table[\"name\"])) {
			\$sql_1 = \$DBDriver->getDropTableForeignKeysStatement(\$old_table[\"name\"], \$sql_options);
			\$sql_2 = \$DBDriver->getDropTableStatement(\$old_table[\"name\"], \$sql_options);
			
			if (!\$DBDriver->setData(\$sql_1) || !\$DBDriver->setData(\$sql_2))
				echo \"\\nError: Table '\" . \$old_table[\"name\"] . \"' in '$task_label' DB could not be removed!\";
		}
*/

//load dbsqldump_schema.$task_label.sql (to migrate the latest db schema)
\$dbsqldump_schema_path = \$deploying_folder_path . \"migrate_dbs/dbsbackup/dbsqldump_schema.$task_label.sql\";

if (!file_exists(\$dbsqldump_schema_path))
	exitScript(\"\\nError: File dbsqldump_schema.$task_label.sql does not exists!\");

\$contents = file_get_contents(\$dbsqldump_schema_path);
\$imported = true;
\$msg = \"\";

try {
	\$imported = \$DBDriver->setData(\$contents, array(\"remove_comments\" => true)); //This must be executed in a batch (this is, all sqls together) bc we may have store procedures or other sql commands that can only take effect if executed together in the same sql session. SO PLEASE DO NOT SPLIT THE SQL STATEMENTS!
}
catch(Exception \$e) {
	\$exception = \$e;
}

if (!\$imported || \$exception) {
	\$msg = \$exception ? PHP_EOL . (!empty(\$exception->problem) ? \$exception->problem . PHP_EOL : \"\") . \$exception->getMessage() : \"\";
	exitScript(\"\\nError: Could not migrate schema for DB '$task_label'!\" . (\$msg ? \"\\n\" . \$msg : \"\"));
}
";
					
						$code .= "
//load the dbsqldump_data.$task_label.sql (to copy the db data)
//only insert the new values and do not replace the existent values. This file has insert ignore! If we wish to replace all data, we execute first the dbsqldump_schema.$task_label.sql and then dbsqldump_data.$task_label.sql.
\$dbsqldump_data_path = \$deploying_folder_path . \"migrate_dbs/dbsbackup/dbsqldump_data.$task_label.sql\";

if (!file_exists(\$dbsqldump_data_path))
	exitScript(\"\\nError: File dbsqldump_data.$task_label.sql does not exists!\");

\$contents = file_get_contents(\$dbsqldump_data_path);
\$msg = \"\";

try {
	\$imported = \$DBDriver->setData(\$contents, array(\"remove_comments\" => true)); //This must be executed in a batch (this is, all sqls together) bc we may have store procedures or other sql commands that can only take effect if executed together in the same sql session. SO PLEASE DO NOT SPLIT THE SQL STATEMENTS!
}
catch(Exception \$e) {
	\$exception = \$e;
}

if (!\$imported || \$exception) {
	\$msg = \$exception ? PHP_EOL . (!empty(\$exception->problem) ? \$exception->problem . PHP_EOL : \"\") . \$exception->getMessage() : \"\";
	exitScript(\"\\nError: Could not migrate data for DB '$task_label'!\" . (\$msg ? \"\\n\" . \$msg : \"\"));
}
";
					}
					
					$code .= "
\$DBDriver->disconnect();
";
				}
			}
			
			$code .= "
//remove deploying folder 
removeFile(\$deploying_folder_path);

" . $this->getRemoteServerExitScriptPHPCode() . "
" . $this->getRemoteServerGetLibFunctionPHPCode() . "
" . $this->getRemoteServerGetLaunchExceptionFunctionPHPCode() . "
" . $this->getRemoteServerGetDebugLogFunctionFunctionPHPCode() . "
" . $this->getRemoteServerGetDebugLogFunctionPHPCode() . "
" . $this->getRemoteServerRemoveFileFunctionPHPCode();
		}
		
		$code .= "
exitScript(); //terminate script without errors
?>";
		return file_put_contents($php_file, $code) !== false;
	}
	
	private function createRemoteServerUpdateWordPressSettingsPHPFile($server_template, $php_file, $error_messages) {
		//Loops for all sub-folders of $wordpress_installations_folder_path, reads the DB credentials in wp-config.php, connects to the DB, and update the wordpress siteurl and home wp_options and updates the .htaccess file with the correct uri path.
		$server_installation_folder_path = isset($server_template["properties"]["server_installation_folder_path"]) ? $server_template["properties"]["server_installation_folder_path"] : null;
		$server_installation_url = isset($server_template["properties"]["server_installation_url"]) ? $server_template["properties"]["server_installation_url"] : null;
		
		if (substr($server_installation_folder_path, -1) != "/")
			$server_installation_folder_path .= "/";
		
		//prepare server_installation_url
		$server_installation_url = (strpos($server_installation_url, "://") === false ? "http://" : "") . $server_installation_url;
		$server_installation_url .= substr($server_installation_url, -1) != "/" ? "/" : "";
		$parsed_url = parse_url($server_installation_url);
		unset($parsed_url["user"]);
		unset($parsed_url["pass"]);
		unset($parsed_url["query"]);
		unset($parsed_url["fragment"]);
		$server_installation_url = $this->unparseUrl($parsed_url);
		
		$code = '<?php
$layer_folder_name = isset($argv[1]) ? $argv[1] : null;
$wordpress_installation = isset($argv[2]) ? $argv[2] : null;

if ($wordpress_installation) {
	$wordpress_folder_path = "' . $server_installation_folder_path . 'app/layer/$layer_folder_name/common/webroot/' . WordPressUrlsParser::WORDPRESS_FOLDER_PREFIX . '/$wordpress_installation/";
	$wordpress_url = "' . ($server_installation_url ? $server_installation_url . 'common/' . WordPressUrlsParser::WORDPRESS_FOLDER_PREFIX . '/$wordpress_installation' : '') . '";
	
	if (!file_exists($wordpress_folder_path)) 
		echo "\'$wordpress_folder_path\' folder does not exists!";
	else if (!$wordpress_url) 
		echo "wordpress_url var cannot be empty!";
	else {
		$status = true;
		$error_message = "";
		
		//include wordpress lib
		require $wordpress_folder_path . "wp-load.php";
		
		//check if the WP_HOME and WP_SITEURL are the same than the $wordpress_url. If not, it means that the wordpress was moved through the deployment process and it should be updated before it continues
		$wp_home_url = get_option("home");
		$wp_site_url = get_option("siteurl");
		
		//prepare $wordpress_url with right protocol;
		if (empty(parse_url($wordpress_url, PHP_URL_SCHEME))) {
			$protocol = parse_url($wp_home_url, PHP_URL_SCHEME);
			
			if (!$protocol)
				$protocol = parse_url($wp_site_url, PHP_URL_SCHEME);
			
			if ($protocol)
				$wordpress_url = $protocol . "://" . $wordpress_url;
		}
		
		if ($wordpress_url != $wp_home_url || $wordpress_url != $wp_site_url) {
			//update site_url and home url in the wordpress DB, otherwise will have this file url as default and wordpress will gets reinstalled, loosing all its hacks that the system did on the installation process...
			update_option("siteurl", $wordpress_url);
			update_option("home", $wordpress_url);
			
			//error if update did not run correctly
			if ($wordpress_url != get_option("home") || $wordpress_url != get_option("siteurl")) {
				$status = false;
				
				$error_message = "Could not automatically update the new URL in the DB of the WordPress installation: \'$wordpress_installation\'! Please try again or contact the system administrator...";
			}
			
			//check the wordpress/.htaccess file to see if contains the host or uri that do not correspond to the $wordpress_url
			$htaccess_fp = $wordpress_folder_path . ".htaccess";
			
			if (file_exists($htaccess_fp)) {
				$htaccess_contents = file_get_contents($htaccess_fp);
				
				$new_url_parts = parse_url($wordpress_url);
				$old_url_parts = parse_url($wordpress_url != $wp_home_url ? $wp_home_url : $wp_site_url);
				
				//remove last slash so the paths be sanitized
				if (substr($new_url_parts["path"], -1) == "/")
					$new_url_parts["path"] = substr($new_url_parts["path"], 0, -1);
				
				if (substr($old_url_parts["path"], -1) == "/")
					$old_url_parts["path"] = substr($old_url_parts["path"], 0, -1);
				
				//replace htaccess with new host and path
				$new_url_parts_host = isset($new_url_parts["host"]) ? $new_url_parts["host"] : null;
				$old_url_parts_host = isset($old_url_parts["host"]) ? $old_url_parts["host"] : null;
				
				if ($new_url_parts_host != $old_url_parts_host && strpos($htaccess_contents, $old_url_parts_host) !== false)
					$htaccess_contents = str_replace($old_url_parts_host, $new_url_parts_host, $htaccess_contents);
				
				if ($new_url_parts["path"] != $old_url_parts["path"] && strpos($htaccess_contents, $old_url_parts["path"]) !== false)
					$htaccess_contents = str_replace($old_url_parts["path"], $new_url_parts["path"], $htaccess_contents);
				
				if (file_put_contents($htaccess_fp, $htaccess_contents) === false) {
					$status = false;
					
					$error_message = ($error_message ? "\n" : "") . "Could not automatically update the new URL in the .htaccess of the WordPress installation: \'$wordpress_installation\'! Please try again or contact the system administrator...";
				}
			}
			
			//flush wordpress cache
			//flush_rewrite_rules();
			wp_clean_update_cache();
			wp_cache_flush();
		}
		
		echo $status ? 1 : $error_message;
	}
}
?>';
		
		return file_put_contents($php_file, $code) !== false;
	}
	
	private function getRemoteServerExitScriptPHPCode() {
		return "
function exitScript(\$msg = null) {
	if (\$msg) {
		echo \$msg;
		exit(1);
	}
	
	echo 1;
	exit();
}
";
	}
	
	private function getRemoteServerGetLibFunctionPHPCode() {
		return "
function get_lib(\$str) {
	global \$lib_folder_path;
	\$str = preg_replace(\"/^lib(\\.|\\/)/\", \"\", \$str); //remove first lib prefix, bc all files will be already added from the lib_folder_path
	return \$lib_folder_path . str_replace(\".\", \"/\", \$str) . \".php\";
}
";
	}
	
	private function getRemoteServerGetLaunchExceptionFunctionPHPCode() {
		return "
function launch_exception(Exception \$exception) {
	throw \$exception;
	return false;
}
";
	}
	
	private function getRemoteServerGetDebugLogFunctionFunctionPHPCode() {
		return "
function debug_log_function(\$func, \$args, \$log_type = \"debug\") {
	\$message = \$func . \"(\";
	
	if (is_array(\$args))
		foreach(\$args as \$arg) {
			\$message .= \$message ? \", \" : \"\";
			
			if (is_array(\$arg)) 
				\$message .= stripslashes(json_encode(\$arg));
			else if (is_object(\$arg)) 
				\$message .= \"Object(\" . get_class(\$arg) . \")\";
			else if (\$arg === true)
				\$message .= \"true\";
			else if (\$arg === false) 
				\$message .= \"false\";
			else if (\$arg == null)
				\$message .= \"null\";
			else if (is_numeric(\$arg)) 
				\$message .= (int)\$arg;
			else 
				\$message .= \"'\" . \$arg . \"'\";
		}
		
	\$message .= \")\";
	debug_log(\$message);
}
";
	}
	
	private function getRemoteServerGetDebugLogFunctionPHPCode() {
		return "
function debug_log(\$message, \$log_type = \"debug\") {
	if (\$log_type == \"exception\" || \$log_type == \"error\")
		error_log(\"[\" . date(\"Y-m-d H:i:s\") . \"][\$log_type] \$message\");
}
";
	}
	
	private function getRemoteServerRemoveFileFunctionPHPCode() {
		return "
function removeFile(\$path) {
	\$status = true;
	
	if (\$path) {
		if (is_dir(\$path)) {
			\$files = array_diff(scandir(\$path), array('.', '..'));
			
			foreach (\$files as \$file)
				if (!removeFile(\"\$path/\$file\"))
					\$status = false;
			
			if (\$status)
				\$status = rmdir(\$path);
		}
		else if (file_exists(\$path) && !unlink(\$path))
			\$status = false;
	}
	return \$status;
}
";
	}
	
	private function getRemoteServerGetFolderFilesPermissionsFunctionPHPCode() {
		return "
function getFolderFilesPermissions(\$path, \$key = \"\", \$this_file_uid = null) {
	\$perms = array();
		
	if (\$path) {
		\$file_path = \"\$path/\$key\";
		
		if (file_exists(\$file_path)) {
			\$file_perm = fileperms(\$file_path) & 0777;
			\$file_uid = fileowner(\$file_path); //this is the file owner user id
			\$this_file_uid = \$this_file_uid ? \$this_file_uid : fileowner(__FILE__); //this is the ftp user like jplpinto
			
			//if file's owner is different than this_file_uid, we set the perm to 777, bc we cannot change the user owner. Var \$this_file_uid is jplpinto!
			if (\$this_file_uid != \$file_uid) 
				\$file_perm = 0777;
				
			\$perms[ \$key ? \$key : \"/\" ] = \"0\" . decoct(\$file_perm);
			
			if (is_dir(\$file_path)) {
				\$files = scandir(\$file_path);
				
				if (\$files)
					foreach (\$files as \$file)
						if (!in_array(\$file, " . self::getInvalidFilesVarExport() . ")) 
							\$perms = array_merge(\$perms, getFolderFilesPermissions(\$path, \"\$key/\$file\", \$this_file_uid));
			}
		}
	}
	
	return \$perms;
}
";
	}
	
	/* GLOBAL SETTINGS UTILS */
	
	private function setGlobalSettings($user_global_settings_file_path, $global_settings) {
		return PHPVariablesFileHandler::saveVarsToFile($user_global_settings_file_path, $global_settings);
	}
	
	/* GLOBAL VARIABLES UTILS */
	
	private function convertGlobalVariables($global_vars) {
		$global_variables = array();
		
		//prepare global_variables (key-value pair)
		$vars_name = isset($global_vars["vars_name"]) ? $global_vars["vars_name"] : null;
		$vars_value = isset($global_vars["vars_value"]) ? $global_vars["vars_value"] : null;
		
		if ($vars_name) {
			if (!is_array($vars_name)) {
				$vars_name = array($vars_name);
				$vars_value = array($vars_value);
			}
			
			$t = count($vars_name);
			for($i = 0; $i < $t; $i++)
				$global_variables[ $vars_name[$i] ] = isset($vars_value[$i]) ? $vars_value[$i] : null;
		}
		
		/* DO NOT ADD HERE THE MISSING GLOBAL VARIABLES, BC THE DEPLOYMENT UI ALREADY GAVE THIS CHANGE TO THE USER AND IF THERE ARE MISSING GLOBAL VARIABLES AT THIS POINT, IT'S ON PURPOSE, THIS IS, IT'S THE USER CHOICE TO NOT INCLUDE ALL GLOBAL VARIABLES!
		//Set the missing global vars from the original layers diagram
		$vars = PHPVariablesFileHandler::getVarsFromFileContent($this->user_global_variables_file_path);
		
		if (is_array($vars)) 
			foreach ($vars as $name => $value)
				if (!array_key_exists($name, $global_variables))
					$global_variables[$name] = $value;
		*/
		return $global_variables;
	}
	
	private function setGlobalVariables($user_global_variables_file_path, $global_vars) {
		$global_variables = $this->convertGlobalVariables($global_vars);
		return PHPVariablesFileHandler::saveVarsToFile($user_global_variables_file_path, $global_variables, true);
	}
	
	/* NEW LAYERS DIAGRAM UTILS */
	
	private function setLayersBeansDiagram($diagram_path, $tasks_props, $connections_props) {
		//prepare layers tasks
		$new_layer_tasks = $this->layer_tasks;
		
		if ($new_layer_tasks && !empty($new_layer_tasks["tasks"])) {
			$tasks_id_to_remove = array();
			$dbdriver_task_type = isset($this->template_tasks_types_by_tag["dbdriver"]) ? $this->template_tasks_types_by_tag["dbdriver"] : null;
			
			//remove inactive dbdrivers tasks. Leave all the other tasks bc the user can activate them later on. Note that the others inactive layers will be empty.
			foreach ($new_layer_tasks["tasks"] as $task_id => $task) {
				$task_label = isset($task["label"]) ? $task["label"] : null;
				$task_props = isset($tasks_props[$task_label]) ? $tasks_props[$task_label] : null;
				$task_props = $task_props ? $task_props : array();
				$task_type = isset($task["type"]) ? $task["type"] : null;
				
				if (empty($task_props["active"]) && $task_type == $dbdriver_task_type) {
					$tasks_id_to_remove[] = $task_id;
					unset($new_layer_tasks["tasks"][$task_id]);
				}
			}
			
			//prepare tasks with deployment properties
			foreach ($new_layer_tasks["tasks"] as $task_id => $task) {
				$task_label = isset($task["label"]) ? $task["label"] : null;
				$task_props = isset($tasks_props[$task_label]) ? $tasks_props[$task_label] : null;
				$task_props = $task_props ? $task_props : array();
				$task_type = isset($task["type"]) ? $task["type"] : null;
				
				//prepare dbdrivers properties
				if ($task_type == $dbdriver_task_type && $task_props) {
					if (!empty($task["properties"])) {
						foreach ($task["properties"] as $k => $v)
							if (array_key_exists($k, $task_props))
								$new_layer_tasks["tasks"][$task_id]["properties"][$k] = $task_props[$k];
					}
					else {
						unset($task_props["migrate_db_schema"]);
						unset($task_props["remove_deprecated_tables_and_attributes"]);
						unset($task_props["migrate_db_data"]);
						$new_layer_tasks["tasks"][$task_id]["properties"] = $task_props;
					}
				}
				
				//prepare active property
				if (array_key_exists("active", $task_props))
					$new_layer_tasks["tasks"][$task_id]["properties"]["active"] = $task_props["active"];
				
				//prepare default layer, this is if layer is start it means is the default layer
				$new_layer_tasks["tasks"][$task_id]["start"] = array_key_exists("start", $task_props) && $task_props["start"] ? 1 : 0;
				
				//prepare exits
				if (!empty($task["exits"]["layer_exit"])) {
					$exits = $task["exits"]["layer_exit"];
					
					if (!empty($exits["task_id"]))
						$exits = array($exits);
					
					foreach ($exits as $exit_name => $e) {
						$exit_task_id = isset($e["task_id"]) ? $e["task_id"] : null;
						
						if (in_array($exit_task_id, $tasks_id_to_remove)) 
							unset($exits[$exit_name]);
						else {
							$t = isset($this->layer_tasks["tasks"][$exit_task_id]) ? $this->layer_tasks["tasks"][$exit_task_id] : null;
							$t_label = isset($t["label"]) ? $t["label"] : null;
							
							if (!empty($connections_props[$task_label]) && array_key_exists($t_label, $connections_props[$task_label]))
								$exits[$exit_name]["properties"] = $connections_props[$task_label][$t_label];
						}
					}
					
					$new_layer_tasks["tasks"][$task_id]["exits"]["layer_exit"] = $exits;
				}
			}
		}
		
		//save new layers_diagram.xml based in the $new_layer_tasks
		//echo "diagram_path:$diagram_path\n";print_r($new_layer_tasks);die();
		return WorkFlowTasksFileHandler::createTasksFile($diagram_path, $new_layer_tasks);
	}
	
	//create new beans based in the new layer tasks from the $diagram_path
	private function setLayersBeans($deployment_folder_path, $user_global_variables_file_path, $user_global_settings_file_path, $diagram_path) {
		$user_beans_folder_path = $deployment_folder_path . "app/config/bean/";
		
		$global_paths = array(
			"LAYER_CACHE_PATH" => $deployment_folder_path . "tmp/cache/layer/",
			"LAYER_PATH" => $deployment_folder_path . "app/layer/",
			"SYSTEM_LAYER_PATH" => $deployment_folder_path . "app/__system/layer/",
			"BEAN_PATH" => $user_beans_folder_path,
		);
		
		$WorkFlowBeansConverter = new WorkFlowBeansConverter($diagram_path, $user_beans_folder_path, $user_global_variables_file_path, $user_global_settings_file_path, $global_paths);
		$WorkFlowBeansConverter->init();
		$status = $WorkFlowBeansConverter->createBeans();
		
		return $status;
	}
	
	//prepare files according with layer type (presentation must include common project, businesslogic include common and module, etc...)
	private function prepareLayerTypeFiles($task_type, $orig_layer_path, &$files) {
		if ($files) { //if not files, the layer's folder will be copied totally
			//add default files from layer - DO NOT DO THIS BC IN THE "SERVER TEMPLATE DIAGRAM" IS GIVING THE OPTION TO THE USER DECIDE IF HE WISHES TO INCLUDE THE COMMON AND MODULE FOLDERS
			/*switch($task_type) {
				case $this->template_tasks_types_by_tag["db"]:
					$files = null; //db layer should not have any selected files, bc it should be copied with all files
					return; //exit from this  method so it doesn't add the files bellow
				case $this->template_tasks_types_by_tag["dataaccess"]:
					$this->addFileIfDoesNotExists("module", $files);
					break;
				case $this->template_tasks_types_by_tag["businesslogic"]:
					$this->addFileIfDoesNotExists("module", $files);
					$this->addFileIfDoesNotExists("common", $files);
					break;
				case $this->template_tasks_types_by_tag["presentation"]:
					$this->addFileIfDoesNotExists("common", $files);
					break;
			}*/
			
			//add all files from layer
			$orig_files = is_dir($orig_layer_path) ? scandir($orig_layer_path) : null;
			
			if ($orig_files)
				foreach ($orig_files as $f)
					if (!in_array($f, self::$invalid_files) && !is_dir("$orig_layer_path/$f")) 
						$this->addFileIfDoesNotExists($f, $files);
		}
	}
	
	private function addFileIfDoesNotExists($file, &$files) {
		$exists = in_array($file, $files);
		
		if (!$exists)
			foreach ($files as $f) {
				$f = trim($f);
				$f = substr($f, 0, 1) == "/" ? substr($f, 1) : $f;
				$f = substr($f, -1) == "/" ? substr($f, 0, -1) : $f;
				
				if ($f == $file) {
					$exists = true;
					break;
				}
			}
		
		if (!$exists)
			$files[] = $file;
	}
	
	/* DBS BACKUP UTILS */
	
	private function setDBsBackups($dbs_backup_folder_path, $tasks_props, &$error_messages) {
		$status = true;
		$dbs_backup_folder_path .= substr($dbs_backup_folder_path, -1) != "/" ? "/" : "";
		
		$tasks = $this->LayerWorkFlowTasksFileHandler->getTasksByLayerTag("dbdriver");
		$WorkFlowDBHandler = new WorkFlowDBHandler($this->user_beans_folder_path, $this->user_global_variables_file_path);
		
		if (!is_dir($dbs_backup_folder_path))
			mkdir($dbs_backup_folder_path, 0755, true);
		
		if (!is_dir($dbs_backup_folder_path)) {
			$status = false;
			$error_messages[] = "Error: Could not create dbs_backup_folder_path: .dbsbackup";
		}
		else
			foreach ($tasks as $task) {
				$task_label = isset($task["label"]) ? $task["label"] : null;
				$task_props = isset($tasks_props[$task_label]) ? $tasks_props[$task_label] : null;
				$active = $task_props && !empty($task_props["active"]);
				$migrate_db_schema = isset($task_props["migrate_db_schema"]) ? $task_props["migrate_db_schema"] : null;
				$migrate_db_data = isset($task_props["migrate_db_data"]) ? $task_props["migrate_db_data"] : null;
				
				if ($active && ($migrate_db_schema || $migrate_db_data)) {
					$beans_file_name = WorkFlowBeansConverter::getFileNameFromRawLabel($task_label) . "_dbdriver.xml";
					$object = $WorkFlowDBHandler->getBeanObject($beans_file_name, $task_label);
					
					if ($object) {
						$status = $object->connect();
						
						if ($status) {
							$tables = $object->listTables();
							
							if ($tables) {
								if (!DBDumper::isValid($object)) {
									$status = false;
									$object->disconnect();
								}
								else {
									$db_options = $object->getOptions();
									$pdo_settings = !empty($db_options["persistent"]) && empty($db_options["new_link"]) ? array(PDO::ATTR_PERSISTENT => true) : array();
									$DBDumperHandler = null;
									
									if ($migrate_db_schema || $migrate_db_data) {
										//save tables structure
										foreach ($tables as $idx => $table) 
											if (!empty($table["name"])) {
												$tables[$idx]["attributes"] = $object->listTableFields($table["name"]);
												$tables[$idx]["foreign_keys"] = $object->listForeignKeys($table["name"]);
											}
										
										//TODO: get views, indexes, triggers, procedures and save them too...
										
										$object->disconnect();
										
										$db_structure_file_path = "$dbs_backup_folder_path/db_structure.$task_label.json";
										
										if (file_put_contents($db_structure_file_path, json_encode($tables)) === false)
											$status = false;
										
										//save schema sql
										$dump_settings = array(
											'include-tables' => array(),
											'exclude-tables' => array(),
											'include-views' => array(),
											'compress' => DBDumperHandler::NONE,
											'no-data' => true,
											'reset-auto-increment' => false,
											'add-drop-database' => false,
											'add-drop-table' => true,
											'add-drop-trigger' => false,
											'add-drop-routine' => true,
											'add-drop-event' => false,
											'add-locks' => true,
											'complete-insert' => false,
											'databases' => false,
											'default-character-set' => !empty($db_options["encoding"]) ? $db_options["encoding"] : DBDumperHandler::UTF8,
											'disable-keys' => true,
											'extended-insert' => false,
											'events' => false,
											'hex-blob' => false, //faster than escaped content 
											'insert-ignore' => false,
											'net_buffer_length' => DBDumperHandler::MAX_LINE_SIZE,
											'no-autocommit' => false,
											'no-create-info' => false,
											'lock-tables' => true,
											'routines' => true, //for store procedure
											'single-transaction' => false,
											'skip-triggers' => false,
											'skip-tz-utc' => true,
											'skip-comments' => true,
											'skip-dump-date' => false,
											'skip-definer' => false,
											'where' => '',
										);
										
										$dump_file_path = "$dbs_backup_folder_path/dbsqldump_schema.$task_label.sql";
										
										$DBDumperHandler = new DBDumperHandler($object, $dump_settings, $pdo_settings);
										$DBDumperHandler->connect();
										$DBDumperHandler->run($dump_file_path);
										
										if (!file_exists($dump_file_path))
											$status = false;
									}
									else
										$object->disconnect();
									
									if ($migrate_db_data) {
										$dump_settings = array(
											'include-tables' => array(),
											'exclude-tables' => array(),
											'include-views' => array(),
											'compress' => DBDumperHandler::NONE,
											'no-data' => false,
											'reset-auto-increment' => false,
											'add-drop-database' => false,
											'add-drop-table' => false,
											'add-drop-trigger' => false,
											'add-drop-routine' => false,
											'add-drop-event' => false,
											'add-locks' => false,
											'complete-insert' => true, //must be complete-insert bc postgres gives an error when dumping insert queries without column names.
											'databases' => false,
											'default-character-set' => !empty($db_options["encoding"]) ? $db_options["encoding"] : DBDumperHandler::UTF8,
											'disable-keys' => true,
											'extended-insert' => false,
											'events' => false,
											'hex-blob' => false, //faster than escaped content
											'insert-ignore' => true, //must be true, bc if we migrate the DB Data without migrating the DB Schema, we don't want to replace the existent values, but only add the new ones. In case we wish to insert all new data, we run first the DB Schema (dbsqldump_schema.$task_label.sql) and then the DB Data (dbsqldump_data.$task_label.sql) files.
											'net_buffer_length' => DBDumperHandler::MAX_LINE_SIZE,
											'no-autocommit' => false,
											'no-create-info' => true,
											'lock-tables' => true,
											'routines' => false,
											'single-transaction' => false,
											'skip-triggers' => true,
											'skip-tz-utc' => true,
											'skip-comments' => true,
											'skip-dump-date' => false,
											'skip-definer' => false,
											'where' => '',
										);
										
										$dump_file_path = "$dbs_backup_folder_path/dbsqldump_data.$task_label.sql";
										
										if ($DBDumperHandler)
											$DBDumperHandler->setDBDumperSettings($dump_settings);
										else {
											$DBDumperHandler = new DBDumperHandler($object, $dump_settings, $pdo_settings);
											$DBDumperHandler->connect();
										}
										
										$DBDumperHandler->run($dump_file_path);
										
										if (!file_exists($dump_file_path))
											$status = false;
									}
									
									if ($DBDumperHandler)
										$DBDumperHandler->disconnect();
								}
							}
							else
								$object->disconnect();
						}
						else
							$error_messages[] = "Error: Could not connect to DB Driver $task_label!";
					}
				}
			}
		
		return $status;
	}
	
	/* CREATE LICENCE UTILS */
	
	private function createAppLicence($deployment_folder_path, $create_licence_settings, &$error_messages) {
		$keys_file = isset($create_licence_settings["keys_file"]) ? $create_licence_settings["keys_file"] : null;
		$private_key = isset($create_licence_settings["private_key"]) ? $create_licence_settings["private_key"] : null;
		$public_key = isset($create_licence_settings["public_key"]) ? $create_licence_settings["public_key"] : null;
		$private_key_file = isset($create_licence_settings["private_key_file"]) ? $create_licence_settings["private_key_file"] : null;
		$public_key_file = isset($create_licence_settings["public_key_file"]) ? $create_licence_settings["public_key_file"] : null;
		$passphrase = isset($create_licence_settings["passphrase"]) ? $create_licence_settings["passphrase"] : null;
		
		//avoids user to find this code from grepping the files system
		$projects_expiration_date = isset($create_licence_settings["pro" . 'jects' . "_expira" . 'tion_d' . "ate"]) ? $create_licence_settings["pro" . 'jects' . "_expira" . 'tion_d' . "ate"] : null;
		$sysadmin_expiration_date = isset($create_licence_settings["sy" . 'sad' . "min_" . "expi" . 'ration' . "_date"]) ? $create_licence_settings["sy" . 'sad' . "min_" . "expi" . 'ration' . "_date"] : null;
		$projects_maximum_number = isset($create_licence_settings["pr" . 'oject' . "s_maxi" . 'mum_nu' . "mber"]) ? $create_licence_settings["pr" . 'oject' . "s_maxi" . 'mum_nu' . "mber"] : null;
		$users_maximum_number = isset($create_licence_settings["us" . 'er' . "s_maxi" . 'mum_nu' . "mber"]) ? $create_licence_settings["us" . 'er' . "s_maxi" . 'mum_nu' . "mber"] : null;
		$end_users_maximum_number = isset($create_licence_settings["e" . "nd" . "_us" . 'er' . "s_maxi" . 'mum_nu' . "mber"]) ? $create_licence_settings["e" . "nd" . "_us" . 'er' . "s_maxi" . 'mum_nu' . "mber"] : null;
		$actions_maximum_number = isset($create_licence_settings["ac" . 'tion' . "s_maxi" . 'mum_nu' . "mber"]) ? $create_licence_settings["ac" . 'tion' . "s_maxi" . 'mum_nu' . "mber"] : null;
		$allowed_paths = isset($create_licence_settings["al" . "low" . "ed_pa" . "ths"]) ? $create_licence_settings["al" . "low" . "ed_pa" . "ths"] : null;
		$allowed_domains = isset($create_licence_settings["al" . "low" . "ed_do" . "mains"]) ? $create_licence_settings["al" . "low" . "ed_do" . "mains"] : null;
		$check_allowed_domains_port = isset($create_licence_settings["chec" . "k_al" . "low" . "ed_do" . "mai" . "ns_p" . "ort"]) ? $create_licence_settings["chec" . "k_al" . "low" . "ed_do" . "mai" . "ns_p" . "ort"] : null;
		$allowed_sysadmin_migration = isset($create_licence_settings["sy" . "sadmi" . "n"]) ? $create_licence_settings["sy" . "sadmi" . "n"] : null;
		
		//error_log("sysadmin_expiration_date:$sysadmin_expiration_date\nprojects_maximum_number:$projects_maximum_number\nprojects_expiration_date:$projects_expiration_date\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		//prepare licence options, by changing the options with the correct values according with the current licence.
		$licence_projects_expiration_date = isset($this->licence_data["ped"]) ? $this->licence_data["ped"] : null;
		$licence_sysadmin_expiration_date = isset($this->licence_data["sed"]) ? $this->licence_data["sed"] : null;
		$licence_projects_maximum_number = isset($this->licence_data["pmn"]) ? $this->licence_data["pmn"] : null;
		$licence_users_maximum_number = isset($this->licence_data["umn"]) ? $this->licence_data["umn"] : null;
		$licence_end_users_maximum_number = isset($this->licence_data["eumn"]) ? $this->licence_data["eumn"] : null;
		$licence_actions_maximum_number = isset($this->licence_data["amn"]) ? $this->licence_data["amn"] : null;
		$licence_allowed_paths = isset($this->licence_data["ap"]) ? $this->licence_data["ap"] : null;
		$licence_allowed_sysadmin_migration = isset($this->licence_data["asm"]) ? $this->licence_data["asm"] : null;
		
		if ($licence_projects_expiration_date != -1 && (!trim($projects_expiration_date) || $projects_expiration_date < 0 || strtotime($projects_expiration_date) > strtotime($licence_projects_expiration_date))) //$projects_expiration_date < 0 includes $projects_expiration_date == -1 and < -1 
			$projects_expiration_date = $licence_projects_expiration_date;
		
		$sysadmin_expiration_time = $sysadmin_expiration_date ? strtotime($sysadmin_expiration_date) : time() + (60 * 60 * 24 * 30); //+1 month
		if ($sysadmin_expiration_time > strtotime($licence_sysadmin_expiration_date)) 
			$sysadmin_expiration_date = $licence_sysadmin_expiration_date;
		
		if ($licence_projects_maximum_number > 0)
			$licence_projects_maximum_number--; //decrease 1 project bc the $projects_max_num_allowed contains the common project
		
		if ($licence_projects_maximum_number != -1 && (!is_numeric($projects_maximum_number) || $projects_maximum_number < 0 || $projects_maximum_number > $licence_projects_maximum_number)) //$projects_maximum_number < 0 includes $projects_maximum_number == -1 and < -1
			$projects_maximum_number = $licence_projects_maximum_number;
		
		if ($licence_users_maximum_number != -1 && (!is_numeric($users_maximum_number) || $users_maximum_number < 0 || $users_maximum_number > $licence_users_maximum_number)) //$users_maximum_number < 0 includes $users_maximum_number == -1 and < -1
			$users_maximum_number = $licence_users_maximum_number;
		
		if ($licence_end_users_maximum_number != -1 && (!is_numeric($end_users_maximum_number) || $end_users_maximum_number < 0 || $end_users_maximum_number > $licence_end_users_maximum_number)) //$end_users_maximum_number < 0 includes $end_users_maximum_number == -1 and < -1
			$end_users_maximum_number = $licence_end_users_maximum_number;
		
		if ($licence_actions_maximum_number != -1 && (!is_numeric($actions_maximum_number) || $actions_maximum_number < 0 || $actions_maximum_number > $licence_actions_maximum_number)) //$actions_maximum_number < 0 includes $actions_maximum_number == -1 and < -1
			$actions_maximum_number = $licence_actions_maximum_number;
		
		if (!$licence_allowed_sysadmin_migration && $allowed_sysadmin_migration) //check if sysadmin migration is allowed
			$allowed_sysadmin_migration = $licence_allowed_sysadmin_migration;
		
		//check the allowed_paths that the user wrote, this is, check if any of the paths inserted by the user, are any of the paths registered in the current licence. Do not check if the paths are inside of the registered paths, bc the paths must be exactly the same! Not childs!
		//Note that the allowed_paths must be absolute paths!
		if ($licence_allowed_paths) {
			$allowed_paths = trim($allowed_paths);
			
			if ($allowed_paths) {
				$allowed_paths = str_replace(";", ",", $allowed_paths);
				$lap = "," . trim($licence_allowed_paths) . ",";
				$new_allowed_paths = "";
				$parts = explode(",", $allowed_paths);
				
				foreach ($parts as $part) {
					$part = trim($part);
					
					if ($part) {
						$part = preg_replace("/\/+/", "/", $part); //replaces multiple slashes to only one
						$part = preg_replace("/\/+$/", "", $part); //remove last slash
						
						if (preg_match("/,\s*" . str_replace("/", "\\/", str_replace(".", "\\.", $part)) . "\s*\/?,/i", $lap)) //escape . and / bc of regex
							$new_allowed_paths .= ($new_allowed_paths ? "," : "") . $part;
					}
				}
				
				$allowed_paths = $new_allowed_paths;
			}
			
			if (!$allowed_paths)
				$allowed_paths = $licence_allowed_paths;
		}
		
		$this->prepareAllowedDomains($allowed_domains, $check_allowed_domains_port);
		
		//error_log("sysadmin_expiration_date:$sysadmin_expiration_date\nprojects_maximum_number:$projects_maximum_number\nprojects_expiration_date:$projects_expiration_date\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		//prepare pub and pri files
		if ($keys_file == "key_strings") {
			$private_key = trim($private_key);
			$public_key = trim($public_key);
			
			if (!$private_key || !$public_key) {
				$error_messages[] = "Error: Private and Public Key strings cannot be empty! You must enter the text from your private/public .pem file!";
				return false;
			}
			
			$private_key_file = $deployment_folder_path . "deployment_app_priv_key.pem";
			$public_key_file = $deployment_folder_path . "deployment_app_pub_key.pem";
			
			if (file_put_contents($private_key_file, $private_key) === false || file_put_contents($public_key_file, $public_key) === false) {
				//remove files now for safety reasons
				$this->removeFile($private_key_file);
				$this->removeFile($public_key_file);
				
				$error_messages[] = "Error: Private or Public Key strings could not be saved in temporary files to be used to create licence!";
				return false;
			}
		}
		else { //check if key files exists
			if (!$private_key_file || !$public_key_file) {
				$error_messages[] = "Error: Private and Public Key files cannot be empty! You must enter the CMS relative url for your priv.pem/pub.pem files!";
				return false;
			}
			
			$private_key_file = CMS_PATH . $private_key_file;
			$public_key_file = CMS_PATH . $public_key_file;
		}
		
		//create licence
		CMSDeploymentSecurityHandler::createAppLicence($deployment_folder_path . "app", $private_key_file, $public_key_file, $passphrase, $projects_expiration_date, $sysadmin_expiration_date, $projects_maximum_number, $users_maximum_number, $end_users_maximum_number, $actions_maximum_number, $allowed_paths, $allowed_domains, $check_allowed_domains_port, $allowed_sysadmin_migration, $error_messages);
		
		//remove files now for safety reasons
		if ($keys_file == "key_strings") {
			$this->removeFile($private_key_file);
			$this->removeFile($public_key_file);
		}
	}
	
	/*
	 * If check_allowed_domains_port == true
	 * 	$licence_allowed_domains contain domains without ports and $allowed_domains contain domains with ports
	 */
	private function prepareAllowedDomains(&$allowed_domains, &$check_allowed_domains_port) {
		//prepare licence options, by changing the options with the correct values according with the current licence.
		$licence_allowed_domains = isset($this->licence_data["ad"]) ? $this->licence_data["ad"] : null;
		$licence_check_allowed_domains_port = isset($this->licence_data["cadp"]) ? $this->licence_data["cadp"] : null;
		
		//overwrite check_allowed_domains_port if value in current licence is true
		$check_allowed_domains_port = $licence_check_allowed_domains_port ? true : $check_allowed_domains_port;
		
		//check the allowed_domains that the user wrote, this is, check if any of the domains inserted by the user, are any of the domains registered in the current licence.
		if ($licence_allowed_domains) {
			$lad = trim(str_replace(";", ",", $licence_allowed_domains)) . ",";
			$lad = preg_replace("/:80,/", ",", $lad); //remove port 80 bc is the default for the browsers. The browsers will remove this port if implict. This is already done in the CMSDeploymentSecurityHandler::createAppLicence when we create the licence.
			$lad = strtolower($licence_check_allowed_domains_port ? $lad : preg_replace("/:[0-9]+,/", ",", $lad));
			$licence_allowed_domains_arr = explode(",", $lad);
			$licence_allowed_domains_arr_parsed = array();
			
			foreach ($licence_allowed_domains_arr as $licence_allowed_domain) {
				$licence_allowed_domain = trim($licence_allowed_domain);
				
				if ($licence_allowed_domain)
					$licence_allowed_domains_arr_parsed[] = $licence_allowed_domain;
			}
			
			if (!empty($licence_allowed_domains_arr_parsed)) {
				$allowed_domains = trim($allowed_domains);
			
				if ($allowed_domains) {
					$allowed_domains = trim(str_replace(";", ",", $allowed_domains)) . ",";
					$allowed_domains = preg_replace("/:80,/", ",", $allowed_domains); //remove port 80 bc is the default for the browsers. The browsers will remove this port if implict.
					$allowed_domains_arr = explode(",", $allowed_domains);
					
					$new_allowed_domains = "";
					
					foreach ($allowed_domains_arr as $allowed_domain) {
						$allowed_domain = trim($allowed_domain);
						
						if ($allowed_domain) {
							$ad = strtolower($licence_check_allowed_domains_port ? $allowed_domain : preg_replace("/:[0-9]+,/", ",", $allowed_domain));
							$is_valid = array_search($ad, $licence_allowed_domains_arr_parsed) !== false;
							
							//check if $allowed_domain is a subdomain from any of the  $licence_allowed_domains
							if (!$is_valid)
								foreach ($licence_allowed_domains_arr_parsed as $licence_allowed_domain)
									if (strpos("$ad,", ".$licence_allowed_domain,") !== false) {
										$is_valid = true;
										break;
									}
							
							if ($is_valid) 
								$new_allowed_domains .= ($new_allowed_domains ? "," : "") . $allowed_domain;
							
						}
					}
					
					$allowed_domains = $new_allowed_domains;
				}
				
				if (!$allowed_domains)
					$allowed_domains = implode(",", $licence_allowed_domains_arr_parsed);
			}
		}
	}
	
	/* OBFUSCATE FILES UTILS */
	
	private function obfuscateProprietaryPHPFiles($deployment_folder_path, $files_settings, &$error_messages) {
		$options = self::$obfuscate_php_files_options;
		
		$CMSObfuscatePHPFilesHandler = new CMSObfuscatePHPFilesHandler($deployment_folder_path);
		$serialized_files = $CMSObfuscatePHPFilesHandler->getDefaultSerializedFiles();
		$opts = $CMSObfuscatePHPFilesHandler->getConfiguredOptions($options);
		$avoid_warnings_for_files = $CMSObfuscatePHPFilesHandler->getDefaultFilesToAvoidWarnings();
		$ret = $CMSObfuscatePHPFilesHandler->obfuscate($opts, $files_settings, $serialized_files, $avoid_warnings_for_files);
		
		$msg = !empty($ret["errors"]) ? "PHP obfuscation error files: [" . implode(", ", $ret["errors"]) . "]" : "";
		$msg .= ($msg ? "\n" : "") . (isset($ret["warning_msg"]) ? $ret["warning_msg"] : "");
		
		if (empty($ret["status"]) || $msg)
			$error_messages[] = "Error: trying to obfuscate php files!" . ($msg ? "\n" . $msg : "");
	}
	
	private function getObfuscateProprietaryDefaultPHPFilesSettings($deployment_folder_path, &$error_messages) {
		$CMSObfuscatePHPFilesHandler = new CMSObfuscatePHPFilesHandler($deployment_folder_path);
		$files_settings = $CMSObfuscatePHPFilesHandler->getDefaultFilesSettings($deployment_folder_path);
		
		//Removing getDefaultFilesSettings method from CMSObfuscatePHPFilesHandler.php
		//This is very important bc if CMSObfuscatePHPFilesHandler::getDefaultFilesSettings is not empty when we run this code again it will obfuscate twice and propbably give errors.
		//Note that this MUST RUN BEFORE the $CMSObfuscateJSFilesHandler->obfuscate, otherwise this method won't work and will mess the php code inside of the CMSObfuscateJSFilesHandler.php
		CMSDeploymentSecurityHandler::emptyFileClassMethod("$deployment_folder_path/app/__system/layer/presentation/phpframework/src/util/CMSObfuscatePHPFilesHandler.php", "CMSObfuscatePHPFilesHandler", "getDefaultFilesSettings", $error_messages);
		
		return $files_settings;
	}
	
	private function obfuscateProprietaryJSFiles($deployment_folder_path, $allowed_domains, $check_allowed_domains_port, $files_settings, &$error_messages) {
		$this->prepareAllowedDomains($allowed_domains, $check_allowed_domains_port);
		
		$options = str_replace("#check_allowed_domains_port#", $check_allowed_domains_port, str_replace("#allowed_domains#", $allowed_domains, self::$obfuscate_js_files_options));
		
		$CMSObfuscateJSFilesHandler = new CMSObfuscateJSFilesHandler($deployment_folder_path);
		$opts = $CMSObfuscateJSFilesHandler->getConfiguredOptions($options);
		$ret = $CMSObfuscateJSFilesHandler->obfuscate($opts, $files_settings);
		
		$msg = !empty($ret["errors"]) ? "JS obfuscation error files: [" . implode(", ", $ret["errors"]) . "]" : "";
		
		if (empty($ret["status"]) || $msg)
			$error_messages[] = "Error: trying to obfuscate js files!" . ($msg ? "\n" . $msg : "");
	}
	
	private function getObfuscateProprietaryDefaultJSFilesSettings($deployment_folder_path, &$error_messages) {
		$cms_relative_common_webroot_path = "/app/layer/presentation/common/webroot/";
		$cms_relative_system_common_webroot_path = "/app/__system/layer/presentation/common/webroot/";
		$cms_relative_system_webroot_path = "/app/__system/layer/presentation/phpframework/webroot/";
		
		$CMSObfuscateJSFilesHandler = new CMSObfuscateJSFilesHandler($deployment_folder_path);
		$files_settings = $CMSObfuscateJSFilesHandler->getDefaultFilesSettings($deployment_folder_path, $cms_relative_common_webroot_path, $cms_relative_system_common_webroot_path, $cms_relative_system_webroot_path);
		
		//Removing getDefaultFilesSettings method from CMSObfuscateJSFilesHandler.php
		//This is very important bc if CMSObfuscateJSFilesHandler::getDefaultFilesSettings is not empty when we run this code again it will obfuscate twice and propbably give errors.
		//Note that this MUST RUN BEFORE the $CMSObfuscateJSFilesHandler->obfuscate, otherwise this method won't work and will mess the php code inside of the CMSObfuscateJSFilesHandler.php
		CMSDeploymentSecurityHandler::emptyFileClassMethod("$deployment_folder_path/app/__system/layer/presentation/phpframework/src/util/CMSObfuscateJSFilesHandler.php", "CMSObfuscateJSFilesHandler", "getDefaultFilesSettings", $error_messages);
		
		return $files_settings;
	}
	
	/* FILES UTILS */
	
	private function createFolder($path) {
		$this->removeFile($path);
		
		if (!is_dir($path))
			@mkdir($path, 0755, true);
		
		return is_dir($path);
	}
	
	private function copyFolderHiddenFiles($src, $dst) {
		$status = true;
		$files = file_exists($src) ? scandir($src) : null;
		
		if ($files)
			foreach ($files as $file) 
				if (!in_array($file, self::$invalid_files) && substr($file, 0, 1) == ".")
					if (!$this->copyFile("$src/$file", "$dst/$file"))
						$status = false;
		
		return $status;
	}
	
	private function copyParentsFolderHiddenFiles($src, $dst, $src_stop = null) {
		$status = true;
		
		if ($src && $dst) {
			$src_parent = dirname($src);
			$dst_parent = dirname($dst);
			
			$src_parent = $src_parent == "." ? $src_parent : "";
			$dst_parent = $dst_parent == "." ? $dst_parent : "";
			
			$status = false;
			
			if (!$src_parent || !$dst_parent)
				$status = true;
			else if (is_dir($src_parent) && is_dir($dst_parent)) {
				$status = true;
				
				$src_parent .= "/";
				$src_stop .= substr($src_stop, -1) != "/" ? "/" : "";
				$continue = !empty( trim(str_replace($src_stop, "", $src_parent)) );
				
				if (!$this->copyFolderHiddenFiles($src_parent, $dst_parent))
					$status = false;
				
				if ($continue && !$this->copyParentsFolderHiddenFiles($src_parent, $dst_parent, $src_stop))
					$status = false;
			}
		}
		
		return $status;
	}
	
	private function copyFolder($src, $dst) {
		if ($src && $dst && is_dir($src)) {
			if (!is_dir($dst)) 
				@mkdir($dst, 0755, true);
			
			if (is_dir($dst)) {
				$status = true;
				$files = scandir($src);
				
				if ($files)
					foreach ($files as $file)
						if (!in_array($file, self::$invalid_files) && !$this->copyFile("$src/$file", "$dst/$file"))
							$status = false;
				
				if (!$this->setSameFilePermissions($src, $dst)) 
					$status = false;
				
				return $status; 
			}
		}
	}
	
	private function copyFile($src, $dst) {
		if ($src && $dst && file_exists($src)) {
			if (is_dir($src))
				$status = $this->copyFolder($src, $dst);
			else {
				$dst_parent = dirname($dst);
				
				if ($dst_parent && !is_dir($dst_parent))
					mkdir($dst_parent, 0755, true);
				
				$status = is_dir($dst_parent) && copy($src, $dst);
			}
			
			//set same permissions $src => $dst
			if (!$this->setSameFilePermissions($src, $dst)) 
				$status = false;
			
			return $status;
		}
	}
	
	private function setSameFilePermissions($src, $dst) {
		$status = true;
			
		if ($src && $dst && file_exists($src) && file_exists($dst)) {
			$uid = function_exists("posix_getuid") ? posix_getuid() : null; //this is the apache user id. posix_getuid does not exists in windows.
			$src_uid = fileowner($src); //this is the file owner user id
			
			$src_perms = fileperms($src) & 0777;
			$dst_perms = fileperms($dst) & 0777;
			
			//if file's owner is the apache, we set the perm to 777, bc we cannot change the user owner. Var $this->this_file_uid is jplpinto!
			//$uid does not exists on windows bc posix_getuid does not exists on windows.
			if (!$uid || ($uid == $src_uid && $this->this_file_uid != $uid))
				$src_perms = 0777;
			
			//if (strpos($src, "/layoutuieditor/") !== false)echo "$uid:$src_uid:0".decoct($src_perms)." = $src\n<br>";
			//echo decoct($src_perms).": ".substr($src, -20)."\n<br>";
			
			if ($src_perms != $dst_perms && !chmod($dst, $src_perms))
				$status = false;
		}
		
		return $status;
	}
	
	private function setDeploymentFolderFilesPermissions($src, $dst) {
		if ($src && $dst && is_dir($src) && is_dir($dst)) {
			$perms = $this->getDeploymentFolderFilesPermissions($src, $dst);
			return file_put_contents($dst . "/perms.json", json_encode($perms));
		}
	}
	
	private function getDeploymentFolderFilesPermissions($src, $dst, $suffix = "") {
		$perms = array();
			
		if ($dst) {
			$dst_path = "$dst/$suffix";
			$src_path = "$src/$suffix";
			
			if (is_dir($dst_path)) {
				$files = scandir($dst_path);
				
				if ($files)
					foreach ($files as $file)
						if (!in_array($file, self::$invalid_files)) {
							$dst_file_path = "$dst_path$file";
							$src_file_path = "$src_path$file";
							$is_dir = is_dir($dst_file_path);
							$key = "$suffix$file" . ($is_dir ? "/" : "");
							$file_perm = fileperms($dst_file_path) & 0777;
							
							if (file_exists($src_file_path)) {
								$file_uid = fileowner($src_file_path); //this is the file owner user id
								
								//if file's owner is different than this->this_file_uid, we set the perm to 777, bc we cannot change the user owner. Var $this->this_file_uid is jplpinto!
								if ($this->this_file_uid != $file_uid) 
									$file_perm = 0777;
							}
								
							$perms[$key] = "0" . decoct($file_perm);
							
							if ($is_dir)
								$perms = array_merge($perms, $this->getDeploymentFolderFilesPermissions($src, $dst, $key));
						}
				
			}
		}
		
		return $perms;
	}
	
	private function removeDeploymentsFiles() {
		//be sure that all deployments get deleted
		if ($this->deployments_files)
			foreach ($this->deployments_files as $path) 
				$this->removeFile($path);
	}
	
	private function removeFile($path) {
		if ($path && file_exists($path)) {
			if (is_dir($path))
				return CacheHandlerUtil::deleteFolder($path);
			else 
				return unlink($path);
		}
		
		return true;
	}
	
	private function removeFolderFiles($path) {
		return CacheHandlerUtil::deleteFolder($path, false);
	}
	
	/* SERVER WORKFLOW TASK UTILS */
	
	public static function validateServerTemplateLicenceData($server_template, $licence_data, &$error_messages) {
		$status = true;
		
		if ($server_template && !empty($server_template["properties"]["actions"])) {
			$template_actions = $server_template["properties"]["actions"];
			$is_assoc = array_keys($template_actions) !== range(0, count($template_actions) - 1);
			
			if ($is_assoc)
				$template_actions = array($template_actions);
			
			$licence_sysadmin_expiration_date = isset($licence_data["sed"]) ? $licence_data["sed"] : null;
			$licence_projects_maximum_number = isset($licence_data["pmn"]) ? $licence_data["pmn"] : null;
			$licence_projects_expiration_date = isset($licence_data["ped"]) ? $licence_data["ped"] : null;
			
			if ($licence_projects_maximum_number > 0)
				$licence_projects_maximum_number--; //decrease 1 project bc the $projects_max_num_allowed contains the common project
			
			foreach ($template_actions as $idx => $template_action)
				foreach ($template_action as $action_type => $action) 
					if ($action_type == "copy_layers" && (!isset($action["active"]) || $action["active"])) {
						//avoids user to find this code from grepping the files system
						$projects_maximum_number = isset($action["proj" . 'ects_m' . "aximum_nu" . 'mber']) ? $action["proj" . 'ects_m' . "aximum_nu" . 'mber'] : null;
						$projects_expiration_date = isset($action['proj' . "ects_expi" . 'ration' . '_date']) ? $action['proj' . "ects_expi" . 'ration' . '_date'] : null;
						$sysadmin_expiration_date = isset($action["sy" . 'sad' . "min_ex" . 'pirati' . "on_date"]) ? $action["sy" . 'sad' . "min_ex" . 'pirati' . "on_date"] : null;
						
						if ($projects_maximum_number && $licence_projects_maximum_number != -1 && ($projects_maximum_number == -1 || $projects_maximum_number > $licence_projects_maximum_number)) {
							$error_messages[] = "Error: Maximum number of projects cannot be -1 or bigger than $licence_projects_maximum_number.";
							$status = false;
						}
						
						if ($projects_expiration_date && $licence_projects_expiration_date != -1 && ($projects_expiration_date == -1 || strtotime($projects_expiration_date) > strtotime($licence_projects_expiration_date))) {
							$error_messages[] = "Error: Projects expiration date cannot be -1 and bigger than '$licence_projects_expiration_date'.";
							$status = false;
						}
						
						if ($sysadmin_expiration_date && strtotime($sysadmin_expiration_date) > strtotime($licence_sysadmin_expiration_date)) {
							$error_messages[] = "Error: SysAdmin expiration date cannot be bigger than '$licence_sysadmin_expiration_date'.";
							$status = false;
						}
					}
		}
		
		return $status;
	}
	
	public static function getServerTask($tasks, $server_name) {
		if (!empty($tasks["tasks"]))
			foreach ($tasks["tasks"] as $task_id => $task) {
				$task_label = isset($task["label"]) ? $task["label"] : null;
				
				if ($task_label == $server_name)
					return $task;
			}
			
		return null;
	}
	
	public static function getServerTaskTemplate($tasks, $server_name, $template_id) {
		$task = self::getServerTask($tasks, $server_name);
		
		if ($task && !empty($task["properties"]) && !empty($task["properties"]["templates"])) {
			$templates = $task["properties"]["templates"];
			
			if (isset($templates["name"]) || isset($templates["created_date"]) || isset($templates["modified_date"]) || isset($templates["template_id"]))
				$templates = array($templates);
			
			foreach ($templates as $idx => $template) {
				$tid = isset($template["template_id"]) ? $template["template_id"] : null;
				
				if ($tid == $template_id)
					return $template;
			}
		}
		
		return null;
	}
	
	public static function getTasksByLabel($tasks) {
		$tasks_by_label = array();
		
		if ($tasks && !empty($tasks["tasks"])) {
			foreach ($tasks["tasks"] as $task_id => $task) {
				$task_label = isset($task["label"]) ? $task["label"] : null;
				$tasks_by_label[$task_label] = $task;
			}
			
			/*2020-09-10: I think this is not used anywhere, so I commeted
			foreach ($tasks_by_label as $task_label => $task)
				if (!empty($task["exits"]["layer_exit"])) {
					$connections = array();
					
					$exits = $task["exits"]["layer_exit"];
					if (!empty($exits["task_id"]))
						$exits = array($exits);
					
					foreach ($exits as $idx => $e) {
						$exit_task_id = isset($e["task_id"]) ? $e["task_id"] : null;
						$t = isset($tasks["tasks"][$exit_task_id]) ? $tasks["tasks"][$exit_task_id] : null;
						
						if ($t)
							$connections[] = isset($t["label"]) ? $t["label"] : null;
					}
					
					if ($connections)
						$tasks_by_label[$task_label]["connections"] = $connections;
				}*/
		}
		
		return $tasks_by_label;
	}
	
	public static function getTasksPropsByLabel($tasks_props) {
		$tasks_props_by_label = array();
		
		if ($tasks_props)
			foreach ($tasks_props as $task) {
				$task_label = isset($task["label"]) ? $task["label"] : null;
				$tasks_props_by_label[$task_label] = isset($task["properties"]) ? $task["properties"] : null;
			}
		
		return $tasks_props_by_label;
	}
	
	public static function getConnectionsPropsByTaskLabels($connections_props) {
		$connections_props_by_label = array();
		
		if ($connections_props)
			foreach ($connections_props as $connection) {
				$source_label = isset($connection["source_label"]) ? $connection["source_label"] : null;
				$target_label = isset($connection["target_label"]) ? $connection["target_label"] : null;
				$connections_props_by_label[$source_label][$target_label] = isset($connection["properties"]) ? $connection["properties"] : null;
			}
		
		return $connections_props_by_label;
	}
	
	/* UTILS */
	
	private function prepareArray(&$arr) {
		$is_simple = !is_array($arr);
		
		if (!$is_simple)
			foreach ($arr as $idx => $aux)
				if (!is_numeric($idx)) {
					$is_simple = true;
					break;
				}
		
		if ($is_simple)
			$arr = array($arr);
	}
	
	private function unparseUrl($parsed_url) {
		$scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
		$host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
		$port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
		$user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
		$pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
		$pass = ($user || $pass) ? "$pass@" : '';
		$path = isset($parsed_url['path']) ? (substr($parsed_url['path'], 0, 1) != "/" ? "/" : "") . $parsed_url['path'] : '';
		$query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
		$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
		
		return "$scheme$user$pass$host$port$path$query$fragment";
	}
	
	private static function getInvalidFilesVarExport() {
		return str_replace("\n", "", var_export(self::$invalid_files, true));
	}
}
?>
