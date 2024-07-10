<?php
include_once get_lib("org.phpframework.util.xml.MyXML");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSProgramExtraTableInstallationUtil");
include_once get_lib("org.phpframework.cms.wordpress.WordPressUrlsParser");
include_once $EVC->getUtilPath("WorkFlowBeansFolderHandler");
include_once $EVC->getUtilPath("WorkFlowTasksFileHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

class WorkFlowBeansConverter {
	private $task_layer_tags;
	private $user_beans_folder_path;
	private $user_global_variables_file_path;
	private $user_global_settings_file_path;
	
	private $WorkFlowBeansFolderHandler;
	private $WorkFlowTasksFileHandler;
	
	public function __construct($tasks_file_path, $user_beans_folder_path, $user_global_variables_file_path, $user_global_settings_file_path, $global_paths = array()) {
		$this->user_beans_folder_path = $user_beans_folder_path;
		$this->user_global_variables_file_path = $user_global_variables_file_path;
		$this->user_global_settings_file_path = $user_global_settings_file_path;
		@mkdir($this->user_beans_folder_path, 0775, true);
		
		$this->WorkFlowBeansFolderHandler = new WorkFlowBeansFolderHandler($user_beans_folder_path, $user_global_variables_file_path, $user_global_settings_file_path, $global_paths);
		
		$this->WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($tasks_file_path);
		$this->task_layer_tags = WorkFlowTasksFileHandler::getTaskLayerTags();
	}
	
	public function init() {
		$this->WorkFlowTasksFileHandler->init();
	}
	
	public function getWorkFlowTasksFileHandler() {
		return $this->WorkFlowTasksFileHandler;
	}
	
	public function getUserBeansFolderPath() {
		return $this->user_beans_folder_path;
	}
	
	public function createBeans($UserAuthenticationHandler = null, $options = null) {
		$status = true;
		
		if ($options && $options["tasks_folders"])
			$status = $this->renameExistentTasksFolders($UserAuthenticationHandler, $options["tasks_folders"], $options["tasks_labels"]);
		
		$new_default_layer_folder = $this->getSelectedDefaultLayerFolder();
		
		return $status && $this->WorkFlowBeansFolderHandler->createDefaultFiles() &&
			$this->WorkFlowBeansFolderHandler->removeOldBeansFiles() &&
			$this->createAppBeans() &&
			$this->createDBDriverBeans() &&
			$this->createDBLayerBeans() &&
			$this->createDataAccessLayerBeans() &&
			$this->createBusinessLogicCommonServicesBeans() &&
			$this->createBusinessLogicLayerBeans() &&
			$this->createPresentationBeans() &&
			$this->WorkFlowBeansFolderHandler->createDefaultLayer($new_default_layer_folder);
	}
	
	public function recreateBean($task_id_to_search) {
		$status = false;
		$tasks = $this->WorkFlowTasksFileHandler->getWorkflowData();
		$found_task = null;
		
		if ($tasks && $tasks["tasks"])
			foreach ($tasks["tasks"] as $task_id => $task) 
				if ($task_id == $task_id_to_search) {
					$found_task = $task;
					break;
				}
		
		if ($found_task) {
			switch($found_task["tag"]) {
				case $this->task_layer_tags["dbdriver"]: 
					$status = $this->createDBDriverBeans($task_id_to_search);
					break;
				case $this->task_layer_tags["db"]: 
					$status = $this->createDBLayerBeans($task_id_to_search);
					break;
				case $this->task_layer_tags["dataaccess"]: 
					$status = $this->createDataAccessLayerBeans($task_id_to_search);
					break;
				case $this->task_layer_tags["businesslogic"]:
					$status = $this->createBusinessLogicCommonServicesBeans($task_id_to_search) && $this->createBusinessLogicLayerBeans($task_id_to_search);
					break; 
				case $this->task_layer_tags["presentation"]:
					$status = $this->createPresentationBeans($task_id_to_search);
					break;
			}
		}
		
		return $status;
	}
	
	//check if exists any wordpress installation folder that don't correspond to any db driver task or that has different db credentials
	public function getWordPressInstallationsWithoutDBDrivers() {
		$tasks = $this->WorkFlowTasksFileHandler->getWorkflowData();
		$global_paths = $this->WorkFlowBeansFolderHandler->getGlobalPaths();
		
		$db_drivers_by_folders = array();
		$presentation_layers_folders = array();
		
		if ($tasks && $tasks["tasks"])
			foreach ($tasks["tasks"] as $task)
				if ($task["tag"] == $this->task_layer_tags["dbdriver"] || $task["tag"] == $this->task_layer_tags["presentation"]) {
					self::prepareLabel($task["label"]);
					$ll = self::getVariableNameFromLabel($task["label"]);
					
					if ($task["tag"] == $this->task_layer_tags["dbdriver"])
						$db_drivers_by_folders[$ll] = $task;
					else
						$presentation_layers_folders[] = $ll;
				}
		
		$non_existent_db_drivers = array();
		$wrong_db_drivers_credentials = array();
		
		foreach ($presentation_layers_folders as $folder_name) {
			$wordpress_installations_path = $global_paths["LAYER_PATH"] . "$folder_name/common/webroot/" . WordPressUrlsParser::WORDPRESS_FOLDER_PREFIX . "/";
			
			$files = file_exists($wordpress_installations_path) ? array_diff(scandir($wordpress_installations_path), array('.', '..')) : null;
			
			if ($files)
				foreach ($files as $file)
					if (is_dir($wordpress_installations_path . $file)) {
						$task = $db_drivers_by_folders[$file];
						
						if (!isset($task))
							$non_existent_db_drivers[] = $file;
						else {
							$wp_config_fp = $wordpress_installations_path . "$file/wp-config.php";
							
							if (!file_exists($wp_config_fp)) 
								$wrong_db_drivers_credentials[] = $file;
							else {
								$contents = file_get_contents($wp_config_fp);
								
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
								$db_port = $parts[1];
								
								//compare if original db driver task is the same than wordpress installation, and if true, changes config file with new credentials
								if ($task["properties"]["host"] != $db_host || $task["properties"]["port"] != $db_port || $task["properties"]["db_name"] != $db_name) 
									$wrong_db_drivers_credentials[] = $file;
							}
						}
					}
		}
		
		$wordpress_installations_to_check = array_values(array_unique(array_merge($non_existent_db_drivers, $wrong_db_drivers_credentials)));
		
		return $wordpress_installations_to_check;
	}
	
	//check if exists any extra folders that don't correspond to any layer
	public function getDeprecatedLayerFolders() {
		$tasks = $this->WorkFlowTasksFileHandler->getWorkflowData();
		$global_paths = $this->WorkFlowBeansFolderHandler->getGlobalPaths();
		$allowed_task_tags = array($this->task_layer_tags["db"], $this->task_layer_tags["dataaccess"], $this->task_layer_tags["businesslogic"], $this->task_layer_tags["presentation"]);
		$tasks_folders = array();
		
		if ($tasks && $tasks["tasks"])
			foreach ($tasks["tasks"] as $task)
				if (in_array($task["tag"], $allowed_task_tags)) {
					self::prepareLabel($task["label"]);
					$ll = self::getVariableNameFromLabel($task["label"]);
					
					$tasks_folders[] = $ll;
				}
		
		$existent_folders = array();
		$files = array_diff(scandir($global_paths["LAYER_PATH"]), array('.', '..'));
		
		if ($files)
			foreach ($files as $file)
				if (is_dir($global_paths["LAYER_PATH"] . $file) && !in_array($file, $tasks_folders))
					$existent_folders[] = $file;
		
		return $existent_folders;
	}
	
	//check the new tasks and check if any has a different name than previously. If so, rename the old folder with the new name
	private function renameExistentTasksFolders($UserAuthenticationHandler, $tasks_folders, $tasks_labels) {
		$tasks = $this->WorkFlowTasksFileHandler->getWorkflowData();
		$global_paths = $this->WorkFlowBeansFolderHandler->getGlobalPaths();
		$allowed_task_tags = array($this->task_layer_tags["db"], $this->task_layer_tags["dataaccess"], $this->task_layer_tags["businesslogic"], $this->task_layer_tags["presentation"]);
		$status = true;
		
		if ($tasks && $tasks["tasks"]) {
			$db_layers = array();
			$renamed = false;
			
			$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $this->user_global_variables_file_path, $this->user_beans_folder_path);
			
			foreach ($tasks["tasks"] as $task) {
				if ($task["tag"] == $this->task_layer_tags["db"]) {
					$task_id = $task["id"];
					$previous_folder = $tasks_folders[$task_id];
					
					self::prepareLabel($task["label"]);
					$ll = self::getVariableNameFromLabel($task["label"]);
					
					$db_layers[$previous_folder] = $ll;
				}
			}
			
			foreach ($tasks["tasks"] as $task) {
				if ($task["tag"] == $this->task_layer_tags["dbdriver"]) {
					$task_id = $task["id"];
					$previous_label = $tasks_labels[$task_id];
					
					if ($previous_label && $task["label"] != $previous_label) {
						self::prepareLabel($previous_label);
						$old_label = self::getObjectNameFromLabel($previous_label);
						
						self::prepareLabel($task["label"]);
						$new_label = self::getObjectNameFromLabel($task["label"]);
						
						//rename all layout types and layout type permissions with old paths
						foreach ($db_layers as $db_old_folder => $db_new_folder) {
							$old_db_driver_path = $global_paths["LAYER_PATH"] . "$db_old_folder/$old_label";
							$new_db_driver_path = $global_paths["LAYER_PATH"] . "$db_new_folder/$new_label";
							
							if (!$LayoutTypeProjectHandler->renameLayoutTypePermissionsFromDBDriverPath($old_db_driver_path, $new_db_driver_path))
								$status = false;
						}
					}
				}
				else if (in_array($task["tag"], $allowed_task_tags)) {
					$task_id = $task["id"];
					$previous_folder = $tasks_folders[$task_id];
					
					if ($previous_folder) {
						self::prepareLabel($task["label"]);
						$ll = self::getVariableNameFromLabel($task["label"]);
						
						$old_path = $global_paths["LAYER_PATH"] . $previous_folder;
						$new_path = $global_paths["LAYER_PATH"] . $ll;
						
						if ($ll != $previous_folder && !file_exists($new_path) && is_writable($old_path)) {
							if (rename($old_path, $new_path)) {
								$renamed = true;
								
								if (!$LayoutTypeProjectHandler->renameLayoutTypePermissionsFromLayerPath($old_path, $new_path, false))
									$status = false;
								
								//rename all layout types and layout type permissions with old paths
								if ($status && $task["tag"] == $this->task_layer_tags["presentation"]) {
									if (!$LayoutTypeProjectHandler->renameLayoutTypesFromLayerPath($old_path, $new_path, false))
										$status = false;
								}
							}
							else
								$status = false;
						}
					}
				}
			}
			
			if ($renamed) {
				$LayoutTypeProjectHandler->refreshLoadedLayouts();
				$LayoutTypeProjectHandler->refreshLoadedLayoutsTypePermissionsByObject();
			}
		}
		
		return $status;
	}
	
	//get changed db_drivers and check if there are extra_attributes_settings.php in modules
	public function renameExtraAttributesFiles($options, &$changed = false) {
		$non_renamed_files = array();
		$tasks_folders = $options ? $options["tasks_folders"] : null;
		
		if ($tasks_folders) {
			$tasks = $this->WorkFlowTasksFileHandler->getTasksByLayerTag("dbdriver");
			
			if ($tasks) {
				$pres_tasks = $this->WorkFlowTasksFileHandler->getTasksByLayerTag("presentation");
				$global_paths = $this->WorkFlowBeansFolderHandler->getGlobalPaths();
				$modules_folder_paths = array();
				
				if ($pres_tasks)
					foreach ($pres_tasks as $task) {
						self::prepareLabel($task["label"]);
						$ll = self::getVariableNameFromLabel($task["label"]);
						
						if (file_exists($global_paths["LAYER_PATH"] . $ll)) {
							$modules_folder_path = $global_paths["LAYER_PATH"] . "$ll/common/src/module/";
							
							$files = file_exists($modules_folder_path) ? array_diff(scandir($modules_folder_path), array('.', '..')) : null;
							
							if ($files)
								foreach ($files as $file)
									if (is_dir($modules_folder_path . $file))
										$modules_folder_paths[] = $modules_folder_path . $file . "/";
						}
					}
				
				if ($modules_folder_paths) {
					$bl_tasks = $this->WorkFlowTasksFileHandler->getTasksByLayerTag("businesslogic");
					$da_tasks = $this->WorkFlowTasksFileHandler->getTasksByLayerTag("dataaccess");
					$changed_db_drivers = $this->getChangedDBDrivers($options);
					
					foreach ($changed_db_drivers as $new_db_driver_name => $aux) {
						$old_db_driver_name = $aux[0];
						$overlapped_names = $aux[1];
						$length = strlen($old_db_driver_name);
						
						//change extra_attributes files in $modules_folder_paths
						foreach ($modules_folder_paths as $folder_path) {
							$files = file_exists($folder_path) ? array_diff(scandir($folder_path), array('.', '..')) : null;
							
							if ($files)
								foreach ($files as $file)
									if (!is_dir($folder_path . $file)) {
										$info = pathinfo($file);
										
										if (strtolower($info["extension"]) == "php" && substr($info["filename"], -20) == "_attributes_settings" && substr($info["filename"], 0, $length + 1) == $old_db_driver_name . "_") {
											$belongs_to_this_driver = true;
											
											foreach($overlapped_names as $overlapped_name)
												if (substr($info["filename"], 0, strlen($overlapped_name) + 1) == $overlapped_name . "_") {
													$belongs_to_this_driver = false;
													break;
												}
											
											if ($belongs_to_this_driver) {
												$new_file = $new_db_driver_name . substr($file, $length);
												$changed = true;
												
												//rename presentation attributes_settings file
												if (!rename($folder_path . $file, $folder_path . $new_file)) 
													$non_renamed_files[] = str_replace($global_paths["LAYER_PATH"], "", $folder_path . $file);
												else if ($bl_tasks || $da_tasks) {
													//$length + 1 => $old_db_driver_name . "_"
													//-20 => "_attributes_settings"
													$table_extra_alias = substr($info["filename"], $length + 1, -20); 
													$module_id = basename($folder_path);
													$old_table_extra_xml_name = $old_db_driver_name . "_" . $table_extra_alias;
													$new_table_extra_xml_name = $new_db_driver_name . "_" . $table_extra_alias;
													$old_table_extra_hbn_obj_name = str_replace("_", "", self::getObjectNameFromLabel($old_table_extra_xml_name));
													$new_table_extra_hbn_obj_name = str_replace("_", "", self::getObjectNameFromLabel($new_table_extra_xml_name));
													$old_table_extra_bl_obj_name = $old_table_extra_hbn_obj_name . "Service";
													$new_table_extra_bl_obj_name = $new_table_extra_hbn_obj_name . "Service";
													
													//rename business logic file
													if ($bl_tasks)
														foreach ($bl_tasks as $task) {
															self::prepareLabel($task["label"]);
															$ll = self::getVariableNameFromLabel($task["label"]);
															
															//if layer folder exists
															if (file_exists($global_paths["LAYER_PATH"] . $ll)) {
																$layer_module_folder_path = $global_paths["LAYER_PATH"] . "$ll/module/$module_id/";
																$old_fp = $layer_module_folder_path . $old_table_extra_bl_obj_name . ".php";
																
																//if module extra table file exists
																if (file_exists($old_fp)) {
																	$new_fp = $layer_module_folder_path . $new_table_extra_bl_obj_name . ".php";
																	
																	//if new file doesn't exists, rename old file with new name
																	if (!file_exists($new_fp)) {
																		//rename class of object, all object calls and data-access rules and file name too.
																		if (!CMSProgramExtraTableInstallationUtil::updateBusinessLogicServiceClassNameInFile($old_fp, $old_table_extra_bl_obj_name, $new_table_extra_bl_obj_name) || !CMSProgramExtraTableInstallationUtil::updateOldExtraAttributesTableCode($old_fp, $old_table_extra_xml_name, $new_table_extra_xml_name) || !rename($old_fp, $new_fp))
																			$non_renamed_files[] = str_replace($global_paths["LAYER_PATH"], "", $new_fp);
																	}
																	else
																		$non_renamed_files[] = str_replace($global_paths["LAYER_PATH"], "", $old_fp);
																}
															}
														}
													
													//rename ibatis and hibernate files
													if ($da_tasks)
														foreach ($da_tasks as $task) {
															self::prepareLabel($task["label"]);
															$ll = self::getVariableNameFromLabel($task["label"]);
															
															//if layer folder exists
															if (file_exists($global_paths["LAYER_PATH"] . $ll)) {
																$layer_module_folder_path = $global_paths["LAYER_PATH"] . "$ll/module/$module_id/";
																$old_fp = $layer_module_folder_path . $old_table_extra_xml_name . ".xml";
																
																//if module extra table file exists
																if (file_exists($old_fp)) {
																	$new_fp = $layer_module_folder_path . $new_table_extra_xml_name . ".xml";
																	
																	//if new file doesn't exists, rename old file with new name
																	if (!file_exists($new_fp)) {
																		//rename rules and file name too
																		if (!CMSProgramExtraTableInstallationUtil::updateOldExtraAttributesTableCode($old_fp, $old_table_extra_xml_name, $new_table_extra_xml_name, false, true) || !rename($old_fp, $new_fp))
																			$non_renamed_files[] = str_replace($global_paths["LAYER_PATH"], "", $new_fp);
																	}
																	else
																		$non_renamed_files[] = str_replace($global_paths["LAYER_PATH"], "", $old_fp);
																}
															}
														}
												}
											}
										}
									}
						}
					}
				}
			}
		}
		
		return $non_renamed_files;
	}
	
	public function getChangedDBDrivers($options = null) {
		$changed_db_drivers = array();
		
		$tasks_folders = $options ? $options["tasks_folders"] : null;
		
		if ($tasks_folders) {
			$tasks = $this->WorkFlowTasksFileHandler->getTasksByLayerTag("dbdriver");
			
			if ($tasks) {
				$l = count($tasks);
				
				for ($i = 0; $i < $l; $i++) {
					$task = $tasks[$i];
					$task_id = $task["id"];
					$old_db_driver_name = $tasks_folders[$task_id];
					
					//only if existed before
					if ($old_db_driver_name) {
						self::prepareLabel($task["label"]);
						$new_db_driver_name = self::getVariableNameFromLabel($task["label"]);
						
						//if name changed
						if ($new_db_driver_name != $old_db_driver_name) {
							//get overlapped names
							$overlapped_names = array();
							$length = strlen($old_db_driver_name);
							
							for ($j = 0; $j < $l; $j++) {
								$t = $tasks[$j];
								$t_id = $t["id"];
								
								if ($t_id != $task_id) {
									self::prepareLabel($t["label"]);
									$t_name = self::getVariableNameFromLabel($t["label"]);
									
									if (strlen($t_name) > $length && substr($t_name, 0, $length) == $old_db_driver_name)
										$overlapped_names[] = $t_name;
								}
							}
							
							$changed_db_drivers[$new_db_driver_name] = array($old_db_driver_name, $overlapped_names);
						}
					}
				}
			}
		}
		
		return $changed_db_drivers;
	}
	
	//check the new tasks and check if any has a different name than previously. If so, rename the old folder with the new name
	public function removeDeprecatedProjectLayouts($UserAuthenticationHandler, $tasks_folders) {
		if ($tasks_folders) {
			$tasks = $this->WorkFlowTasksFileHandler->getWorkflowData();
			$status = true;
			
			foreach ($tasks_folders as $task_id => $task_folder) 
				if ($task_id && $task_folder && (!$tasks || !$tasks["tasks"] || !$tasks["tasks"][$task_id])) {
					$bean_file_prefix = $task_folder . "_pl";
					$bean_file_path = $this->getBeansFilePath($bean_file_prefix);
					//error_log("bean_file_path:$bean_file_path\n", 3, $GLOBALS["log_file_path"]);
					
					if (file_exists($bean_file_path)) {
						$on = self::getObjectNameFromLabel($task_folder);
						$bean_name = $on . 'PLayer';
						$bean_file_name = $this->getBeansFileName($bean_file_prefix);
						$xml_content = file_get_contents($bean_file_path);
						//error_log("xml_content:$xml_content\n", 3, $GLOBALS["log_file_path"]);
						
						if ($xml_content && preg_match('/<bean\s+([^>]*)name\s*=\s*"PresPLayer"([^>]*)>/', $xml_content, $matches, PREG_OFFSET_CAPTURE)) {
							$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $this->user_global_variables_file_path, $this->user_beans_folder_path, $bean_file_name, $bean_name);
							$layer_path = LAYER_PATH . $task_folder . "/";
							//error_log("layer_path:$layer_path\n", 3, $GLOBALS["log_file_path"]);
							
							if (!$LayoutTypeProjectHandler->removeLayoutFromProjectFolderPath($layer_path))
								$status = false;
						}
					}
				}
		}
		
		return $status;
	}
	
	public function createSetupDefaultProjectLayouts($UserAuthenticationHandler, $create_layers_project_folder_if_not_exists = true) {
		$status = true;
		$setup_project_name = $this->WorkFlowBeansFolderHandler->getSetupProjectName();
		//echo "setup_project_name:$setup_project_name";die();
		
		//loop all presentation beans
		$tasks = $this->WorkFlowTasksFileHandler->getTasksByLayerTag("presentation");
		//echo "<pre>";print_r($tasks);die();
		
		foreach ($tasks as $task) {
			if (!$task["properties"]["active"]) 
				continue 1;
			
			self::prepareLabel($task["label"]);
			$ll = self::getVariableNameFromLabel($task["label"]);
			$on = self::getObjectNameFromLabel($task["label"]);
			
			$bean_name = $on . 'PLayer';
			$bean_file_prefix = self::getFileNameFromLabel($task["label"]) . "_pl";
			$bean_file_name = $this->getBeansFileName($bean_file_prefix);
			
			$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $this->user_global_variables_file_path, $this->user_beans_folder_path, $bean_file_name, $bean_name);
			
			if (!$LayoutTypeProjectHandler->createPresentationLayerSetupDefaultProjectLayouts($setup_project_name, $create_layers_project_folder_if_not_exists))
				$status = false;
		}
		
		return $status;
	}
	
	private function getSelectedDefaultLayerFolder() {
		$tasks = $this->WorkFlowTasksFileHandler->getWorkflowData();
		$allowed_task_tags = array($this->task_layer_tags["db"], $this->task_layer_tags["dataaccess"], $this->task_layer_tags["businesslogic"], $this->task_layer_tags["presentation"]);
		
		if ($tasks && $tasks["tasks"])
			foreach ($tasks["tasks"] as $task)
				if (in_array($task["tag"], $allowed_task_tags) && $task["start"]) {
					self::prepareLabel($task["label"]);
					$ll = self::getVariableNameFromLabel($task["label"]);
					return $ll;
				}
		
		return null;
	}
	
	private function createPresentationBeans($task_id_to_filter = array()) {
		$status = true;
		
		$task_id_to_filter = $task_id_to_filter && !is_array($task_id_to_filter) ? array($task_id_to_filter) : $task_id_to_filter;
		$tasks = $this->WorkFlowTasksFileHandler->getTasksByLayerTag("presentation");
		
		foreach ($tasks as $task) {
			if (!$task["properties"]["active"] || ($task_id_to_filter && !in_array($task["id"], $task_id_to_filter))) 
				continue 1;
			
			self::prepareLabel($task["label"]);
			$ll = self::getVariableNameFromLabel($task["label"]);
			$on = self::getObjectNameFromLabel($task["label"]);
			
			$xml = '<?xml version="1.0" encoding="UTF-8"?>
<beans>
	<import relative="1">app.xml</import>

	<!-- PRESENTATION -->
	<var name="' . $ll . '_p_vars">
		<list>
			<item name="presentations_path"><?php echo LAYER_PATH; ?>' . $ll . '/</item>
			<item name="presentations_modules_file_path"><?php echo LAYER_PATH; ?>' . $ll . '/modules.xml</item>
			<item name="presentation_configs_path">src/config/</item>
			<item name="presentation_utils_path">src/util/</item>
			<item name="presentation_controllers_path">src/controller/</item>
			<item name="presentation_entities_path">src/entity/</item>
			<item name="presentation_views_path">src/view/</item>
			<item name="presentation_templates_path">src/template/</item>
			<item name="presentation_blocks_path">src/block/</item>
			<item name="presentation_modules_path">src/module/</item>
			<item name="presentation_webroot_path">webroot/</item>
	
			<item name="presentation_common_project_name">common</item>
			<item name="presentation_common_path"><?php echo LAYER_PATH; ?>presentation/common/</item>
	
			<!--item name="presentation_files_extension">php</item-->
		</list>
	</var>

	<!-- DISPATCHER CACHE -->
	<var name="' . $ll . '_dispatcher_cache_vars">
		<list>
			<item name="dispatcher_caches_path">src/config/cache/</item>
			<item name="dispatchers_cache_file_name">dispatcher.xml</item>
			<item name="dispatchers_cache_path"><?php echo LAYER_CACHE_PATH;?>' . $ll . '/dispatcher/</item>
			<item name="dispatchers_default_cache_ttl">600</item>
			<item name="dispatchers_default_cache_type">text</item>
			<item name="dispatchers_module_cache_maximum_size"></item>
		</list>
	</var>

	<bean name="' . $on . 'PDispatcherCacheHandler" path="lib.org.phpframework.dispatcher.DispatcherCacheHandler">
		<constructor_arg reference="' . $ll . '_dispatcher_cache_vars" />
		<constructor_arg reference="' . $ll . '_p_vars" />
	</bean>

	<!-- PRESENTATION -->';

			$aux = $this->getBrokerClients($task);
			$xml .= $aux[0];
			$add_broker_xml = $aux[1];

			$xml .= '
			
	<bean name="' . $on . 'PLayer" path="lib.org.phpframework.layer.presentation.PresentationLayer">
		<constructor_arg reference="' . $ll . '_p_vars" />

		<property name="isDefaultLayer" value="' . ($task["start"] ? 1 : 0) . '" />
		<property name="cacheLayer" reference="' . $on . 'PCacheLayer" />
		<property name="PHPFrameWorkObjName" reference="phpframework_obj_name" />
		' . $add_broker_xml . '
	</bean>

	<var name="' . $ll . '_p_cache_vars">
		<list>
			<item name="presentation_caches_path">src/config/cache/</item>
			<item name="presentations_cache_file_name">pages.xml</item>
			<item name="presentations_cache_path"><?php echo LAYER_CACHE_PATH; ?>' . $ll . '/pages/</item>
			<item name="presentations_default_cache_ttl">600</item>
			<item name="presentations_default_cache_type">text</item>
			<item name="presentations_module_cache_maximum_size"></item>
		</list>
	</var>

	<bean name="' . $on . 'PCacheLayer" path="lib.org.phpframework.layer.cache.PresentationCacheLayer">
		<constructor_arg reference="' . $on . 'PLayer" />
		<constructor_arg reference="' . $ll . '_p_cache_vars" />
	</bean>

	<!-- EVC + CMS LAYER -->
	<bean name="' . $on . 'EVC" path="lib.org.phpframework.layer.presentation.evc.EVC">
		<property name="presentationLayer" reference="' . $on . 'PLayer" />
		<property name="defaultController">index</property>
	</bean>
	
	<bean name="' . $on . 'CMSLayer" path="lib.org.phpframework.layer.presentation.cms.CMSLayer">
		<constructor_arg reference="' . $on . 'EVC" />
		
		<property name="cacheLayer" reference="' . $on . 'MultipleCMSCacheLayer" />
	</bean>
	
	<function name="setCMSLayer" reference="' . $on . 'EVC">
		<parameter reference="' . $on . 'CMSLayer" />
	</function>
	
	<var name="' . $ll . '_multiple_cms_cache_vars">
		<list>
			<item name="presentation_cms_module_caches_path">src/config/cache/</item>
			<item name="presentations_cms_module_cache_file_name">modules.xml</item>
			<item name="presentations_cms_module_cache_path"><?php echo LAYER_CACHE_PATH; ?>' . $ll . '/modules/</item>
			<item name="presentations_cms_module_default_cache_ttl">600</item>
			<item name="presentations_cms_module_default_cache_type">text</item>
			<item name="presentations_cms_module_module_cache_maximum_size"></item>
			
			<item name="presentation_cms_block_caches_path">src/config/cache/</item>
			<item name="presentations_cms_block_cache_file_name">blocks.xml</item>
			<item name="presentations_cms_block_cache_path"><?php echo LAYER_CACHE_PATH; ?>' . $ll . '/blocks/</item>
			<item name="presentations_cms_block_default_cache_ttl">600</item>
			<item name="presentations_cms_block_default_cache_type">text</item>
			<item name="presentations_cms_block_module_cache_maximum_size"></item>
		</list>
	</var>
	
	<bean name="' . $on . 'MultipleCMSCacheLayer" path="lib.org.phpframework.layer.presentation.cms.cache.MultipleCMSCacheLayer">
		<constructor_arg reference="' . $on . 'CMSLayer" />
		<constructor_arg reference="' . $ll . '_multiple_cms_cache_vars" />
	</bean>

	<!-- ROUTER -->
	<var name="' . $ll . '_router_vars">
		<list>
			<item name="routers_path">src/config/</item>
			<item name="routers_file_name">router.xml</item>
		</list>
	</var>

	<bean name="' . $on . 'PRouter" path="lib.org.phpframework.router.PresentationRouter">
		<constructor_arg reference="' . $ll . '_router_vars" />

		<property name="presentationLayer" reference="' . $on . 'PLayer" />
	</bean>

	<!-- PRESENTATION_DISPATCHER -->
	<bean name="' . $on . 'EVCDispatcher" path="lib.org.phpframework.dispatcher.EVCDispatcher">
		<property name="router" reference="' . $on . 'PRouter" />
		<property name="EVC" reference="' . $on . 'EVC" />
	</bean>
	<!--bean name="' . $on . 'EVCDispatcher" path="org.phpframework.dispatcher.PresentationDispatcher" path_prefix="<?php echo LIB_PATH;?>">
		<property name="router" reference="' . $on . 'PRouter" />
		<property name="presentationLayer" reference="' . $on . 'PLayer" />
	</bean-->
</beans>';

			if (!$this->saveBeansFile(self::getFileNameFromLabel($task["label"]) . "_pl", $xml))
				$status = false;
		}
		
		return $status;
	}
	
	private function createBusinessLogicCommonServicesBeans($task_id_to_filter = array()) {
		$status = true;
		
		$task_id_to_filter = $task_id_to_filter && !is_array($task_id_to_filter) ? array($task_id_to_filter) : $task_id_to_filter;
		$tasks = $this->WorkFlowTasksFileHandler->getTasksByLayerTag("businesslogic");
		
		foreach ($tasks as $task) {
			if (!$task["properties"]["active"] || ($task_id_to_filter && !in_array($task["id"], $task_id_to_filter))) 
				continue 1;
			
			self::prepareLabel($task["label"]);
			$ll = self::getVariableNameFromLabel($task["label"]);
			$on = self::getObjectNameFromLabel($task["label"]);
			
			$xml = '<?xml version="1.0" encoding="UTF-8"?>
<beans>
	<bean name="CommonService" namespace="' . $ll . '" path="CommonService" path_prefix="<?php echo $vars["business_logic_modules_common_path"];?>" extension="php">
		<property name="PHPFrameWorkObjName"><?php echo $vars["phpframework_obj_name"] ? $vars["phpframework_obj_name"] : $objs["phpframework_obj_name"]; ?></property>
		<property name="businessLogicLayer" reference="' . $on . 'BLLayer" />
		<property name="userCacheHandler" reference="UserCacheHandler" />
	</bean>
</beans>';
			
			if (!$this->saveBeansFile(self::getFileNameFromLabel($task["label"]) . "_bll_common_services", $xml))
				$status = false;
		}
		
		return $status;			
	}
	
	private function createBusinessLogicLayerBeans($task_id_to_filter = array()) {
		$status = true;
		
		$task_id_to_filter = $task_id_to_filter && !is_array($task_id_to_filter) ? array($task_id_to_filter) : $task_id_to_filter;
		$tasks = $this->WorkFlowTasksFileHandler->getTasksByLayerTag("businesslogic");
		
		foreach ($tasks as $task) {
			if (!$task["properties"]["active"] || ($task_id_to_filter && !in_array($task["id"], $task_id_to_filter))) 
				continue 1;
			
			self::prepareLabel($task["label"]);
			$ll = self::getVariableNameFromLabel($task["label"]);
			$on = self::getObjectNameFromLabel($task["label"]);
			
			$xml = '<?xml version="1.0" encoding="UTF-8"?>
<beans>
	<import relative="1">app.xml</import>
	
	<!-- BUSINESS LOGIC -->';

			$aux = $this->getBrokerClients($task);
			$xml .= $aux[0];
			$add_broker_xml = $aux[1];
			
			$xml .= '

	<var name="' . $ll . '_business_logic_vars">
		<list>
			<item name="business_logic_path"><?php echo LAYER_PATH; ?>' . $ll . '/</item>
			<item name="business_logic_modules_file_path"><?php echo LAYER_PATH; ?>' . $ll . '/modules.xml</item>
			<item name="business_logic_services_file_name">services.xml</item>
	
			<item name="business_logic_modules_common_name">common</item>
			<item name="business_logic_modules_common_path"><?php echo LAYER_PATH; ?>' . $ll . '/common/</item>
			<item name="business_logic_modules_service_common_file_path"><?php echo LAYER_PATH; ?>' . $ll . '/common/CommonService.php</item>
	
			<item name="business_logic_services_annotations_enabled">1</item>
		</list>
	</var>

	<bean name="' . $on . 'BLLayer" path="lib.org.phpframework.layer.businesslogic.BusinessLogicLayer">
		<constructor_arg reference="' . $ll . '_business_logic_vars" />

		<property name="isDefaultLayer" value="' . ($task["start"] ? 1 : 0) . '" />
		<property name="cacheLayer" reference="' . $on . 'BLCacheLayer" />
		<property name="PHPFrameWorkObjName" reference="phpframework_obj_name" />
		<property name="docBlockParser" reference="' . $on . 'BLDocBlockParser" />
		' . $add_broker_xml . '
	</bean>
	' . $this->getBrokerServers($task) . '

	<var name="' . $ll . '_business_logic_cache_vars">
		<list>
			<item name="business_logic_cache_file_name">cache.xml</item>
			<item name="business_logic_cache_path"><?php echo LAYER_CACHE_PATH; ?>' . $ll . '/</item>
			<item name="business_logic_default_cache_ttl">600</item>
			<item name="business_logic_module_cache_maximum_size"></item>
		</list>
	</var>

	<bean name="' . $on . 'BLCacheLayer" path="lib.org.phpframework.layer.cache.BusinessLogicCacheLayer">
		<constructor_arg reference="' . $on . 'BLLayer" />
		<constructor_arg reference="' . $ll . '_business_logic_cache_vars" />
	</bean>

	<bean name="' . $on . 'BLDocBlockParser" path="org.phpframework.phpscript.docblock.DocBlockParser">
		<property name="cacheHandler" reference="' . $on . 'BLDocBlockParserCacheHandler" />
	</bean>

	<bean name="' . $on . 'BLDocBlockParserCacheHandler" path="org.phpframework.cache.user.filesystem.FileSystemUserCacheHandler">
		<property name="rootPath"><?php echo LAYER_CACHE_PATH; ?>' . $ll . '/annotations/</property>
	</bean>
</beans>';
			
			if (!$this->saveBeansFile(self::getFileNameFromLabel($task["label"]) . "_bll", $xml, $task["properties"]))
				$status = false;
		}
		
		return $status;
	}
	
	private function createDataAccessLayerBeans($task_id_to_filter = array()) {
		$status = true;
		
		$task_id_to_filter = $task_id_to_filter && !is_array($task_id_to_filter) ? array($task_id_to_filter) : $task_id_to_filter;
		$tasks = $this->WorkFlowTasksFileHandler->getTasksByLayerTag("dataaccess");
		
		foreach ($tasks as $task) {
			if (!$task["properties"]["active"] || ($task_id_to_filter && !in_array($task["id"], $task_id_to_filter))) 
				continue 1;
			
			self::prepareLabel($task["label"]);
			$ll = self::getVariableNameFromLabel($task["label"]);
			$on = self::getObjectNameFromLabel($task["label"]);
			
			$xml = '<?xml version="1.0" encoding="UTF-8"?>
<beans>
	<import relative="1">app.xml</import>
	';
			
			$aux = $this->getBrokerClients($task);
			$xml .= $aux[0];
			$add_broker_xml = $aux[1];

			if ($task["properties"]["type"] == "ibatis") {
				$xml .= '
	<!-- IBATIS -->
	<bean name="' . $on . 'IClient" path="lib.org.phpframework.sqlmap.ibatis.IBatisClient"></bean>

	<var name="' . $ll . '_ida_vars">
		<list>
			<item name="dal_path"><?php echo LAYER_PATH; ?>' . $ll . '/</item>
			<item name="dal_modules_file_path"><?php echo LAYER_PATH; ?>' . $ll . '/modules.xml</item>
			<item name="dal_services_file_name">services.xml</item>
		</list>
	</var>

	<bean name="' . $on . 'IDALayer" path="lib.org.phpframework.layer.dataaccess.IbatisDataAccessLayer">
		<constructor_arg reference="' . $on . 'IClient" />
		<constructor_arg reference="' . $ll . '_ida_vars" />

		<property name="isDefaultLayer" value="' . ($task["start"] ? 1 : 0) . '" />
		<property name="cacheLayer" reference="' . $on . 'IDACacheLayer" />
		<property name="PHPFrameWorkObjName" reference="phpframework_obj_name" />
		' . $add_broker_xml . '

		<function name="setDefaultBrokerName">
			<parameter value="&lt;?php echo $GLOBALS[\'default_db_broker\']; ?>" />
		</function>
	</bean>
	' . $this->getBrokerServers($task) . '

	<var name="' . $ll . '_ida_cache_vars">
		<list>
			<item name="dal_cache_file_name">cache.xml</item>
			<item name="dal_cache_path"><?php echo LAYER_CACHE_PATH; ?>' . $ll . '/</item>
			<item name="dal_default_cache_ttl">600</item>
			<item name="dal_module_cache_maximum_size"></item>
		</list>
	</var>

	<bean name="' . $on . 'IDACacheLayer" path="lib.org.phpframework.layer.cache.DataAccessCacheLayer">
		<constructor_arg reference="' . $on . 'IDALayer" />
		<constructor_arg reference="' . $ll . '_ida_cache_vars" />
	</bean>';
			}
			else {//HIBERNATE
				$xml .= '
	<!-- HIBERNATE -->
	<bean name="' . $on . 'HClient" path="lib.org.phpframework.sqlmap.hibernate.HibernateClient"></bean>

	<var name="' . $ll . '_hda_vars">
		<list>
			<item name="dal_path"><?php echo LAYER_PATH; ?>' . $ll . '/</item>
			<item name="dal_modules_file_path"><?php echo LAYER_PATH; ?>' . $ll . '/modules.xml</item>
			<item name="dal_services_file_name">services.xml</item>
		</list>
	</var>

	<bean name="' . $on . 'HDALayer" path="lib.org.phpframework.layer.dataaccess.HibernateDataAccessLayer">
		<constructor_arg reference="' . $on . 'HClient" /> 
		<constructor_arg reference="' . $ll . '_hda_vars" />

		<property name="isDefaultLayer" value="' . ($task["start"] ? 1 : 0) . '" />
		<property name="cacheLayer" reference="' . $on . 'HDACacheLayer" />
		<property name="PHPFrameWorkObjName" reference="phpframework_obj_name" />
		' . $add_broker_xml . '

		<function name="setDefaultBrokerName">
			<parameter value="&lt;?php echo $GLOBALS[\'default_db_broker\']; ?>" />
		</function>
	</bean>
	' . $this->getBrokerServers($task) . '

	<var name="' . $ll . '_hda_cache_vars">
		<list>
			<item name="dal_cache_file_name">cache.xml</item>
			<item name="dal_cache_path"><?php echo LAYER_CACHE_PATH; ?>' . $ll . '/</item>
			<item name="dal_default_cache_ttl">600</item>
			<item name="dal_module_cache_maximum_size"></item>
		</list>
	</var>

	<bean name="' . $on . 'HDACacheLayer" path="lib.org.phpframework.layer.cache.DataAccessCacheLayer">
		<constructor_arg reference="' . $on . 'HDALayer" />
		<constructor_arg reference="' . $ll . '_hda_cache_vars" />
	</bean>';
			}
			
			$xml .= '
</beans>';
			
			if (!$this->saveBeansFile(self::getFileNameFromLabel($task["label"]) . "_dal", $xml, $task["properties"]))
				$status = false;
		}
		
		return $status;
	}
	
	private function createDBLayerBeans($task_id_to_filter = array()) {
		$status = true;
		
		$task_id_to_filter = $task_id_to_filter && !is_array($task_id_to_filter) ? array($task_id_to_filter) : $task_id_to_filter;
		$tasks = $this->WorkFlowTasksFileHandler->getTasksByLayerTag("db");
		
		foreach ($tasks as $task) {
			if (!$task["properties"]["active"] || ($task_id_to_filter && !in_array($task["id"], $task_id_to_filter))) 
				continue 1;
			
			self::prepareLabel($task["label"]);
			$ll = self::getVariableNameFromLabel($task["label"]);
			$on = self::getObjectNameFromLabel($task["label"]);
			
			$xml = '<?xml version="1.0" encoding="UTF-8"?>
<beans>
	<import relative="1">app.xml</import>';

			//Preparing DB Drivers
			$aux = $this->getBrokerClients($task);
			$xml .= $aux[0];
			$add_broker_xml = $aux[1];
			
			//Preparing rest of xml
			$xml .= '

	<!-- DB -->
	<var name="' . $ll . '_dbl_vars">
		<list>
			<item name="dbl_path"><?php echo LAYER_PATH; ?>' . $ll . '/</item>
		</list>
	</var>
	
	<bean name="' . $on . 'DBLayer" path="lib.org.phpframework.layer.db.DBLayer">
		<constructor_arg reference="' . $ll . '_dbl_vars" />
		
		<property name="isDefaultLayer" value="' . ($task["start"] ? 1 : 0) . '" />
		<property name="cacheLayer" reference="' . $on . 'DBCacheLayer" />
		<property name="PHPFrameWorkObjName" reference="phpframework_obj_name" />
		' . $add_broker_xml . '

		<function name="setDefaultBrokerName">
			<parameter value="&lt;?php echo $GLOBALS[\'default_db_driver\']; ?>" />
		</function>
	</bean>
	' . $this->getBrokerServers($task) . '
	
	<var name="' . $ll . '_dbl_cache_vars">
		<list>
			<item name="dbl_cache_file_name">cache.xml</item>
			<item name="dbl_cache_path"><?php echo LAYER_CACHE_PATH; ?>' . $ll . '/</item>
			<item name="dbl_default_cache_ttl">600</item>
			<item name="dbl_module_cache_maximum_size"></item>
		</list>
	</var>
	
	<bean name="' . $on . 'DBCacheLayer" path="lib.org.phpframework.layer.cache.DBCacheLayer">
		<constructor_arg reference="' . $on . 'DBLayer" />
		<constructor_arg reference="' . $ll . '_dbl_cache_vars" />
	</bean>
</beans>';
			
			if (!$this->saveBeansFile(self::getFileNameFromLabel($task["label"]) . "_dbl", $xml, $task["properties"]))
				$status = false;
		}
	
		return $status;
	}
	
	private function createDBDriverBeans($task_id_to_filter = array()) {
		$status = true;
		
		$task_id_to_filter = $task_id_to_filter && !is_array($task_id_to_filter) ? array($task_id_to_filter) : $task_id_to_filter;
		$tasks = $this->WorkFlowTasksFileHandler->getTasksByLayerTag("dbdriver");
		
		foreach ($tasks as $task) {
			if (!$task["properties"]["active"] || ($task_id_to_filter && !in_array($task["id"], $task_id_to_filter))) 
				continue 1;
			
			self::prepareLabel($task["label"]);
			$ll = self::getVariableNameFromLabel($task["label"]);
			$on = self::getObjectNameFromLabel($task["label"]);
			
			$class_name = DB::getDriverClassNameByType($task["properties"]["type"]);
			$class = "lib.org.phpframework.db.driver." . ($class_name ? $class_name : "MySqlDB");
		
			$xml = '<?xml version="1.0" encoding="UTF-8"?>
<beans>
	<import relative="1">app.xml</import>

	<!-- DRIVER -->
	<var name="' . $ll . '_options">
		<list>';
			
			$cdata_props = array("host", "db_name", "username", "password", "schema", "odbc_data_source", "odbc_driver", "extra_dsn");
			
			foreach ($task["properties"] as $property_name => $property_value)
				if (!in_array($property_name, array("type", "active")))
					$xml .= '
			<item name="' . $property_name . '">' . (in_array($property_name, $cdata_props) ? '<![CDATA[' . self::prepareValue($property_value) . ']]>' : self::prepareValue($property_value)) . '</item>';
			
			$xml .= '
		</list>
	</var>
	<bean name="' . $on . '" path="' . $class . '" bean_group="dbdriver">
		<function name="setOptions">
			<parameter reference="' . $ll . '_options" />
		</function>
	</bean>
</beans>';
			if (!$this->saveBeansFile(self::getFileNameFromLabel($task["label"]) . "_dbdriver", $xml))
				$status = false;
		}
	
		return $status;
	}

	private function createAppBeans() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
<beans>
	<var name="phpframework_obj_name">PHPFrameWork</var>
	
	<!-- MESSAGE -->
	<var name="message_vars">
		<list>
			<item name="messages_path"><?php echo CMS_PATH; ?>other/data/messages/</item>
			<item name="messages_modules_file_path"><?php echo CMS_PATH; ?>other/data/messages/modules.xml</item>
			<item name="messages_cache_path"><?php echo LAYER_CACHE_PATH; ?>messages/</item>
			<item name="messages_module_cache_maximum_size"></item>
			<item name="messages_default_cache_ttl"><?php echo 365 * 24 * 60 * 60; ?></item>
			<item name="messages_default_cache_type">php</item>
		</list>
	</var>
	
	<bean name="MessageHandler" path="lib.org.phpframework.message.MessageHandler">
		<constructor_arg reference="message_vars" />
	</bean>
	
	<bean name="UserCacheHandler" path="org.phpframework.cache.user.filesystem.FileSystemUserCacheHandler" path_prefix="<?php echo LIB_PATH;?>">
		<property name="rootPath"><?php echo CACHE_PATH; ?>user_cache/</property>
	</bean>
	
	<!-- LOG -->
	<!-- Only change the LOG_HANDLER if you want to have your own class. But your own class must be an extend of the org.phpframework.log.LogHandler or must implements the interface org.phpframework.log.ILogHandler -->
	<!--bean name="LogHandler" path="org.phpframework.log.LogHandler" path_prefix="<?php echo LIB_PATH;?>"></bean-->
	
	<var name="log_vars">
		<list>
			<item name="log_level">&lt;?php echo $GLOBALS["log_level"]; ?&gt;</item>
			<item name="log_echo_active">&lt;?php echo $GLOBALS["log_echo_active"]; ?&gt;</item>
			<item name="log_file_path">&lt;?php echo $GLOBALS["log_file_path"]; ?&gt;</item>
			<item name="log_css"><![CDATA[
				body {
					overflow:overlay;
					background-color:#F0F1F5;
					font-family:verdana,arial,courier;
					font-size:11px;
				}
				.log_handler {
					font-style:italic;
					
					color:#83889E;
					/*background-color:#F8F9FC;
					border:1px outset #BFC4DB;
					border-radius:5px;*/
					margin:10px;
					padding:5px;
				}
				
				.log_handler .message {
					color:#333;
					position:relative;
				}
				.log_handler .message .exception {
					color:#FF0000;
				}
				.log_handler .message .error {
					color:#990000;
				}
				.log_handler .message .info {
					color:#000099;
				}
				.log_handler .message .debug {
					color:#009999;
				}
				.log_handler .message .message {
					color:#009900;
				}
				.log_handler .message .toggle_trace {
					margin-right:10px;
					display:inline-block;
					font-style:normal;
					font-weight:bold;
					cursor:pointer;
				}
				.log_handler .message p {
					margin:0;
					padding:0;
				}
				.log_handler .trace {
					margin-top:15px;
					font-size:10px;
				}
				.log_handler .trace.hidden {
					display:none;
				}
				.log_handler .trace .exception {
					white-space:nowrap;
				}
			]]></item>
		</list>
	</var>
</beans>';
		return $this->saveBeansFile("app", $xml);
	}
	
	private function getBrokerClients($task) {
		$broker_client_xml = '';
		$add_broker_xml = '';
		
		$allowed_broker_types = array();
		switch($task["tag"]) {
			case $this->task_layer_tags["db"]: 
				$allowed_broker_types = array( $this->task_layer_tags["dbdriver"] ); 
				break;
			case $this->task_layer_tags["dataaccess"]: 
				$allowed_broker_types = array( $this->task_layer_tags["db"] ); 
				break;
			case $this->task_layer_tags["businesslogic"]: 
			case $this->task_layer_tags["presentation"]:
				$allowed_broker_types = array( $this->task_layer_tags["businesslogic"], $this->task_layer_tags["dataaccess"], $this->task_layer_tags["db"] ); 
				break;
		}
		
		if (!empty($task["exits"]["layer_exit"])) {
			$exits = isset($task["exits"]["layer_exit"][0]) ? $task["exits"]["layer_exit"] : array($task["exits"]["layer_exit"]);
			
			if ($exits) {
				$on = self::getObjectNameFromLabel($task["label"]);
				$tasks = $this->WorkFlowTasksFileHandler->getWorkflowData();
				$repeated_connection_types = array();
				
				$t = count($exits);
				for ($i = 0; $i < $t; $i++) {
					foreach ($tasks["tasks"] as $sub_task) {
						//preparing brokers
						if ($sub_task["id"] == $exits[$i]["task_id"] && in_array($sub_task["tag"], $allowed_broker_types) && $sub_task["properties"]["active"]) {
							self::prepareLabel($sub_task["label"]);
							$sub_ll = self::getVariableNameFromLabel($sub_task["label"]);
							$sub_on = self::getObjectNameFromLabel($sub_task["label"]);
							$sub_broker_name = self::getBrokerNameFromLabel($sub_task["label"]);
							
							//preparing connection properties
							$sub_task_layer_brokers = $sub_task["properties"]["layer_brokers"];
							$exit_properties = $exits[$i]["properties"];
							$connection_type = $exit_properties["connection_type"];
							$lib_prefix = "lib.org.phpframework.broker.client.local.Local";
							$name_prefix = "";
							$list = "";
							
							//checks if exists any remote broker defined in the sub_task
							if (!$sub_task_layer_brokers[$connection_type]["active"])
								$connection_type = "";
							
							if (!$repeated_connection_types[ $sub_task["id"] ] || !in_array($connection_type, $repeated_connection_types[ $sub_task["id"] ])) {
								$repeated_connection_types[ $sub_task["id"] ][] = $connection_type;
								
								//if remote broker correctly defined
								if ($connection_type) {
									$lib_prefix = "lib.org.phpframework.broker.client.$connection_type." . strtoupper($connection_type);
									$name_prefix = self::getBrokerServerNamePrefix($connection_type);
									
									//prepare connection_settings - converts connection_settings to an associative array
									if (is_array($exit_properties["connection_settings"]) && $exit_properties["connection_settings"]["vars_name"]) {
										$vars_name = $exit_properties["connection_settings"]["vars_name"];
										$vars_value = $exit_properties["connection_settings"]["vars_value"];
										$exit_properties["connection_settings"] = array();
										
										if (!is_array($vars_name)) {
											$vars_name = array($vars_name);
											$vars_value = array($vars_value);
										}
										
										foreach ($vars_name as $idx => $name)
											if ($name)
												$exit_properties["connection_settings"][$name] = $vars_value[$idx];
									}
									else
										$exit_properties["connection_settings"] = array();
									
									if ($sub_task_layer_brokers[$connection_type]) {
										$exit_properties["connection_settings"] = array_merge($sub_task_layer_brokers[$connection_type], $exit_properties["connection_settings"]);
										
										unset($exit_properties["connection_settings"]["active"]);
										unset($exit_properties["connection_settings"]["other_settings"]); //Note: do not add the $sub_task_layer_brokers[$connection_type]["other_settings"] here bc it doesn't make sense. The $sub_task_layer_brokers[$connection_type]["other_settings"] is correspondent to the $sub_task. Not here!
									}
									
									$exit_properties["connection_settings"]["response_type"] = $exit_properties["connection_response_type"];
									
									$list = self::getRemoteBrokerBeanExtraConstructArgsXML($exit_properties);
									$list = $list ? $list . "\n\t" : "";
								}
								
								if ($sub_task["tag"] == $this->task_layer_tags["dataaccess"]) {
									if ($sub_task["properties"]["type"] == "ibatis") {
										if ($connection_type)
											$broker_client_xml .= '
	<bean name="' . $on . $sub_on . 'IDABrokerClient" path="' . $lib_prefix . 'IbatisDataAccessBrokerClient">' . $list . '</bean>';
										else
											$broker_client_xml .= '
	<bean name="' . $on . $sub_on . 'IDABrokerClient" path="' . $lib_prefix . 'IbatisDataAccessBrokerClient">
		<property name="PHPFrameWorkObjName" reference="phpframework_obj_name" />

		<function name="addBeansFilePath">
			<parameter><?php echo BEAN_PATH; ?>' . $sub_ll . '_dal.xml</parameter>
		</function>
		<function name="setBeanName">
			<parameter>' . $name_prefix . $sub_on . 'IDABrokerServer</parameter>
		</function>
	</bean>';
									
										$add_broker_xml .= '
		<function name="addBroker">
			<parameter reference="' . $on . $sub_on . 'IDABrokerClient" />
			<parameter value="' . $sub_broker_name . '" />
		</function>';
									}
									else {
										if ($connection_type)
											$broker_client_xml .= '
	<bean name="' . $on . $sub_on . 'HDABrokerClient" path="' . $lib_prefix . 'HibernateDataAccessBrokerClient">' . $list . '</bean>';
										else
											$broker_client_xml .= '
	<bean name="' . $on . $sub_on . 'HDABrokerClient" path="' . $lib_prefix . 'HibernateDataAccessBrokerClient">
		<property name="PHPFrameWorkObjName" reference="phpframework_obj_name" />

		<function name="addBeansFilePath">
			<parameter><?php echo BEAN_PATH; ?>' . $sub_ll . '_dal.xml</parameter>
		</function>
		<function name="setBeanName">
			<parameter>' . $name_prefix . $sub_on . 'HDABrokerServer</parameter>
		</function>
	</bean>';
									
										$add_broker_xml .= '
		<function name="addBroker">
			<parameter reference="' . $on . $sub_on . 'HDABrokerClient" />
			<parameter value="' . $sub_broker_name . '" />
		</function>';
									}
					
									break;
								}
								else if ($sub_task["tag"] == $this->task_layer_tags["businesslogic"]) {
									if ($connection_type)
										$broker_client_xml .= '
	<bean name="' . $on . $sub_on . 'BLBrokerClient" path="' . $lib_prefix . 'BusinessLogicBrokerClient">' . $list . '</bean>';
									else
										$broker_client_xml .= '
	<bean name="' . $on . $sub_on . 'BLBrokerClient" path="' . $lib_prefix . 'BusinessLogicBrokerClient">
		<property name="PHPFrameWorkObjName" reference="phpframework_obj_name" />

		<function name="addBeansFilePath">
			<parameter><?php echo BEAN_PATH; ?>' . $sub_ll . '_bll.xml</parameter>
		</function>
		<function name="setBeanName">
			<parameter>' . $name_prefix . $sub_on . 'BLBrokerServer</parameter>
		</function>
	</bean>';
								
									$add_broker_xml .= '
		<function name="addBroker">
			<parameter reference="' . $on . $sub_on . 'BLBrokerClient" />
			<parameter value="' . $sub_broker_name . '" />
		</function>';
								
									break;
								}
								else if ($sub_task["tag"] == $this->task_layer_tags["db"]) {
									if ($connection_type)
										$broker_client_xml .= '
	<bean name="' . $on . $sub_on . 'DBBrokerClient" path="' . $lib_prefix . 'DBBrokerClient">' . $list . '</bean>';
									else
										$broker_client_xml .= '
	<bean name="' . $on . $sub_on . 'DBBrokerClient" path="' . $lib_prefix . 'DBBrokerClient">
		<property name="PHPFrameWorkObjName" reference="phpframework_obj_name" />

		<function name="addBeansFilePath">
			<parameter><?php echo BEAN_PATH; ?>' . $sub_ll . '_dbl.xml</parameter>
		</function>
		<function name="setBeanName">
			<parameter>' . $name_prefix . $sub_on . 'DBBrokerServer</parameter>
		</function>
	</bean>';
								
									$add_broker_xml .= '
		<function name="addBroker">
			<parameter reference="' . $on . $sub_on . 'DBBrokerClient" />
			<parameter value="' . $sub_broker_name . '" />
		</function>';
								
									break;
								}
								else if ($sub_task["tag"] == $this->task_layer_tags["dbdriver"]) {
									$broker_client_xml .= '
	<import relative="1">' . self::getFileNameFromLabel($sub_task["label"]) . '_dbdriver.xml</import>';
									
									$add_broker_xml .= '
		<function name="addBroker">
			<parameter reference="' . $sub_on . '" />
			<parameter value="' . $sub_broker_name . '" />
		</function>';
								
									break;
								}
							}
						}
					}
				}
			}
		}
		
		return array($broker_client_xml, $add_broker_xml);
	}
	
	private function getBrokerServers($task) {
		$broker_server_xml = '';
		
		$task_id = $task["id"];
		
		$allowed_parent_broker_types = array();
		switch($task["tag"]) {
			case $this->task_layer_tags["dbdriver"]: 
				$allowed_parent_broker_types = array( $this->task_layer_tags["db"], $this->task_layer_tags["businesslogic"], $this->task_layer_tags["presentation"] ); 
				break;
			case $this->task_layer_tags["db"]: 
				$allowed_parent_broker_types = array( $this->task_layer_tags["dataaccess"], $this->task_layer_tags["businesslogic"], $this->task_layer_tags["presentation"] ); 
				break;
			case $this->task_layer_tags["dataaccess"]: 
				$allowed_parent_broker_types = array( $this->task_layer_tags["businesslogic"], $this->task_layer_tags["presentation"] ); 
				break;
			case $this->task_layer_tags["businesslogic"]: 
				$allowed_parent_broker_types = array( $this->task_layer_tags["businesslogic"], $this->task_layer_tags["presentation"] ); 
				break;
			case $this->task_layer_tags["presentation"]: 
				$allowed_parent_broker_types = array(); 
				break;
		}
		
		if ($allowed_parent_broker_types) {
			$on = self::getObjectNameFromLabel($task["label"]);
			$tasks = $this->WorkFlowTasksFileHandler->getWorkflowData();
			$has_local_connection = false;
			
			//check if there is any local connection
			foreach ($tasks["tasks"] as $sub_task)
				if (!empty($sub_task["exits"]["layer_exit"]) && $sub_task["properties"]["active"]) { 
					//must be active, otherwise the connection will be ignored, this is, if the $sub_task is disabled, do NOT create Broker Server. Note that $task is always active, otherwise it wouldn't enter in this function!
					
					$exits = isset($sub_task["exits"]["layer_exit"][0]) ? $sub_task["exits"]["layer_exit"] : array($sub_task["exits"]["layer_exit"]);
					
					if ($exits) {
						$t = count($exits);
						for ($i = 0; $i < $t; $i++)
							if ($exits[$i]["task_id"] == $task_id && in_array($sub_task["tag"], $allowed_parent_broker_types)) {
								$exit_properties = $exits[$i]["properties"];
								$connection_type = $exit_properties["connection_type"];
								
								if (!$connection_type || !$task["properties"]["layer_brokers"][$connection_type]["active"]) {
									$has_local_connection = true;
									break;
								}
							}
						
						if ($has_local_connection)
							break;
					}
				}
			
			//create local broker server
			if ($has_local_connection) {
				if ($task["tag"] == $this->task_layer_tags["dataaccess"]) {
					if ($task["properties"]["type"] == "ibatis")
						$broker_server_xml .= '
	<bean name="' . $on . 'IDABrokerServer" path="lib.org.phpframework.broker.server.local.LocalIbatisDataAccessBrokerServer">
		<constructor_arg reference="' . $on . 'IDALayer" />
	</bean>';
					else
						$broker_server_xml .= '
	<bean name="' . $on . 'HDABrokerServer" path="lib.org.phpframework.broker.server.local.LocalHibernateDataAccessBrokerServer">
		<constructor_arg reference="' . $on . 'HDALayer" />
	</bean>';
				}
				else if ($task["tag"] == $this->task_layer_tags["businesslogic"])
					$broker_server_xml .= '
	<bean name="' . $on . 'BLBrokerServer" path="lib.org.phpframework.broker.server.local.LocalBusinessLogicBrokerServer">
		<constructor_arg reference="' . $on . 'BLLayer" />
	</bean>';
				else if ($task["tag"] == $this->task_layer_tags["db"])
					$broker_server_xml .= '
	<bean name="' . $on . 'DBBrokerServer" path="lib.org.phpframework.broker.server.local.LocalDBBrokerServer">
		<constructor_arg reference="' . $on . 'DBLayer" />
	</bean>';
			}
			
			//create remote brokers servers
			$layer_brokers = $task["properties"]["layer_brokers"];
			
			if ($layer_brokers)
				foreach ($layer_brokers as $layer_broker_type => $layer_broker) 
					if ($layer_broker["active"]) {
						$lib_prefix = "lib.org.phpframework.broker.server.$layer_broker_type." . strtoupper($layer_broker_type);
						$name_prefix = self::getBrokerServerNamePrefix($layer_broker_type);
						
						//merge other_settings with default settings
						if (is_array($layer_broker["other_settings"]) && $layer_broker["other_settings"]["vars_name"]) { 
							$vars_name = $layer_broker["other_settings"]["vars_name"];
							$vars_value = $layer_broker["other_settings"]["vars_value"];
							
							if (!is_array($vars_name)) {
								$vars_name = array($vars_name);
								$vars_value = array($vars_value);
							}
							
							foreach ($vars_name as $idx => $name)
								if ($name)
									$layer_broker[$name] = $vars_value[$idx];
						}
						
						//avoid some request settings
						unset($layer_broker["url"]);
						unset($layer_broker["http_auth"]);
						unset($layer_broker["user_pwd"]);
						unset($layer_broker["other_settings"]);
						unset($layer_broker["global_variables"]);
						unset($layer_broker["active"]);
						
						$list = self::getRemoteBrokerBeanExtraConstructArgsXML(array("connection_settings" => $layer_broker));
						
						if ($task["tag"] == $this->task_layer_tags["dataaccess"]) {
							if ($task["properties"]["type"] == "ibatis")
								$broker_server_xml .= '
	<bean name="' . $name_prefix . $on . 'IDABrokerServer" path="' . $lib_prefix . 'IbatisDataAccessBrokerServer">
		<constructor_arg reference="' . $on . 'IDALayer" />' . $list . '
	</bean>';
							else
								$broker_server_xml .= '
	<bean name="' . $name_prefix . $on . 'HDABrokerServer" path="' . $lib_prefix . 'HibernateDataAccessBrokerServer">
		<constructor_arg reference="' . $on . 'HDALayer" />' . $list . '
	</bean>';
						}
						else if ($task["tag"] == $this->task_layer_tags["businesslogic"]) 
							$broker_server_xml .= '
	<bean name="' . $name_prefix . $on . 'BLBrokerServer" path="' . $lib_prefix . 'BusinessLogicBrokerServer">
		<constructor_arg reference="' . $on . 'BLLayer" />' . $list . '
	</bean>';
						else if ($task["tag"] == $this->task_layer_tags["db"])
							$broker_server_xml .= '
	<bean name="' . $name_prefix . $on . 'DBBrokerServer" path="' . $lib_prefix . 'DBBrokerServer">
		<constructor_arg reference="' . $on . 'DBLayer" />' . $list . '
	</bean>';
					}
		}
		
		return $broker_server_xml;
	}
	
	private function saveBeansFile($file_name, $xml, $settings = false) {
		$file_path = $this->getBeansFilePath($file_name);
		
		return file_put_contents($file_path, $xml) && $this->WorkFlowBeansFolderHandler->prepareBeansFolder($file_path, $settings);
	}
	
	private function getBeansFilePath($file_name) {
		return $this->user_beans_folder_path . $this->getBeansFileName($file_name);
	}
	
	private function getBeansFileName($file_name) {
		return $file_name . ".xml";
	}
	
	private static function getRemoteBrokerBeanExtraConstructArgsXML($exit_properties) {
		$xml = "";
		
		$connection_settings = $exit_properties["connection_settings"];
		$connection_global_vars_name = $exit_properties["connection_global_variables_name"];
		$connection_global_vars_name = $connection_global_vars_name && !is_array($connection_global_vars_name) ? array($connection_global_vars_name) : $connection_global_vars_name;
		
		if ($connection_settings || $connection_global_vars_name) {
			$xml = "";
		
			if ($connection_settings) {
				$xml .= "
		<constructor_arg>
			<list>";
			
				foreach ($connection_settings as $connection_settings_name => $connection_settings_value)
					$xml .= "
				" . '<item name="' . self::prepareValue($connection_settings_name) . '">' . self::prepareValue($connection_settings_value) . '</item>';
										
					$xml .= "
			</list>
		</constructor_arg>";
			}
			else
				$xml .= "
		<constructor_arg />";
		
			if ($connection_global_vars_name) {
				$xml .= "
		<constructor_arg>
			<list>";
				
				foreach ($connection_global_vars_name as $var_name)
					$xml .= "
				" . '<item>' . self::prepareValue($var_name) . '</item>';
				
				$xml .= "
			</list>
		</constructor_arg>";
			}
		}
		
		return $xml;
	}
	
	private static function getBrokerServerNamePrefix($connection_type) {
		return $connection_type ? self::getObjectNameFromLabel($connection_type) : "";
	}
	
	//if label if not a php code or a variable, replace all " " in strings with "_"
	private static function getVariableNameFromLabel($label) {
		if (strpos($label, "<?") === false && strpos($label, "&lt;?") === false && strpos($label, '$') === false) 
			return str_replace(" ", "_", strtolower($label));
		
		return $label;
	}
	
	//if label if not a php code or a variable, ucwords and then removes all " " and "_" in strings
	private static function getObjectNameFromLabel($label) {
		if (strpos($label, "<?") === false && strpos($label, "&lt;?") === false && strpos($label, '$') === false)
			return str_replace(" ", "_", ucwords(str_replace("_", " ", strtolower($label))));
		
		return $label;
	}
	
	private static function getBrokerNameFromLabel($label) {
		return self::getVariableNameFromLabel($label);
	}
	
	//if label if not a php code or a variable, replace all " " in strings with "_"
	private static function getFileNameFromLabel($label) {
		return self::getVariableNameFromLabel($label);
	}
	
	//replace all the " " and "-' from any strings (non php code) in $label with "_"
	private static function prepareLabel(&$label) {
		self::prepareValue($label);
		
		preg_match_all('/&lt;\?([^>]*)\?>/u', $label,  $out, PREG_OFFSET_CAPTURE); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
		
		$vars = $out[0];
		$start = 0;
		$new_label = "";
		
		if (!empty($vars)) {
			$t = count($vars);
			for ($i = 0; $i < $t; $i++) {
				$var_name = $vars[$i][0];
				$idx = $vars[$i][1];
			
				$item = substr($label, $start, $idx - $start);
				//echo "item ($start - $idx): $item\n";
			
				if (!empty($item)) {
					//$new_label .= str_replace(array("-", " "), "_", strtoupper($item));//OLD - DEPRECATED
					$new_label .= str_replace(array("-", " "), "_", $item);
				}
			
				$new_label .= $var_name;
				$start = $idx + strlen($var_name);
			}
			$label = $new_label;
		}
		
		return $label;
	}
	
	/*replace all the $variables in $value with <?php echo $GLOBALS['#var_name#']; ?>*/
	private static function prepareValue(&$value) {
		preg_match_all('/\{?([\\\\]*)\$\{?([\w]+)}?/u', $value,  $out, PREG_PATTERN_ORDER); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars. 'u' means converts to unicode.
		
		$vars = $out[0];
		
		//sort array by item value's length...
		usort($vars, function($a, $b) {
			return strlen($b) - strlen($a);
		});
		
		$t = count($vars);
		for ($i = 0; $i < $t; $i++) {
			$var_name = str_replace(array("{", "}"), "", $vars[$i]);
			$is_escaped = false;
			$slashes = null;
			
			if (preg_match('/^\\\\+\$/', $var_name)) {
				preg_match_all('/\\\\/', substr($var_name, 0, strpos($var_name, '$')),  $slashes, PREG_PATTERN_ORDER);
				$is_escaped = $slashes[0] && count($slashes[0]) % 2 != 0;
			}
			
			if (!$is_escaped) {
				$var_name = preg_replace('/^\\\\*\$/', '', $var_name);
				$value = str_replace($vars[$i], '<?php echo $GLOBALS[\'' . $var_name . '\']; ?>', $value);
			}
		}
		
		$value = str_replace('<?', '&lt;?', $value);
		
		return $value;
	}

	public static function getVariableNameFromRawLabel($label) {
		self::prepareLabel($label);
		return self::getVariableNameFromLabel($label);
	}

	public static function getObjectNameFromRawLabel($label) {
		self::prepareLabel($label);
		return self::getObjectNameFromLabel($label);
	}

	public static function getBrokerNameFromRawLabel($label) {
		self::prepareLabel($label);
		return self::getBrokerNameFromLabel($label);
	}

	//dash is allowed. Only upper case will be converted to lower case and spaces to under-score.
	public static function getFileNameFromRawLabel($label) {
		self::prepareLabel($label);
		return self::getFileNameFromLabel($label);
	}
}
?>
