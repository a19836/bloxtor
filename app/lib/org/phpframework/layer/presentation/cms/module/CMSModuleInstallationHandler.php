<?php
include_once get_lib("org.phpframework.layer.presentation.cms.module.ICMSModuleInstallationHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleUtil");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleInstallationBLNamespaceHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleInstallationDBDAOHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.cache.CMSModuleSettingsCacheHandler");
include_once get_lib("org.phpframework.compression.ZipHandler");

class CMSModuleInstallationHandler implements ICMSModuleInstallationHandler {
	protected $layers;
	protected $module_id;
	protected $system_presentation_settings_module_path;
	protected $system_presentation_settings_webroot_module_path;
	protected $unzipped_module_path;
	protected $UserAuthenticationHandler;
	
	protected $presentation_module_paths;
	protected $presentation_webroot_module_paths;
	protected $business_logic_module_paths;
	protected $ibatis_module_paths;
	protected $hibernate_module_paths;
	protected $dao_module_path;
	
	protected $db_drivers;
	protected $reserved_files; //This is initialized in each module CMSModuleInstallationHandler class
	protected $used_db_drivers;
	protected $messages;
	
	public function __construct($layers, $module_id, $system_presentation_settings_module_path, $system_presentation_settings_webroot_module_path, $unzipped_module_path = "", $selected_db_driver = false, $UserAuthenticationHandler = null) {
		$this->layers = $layers;
		$this->module_id = $module_id;
		$this->system_presentation_settings_module_path = $system_presentation_settings_module_path;
		$this->system_presentation_settings_webroot_module_path = $system_presentation_settings_webroot_module_path;
		$this->unzipped_module_path = $unzipped_module_path;
		$this->UserAuthenticationHandler = $UserAuthenticationHandler;
		
		$this->presentation_module_paths = $this->getLayerModulePaths($module_id, "PresentationLayer");
		$this->presentation_webroot_module_paths = $this->getLayerModulePaths($module_id, "PresentationLayer", true);
		$this->business_logic_module_paths = $this->getLayerModulePaths($module_id, "BusinessLogicLayer");
		$this->ibatis_module_paths = $this->getLayerModulePaths($module_id, "IbatisDataAccessLayer");
		$this->hibernate_module_paths = $this->getLayerModulePaths($module_id, "HibernateDataAccessLayer");
		$this->dao_module_path = DAO_PATH . "module/$module_id/";
		
		$this->db_drivers = $this->getLayersDBDrivers($selected_db_driver);
		$this->used_db_drivers = array();
		
		$this->reserved_files = array();
		$this->messages = array();
	}
	
	public static function createCMSModuleInstallationHandlerObject($layers, $module_id, $system_presentation_settings_module_path, $system_presentation_settings_webroot_module_path, $unzipped_module_path = "", $selected_db_driver = false, $UserAuthenticationHandler = null) {
		$CMSModuleInstallationHandler = null;
		
		try {
			$file_path = "";
			if ($unzipped_module_path) {
				if (file_exists($unzipped_module_path . "/CMSModuleInstallationHandlerImpl.php"))
					$file_path = $unzipped_module_path . "/CMSModuleInstallationHandlerImpl.php";
			}
			else if (file_exists($system_presentation_settings_module_path . "/CMSModuleInstallationHandlerImpl.php"))
				$file_path = $system_presentation_settings_module_path . "/CMSModuleInstallationHandlerImpl.php";
			
			if ($file_path) {
				$class = 'CMSModule\\' . str_replace("/", "\\", str_replace(" ", "_", trim($module_id))) . '\CMSModuleInstallationHandlerImpl';
		
				if (!class_exists($class))
					include_once $file_path;
				
				eval ('$CMSModuleInstallationHandler = new ' . $class . '($layers, $module_id, $system_presentation_settings_module_path, $system_presentation_settings_webroot_module_path, $unzipped_module_path, $selected_db_driver, $UserAuthenticationHandler);');
			}
			else
				$CMSModuleInstallationHandler = new CMSModuleInstallationHandler($layers, $module_id, $system_presentation_settings_module_path, $system_presentation_settings_webroot_module_path, $unzipped_module_path, $selected_db_driver, $UserAuthenticationHandler);
		}
		catch (Exception $e) {
			launch_exception($e);
		}
		
		return $CMSModuleInstallationHandler;
	}
	
	public static function unzipModuleFile($zipped_file_path, $unzipped_folder_path = null) {
		if (!$unzipped_folder_path) {
			$unzipped_folder_path = self::getTmpFolderPath();
			
			if (!$unzipped_folder_path)
				return false;
		}
		
		if (ZipHandler::unzip($zipped_file_path, $unzipped_folder_path))
			return $unzipped_folder_path;
		
		return null;
	}
	
	public static function getUnzippedModuleSettings($unzipped_module_path) {
		//get the module info based in the uploaded program.
		$module_xml_file_path = $unzipped_module_path . "/settings.xml";
		$info = null;
		
		if (file_exists($module_xml_file_path)) {
			$arr = XMLFileParser::parseXMLFileToArray($module_xml_file_path);
			$arr = MyXML::complexArrayToBasicArray($arr, array("lower_case_keys" => true, "trim" => true));
			$info = isset($arr["module"]) ? $arr["module"] : null;
		}
		
		return $info;
	}
	
	public function install() {
		$this->messages = array();
		
		if ($this->unzipped_module_path && is_dir($this->unzipped_module_path)) {
			$status = true;
			
			if (is_dir($this->unzipped_module_path . "/system_settings")) {
				if ($this->system_presentation_settings_module_path && !CMSModuleUtil::copyFolder($this->unzipped_module_path . "/system_settings", $this->system_presentation_settings_module_path))
					$status = false;
				
				if (is_dir($this->unzipped_module_path . "/system_settings/webroot")) {
					if (!$this->deleteFileFromSystemPresentationSettingsModuleFolder("webroot"))
						$status = false;
					
					if ($this->system_presentation_settings_webroot_module_path && !CMSModuleUtil::copyFolder($this->unzipped_module_path . "/system_settings/webroot", $this->system_presentation_settings_webroot_module_path))
						$status = false;
				}
				
				if ($status && file_exists($this->unzipped_module_path . "/CMSModuleInstallationHandlerImpl.php") && !$this->copyUnzippedFileToSystemPresentationSettingsModuleFolder("CMSModuleInstallationHandlerImpl.php"))
					$status = false;
			}
			
			if (is_dir($this->unzipped_module_path . "/presentation")) {
				if ($this->presentation_module_paths && !CMSModuleUtil::copyFileToLayers("presentation", "", $this->unzipped_module_path, $this->presentation_module_paths))
					$status = false;
				
				if ($status && is_dir($this->unzipped_module_path . "/presentation/webroot")) {
					if (!$this->deleteFileFromPresentationModuleFolder("webroot"))
						$status = false;
					
					if ($status && $this->presentation_webroot_module_paths && !CMSModuleUtil::copyFileToLayers("presentation/webroot", "", $this->unzipped_module_path, $this->presentation_webroot_module_paths))
						$status = false;
				}
			}
			
			if (is_dir($this->unzipped_module_path . "/businesslogic") && $this->business_logic_module_paths) {
				if (CMSModuleUtil::copyFileToLayers("businesslogic", "", $this->unzipped_module_path, $this->business_logic_module_paths)) 
					$status = CMSModuleInstallationBLNamespaceHandler::updateExtendedCommonServiceCodeInBusinessLogicPHPFiles($this->layers, $this->business_logic_module_paths);
				else
					$status = false;
			}
			
			if (is_dir($this->unzipped_module_path . "/ibatis") && $this->ibatis_module_paths && !CMSModuleUtil::copyFileToLayers("ibatis", "", $this->unzipped_module_path, $this->ibatis_module_paths))
				$status = false;
			
			if (is_dir($this->unzipped_module_path . "/hibernate") && $this->hibernate_module_paths && !CMSModuleUtil::copyFileToLayers("hibernate", "", $this->unzipped_module_path, $this->hibernate_module_paths))
				$status = false;
			
			if (is_dir($this->unzipped_module_path . "/dao") && $this->dao_module_path && !CMSModuleUtil::copyFolder($this->unzipped_module_path . "/dao", $this->dao_module_path)) 
				$status = false;
			
			return $status;
		}
	}
	
	public function uninstall($delete_system_module = false) {
		$status = true;
		
		$this->messages = array();
		$reserved_files = $this->getReservedFiles();
		
		$layers_folder_paths = array(
			$this->presentation_module_paths, 
			$this->presentation_webroot_module_paths, 
			$this->business_logic_module_paths, 
			$this->ibatis_module_paths, 
			$this->hibernate_module_paths,
		);
		
		foreach ($layers_folder_paths as $layer_folder_paths) 
			if ($layer_folder_paths)
				foreach ($layer_folder_paths as $layer_folder_path)
					if (!CMSModuleUtil::deleteFolder($layer_folder_path, $reserved_files))
						$status = false;
		
		if ($status && $delete_system_module)
			return CMSModuleUtil::deleteFolder($this->dao_module_path, $reserved_files) && CMSModuleUtil::deleteFolder($this->system_presentation_settings_module_path, $reserved_files) && CMSModuleUtil::deleteFolder($this->system_presentation_settings_webroot_module_path, $reserved_files);
		
		return $status;
	}
	
	//checks if a module is installed in the correspondent layers
	public function isModuleInstalled($check_system_module = false) {
		$status = null;
		
		$layers_folder_paths = array(
			$this->presentation_module_paths, 
			$this->presentation_webroot_module_paths, 
			$this->business_logic_module_paths, 
			$this->ibatis_module_paths, 
			$this->hibernate_module_paths,
		);
		
		foreach ($layers_folder_paths as $layer_folder_paths) 
			if ($layer_folder_paths) {
				if (!isset($status))
					$status = true;
				
				foreach ($layer_folder_paths as $layer_folder_path)
					if (!file_exists($layer_folder_path)) {
						$status = false;
						break;
					}
				
				if (!$status)
					break;
			}
		
		if ($status && $check_system_module)
			$status = file_exists($this->system_presentation_settings_module_path) && file_exists($this->system_presentation_settings_webroot_module_path);
		
		return $status ? true : false; //it could be null
	}
	
	public function createModuleDBDAOUtilFilesFromHibernateFile($hibernate_xml_file_names = null) {
		if ($hibernate_xml_file_names) {
			$hibernate_xml_files = array();
			$hibernate_xml_file_names = is_array($hibernate_xml_file_names) ? $hibernate_xml_file_names : array($hibernate_xml_file_names);
			
			foreach ($hibernate_xml_file_names as $file_name)
				$hibernate_xml_files[] = $this->unzipped_module_path . "/hibernate/$file_name.xml";
		}
		else
			$hibernate_xml_files = array(
				$this->unzipped_module_path . "/hibernate/" . $this->module_id . ".xml",
				$this->unzipped_module_path . "/hibernate/object_" . $this->module_id . ".xml"
			);
		
		$messages = array();
		
		$status = CMSModuleInstallationDBDAOHandler::createModuleDBDAOUtilFilesFromHibernateFile($hibernate_xml_files, array(
				"businesslogic" => $this->business_logic_module_paths,
				"presentation" => $this->presentation_module_paths,
				"system_settings" => array($this->system_presentation_settings_module_path),
			), $this->module_id, $messages
		);
		
		if ($messages) 
			foreach ($messages as $message)
				$this->addMessage($message);
		
		return $status;
	}
	
	public function freeModuleCache() {
		$status = true;
		
		if (is_array($this->layers)) {
			foreach ($this->layers as $Layer) {
				if (is_a($Layer, "PresentationLayer")) {
					if ($Layer->isCacheActive()) {
						$cache_root_path = $Layer->getModuleCachedLayerDirPath();
		
						if ($cache_root_path) {
							$cache_root_path = $cache_root_path . CMSModuleSettingsCacheHandler::CACHE_DIR_NAME;
				
							if (!CMSModuleUtil::deleteFolder($cache_root_path)) 
								$status = false;
						}
					}
				}
			}
		}
		
		return $status;
	}
	
	public function getUsedDBDrivers() {
		return $this->used_db_drivers;
	}
	public function setUsedDBDrivers($used_db_drivers) {
		return $this->used_db_drivers = $used_db_drivers;
	}
	public function areAllDBDriversUsed() {
		$all_used = true;
		
		if ($this->db_drivers)
			foreach ($this->db_drivers as $db_driver) {
				$opts = $db_driver->getOptions();
				$db_driver_id = md5(serialize($opts));
				
				if (!$this->isUsedDBDriver($db_driver_id)) {
					$all_used = false;
					break;
				}
			}
		
		return $all_used;
	}
	
	public function detectedLayerByClass($layer_class) {
		if (is_array($this->layers)) 
			foreach ($this->layers as $Layer)
				if (is_a($Layer, $layer_class))
					return true;
		
		return false;
	}
	
	public function addMessage($message) {
		return $this->messages[] = $message;
	}
	
	public function getMessages() {
		return $this->messages;
	}
	
	public static function getTmpRootFolderPath() {
		return (defined("TMP_PATH") ? TMP_PATH : sys_get_temp_dir()) . "/module/";
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
	
	/* PROTECTED */
	protected function addUsedDBDriver($db_driver) {
		$this->used_db_drivers[] = $db_driver;
	}
	
	protected function isUsedDBDriver($db_driver) {
		return in_array($db_driver, $this->used_db_drivers);
	}
	
	protected function getReservedFiles() {
		$reserved_files = array();
		
		if ($this->reserved_files)
			foreach ($this->reserved_files as $file)
				$reserved_files[] = file_exists($file) ? realpath($file) : $file; //file_exists is very important bc if file doesn't exists, the realpath will return "/" but bc of the basedir in the php.ini we will get a php error bc the "/" folder is not allowed (bc of security reasons).
		
		return $reserved_files;
	}
	
	protected function copyUnzippedFileToSystemPresentationSettingsModuleFolder($src, $dst = false) {
		$dst = $dst ? $dst : $src;
		
		return CMSModuleUtil::copyFile($this->unzipped_module_path . "/$src", $this->system_presentation_settings_module_path . "/$dst");
	}
	
	protected function deleteFileFromSystemPresentationSettingsModuleFolder($src) {
		return CMSModuleUtil::deleteFiles(array($this->system_presentation_settings_module_path . "/$src"), $this->getReservedFiles());
	}
	
	protected function copyUnzippedFileToSystemPresentationSettingsWebrootModuleFolder($src, $dst = false) {
		$dst = $dst ? $dst : $src;
		return CMSModuleUtil::copyFile($this->unzipped_module_path . "/$src", $this->system_presentation_settings_webroot_module_path . "/$dst");
	}
	
	protected function deleteFileFromSystemPresentationSettingsWebrootModuleFolder($src) {
		return CMSModuleUtil::deleteFiles(array($this->system_presentation_settings_webroot_module_path . "/$src"), $this->getReservedFiles());
	}
	
	
	protected function copyUnzippedFileToPresentationModuleFolder($src, $dst = false) {
		$dst = $dst ? $dst : $src;
		return CMSModuleUtil::copyFileToLayers($src, $dst, $this->unzipped_module_path, $this->presentation_module_paths);
	}
	
	protected function deleteFileFromPresentationModuleFolder($src) {
		return CMSModuleUtil::deleteFileFromLayers($src, $this->presentation_module_paths, $this->getReservedFiles());
	}
	
	protected function copyUnzippedFileToPresentationWebrootFolder($src, $dst = false) {
		$dst = $dst ? $dst : $src;
		return CMSModuleUtil::copyFileToLayers($src, $dst, $this->unzipped_module_path, $this->presentation_webroot_module_paths);
	}
	
	protected function deleteFileFromPresentationWebrootFolder($src) {
		return CMSModuleUtil::deleteFileFromLayers($src, $this->presentation_webroot_module_paths, $this->getReservedFiles());
	}
	
	protected function copyUnzippedFileToBusinessLogicModuleFolder($src, $dst = false) {
		$dst = $dst ? $dst : $src;
		return CMSModuleUtil::copyFileToLayers($src, $dst, $this->unzipped_module_path, $this->business_logic_module_paths);
	}
	
	protected function deleteFileFromBusinessLogicModuleFolder($src) {
		return CMSModuleUtil::deleteFileFromLayers($src, $this->business_logic_module_paths, $this->getReservedFiles());
	}
	
	protected function copyUnzippedFileToIbatisModuleFolder($src, $dst = false) {
		$dst = $dst ? $dst : $src;
		return CMSModuleUtil::copyFileToLayers($src, $dst, $this->unzipped_module_path, $this->ibatis_module_paths);
	}
	
	protected function deleteFileFromIbatisModuleFolder($src) {
		return CMSModuleUtil::deleteFileFromLayers($src, $this->ibatis_module_paths, $this->getReservedFiles());
	}
	
	protected function copyUnzippedFileToHibernateModuleFolder($src, $dst = false) {
		$dst = $dst ? $dst : $src;
		return CMSModuleUtil::copyFileToLayers($src, $dst, $this->unzipped_module_path, $this->hibernate_module_paths);
	}
	
	protected function deleteFileFromHibernateModuleFolder($src) {
		return CMSModuleUtil::deleteFileFromLayers($src, $this->hibernate_module_paths, $this->getReservedFiles());
	}
	
	protected function setDBsData($sql) {
		$statuses = array();
		
		foreach ($this->db_drivers as $db_driver) {
			$statuses[] = $db_driver->setData($sql);
		}
		
		return $statuses;
	}
	
	protected function getDBsData($sql) {
		$results = array();
		
		foreach ($this->db_drivers as $db_driver) {
			$results[] = $db_driver->detData($sql);
		}
		
		return $results;
	}
	
	protected function installDataToDBs($tables_data, $options = false) {
		$options = $options ? $options : array();
		$indexes_to_insert = isset($options["indexes_to_insert"]) ? $options["indexes_to_insert"] : null;
		$objects_to_insert = isset($options["objects_to_insert"]) ? $options["objects_to_insert"] : null;
		$sqls = isset($options["sqls"]) ? $options["sqls"] : null;
		
		if (!$tables_data && !$indexes_to_insert && !$objects_to_insert && !$sqls)
			return true;
		
		if (empty($this->db_drivers)) {
			launch_exception(new Exception("Error: There is no DB defined!"));
			return false;
		}
		
		$status = true;
		$exception_msg = null;
		
		foreach ($this->db_drivers as $db_driver) {
			$opts = $db_driver->getOptions();
			$db_driver_id = md5(serialize($opts));
			
			if (!$this->isUsedDBDriver($db_driver_id)) {
				$this->addUsedDBDriver($db_driver_id);
				
				$s = true;
				//echo "db_driver:".$opts["db_name"]."-".$db_driver_id."\n<br>";
				
				//save this table into our internal register so we can detect later if a db table belongs to a module. Leave this code before the setData method and outside of the try-catch below, bc if the table already exists it will give an exception below.
				if ($this->UserAuthenticationHandler)
					foreach ($tables_data as $table_data)
						if (!empty($table_data["table_name"]))
							$this->UserAuthenticationHandler->insertReservedDBTableNameIfNotExistsYet(array("name" => $table_data["table_name"]));
				
				try {
					if ($tables_data)
						foreach ($tables_data as $table_data) {
							if (!empty($table_data["drop"]) && !empty($table_data["table_name"])) {
								$sql = $db_driver->getDropTableStatement($table_data["table_name"]);
								$db_driver->setData($sql);
							}
							
							$sql = $db_driver->getCreateTableStatement($table_data);
							
							if (!$db_driver->setData($sql))
								$s = false;
						}
					
					if ($s && $indexes_to_insert)
						foreach ($indexes_to_insert as $index_to_insert) {
							$index_to_insert_table = isset($index_to_insert[0]) ? $index_to_insert[0] : null;
							$index_to_insert_attributes = isset($index_to_insert[1]) ? $index_to_insert[1] : null;
							$index_to_insert_options = isset($index_to_insert[2]) ? $index_to_insert[2] : null;
							
							$sql = $db_driver->getAddTableIndexStatement($index_to_insert_table, $index_to_insert_attributes, $index_to_insert_options);
							
							if ($sql && !$db_driver->setData($sql, $index_to_insert_options))
								$s = false;
						}
					
					if ($s && $objects_to_insert)
						foreach ($objects_to_insert as $object_to_insert) {
							$object_to_insert_table = isset($object_to_insert[0]) ? $object_to_insert[0] : null;
							$object_to_insert_attributes = isset($object_to_insert[1]) ? $object_to_insert[1] : null;
							$object_to_insert_options = isset($object_to_insert[2]) ? $object_to_insert[2] : null;
							
							if (!$db_driver->insertObject($object_to_insert_table, $object_to_insert_attributes, $object_to_insert_options))
								$s = false;
						}
					
					if ($s && $sqls)
						foreach ($sqls as $sql)
							if (!$db_driver->setData($sql))
								$s = false;
				}
				catch (Exception $e) {
					$s = false;
					$exception_msg .= "\n\nDB DRIVER: " . ($opts["db_name"] ? $opts["db_name"] : null) . "\n" . $e->problem . $e->getMessage();
				}
				
				if (!$s)
					$status = false;
			}
		}
		
		if ($exception_msg)
			launch_exception(new Exception($exception_msg));
		
		return $status;
	}
	
	/* PRIVATE */
	private function getLayerModulePaths($module_id, $layer_type, $webroot = false) {
		$paths = array();
		
		if (is_array($this->layers))
			foreach ($this->layers as $Layer)
				if (is_a($Layer, $layer_type)) {
					if ($layer_type == "PresentationLayer") {
						if ($webroot) {
							if (empty($Layer->settings["presentation_webroot_path"]))
								launch_exception(new Exception("\$Layer->settings[presentation_webroot_path] cannot be empty!"));
							
							$module_path = $Layer->getLayerPathSetting() . $Layer->getCommonProjectName() . "/" . $Layer->settings["presentation_webroot_path"] . "module/$module_id";
						}
						else {
							if (empty($Layer->settings["presentation_modules_path"]))
								launch_exception(new Exception("\$Layer->settings[presentation_modules_path] cannot be empty!"));
							
							$module_path = $Layer->getLayerPathSetting() . $Layer->getCommonProjectName() . "/" . $Layer->settings["presentation_modules_path"] . $module_id;
						}
					}
					else
						$module_path = $Layer->getLayerPathSetting() . "module/$module_id";
					
					if ($module_path)
						$paths[] = $module_path;
				}
		
		return array_unique($paths);
	}
	
	private function getLayersDBDrivers($selected_db_driver = false) {
		$db_drivers = array();
		
		if (is_array($this->layers))
			foreach ($this->layers as $Layer) 
				if (is_a($Layer, "DBLayer")) {
					$broker_drivers = $Layer->getBrokers();
					
					foreach ($broker_drivers as $broker_name => $broker_driver)
						if ((!$selected_db_driver || $broker_name == $selected_db_driver) && is_a($broker_driver, "DB") && !in_array($broker_driver, $db_drivers))
							$db_drivers[] = $broker_driver;
				}
		
		return $db_drivers;
	}
}
?>
