<?php
class CMSProgramExtraTableInstallationUtil {
	
	const EXTRA_ATTRIBUTES_TABLE_NAME_SUFFIX = "extra";
	
	protected $user_global_variables_file_path;
	protected $db_drivers;
	protected $projects_evcs;
	protected $program_id;
	protected $unzipped_program_path;
	protected $user_settings;
	protected $UserAuthenticationHandler;
	
	protected $presentation_modules_paths;
	protected $business_logic_modules_paths;
	protected $ibatis_modules_paths;
	protected $hibernate_modules_paths;
	
	protected $messages;
	protected $errors;
	
	public function renameProjectsTableExtraAttributesCallsInPresentationFilesBasedInDefaultDBDriver($table_alias) {
		$status = true;
		
		if ($table_alias && $this->projects_evcs) {
			$entities_path = $this->unzipped_program_path . "/presentation/entity/";
			$entities = $this->getFolderPagesList($entities_path);
			
			$blocks_path = $this->unzipped_program_path . "/presentation/block/";
			$bloks = $this->getFolderPagesList($blocks_path);
			
			$utils_path = $this->unzipped_program_path . "/presentation/util/";
			$utils = $this->getFolderPagesList($utils_path);
			
			foreach ($this->projects_evcs as $broker_name => $projects)
				foreach ($projects as $project => $PEVC)
					if ($PEVC) {
						//set global variables
						$pre_init_config_path = $PEVC->getConfigPath("pre_init_config");
						$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($this->user_global_variables_file_path, $pre_init_config_path));
						$PHPVariablesFileHandler->startUserGlobalVariables();
						
						//get default_db_driver
						$db_driver_name = !empty($GLOBALS["default_db_driver"]) ? $GLOBALS["default_db_driver"] : "default";
						
						//rollback to original global variables
						$PHPVariablesFileHandler->endUserGlobalVariables();
						
						$project_entities_path = $PEVC->getEntitiesPath() . $this->program_id . "/";
						$project_blocks_path = $PEVC->getBlocksPath() . $this->program_id . "/";
						$project_utils_path = $PEVC->getUtilsPath() . $this->program_id . "/";
						
						if (!$this->renameProjectTableExtraAttributesCallsInPresentationFiles($table_alias, $db_driver_name, $project_entities_path, $entities) || !$this->renameProjectTableExtraAttributesCallsInPresentationFiles($table_alias, $db_driver_name, $project_blocks_path, $bloks) || !$this->renameProjectTableExtraAttributesCallsInPresentationFiles($table_alias, $db_driver_name, $project_utils_path, $utils))
							$status = false;
					}
		}
		
		return $status;
	}
	
	public function renameProjectTableExtraAttributesCallsInPresentationFiles($table_alias, $db_driver_name, $parent_folder, $relative_files) {
		$status = true;
		
		if ($table_alias && $db_driver_name && $relative_files) {
			$table_extra_alias = $table_alias . "_" . self::EXTRA_ATTRIBUTES_TABLE_NAME_SUFFIX;
			
			foreach ($relative_files as $f) {
				$fp = $parent_folder . $f;
				
				if (pathinfo($f, PATHINFO_EXTENSION) == "php" && file_exists($fp)) 
					if (!self::updateOldExtraAttributesTableCode($fp, $table_extra_alias, $db_driver_name . "_" . $table_extra_alias, true))
						$status = false;
			}
		}
		
		return $status;	
	}
	
	public function copyTableExtraAttributesSettings($module_id, $table, $table_alias, $src_files) {
		$status = true;
		
		$table_extra = $table . "_" . self::EXTRA_ATTRIBUTES_TABLE_NAME_SUFFIX;
		$table_extra_alias = $table_alias . "_" . self::EXTRA_ATTRIBUTES_TABLE_NAME_SUFFIX;
		
		if (!empty($this->user_settings["db_drivers"])) {
			//only if exists presentation_program_paths, otherwise it means that no project was selected and so do not copy the table_extra_attributes_settings.php
			//copy table_extra_attributes_settings.php to common/src/module/module_id/ folders
			if ($this->presentation_modules_paths && !empty($src_files["attributes_settings"])) {
				$src_fp = $src_files["attributes_settings"];
				$basename = "{$table_extra_alias}_attributes_settings.php";
				
				foreach ($this->presentation_modules_paths as $path) {
					$module_path = $path . $module_id . "/";
					$relative_module_path = str_replace(LAYER_PATH, "", $module_path);
					
					if (!is_dir($module_path)) {
						$this->addError(ucfirst($module_id) . " module is not installed in '$relative_module_path' layer!");
						$status = false;
					}
					else {
						foreach ($this->user_settings["db_drivers"] as $db_driver_name) {
							$dst_fp = $module_path . $db_driver_name . "_$basename";
							
							if (file_exists($dst_fp)) {
								if (!self::mergeTableExtraAttributesSettingsFiles($dst_fp, $src_fp)) {
									$this->addError("Could not merge {$table_extra_alias}_attributes_settings.php in '" . $relative_module_path . "'!");
									$status = false;
								}
							}
							else if (!copy($src_fp, $dst_fp)) {
								$this->addError("Could not copy {$table_extra_alias}_attributes_settings.php to '" . $relative_module_path . "'!");
								$status = false;
							}
						}
					}
				}
			}
			
			//only if exists business_logic_modules_paths, otherwise it means that no project was selected and so do not copy the TableExtraService.php
			//copy GenericTableExtraService.php to module/module_id/ folders
			if ($this->business_logic_modules_paths && !empty($src_files["generic_business_logic_service"])) {
				$src_fp = $src_files["generic_business_logic_service"];
				$src_object_name = pathinfo($src_fp, PATHINFO_FILENAME);
				
				$basename = self::getObjectName($table_extra_alias) . "Service.php";
				
				foreach ($this->business_logic_modules_paths as $path) {
					$module_path = $path . $module_id . "/";
					$relative_module_path = str_replace(LAYER_PATH, "", $module_path);
					
					if (!is_dir($module_path)) {
						$this->addError(ucfirst($module_id) . " module is not installed in '$relative_module_path' layer!");
						$status = false;
					}
					else {
						$dst_fp = $module_path . $basename;
						$dst_object_name = pathinfo($basename, PATHINFO_FILENAME);
						$exists = file_exists($dst_fp);
						
						if ($exists && !self::mergeBusinessLogicServiceFiles($dst_fp, $src_fp, null, null, $bkp_fp)) {
							$this->addError("Could not merge $basename in '" . $relative_module_path . "'!");
							$status = false;
						}
						else if (!$exists && !copy($src_fp, $dst_fp)) {
							$this->addError("Could not copy $basename to '" . $relative_module_path . "'!");
							$status = false;
						}
						else if (!$exists || !empty($bkp_fp)) { //if old file does not exist or if exists and is different
							if (!self::updateBusinessLogicServiceClassNameInFile($dst_fp, $src_object_name, $dst_object_name)) {
								$this->addError("Could not rename business logic class name in '" . $relative_module_path . $basename . "'!");
								$status = false;
							}
						}
					}
				}
			}
			
			//only if exists business_logic_modules_paths, otherwise it means that no project was selected and so do not copy the TableExtraService.php
			//copy TableExtraService.php to module/module_id/ folders
			if ($this->business_logic_modules_paths && !empty($src_files["business_logic_service"])) {
				$src_fp = $src_files["business_logic_service"];
				$src_object_name = self::getObjectName($table_extra_alias) . "Service";
				
				foreach ($this->user_settings["db_drivers"] as $db_driver_name) {
					$basename = self::getObjectName($db_driver_name . "_" . $table_extra_alias) . "Service.php";
					
					foreach ($this->business_logic_modules_paths as $path) {
						$module_path = $path . $module_id . "/";
						$relative_module_path = str_replace(LAYER_PATH, "", $module_path);
						
						if (!is_dir($module_path)) {
							$this->addError(ucfirst($module_id) . " module is not installed in '$relative_module_path' layer!");
							$status = false;
						}
						else {
							$dst_fp = $module_path . $basename;
							$dst_object_name = pathinfo($basename, PATHINFO_FILENAME);
							$exists = file_exists($dst_fp);
							
							if ($exists && !self::mergeBusinessLogicServiceFiles($dst_fp, $src_fp, $table_extra_alias, $db_driver_name, $bkp_fp)) {
								$this->addError("Could not merge $basename in '" . $relative_module_path . "'!");
								$status = false;
							}
							else if (!$exists && !copy($src_fp, $dst_fp)) {
								$this->addError("Could not copy $basename to '" . $relative_module_path . "'!");
								$status = false;
							}
							else if (!$exists || !empty($bkp_fp)) { //if old file does not exist or if exists and is different
								if (!empty($bkp_fp)) {
									$bkp_frp = str_replace(LAYER_PATH, "", $bkp_fp);
									$this->addMessage("Note that this program added new attributes to the Article module and replace the previous Business Logic Service in '{$relative_module_path}$basename'. If you wish to have the '$basename' with your old attributes too, please merge this file with the '$bkp_frp' file.");
								}
								
								if (!self::updateOldExtraAttributesTableCode($dst_fp, $table_extra_alias, $db_driver_name . "_" . $table_extra_alias)) {
									$this->addError("Could not update code with db driver info in '" . $relative_module_path . $basename . "'!");
									$status = false;
								}
								else if (!self::updateBusinessLogicServiceClassNameInFile($dst_fp, $src_object_name, $dst_object_name)) {
									$this->addError("Could not rename business logic class name in '" . $relative_module_path . $basename . "'!");
									$status = false;
								}
							}
						}
					}
				}
			}
			
			//only if exists ibatis_modules_paths, otherwise it means that no project was selected and so do not copy the table_extra.xml
			//copy table_extra.xml to module/module_id/ folders
			if ($this->ibatis_modules_paths && !empty($src_files["ibatis"])) {
				$src_fp = $src_files["ibatis"];
				
				foreach ($this->user_settings["db_drivers"] as $db_driver_name) {
					$basename = $db_driver_name . "_" . $table_extra_alias . ".xml";
					
					foreach ($this->ibatis_modules_paths as $path) {
						$module_path = $path . $module_id . "/";
						$relative_module_path = str_replace(LAYER_PATH, "", $module_path);
						
						if (!is_dir($module_path)) {
							$this->addError(ucfirst($module_id) . " module is not installed in '$relative_module_path' layer!");
							$status = false;
						}
						else {
							$dst_fp = $module_path . $basename;
							$exists = file_exists($dst_fp);
							
							if ($exists && !self::mergeIbatisRulesFiles($dst_fp, $src_fp, $table_extra_alias, $db_driver_name, $bkp_fp)) {
								$this->addError("Could not merge $basename in '" . $relative_module_path . "'!");
								$status = false;
							}
							else if (!$exists && !copy($src_fp, $dst_fp)) {
								$this->addError("Could not copy $basename to '" . $relative_module_path . "'!");
								$status = false;
							}
							else if (!$exists || !empty($bkp_fp)) { //if old file does not exist or if exists and is different
								if (!empty($bkp_fp)) {
									$bkp_frp = str_replace(LAYER_PATH, "", $bkp_fp);
									$this->addMessage("Note that this program added new attributes to the Article module and replace the previous Ibatis Rules in '{$relative_module_path}$basename'. If you wish to have the '$basename' with your old attributes too, please merge this file with the '$bkp_frp' file.");
								}
								
								if (!self::updateOldExtraAttributesTableCode($dst_fp, $table_extra_alias, $db_driver_name . "_" . $table_extra_alias)) {
									$this->addError("Could not update code with db driver info in '" . $relative_module_path . $basename . "'!");
									$status = false;
								}
							}
						}
					}
				}
			}
			
			//only if exists hibernate_modules_paths, otherwise it means that no project was selected and so do not copy the table_extra.xml
			//copy table_extra.xml to module/module_id/ folders
			if ($this->hibernate_modules_paths && !empty($src_files["hibernate"])) {
				$src_fp = $src_files["hibernate"];
				
				foreach ($this->user_settings["db_drivers"] as $db_driver_name) {
					$basename = $db_driver_name . "_" . $table_extra_alias . ".xml";
					
					foreach ($this->hibernate_modules_paths as $path) {
						$module_path = $path . $module_id . "/";
						$relative_module_path = str_replace(LAYER_PATH, "", $module_path);
						
						if (!is_dir($module_path)) {
							$this->addError(ucfirst($module_id) . " module is not installed in '$relative_module_path' layer!");
							$status = false;
						}
						else {
							$dst_fp = $module_path . $basename;
							$exists = file_exists($dst_fp);
							
							if ($exists && !self::mergeHibernateRulesFiles($dst_fp, $src_fp, $table_extra_alias, $db_driver_name, $bkp_fp)) {
								$this->addError("Could not merge $basename in '" . $relative_module_path . "'!");
								$status = false;
							}
							else if (!$exists && !copy($src_fp, $dst_fp)) {
								$this->addError("Could not copy $basename to '" . $relative_module_path . "'!");
								$status = false;
							}
							else if (!$exists || !empty($bkp_fp)) { //if old file does not exist or if exists and is different
								if (!empty($bkp_fp)) {
									$bkp_frp = str_replace(LAYER_PATH, "", $bkp_fp);
									$this->addMessage("Note that this program added new attributes to the Article module and replace the previous Hibernate Rules in '{$relative_module_path}$basename'. If you wish to have the '$basename' with your old attributes too, please merge this file with the '$bkp_frp' file.");
								}
								
								if (!self::updateOldExtraAttributesTableCode($dst_fp, $table_extra_alias, $db_driver_name . "_" . $table_extra_alias, false, true)) {
									$this->addError("Could not update code with db driver info in '" . $relative_module_path . $basename . "'!");
									$status = false;
								}
							}
						}
					}
				}
			}
		}
		
		//only if exists db drivers, otherwise it means that no db driver was selected and so it should not execute the DB.
		//create table extra with correspondent attributes in DBs.
		if ($this->existsDBs() && !empty($src_files["attributes_settings"])) {	
			//insert table_extra into DBs
			include $src_files["attributes_settings"];
			
			if (!empty($table_extra_attributes_settings)) {
				$sub_status = true;
				
				//save this table into our internal register so we can detect later if a db table belongs to a module. Leave this code before the next if method, bc if the table already exists it won't not insert it in the UserAuthenticationHandler.
				if ($this->UserAuthenticationHandler)
					$this->UserAuthenticationHandler->insertReservedDBTableNameIfNotExistsYet(array("name" => $table_extra));
				
				foreach ($this->db_drivers as $db_driver) {
					$sql_options = $db_driver->getOptions();
					$tables = $db_driver->listTables();
					$exists = $db_driver->isTableInNamesList($tables, $table_extra);
					
					if ($exists) {
						$table_attrs = $db_driver->listTableFields($table_extra);
						
						foreach ($table_extra_attributes_settings as $attr_name => $attr_props) {
							if (empty($table_attrs[$attr_name]) && !empty($attr_props["db_attribute"])) {
								$sql = $db_driver->getAddTableAttributeStatement($table_extra, $attr_props["db_attribute"], $sql_options);
								
								if (!$db_driver->setData($sql))
									$sub_status = false;
							}
						}
					}
					else { //create table with attributes
						$table_attrs = $db_driver->listTableFields($table);
						$attributes = array();
						
						if ($table_attrs)
							foreach ($table_attrs as $attr_name => $attr_props)
								if (!empty($attr_props["primary_key"]))
									$attributes[] = $attr_props;
						
						if (!$attributes) {
							$this->addError("No primary keys for table '$table' in one of the DB Drivers!");
							$sub_status = false;
						}
						else {
							foreach ($table_extra_attributes_settings as $attr_name => $attr_props)
								if (!empty($attr_props["db_attribute"]))
									$attributes[] = $attr_props["db_attribute"];
							
							$table_data = array(
								"table_name" => $table_extra,
								"attributes" => $attributes,
							);
							$sql = $db_driver->getCreateTableStatement($table_data, $sql_options);
							
							if (!$db_driver->setData($sql))
								$sub_status = false;
						}
					}
				}
				
				if (!$sub_status) {
					$this->addError("Could not add the '$table_extra' table with the correspondent attributes in all DB Drivers!");
					$status = false;
				}
			}
		}
		
		return $status;
	}
	
	private static function mergeTableExtraAttributesSettingsFiles($orig_file, $new_file) {
		$status = true;
		
		$orig_code = file_get_contents($orig_file);
		$new_code = file_get_contents($new_file);
		
		if (trim($orig_code) != trim($new_code)) {
			include $new_file;
			
			if (!empty($table_extra_attributes_settings)) {
				$bkp = $table_extra_attributes_settings;
				
				include $orig_file;
				$table_extra_attributes_settings = $table_extra_attributes_settings ? array_merge($bkp, $table_extra_attributes_settings) : $bkp;
				
				$code = "<?php\n\$table_extra_attributes_settings = " . var_export($table_extra_attributes_settings, true) . ";\n?>";
				
				if (file_put_contents($orig_file, $code) === false)
					$status = false;
			}
		}
		
		return $status;
	}
	
	//used in the __system/layer/presentation/phpframework/src/util/WorkFlowBeansConverter::renameExtraAttributesFiles
	public static function updateBusinessLogicServiceClassNameInFile($file, $old_object_name, $new_object_name) {
		$status = true;
		
		$code = file_get_contents($file);
		$new_code = self::updateBusinessLogicServiceClassNameInCode($code, $old_object_name, $new_object_name);
		
		if ($code != $new_code && file_put_contents($file, $new_code) === false)
			$status = false;
		
		return $status;
	}
	
	private static function updateBusinessLogicServiceClassNameInCode($code, $old_object_name, $new_object_name) {
		return preg_replace("/class\s+" . $old_object_name . "(\s+|\{)/", "class " . $new_object_name . '$1', $code);
	}
	
	//Doesn't merge. Instead, backup old file and copy new one.
	private static function mergeBusinessLogicServiceFiles($orig_file, $new_file, $table_extra_alias = null, $db_driver_name = null, &$new_orig_file = null) {
		$status = true;
		
		$orig_code = file_get_contents($orig_file);
		$new_code = file_get_contents($new_file);
		
		if ($table_extra_alias && $db_driver_name) {
			$new_code = self::getUpdatedOldExtraAttributesTableCode($new_code, $table_extra_alias, $db_driver_name . "_" . $table_extra_alias);
			$new_code = self::updateBusinessLogicServiceClassNameInCode($new_code, pathinfo($new_file, PATHINFO_FILENAME), pathinfo($orig_file, PATHINFO_FILENAME));
		}
		
		if (trim($orig_code) != trim($new_code)) {
			$rand = rand(0, 1000000);
			$pathinfo = pathinfo($orig_file);
			$object_name = substr($pathinfo["filename"], 0, - strlen("Service"));
			$new_orig_file = dirname($orig_file) . "/" . $object_name . $rand . "Service." . (!empty($pathinfo["extension"]) ? $pathinfo["extension"] : "php");
			$status = rename($orig_file, $new_orig_file);
			
			if ($status) {
				if (!self::updateBusinessLogicServiceClassNameInFile($new_orig_file, $object_name . "Service", $object_name . $rand . "Service"))
					$status = false;
				
				if (!copy($new_file, $orig_file))
					$status = false;
			}
		}
		
		return $status;
	}
	
	private static function mergeIbatisRulesFiles($orig_file, $new_file, $table_extra_alias, $db_driver_name, &$new_orig_file) {
		$status = true;
		
		$orig_code = file_get_contents($orig_file);
		$new_code = file_get_contents($new_file);
		
		if ($table_extra_alias && $db_driver_name)
			$new_code = self::getUpdatedOldExtraAttributesTableCode($new_code, $table_extra_alias, $db_driver_name . "_" . $table_extra_alias, false, true);
		
		if (trim($orig_code) != trim($new_code)) {
			$rand = rand(0, 1000000);
			$pathinfo = pathinfo($orig_file);
			$new_orig_file = dirname($orig_file) . "/" . $pathinfo["filename"] . "_" . $rand . "." . (!empty($pathinfo["extension"]) ? $pathinfo["extension"] : "xml");
			
			if (!rename($orig_file, $new_orig_file) || !copy($new_file, $orig_file)) 
				$status = false;
		}
		
		return $status;
	}
	
	private static function mergeHibernateRulesFiles($orig_file, $new_file, $table_extra_alias, $db_driver_name, &$new_orig_file) {
		return self::mergeIbatisRulesFiles($orig_file, $new_file, $table_extra_alias, $db_driver_name, $new_orig_file);
	}
	
	//used in the __system/layer/presentation/phpframework/src/util/WorkFlowBeansConverter::renameExtraAttributesFiles
	public static function updateOldExtraAttributesTableCode($file, $old_table_extra_xml_name, $new_table_extra_xml_name, $strict = false, $is_data_access = false) {
		$code = self::getUpdatedOldExtraAttributesTableFileCode($file, $old_table_extra_xml_name, $new_table_extra_xml_name, $strict, $is_data_access);
		
		return file_put_contents($file, $code) !== false;
	}
	
	public static function getUpdatedOldExtraAttributesTableFileCode($file, $old_table_extra_xml_name, $new_table_extra_xml_name, $strict = false, $is_data_access = false) {
		$code = file_get_contents($file);
		return self::getUpdatedOldExtraAttributesTableCode($code, $old_table_extra_xml_name, $new_table_extra_xml_name, $strict, $is_data_access);
	}
	
	private static function getUpdatedOldExtraAttributesTableCode($code, $old_table_extra_xml_name, $new_table_extra_xml_name, $strict = false, $is_data_access = false) {
		$old_table_extra_hbn_obj_name = self::getObjectName($old_table_extra_xml_name);
		$new_table_extra_hbn_obj_name = self::getObjectName($new_table_extra_xml_name);
		$old_table_extra_bl_obj_name = $old_table_extra_hbn_obj_name . "Service";
		$new_table_extra_bl_obj_name = $new_table_extra_hbn_obj_name . "Service";
		
		if ($is_data_access) {
			preg_match('/table=("|\'|)(\w+)("|\'|)/u', $code, $matches, PREG_OFFSET_CAPTURE); //'\w' means all words with '_' and '/u' means with accents and ç too.
			$table_name = isset($matches[2][0]) ? $matches[2][0] : null;
		}
		
		if ($strict) {
			$code = preg_replace('/("|\')(' . preg_quote($old_table_extra_bl_obj_name) . ')(\.|"|\')/', '$1' . $new_table_extra_bl_obj_name . '$3', $code);
			$code = preg_replace('/("|\')(' . preg_quote($old_table_extra_hbn_obj_name) . ')("|\')/', '$1' . $new_table_extra_hbn_obj_name . '$3', $code);
		}
		else
			$code = str_replace($old_table_extra_hbn_obj_name, $new_table_extra_hbn_obj_name, $code);
		
		$code = preg_replace('/("|\')(\w*)(' . preg_quote($old_table_extra_xml_name) . ')(\w*)("|\')/', '$1$2' . $new_table_extra_xml_name . '$4$5', $code);
		
		if ($is_data_access && $table_name)
			$code = preg_replace('/table=("|\'|)(\w+)("|\'|)/u', 'table=$1' . $table_name . '$3', $code);
		
		return $code;
	}
	
	private static function getObjectName($name) {
		return str_replace(" ", "", ucwords(str_replace("_", " ", strtolower($name))));
	}
	
	protected function getFolderPagesList($abs_path, $rel_path = "") {
		$pages = array();
		$files = array_diff(scandir($abs_path), array('..', '.'));
		
		foreach ($files as $f) {
			$fp = $abs_path . $f;
			
			if (is_dir($fp))
				$pages = array_merge($pages, $this->getFolderPagesList($abs_path . $f . "/", $rel_path . $f . "/"));
			else
				$pages[] = $rel_path . $f;
		}
		
		return $pages;
	}
	
	/* DBS FUNCTIONS */
	
	protected function existsDBs() {
		return count($this->db_drivers);
	}
	
	/* ERRORS FUNCTIONS */
	
	public function addError($error) {
		return $this->errors[] = $error;
	}
	
	public function getErrors() {
		return $this->errors;
	}
	
	/* MESSAGES FUNCTIONS */
	
	public function addMessage($message) {
		return $this->messages[] = $message;
	}
	
	public function getMessages() {
		return $this->messages;
	}
	
	public function existsMessage($message) {
		return in_array($message, $this->messages);
	}
}
?>
