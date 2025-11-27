<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

include_once get_lib("org.phpframework.xmlfile.XMLFileParser");
include_once get_lib("org.phpframework.layer.presentation.cms.module.ICMSProgramInstallationHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleUtil"); //To be used by the CMSProgramInstallationHandlerImpl in each program
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSProgramExtraTableInstallationUtil");
include_once get_lib("org.phpframework.phpscript.PHPCodePrintingHandler");
include_once get_lib("org.phpframework.compression.ZipHandler");

class CMSProgramInstallationHandler extends CMSProgramExtraTableInstallationUtil implements ICMSProgramInstallationHandler {
	protected $EVC;
	//protected $user_global_variables_file_path;
	protected $user_beans_folder_path;
	protected $workflow_paths_id;
	protected $layer_beans_settings;
	protected $layers;
	//protected $db_drivers;
	protected $layers_brokers_settings;
	protected $vendors;
	protected $projects;
	//protected $projects_evcs;
	//protected $program_id;
	//protected $unzipped_program_path;
	//protected $user_settings;
	//protected $UserAuthenticationHandler;
	protected $program_path;
	
	protected $presentation_program_paths;
	protected $presentation_webroot_program_paths;
	protected $business_logic_program_paths;
	protected $ibatis_program_paths;
	protected $hibernate_program_paths;
	protected $dao_program_path;
	
	//protected $presentation_modules_paths;
	//protected $business_logic_modules_paths;
	//protected $ibatis_modules_paths;
	//protected $hibernate_modules_paths;
	
	protected $reserved_files; //This is initialized in each program CMSProgramInstallationHandler class
	//protected $messages;
	//protected $errors;
	
	public function __construct($EVC, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $layer_beans_settings, $layers, $db_drivers, $layers_brokers_settings, $vendors, $projects, $projects_evcs, $program_id, $unzipped_program_path, $user_settings, $UserAuthenticationHandler = null) {
		$this->EVC = $EVC;
		$this->user_global_variables_file_path = $user_global_variables_file_path; //will be used in the CMSProgramInstallationHandlerImpl in each program
		$this->user_beans_folder_path = $user_beans_folder_path; //will be used in the CMSProgramInstallationHandlerImpl in each program
		$this->workflow_paths_id = $workflow_paths_id;
		$this->layer_beans_settings = $layer_beans_settings;
		$this->layers = $layers;
		$this->db_drivers = $db_drivers;
		$this->layers_brokers_settings = $layers_brokers_settings;
		$this->vendors = $vendors;
		$this->projects = $projects;
		$this->projects_evcs = $projects_evcs;
		$this->program_id = $program_id;
		$this->unzipped_program_path = $unzipped_program_path;
		$this->user_settings = $user_settings;
		$this->UserAuthenticationHandler = $UserAuthenticationHandler;
		
		$program_info = self::getUnzippedProgramInfo($unzipped_program_path);
		$this->program_path = $program_info && !empty($program_info["path"]) ? $program_info["path"] : $this->program_id;
		
		$this->presentation_program_paths = $this->getLayerProgramPaths("PresentationLayer");
		$this->presentation_webroot_program_paths = $this->getLayerProgramPaths("PresentationLayer", true);
		$this->business_logic_program_paths = $this->getLayerProgramPaths("BusinessLogicLayer");
		$this->ibatis_program_paths = $this->getLayerProgramPaths("IbatisDataAccessLayer");
		$this->hibernate_program_paths = $this->getLayerProgramPaths("HibernateDataAccessLayer");
		$this->dao_program_path = DAO_PATH . $this->program_path . "/";
		
		$this->presentation_modules_paths = $this->getLayerModulesPaths("PresentationLayer");
		$this->business_logic_modules_paths = $this->getLayerModulesPaths("BusinessLogicLayer");
		$this->ibatis_modules_paths = $this->getLayerModulesPaths("IbatisDataAccessLayer");
		$this->hibernate_modules_paths = $this->getLayerModulesPaths("HibernateDataAccessLayer");
		
		$this->reserved_files = array();
		$this->messages = array();
		$this->errors = array();
	}
	
	/* HANDLERS FUNCTIONS */
	
	public static function createCMSProgramInstallationHandlerObject($EVC, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $layer_beans_settings, $layers, $db_drivers, $layers_brokers_settings, $vendors, $projects, $projects_evcs, $program_id, $unzipped_program_path, $user_settings, $UserAuthenticationHandler = null) {
		$CMSProgramInstallationHandler = null;
		
		try {
			$file_path = "";
			if ($unzipped_program_path && file_exists($unzipped_program_path . "/CMSProgramInstallationHandlerImpl.php"))
				$file_path = $unzipped_program_path . "/CMSProgramInstallationHandlerImpl.php";
			
			if ($file_path) {
				$class = 'CMSProgram\\' . str_replace("/", "\\", str_replace(" ", "_", trim($program_id))) . '\CMSProgramInstallationHandlerImpl';
				
				if (!class_exists($class))
					include_once $file_path;
				
				eval ('$CMSProgramInstallationHandler = new ' . $class . '($EVC, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $layer_beans_settings, $layers, $db_drivers, $layers_brokers_settings, $vendors, $projects, $projects_evcs, $program_id, $unzipped_program_path, $user_settings, $UserAuthenticationHandler);');
			}
			else
				$CMSProgramInstallationHandler = new CMSProgramInstallationHandler($EVC, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $layer_beans_settings, $layers, $db_drivers, $layers_brokers_settings, $vendors, $projects, $projects_evcs, $program_id, $unzipped_program_path, $user_settings, $UserAuthenticationHandler);
		}
		catch (Exception $e) {
			launch_exception($e);
		}
		
		return $CMSProgramInstallationHandler;
	}
	
	public static function unzipProgramFile($zipped_file_path, $unzipped_folder_path = null) {
		if (!$unzipped_folder_path) {
			$unzipped_folder_path = self::getTmpFolderPath();
			
			if (!$unzipped_folder_path)
				return false;
		}
		
		if (ZipHandler::unzip($zipped_file_path, $unzipped_folder_path))
			return $unzipped_folder_path;
		
		return null;
	}
	
	public static function getUnzippedProgramInfo($unzipped_program_path) {
		//get the program info based in the uploaded program.
		$program_xml_file_path = $unzipped_program_path . "/program.xml";
		$info = null;
		
		if (file_exists($program_xml_file_path)) {
			$arr = XMLFileParser::parseXMLFileToArray($program_xml_file_path);
			$arr = MyXML::complexArrayToBasicArray($arr, array("lower_case_keys" => true, "trim" => true));
			$info = isset($arr["program"]) ? $arr["program"] : null;
			
			if (is_array($info) && array_key_exists("with_db", $info))
				$info["with_db"] = empty($info["with_db"]) || in_array(strtolower($info["with_db"]), array("false", "null", "none")) ? false : true;
		}
		
		return $info;
	}
	
	public static function getUnzippedProgramSettingsHtml($program_id, $unzipped_program_path) {
		//get the html with extra settings based in the uploaded program.
		$program_handler_impl_file_path = $unzipped_program_path . "/CMSProgramInstallationHandlerImpl.php";
		$program_settings = "";
		
		if (file_exists($program_handler_impl_file_path)) {
			include $program_handler_impl_file_path;
			
			if (method_exists("CMSProgram\\$program_id\\CMSProgramInstallationHandlerImpl", "getProgramSettingsHtml"))
				eval("\$program_settings = CMSProgram\\$program_id\\CMSProgramInstallationHandlerImpl::getProgramSettingsHtml();");
		}
		
		return $program_settings;
	}
	
	//called from the __system/phpframework/src/entity/admin/install_program.php file. Must be here too bc is in the interface ICMSProgramInstallationHandler
	public static function getProgramSettingsHtml() {
		return null;
	}
	
	//called from the __system/phpframework/src/entity/admin/install_program.php file. Must be here too bc is in the interface ICMSProgramInstallationHandler
	public function getStepHtml($step, $extra_settings = null, $post_data = null) {
		return null; //must return false,empty or null if there are no other step html
	}
	
	//called from the __system/phpframework/src/entity/admin/install_program.php file. Must be here too bc is in the interface ICMSProgramInstallationHandler
	public function installStep($step, $extra_settings = null, $post_data = null) {
		return true; //must return status
	}
	
	public function validate() {
		return true; //This method will be changed in the CMSProgramInstallationHandlerImpl classes, by each program
	}
	
	public function install($user_settings = false) {
		$this->messages = array();
		$this->errors = array("files" => array());
		
		if ($this->validate()) { //validate dependencies
			$overwrite = isset($user_settings["overwrite"]) ? $user_settings["overwrite"] : null;
			$unzipped_sub_files = array_diff(scandir($this->unzipped_program_path), array('..', '.'));
			
			foreach ($unzipped_sub_files as $layer_type) {
				if ($layer_type == "vendor") {
					if ($this->vendors)
						foreach ($this->vendors as $file_name) {
							if (is_dir($this->unzipped_program_path . "$layer_type/$file_name")) {
								$suffix = in_array(strtolower($file_name), array("testunit", "dao")) ? $this->program_path . "/" : "";
								$project_layer_path = VENDOR_PATH . "$file_name/$suffix";
								
								self::copyProgramFolder($file_name, $this->unzipped_program_path . "$layer_type/$file_name/", $project_layer_path, $overwrite, $this->errors["files"]);
							}
							else
								self::copyProgramFile($layer_type, $this->unzipped_program_path . "$layer_type/$file_name", VENDOR_PATH . $file_name, $overwrite, $this->errors["files"]);
						}
				}
				else if ($layer_type == "presentation") {
					//$this->presentation_program_paths
					$pres_sub_files = array_diff(scandir($this->unzipped_program_path . $layer_type), array('..', '.'));
					
					if ($this->presentation_program_paths)
						foreach ($this->presentation_program_paths as $layer_path) {
							foreach ($pres_sub_files as $pres_sub_file) {
								$suffix = in_array(strtolower($pres_sub_file), array("entity", "view", "block", "util")) ? $this->program_path . "/" : "";
								$project_layer_path = $layer_path . (strtolower($pres_sub_file) == "webroot" ? "" : "src/") . "$pres_sub_file/$suffix";
								
								self::copyProgramFolder($layer_type, $this->unzipped_program_path . "$layer_type/$pres_sub_file/", $project_layer_path, $overwrite, $this->errors["files"]);
							}
						}
				}
				else  {
					$layer_paths = array();
					
					switch ($layer_type) {
						case "ibatis": $layer_paths = $this->ibatis_program_paths; break;
						case "hibernate": $layer_paths = $this->hibernate_program_paths; break;
						case "businesslogic": $layer_paths = $this->business_logic_program_paths; break;
					}
					
					if ($layer_paths)
						foreach ($layer_paths as $layer_path)
							self::copyProgramFolder($layer_type, $this->unzipped_program_path . $layer_type . "/", $layer_path, $overwrite, $this->errors["files"]);
				}
			}
		}
		
		//remove files from errors if empty
		if (empty($this->errors["files"]))
			unset($this->errors["files"]);
		
		return empty($this->errors);
	}
	
	public function uninstall() {
		$this->messages = array();
		$this->errors = array("files" => array());
		
		$reserved_files = $this->getReservedFiles();
		$unzipped_sub_files = array_diff(scandir($this->unzipped_program_path), array('..', '.'));
		
		foreach ($unzipped_sub_files as $layer_type) {
			if ($layer_type == "vendor") {
				if ($this->vendors)
					foreach ($this->vendors as $file_name) {
						if (is_dir($this->unzipped_program_path . "$layer_type/$file_name")) {
							if (in_array(strtolower($file_name), array("testunit", "dao"))) {
								//delete full folder
								$project_layer_path = VENDOR_PATH . $file_name . "/" . $this->program_path . "/";
								
								if (!CacheHandlerUtil::deleteFolder($project_layer_path, true, $reserved_files))
									$this->errors["files"][] = $project_layer_path;
							}
							else //go to folder and delete only program files
								self::deleteProgramFile($this->unzipped_program_path . "$layer_type/$file_name/", VENDOR_PATH . $file_name . "/", $reserved_files, $this->errors["files"]);
						}
						else 
							self::deleteProgramFile($this->unzipped_program_path . "$layer_type/$file_name", VENDOR_PATH . $file_name, $reserved_files, $this->errors["files"]);
					}
			}
			else if ($layer_type == "presentation") {
				//$this->presentation_program_paths
				$pres_sub_files = array_diff(scandir($this->unzipped_program_path . $layer_type), array('..', '.'));
				
				if ($this->presentation_program_paths)
					foreach ($this->presentation_program_paths as $layer_path) {
						foreach ($pres_sub_files as $pres_sub_file) {
							if (in_array(strtolower($pres_sub_file), array("entity", "view", "block", "util"))) {
								$project_layer_path = $layer_path . "src/$pres_sub_file/" . $this->program_path . "/";
								
								if (!CacheHandlerUtil::deleteFolder($project_layer_path, true, $reserved_files))
									$this->errors["files"][] = $project_layer_path;
							}
							else {
								$project_layer_path = $layer_path . (strtolower($pres_sub_file) == "webroot" ? "" : "src/") . "$pres_sub_file/";
								
								if (strtolower($pres_sub_file) == "config")
									self::deleteProgramConfigFileVars($this->unzipped_program_path . "$layer_type/$pres_sub_file/", $project_layer_path, $reserved_files, $this->errors["files"]);
								else {
									$reserved_files[] = $project_layer_path;
									
									self::deleteProgramFile($this->unzipped_program_path . "$layer_type/$pres_sub_file/", $project_layer_path, $reserved_files, $this->errors["files"]);
								}
							}
						}
					}
			}
			else  {
				$layer_paths = array();
				
				switch ($layer_type) {
					case "ibatis": $layer_paths = $this->ibatis_program_paths; break;
					case "hibernate": $layer_paths = $this->hibernate_program_paths; break;
					case "businesslogic": $layer_paths = $this->business_logic_program_paths; break;
				}
				
				if ($layer_paths)
					foreach ($layer_paths as $layer_path)
						if (!CacheHandlerUtil::deleteFolder($layer_path, true, $reserved_files))
							$this->errors["files"][] = $layer_path;
			}
		}
		
		//remove files from errors if empty
		if (empty($this->errors["files"]))
			unset($this->errors["files"]);
		
		return empty($this->errors);
	}
	
	/* OTHER FUNCTIONS */
	
	public static function getTmpRootFolderPath() {
		return (defined("TMP_PATH") ? TMP_PATH : sys_get_temp_dir()) . "/program/";
	}
	
	public static function getTmpFolderPath($default_name = null) {
		$root_path = self::getTmpRootFolderPath();
		$tmp_path = $default_name ? $root_path . $default_name : tempnam($root_path, "");
		
		if (file_exists($tmp_path))
			unlink($tmp_path); 
		
		@mkdir($tmp_path, 0755);
		
		if (is_dir($tmp_path))
			return $tmp_path . "/";
	}
	
	/* MODULES FUNCTIONS */
	
	protected function areModulesInstalled($modules_id) {
		$status = true;
		
		if ($modules_id && ($this->presentation_modules_paths || $this->business_logic_modules_paths || $this->ibatis_modules_paths || $this->hibernate_modules_paths)) { //only if paths exists, bc maybe the user only select the db drivers and no paths were selected...
			$modules_id = is_array($modules_id) ? $modules_id : array($modules_id);
			
			foreach ($modules_id as $module_id)
				if (!$this->isModuleInstalled($module_id))
					$status = false;
		}
		
		return $status;
	}
	
	protected function isModuleInstalled($module_id) {
		$status = null;
		
		if ($module_id) {
			$layers_folder_paths = array(
				$this->presentation_modules_paths, 
				$this->business_logic_modules_paths, 
				$this->ibatis_modules_paths, 
				$this->hibernate_modules_paths,
			);
			
			foreach ($layers_folder_paths as $layer_folder_paths) 
				if ($layer_folder_paths) {
					if (!isset($status))
						$status = true;
					
					foreach ($layer_folder_paths as $layer_folder_path)
						if (!file_exists($layer_folder_path . $module_id)) {
							$status = false;
							break;
						}
					
					if (!$status)
						break;
				}
		}
		
		return $status ? true : false; //it could be null
	}
	
	protected function arePresentationModulesInstalled($modules_id) {

		$status = true;
		
		if ($modules_id && $this->presentation_modules_paths) { //only if paths exists, bc maybe the user only select the db drivers and no paths were selected...
			$modules_id = is_array($modules_id) ? $modules_id : array($modules_id);
			
			foreach ($modules_id as $module_id)
				if (!$this->isPresentationModuleInstalled($module_id))
					$status = false;
		}
		
		return $status;
	}
	
	protected function isPresentationModuleInstalled($module_id) {
		$status = null;
		
		if ($module_id && $this->presentation_modules_paths) {
			$status = true;
			
			foreach ($this->presentation_modules_paths as $layer_folder_path)
				if (!file_exists($layer_folder_path . $module_id)) {
					$status = false;
					break;
				}
		}
		
		return $status ? true : false; //it could be null
	}
	
	/* DBS FUNCTIONS */
	
	protected function setDBsData($sql, &$statuses = null) {
		$status = true;
		
		if (!$statuses)
			$statuses = array();
		
		if ($this->db_drivers) {
			foreach ($this->db_drivers as $db_driver) {
				try {
					$s = $db_driver->setData($sql);
				}
				catch(Exception $e) {
					$s = false;
					
					if (empty($this->errors["dbs"]))
						$this->errors["dbs"] = array();
					
					$this->errors["dbs"][] = (!empty($e->problem) ? $e->problem : "") . $e->getMessage();
				}
				
				$statuses[] = $s;
				
				if (!$s)
					$status = false;
			}
		}
		else {
			$message = "This installation needs to run some queries in the DB.";
			
			if (!$this->existsMessage($message)) //add message
				$this->addMessage($message);
		}
		
		return $status;
	}
	
	protected function getDBsData($sql) {
		$results = array();
		
		if ($this->db_drivers) {
			foreach ($this->db_drivers as $db_driver) {
				try {
					$results[] = $db_driver->getData($sql);
				}
				catch(Exception $e) {
					if (empty($this->errors["dbs"]))
						$this->errors["dbs"] = array();
					
					$this->errors["dbs"][] = (!empty($e->problem) ? $e->problem : "") . $e->getMessage();
				}
			}
		}
		else {
			$message = "This installation needs to run some queries in the DB.";
			
			if (!$this->existsMessage($message)) //add message
				$this->addMessage($message);
		}
		
		return $results;
	}
	
	/* FILES FUNCTIONS */
	
	//copy files. Check if exist, and if not overwrite, rename existent files.
	protected static function copyProgramFolder($layer_type, $program_folder_path, $dest_folder_path, $overwrite, &$errors) {
		$program_files = array_diff(scandir($program_folder_path), array('..', '.'));
		//error_log("$program_folder_path|$dest_folder_path\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		//error_log("program_files:".print_r($program_files, 1)."\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		foreach ($program_files as $file) {
			$src_file_path = $program_folder_path . $file;
			$dst_file_path = $dest_folder_path . $file;
			//error_log("$src_file_path|$dst_file_path\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
			
			if (is_dir($src_file_path)) {
				$exists = file_exists($dst_file_path);
				//error_log("$exists|$src_file_path|$dst_file_path\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
				
				if (!$exists)
					$exists = mkdir($dst_file_path, 0755, true);
				
				if ($exists)
					self::copyProgramFolder($layer_type, $src_file_path . "/", $dst_file_path . "/", $overwrite, $errors);
				else
					$errors[$src_file_path] = $dst_file_path;
			}
			else 
				self::copyProgramFile($layer_type, $src_file_path, $dst_file_path, $overwrite, $errors);
		}
		
		return empty($errors);
	}
	
	/*
	 * src_file_path correspond to the files from the uploaded zip file
	 * dst_file_path correspond to the files in the layers (new or existent files)
	 */
	protected static function copyProgramFile($layer_type, $src_file_path, $dst_file_path, $overwrite, &$errors) {
		$exists = file_exists($dst_file_path);
		//error_log("src_file_path:$src_file_path\ndst_file_path:$dst_file_path\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		if ($layer_type == "presentation" && strpos($dst_file_path, "/src/config/") !== false && $exists) {
			$src_contents = trim(file_get_contents($src_file_path));
			$dst_contents = trim(file_get_contents($dst_file_path));
			
			if ($overwrite) {
				$to_replace = trim(str_replace(array("<?php", "<?", "?>"), "", $src_contents));
				$dst_contents = str_replace($to_replace, "", $dst_contents);
				$dst_contents = preg_replace("/\s*\?>/", "\n?>", $dst_contents);
			}
			
			$contents = $dst_contents . $src_contents;
			$contents = preg_replace("/\?>\s*<\?(php|)/", "", $contents);
			
			if (file_put_contents($dst_file_path, $contents) === false)
				$errors[$src_file_path] = $dst_file_path;
		}
		else {
			if ($overwrite)
				$exists = false;
			else if ($exists) {
				$extension = pathinfo($dst_file_path, PATHINFO_EXTENSION);
				$suffix = $extension ? "." . $extension : "";
				$fp = $extension ? substr($dst_file_path, 0, - strlen($suffix)) : $dst_file_path;
				$index = 0;
				
				do {
					$new_path = $fp . "_" . $index . $suffix;
					$index++;
				} while(file_exists($new_path));
				
				$is_class_rename = $layer_type == "businesslogic" || $layer_type == "testunit" || $layer_type == "dao" || ($layer_type == "presentation" && strpos($dst_file_path, "/src/util/") !== false);
				$class_data = $is_class_rename ? PHPCodePrintingHandler::getClassOfFile($dst_file_path) : null;
				
				//rename file
				if (rename($dst_file_path, $new_path)) {
					$exists = false;
					
					//rename class
					if ($is_class_rename && $class_data) {
						$class_name = isset($class_data["name"]) ? $class_data["name"] : null;
						$class_namespace = isset($class_data["namespace"]) ? $class_data["namespace"] : null;
						
						$dst_class_name = PHPCodePrintingHandler::prepareClassNameWithNameSpace($class_name, $class_namespace);
						$new_class_name = PHPCodePrintingHandler::prepareClassNameWithNameSpace(pathinfo($new_path, PATHINFO_FILENAME), $class_namespace);
						
						$status = PHPCodePrintingHandler::renameClassFromFile($new_path, $dst_class_name, $new_class_name);
					}
				}
			}
			
			if ($exists)
				$errors[$src_file_path] = $dst_file_path;
			else {
				$parent_dst_file_path = dirname($dst_file_path);
				
				if (!is_dir($parent_dst_file_path) && !mkdir($parent_dst_file_path, 0755, true))
					$errors[$src_file_path] = $dst_file_path;
				else if (!copy($src_file_path, $dst_file_path))
					$errors[$src_file_path] = $dst_file_path;
			}
		}
		
		return empty($errors);
	}
	
	protected static function deleteProgramConfigFileVars($src_file_path, $dst_file_path, $reserved_files, &$errors) {
		if (file_exists($src_file_path) && file_exists($dst_file_path) && !in_array($dst_file_path, $reserved_files)) {
			if (is_dir($dst_file_path)) {
				$valid_files = array_diff(scandir($src_file_path), array('..', '.'));
				$existent_files = array_diff(scandir($dst_file_path), array('..', '.'));
				
				foreach ($existent_files as $existent_file) 
					if (in_array($existent_file, $valid_files) && !in_array($dst_file_path . "/" . $existent_file, $reserved_files) && !self::deleteProgramConfigFileVars($src_file_path . "/" . $existent_file, $dst_file_path . "/" . $existent_file, $reserved_files, $errors))
						$errors[] = $dst_file_path . "/" . $existent_file;
			}
			else {
				$src_contents = trim(file_get_contents($src_file_path));
				$dst_contents = trim(file_get_contents($dst_file_path));
				
				$to_replace = trim(str_replace(array("<?php", "<?", "?>"), "", $src_contents));
				$dst_contents = str_replace($to_replace, "", $dst_contents);
				$dst_contents = preg_replace("/\s*\?>/", "\n?>", $dst_contents);
				
				if (file_put_contents($dst_file_path, $dst_contents) === false)
					$errors[] = $dst_file_path;
			}
		}
		
		return empty($errors);
	}
	
	protected static function deleteProgramFile($src_file_path, $dst_file_path, $reserved_files, &$errors) {
		if (file_exists($src_file_path) && file_exists($dst_file_path)) {
			if (is_dir($dst_file_path)) {
				$valid_files = array_diff(scandir($src_file_path), array('..', '.'));
				$existent_files = array_diff(scandir($dst_file_path), array('..', '.'));
				
				foreach ($existent_files as $existent_file) 
					if (in_array($existent_file, $valid_files) && !in_array($dst_file_path . "/" . $existent_file, $reserved_files) && !self::deleteProgramFile($src_file_path . "/" . $existent_file, $dst_file_path . "/" . $existent_file, $reserved_files, $errors))
						$errors[] = $dst_file_path . "/" . $existent_file;
				
				if (!in_array($dst_file_path, $reserved_files)) {
					$existent_files = array_diff(scandir($dst_file_path), array('..', '.'));
					
					if (count($existent_files) == 0 && !rmdir($dst_file_path))
						$errors[] = $dst_file_path;
				}
			}
			else if (!in_array($dst_file_path, $reserved_files) && !unlink($dst_file_path))
				$errors[] = $dst_file_path;
		}
		
		return empty($errors);
	}
	
	/* UTILS FUNCTIONS */
	
	protected function getReservedFiles() {
		$reserved_files = array();
		
		if ($this->reserved_files)
			foreach ($this->reserved_files as $file)
				$reserved_files[] = file_exists($file) ? realpath($file) : $file; //file_exists is very important bc if file doesn't exists, the realpath will return "/" but bc of the basedir in the php.ini we will get a php error bc the "/" folder is not allowed (bc of security reasons).
		
		return $reserved_files;
	}
	
	//include UserUtil class
	protected function includeUserUtilClass() {
		$included = false;
		
		if (class_exists("UserUtil", false))
			$included = true;
		else if ($this->presentation_program_paths && $this->projects_evcs)
			foreach ($this->projects_evcs as $broker_name => $projects) {
				foreach ($projects as $project => $EVC) { //must be $EVC bc when include $user_util_path, bc this class uses the $EVC variable to include other classes.
					if ($EVC) {
						//include UserUtil class, but only once
						$user_util_path = $EVC->getModulePath("user/UserUtil", $EVC->getCommonProjectName());
						
						if (file_exists($user_util_path)) {
							include_once $user_util_path;
							$included = true;
							break;
						}
					}
				}
				
				if ($included)
					break;
			}
		
		return $included;
	}
		
	//include AttachmentUtil class
	protected function includeAttachmentUtilClass() {
		$included = false;
			
		if (class_exists("AttachmentUtil", false))
			$included = true;
		else if ($this->presentation_program_paths && $this->projects_evcs)
			foreach ($this->projects_evcs as $broker_name => $projects) {
				foreach ($projects as $project => $EVC) { //must be $EVC bc when include $user_util_path, bc this class may use the $EVC variable to include other classes.
					if ($EVC) {
						//include AttachmentUtil class, but only once
						$attachment_util_path = $EVC->getModulePath("attachment/AttachmentUtil", $EVC->getCommonProjectName());
						
						if (file_exists($attachment_util_path)) {
							include_once $attachment_util_path;
							$included = true;
							break;
						}
					}
				}
				
				if ($included)
					break;
			}
		
		return $included;
	}
	
	//include UserSessionActivitiesHandler class
	protected function includeUserSessionActivitiesHandlerClass() {
		$included = false;
			
		if (class_exists("UserSessionActivitiesHandler", false))
			$included = true;
		else if ($this->presentation_program_paths && $this->projects_evcs)
			foreach ($this->projects_evcs as $broker_name => $projects) {
				foreach ($projects as $project => $EVC) { //must be $EVC bc when include $user_util_path, bc this class may use the $EVC variable to include other classes.
					if ($EVC) {
						//include AttachmentUtil class, but only once
						$user_session_activities_handler_path = $EVC->getModulePath("user/UserSessionActivitiesHandler", $EVC->getCommonProjectName());
						
						if (file_exists($user_session_activities_handler_path)) {
							include_once $user_session_activities_handler_path;
							$included = true;
							break;
						}
					}
				}
				
				if ($included)
					break;
			}
		
		return $included;
	}
	
	//used in CMSProgramInstallationHandlerImpl in each program
	protected function getAvailableAttachmentsFolders() {
		$attachments_paths = array();
		
		if ($this->projects_evcs) {
			$this->includeAttachmentUtilClass();
			
			if (!class_exists("AttachmentUtil", false)){
				$this->addError("AttachmentUtil class does NOT exist!");
				return false;
			}
			
			foreach ($this->projects_evcs as $broker_name => $projects)
				foreach ($projects as $project => $PEVC)
					if ($PEVC) {
						//set global variables
						$pre_init_config_path = $PEVC->getConfigPath("pre_init_config");
						$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($this->user_global_variables_file_path, $pre_init_config_path));
						$PHPVariablesFileHandler->startUserGlobalVariables();
						
						//prepare attachments path
						$abs_path = AttachmentUtil::getAttachmentsFolderPath($PEVC);
						
						if (file_exists($abs_path) && is_dir($abs_path))
							$attachments_paths[] = $abs_path;
						
						//rollback to original global variables
						$PHPVariablesFileHandler->endUserGlobalVariables();
					}
		
			$attachments_paths = array_unique($attachments_paths);
		}
		
		return $attachments_paths;
	}
	
	protected function setUserTypePermissionsToPages($user_type_id, $activity_id, $pages) {
		$status = true;
		
		if ($this->presentation_program_paths && $this->existsDBs()) {
			//include UserUtil class
			$this->includeUserUtilClass();
			
			if (!class_exists("UserUtil", false)) {
				$this->addError("UserUtil class does NOT exist!");
				return false;
			}
			
			//create sql
			$date = date("Y-m-d H:i:s");
			$sql = "";
			
			foreach ($this->presentation_program_paths as $layer_path) {
				$folder_path = $layer_path . "src/entity/";
				
				foreach ($pages as $f) {
					$fp = $folder_path . $f;
					
					if (file_exists($fp)) {
						$object_id = UserUtil::getObjectIdFromFilePath($fp);
						
						$sql .= "INSERT IGNORE INTO `mu_user_type_activity_object` (`user_type_id`, `activity_id`, `object_type_id`, `object_id`, `created_date`, `modified_date`) VALUES ($user_type_id,$activity_id,1,$object_id,'$date','$date');";
					}
				}
			}
			
			//execute sql
			if ($sql && !$this->setDBsData($sql)) {
				$this->addError("Could not insert user permissions in mu_user_type_activity_object table!");
				$status = false;
			}
		}
		
		return $status;
	}
	
	protected function setUserTypePermissionsToProgramPages($user_type_id, $activity_id, $pages) {
		foreach ($pages as $idx => $f)
			$pages[$idx] = $this->program_path . "/" . $f;
		
		return $this->setUserTypePermissionsToPages($user_type_id, $activity_id, $pages);
	}
	
	//delete cache, bc if the program changes the user permissions these permissions are cached and so must be deleted
	protected function deleteUserTypePermissionsCachedFolderPaths() {
		$status = true;
		
		$cached_paths = $this->getUserTypePermissionsCachedFolderPaths();
		
		if ($cached_paths)
			foreach ($cached_paths as $path)
				if (file_exists($path) && !CacheHandlerUtil::deleteFolder($path))
					$status = false;
		
		return $status;
	}
	
	protected function getUserTypePermissionsCachedFolderPaths() {
		$cached_paths = array();
		
		if ($this->projects_evcs) {
			$this->includeUserSessionActivitiesHandlerClass();
			
			if (!class_exists("UserSessionActivitiesHandler", false)){
				$this->addError("UserSessionActivitiesHandler class does NOT exist!");
				return false;
			}
			
			foreach ($this->projects_evcs as $broker_name => $projects)
				foreach ($projects as $project => $PEVC)
					if ($PEVC) {
						//set global variables
						$pre_init_config_path = $PEVC->getConfigPath("pre_init_config");
						$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($this->user_global_variables_file_path, $pre_init_config_path));
						$PHPVariablesFileHandler->startUserGlobalVariables();
						
						//prepare user sessions path
						$UserCacheHandler = $PEVC->getPresentationLayer()->getPHPFrameWork()->getObject("UserCacheHandler");
						$cached_paths[] = $UserCacheHandler->getRootPath() . UserSessionActivitiesHandler::SESSIONS_CACHE_FOLDER_NAME . "/";
						
						//rollback to original global variables
						$PHPVariablesFileHandler->endUserGlobalVariables();
					}
		
			$cached_paths = array_unique($cached_paths);
		}
		
		return $cached_paths;
	}
	
	protected function copyFolderAttachments($src_folder_path, $dst_folder_suffix) {
		$files = file_exists($src_folder_path) ? array_diff(scandir($src_folder_path), array('..', '.')) : array();
		
		return $this->copyAttachmentsFiles($src_folder_path, $dst_folder_suffix, $files);
	}
		
	protected function copyAttachmentsFiles($src_folder_path, $dst_folder_suffix, $files) {
		$status = true;
		
		if ($files && $this->presentation_modules_paths) {
			$attachments_paths = $this->getAvailableAttachmentsFolders(); //this method loads the AttachmentUtil class
			
			if (!$attachments_paths) {
				$this->addError("There no attachments folders to copy this program files. Probably the AttachmentUtil class was not loaded!");
				return false;
			}
			
			foreach ($attachments_paths as $path)
				if ($path) {
					$path = "$path/$dst_folder_suffix/";
					
					/*
					 * AttachmentUtil was loaded before.
					 * $path . "aux": the "aux" doesnt matter bc the AttachmentUtil::createAttachmentFileFolder($file_path) method will do: dirname($file_path).
					 */
					if (AttachmentUtil::createAttachmentFileFolder($path . "aux")) { 
						foreach ($files as $file) {
							$src = $src_folder_path . $file;
							$dst = $path . pathinfo($file, PATHINFO_FILENAME);
							
							if (!copy($src, $dst)) {
								$this->addError("Could not copy image '$file' to '$path'.");
								$status = false;
							}
						}
					}
					else {
						$this->addError("Could not create '$path' folder.");
						$status = false;
					}
				}
		}
		
		return $status;
	}
	
	protected function updateSettingInBlocks($blocks, $setting_name, $setting_value, $only_with_paths_prefix = null, $is_string = true) {
		$status = true;
		
		if ($this->presentation_program_paths && $blocks && $setting_name) {
			$presentation_layer_program_paths = array();
			$new_setting_value = $is_string ? '"' . $setting_value . '"' : $setting_value;
			
			foreach ($this->presentation_program_paths as $layer_path)
				if (substr($layer_path, 0, strlen($only_with_paths_prefix)) == $only_with_paths_prefix)
					$presentation_layer_program_paths[] = $layer_path;
			
			foreach ($presentation_layer_program_paths as $layer_path) {
				foreach ($presentation_layer_program_paths as $layer_path) {
					$folder_path = $layer_path . "src/block/" . $this->program_path . "/";
					
					foreach ($blocks as $f) {
						$fp = $folder_path . $f;
						
						if (file_exists($fp)) {
							$code = file_get_contents($fp);
							
							$new_code = preg_replace('/"' . $setting_name . '"\s*=>[^,]*,/', '"' . $setting_name . '" => ' . $new_setting_value . ',', $code);
							if ($code != $new_code && file_put_contents($fp, $new_code) === false) {
								$this->addError("Could not update '$setting_name' setting in '" . str_replace($layer_path, "", $fp) . "' file.");
								$status = false;
							}
						}
					}
				}
			}
		}
		
		return $status;
	}
	
	protected function updateSettingWithSpecificValueInBlocks($blocks, $setting_name, $old_setting_value, $new_setting_value, $only_with_paths_prefix = null, $is_string = true) {
		$status = true;
		
		if ($this->presentation_program_paths && $blocks && $setting_name) {
			$presentation_layer_program_paths = array();
			$new_setting_value = $is_string ? '"' . $new_setting_value . '"' : $new_setting_value;
			
			foreach ($this->presentation_program_paths as $layer_path)
				if (substr($layer_path, 0, strlen($only_with_paths_prefix)) == $only_with_paths_prefix)
					$presentation_layer_program_paths[] = $layer_path;
			
			foreach ($presentation_layer_program_paths as $layer_path) {
				foreach ($presentation_layer_program_paths as $layer_path) {
					$folder_path = $layer_path . "src/block/" . $this->program_path . "/";
					
					foreach ($blocks as $f) {
						$fp = $folder_path . $f;
						
						if (file_exists($fp)) {
							$code = file_get_contents($fp);
							
							$regex_quotes = $is_string ? '("|\')' : '';
							$new_code = preg_replace('/"' . $setting_name . '"\s*=>\s*' . $regex_quotes . preg_quote($old_setting_value, '/') . $regex_quotes . ',/', '"' . $setting_name . '" => ' . $new_setting_value . ',', $code);
							
							if ($code != $new_code && file_put_contents($fp, $new_code) === false) {
								$this->addError("Could not update '$setting_name' setting in '" . str_replace($layer_path, "", $fp) . "' file.");
								$status = false;
							}
						}
					}
				}
			}
		}
		
		return $status;
	}
	
	protected function updateBllAndDalAndDBBrokerInBlocks() {
		$status = true;
		$path = $this->unzipped_program_path . "/presentation/block/";
		
		if ($this->presentation_program_paths && file_exists($path)) {
			$blocks = $this->getFolderPagesList($path);
			
			if ($blocks && is_array($this->layers))
				foreach ($this->layers as $broker_name => $Layer)
					if (is_a($Layer, "PresentationLayer")) {
						$layer_brokers_settings = isset($this->layers_brokers_settings[$broker_name]) ? $this->layers_brokers_settings[$broker_name] : null;
						
						if (!$layer_brokers_settings)
							$status = false;
						
						$layer_path = $Layer->getLayerPathSetting();
						
						//prepare db_broker - replace dbdata borker by the user broker
						$db_brokers = isset($layer_brokers_settings["db_brokers"]) ? $layer_brokers_settings["db_brokers"] : null;
						$db_brokers_name = array();
						
						if ($db_brokers)
							foreach ($db_brokers as $db_broker_props)
								$db_brokers_name[] = isset($db_broker_props[0]) ? $db_broker_props[0] : null;
						
						if (!in_array("dbdata", $db_brokers_name) && !$this->updateSettingWithSpecificValueInBlocks($blocks, "db_broker", "dbdata", isset($db_brokers_name[0]) ? $db_brokers_name[0] : null, $layer_path))
							$status = false;
						
						//prepare dal_broker - replace iorm borker by the user broker
						$ibatis_brokers = isset($layer_brokers_settings["ibatis_brokers"]) ? $layer_brokers_settings["ibatis_brokers"] : null;
						$ibatis_brokers_name = array();
						
						if ($ibatis_brokers)
							foreach ($ibatis_brokers as $ibatis_brokers_props)
								$ibatis_brokers_name[] = isset($ibatis_brokers_props[0]) ? $ibatis_brokers_props[0] : null;
						
						if (!in_array("iorm", $ibatis_brokers_name) && !$this->updateSettingWithSpecificValueInBlocks($blocks, "dal_broker", "iorm", isset($ibatis_brokers_name[0]) ? $ibatis_brokers_name[0] : null, $layer_path))
							$status = false;
						
						//prepare dal_broker - replace horm borker by the user broker
						$hibernate_brokers = isset($layer_brokers_settings["hibernate_brokers"]) ? $layer_brokers_settings["hibernate_brokers"] : null;
						$hibernate_brokers_name = array();
						
						if ($hibernate_brokers)
							foreach ($hibernate_brokers as $hibernate_broker_props)
								$hibernate_brokers_name[] = isset($hibernate_broker_props[0]) ? $hibernate_broker_props[0] : null;
						
						if (!in_array("horm", $hibernate_brokers_name) && !$this->updateSettingWithSpecificValueInBlocks($blocks, "dal_broker", "horm", isset($hibernate_brokers_name[0]) ? $hibernate_brokers_name[0] : null, $layer_path))
							$status = false;
						
						//prepare busineslogic_broker - replace soa borker by the user broker
						$business_logic_brokers = isset($layer_brokers_settings["business_logic_brokers"]) ? $layer_brokers_settings["business_logic_brokers"] : null;
						$business_logic_brokers_name = array();
						
						if ($business_logic_brokers)
							foreach ($business_logic_brokers as $business_logic_broker_props)
								$business_logic_brokers_name[] = isset($business_logic_broker_props[0]) ? $business_logic_broker_props[0] : null;
						
						if (!in_array("soa", $business_logic_brokers_name) && !$this->updateSettingWithSpecificValueInBlocks($blocks, "method_obj", '$EVC->getBroker("soa")', '$EVC->getBroker("' . (isset($business_logic_brokers_name[0]) ? $business_logic_brokers_name[0] : null) . '")', $layer_path, false))
							$status = false;
					}
		}
		
		return $status;
	}
	
	/*
		$files_diagram_by_page = array("article_group" => "files_diagram/article_group.xml");
		
		if (!$this->copyFilesTaskDiagram($files_diagram_by_page, true)){
			$this->addError("Could not add files diagram");
			$status = false;
		}
	*/
	protected function copyFilesTaskDiagram($files_diagram_by_page, $overwrite = false) {
		$status = true;
		
		if ($this->presentation_program_paths && $files_diagram_by_page) {
			foreach ($this->layers as $broker_name => $Layer)
				if (is_a($Layer, "PresentationLayer")) {
					$layer_bean_settings = isset($this->layer_beans_settings[$broker_name]) ? $this->layer_beans_settings[$broker_name] : null;
					$layer_bean_name = $layer_bean_settings && isset($layer_bean_settings[2]) ? $layer_bean_settings[2] : null;
					
					if ($layer_bean_name && $this->projects && !empty($this->projects[$broker_name]))
						if (empty($Layer->settings["presentation_entities_path"]))
							launch_exception(new Exception("\$Layer->settings[presentation_entities_path] cannot be empty!"));
						
						foreach ($this->projects[$broker_name] as $project) {
							$path = $project . "/";
							
							foreach ($files_diagram_by_page as $folder_path => $files_diagram_path) {
								$files_diagram_fp = $this->unzipped_program_path . $files_diagram_path;
								
								if (file_exists($files_diagram_fp)) {
									$rp = $path . $Layer->settings["presentation_entities_path"] . $this->program_path . "/" . $folder_path;
									$fp = $Layer->getLayerPathSetting() . $rp;
									
									if (is_dir($fp)) {
										$task_file_path = $this->getFilesDiagramTaskFilePath($layer_bean_name, $rp);
										
										if ($task_file_path && (!file_exists($task_file_path) || $overwrite)) {
											$tfp_folder = dirname($task_file_path);
											
											if (is_dir($tfp_folder) || mkdir($tfp_folder, 0775, true)) {
												if (!copy($files_diagram_fp, $task_file_path))
													$status = false;
											}
										}
									}
								}
							}
						}
				}
		}
		
		return $status;
	}
	
	/* PRIVATE */
	
	//This method is based in the __system/layer/presentation/phpframework/src/util/WorkFlowTasksFileHandler::getTaskFilePathByPath method
	private function getFilesDiagramTaskFilePath($presentation_bean_name, $relative_folder_path) {
		if (!empty($this->workflow_paths_id["presentation_ui"])) {
			$relative_folder_path .= substr($relative_folder_path, -1) == "/" ? "" : "/"; //must have the "/" at the end otherwise the $workflow_path will not be correct.
			$extra = "_{$presentation_bean_name}_" . md5($relative_folder_path);
			
			$path_parts = pathinfo($this->workflow_paths_id["presentation_ui"]);
			$path = $path_parts['dirname'] . "/" . $path_parts['filename'] . $extra . (!empty($path_parts['extension']) ? "." . $path_parts['extension'] : "");
			
			return $path;
		}
		
		return null;
	}
	
	private function getLayerProgramPaths($layer_type, $webroot = false) {
		$paths = array();
		$program_info = -1;
		
		if (is_array($this->layers))
			foreach ($this->layers as $broker_name => $Layer)
				if (is_a($Layer, $layer_type)) {
					if ($layer_type == "PresentationLayer") {
						if ($this->projects && !empty($this->projects[$broker_name]))
							foreach ($this->projects[$broker_name] as $project) {
								if ($webroot) {
									if (empty($Layer->settings["presentation_webroot_path"]))
										launch_exception(new Exception("\$Layer->settings[presentation_webroot_path] cannot be empty!"));
									
									$paths[] = $Layer->getLayerPathSetting() . $project . "/" . $Layer->settings["presentation_webroot_path"];
								}
								else
									$paths[] = $Layer->getLayerPathSetting() . $project . "/";
							}
					}
					else
						$paths[] = $Layer->getLayerPathSetting() . "program/" . $this->program_path . "/";
				}
		
		return array_unique($paths);
	}
	
	private function getLayerModulesPaths($layer_type) {
		$paths = array();
		
		if (is_array($this->layers))
			foreach ($this->layers as $Layer)
				if (is_a($Layer, $layer_type)) {
					if ($layer_type == "PresentationLayer") {
						if (empty($Layer->settings["presentation_modules_path"]))
							launch_exception(new Exception("\$Layer->settings[presentation_modules_path] cannot be empty!"));
						
						$module_path = $Layer->getLayerPathSetting() . $Layer->getCommonProjectName() . "/" . $Layer->settings["presentation_modules_path"];
					}
					else
						$module_path = $Layer->getLayerPathSetting() . "module/";
					
					if ($module_path)
						$paths[] = $module_path;
				}
		
		return array_unique($paths);
	}
}
?>
