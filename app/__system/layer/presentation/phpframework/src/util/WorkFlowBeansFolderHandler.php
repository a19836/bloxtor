<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.bean.BeanFactory");
include_once $EVC->getUtilPath("PHPVariablesFileHandler");

class WorkFlowBeansFolderHandler {
	private $BeanFactory;
	private $main_project_name;
	private $user_beans_folder_path;
	private $user_global_variables_file_path;
	private $user_global_settings_file_path;
	private $global_paths;
	
	private $default_layers_folder = array();
	
	public function __construct($user_beans_folder_path, $user_global_variables_file_path, $user_global_settings_file_path, $global_paths = array()) {
		$this->BeanFactory = new BeanFactory();
		
		$this->user_beans_folder_path = $user_beans_folder_path;
		$this->user_global_variables_file_path = $user_global_variables_file_path;
		$this->user_global_settings_file_path = $user_global_settings_file_path;
		$this->global_paths = $global_paths;
		
		//init global_paths. This is very important bc the deployment code uses different paths, so we msut use the $this->global_paths variable instead of using directly the php defined variables.
		$this->global_paths = $this->global_paths ? $this->global_paths : array();
		$this->global_paths["LAYER_CACHE_PATH"] = !empty($this->global_paths["LAYER_CACHE_PATH"]) ? $this->global_paths["LAYER_CACHE_PATH"] : LAYER_CACHE_PATH;
		$this->global_paths["LAYER_PATH"] = !empty($this->global_paths["LAYER_PATH"]) ? $this->global_paths["LAYER_PATH"] : LAYER_PATH;
		$this->global_paths["BEAN_PATH"] = !empty($this->global_paths["BEAN_PATH"]) ? $this->global_paths["BEAN_PATH"] : BEAN_PATH;
		$this->global_paths["SYSTEM_LAYER_PATH"] = !empty($this->global_paths["SYSTEM_LAYER_PATH"]) ? $this->global_paths["SYSTEM_LAYER_PATH"] : SYSTEM_LAYER_PATH;
	}
	
	public function getGlobalPaths() {
		return $this->global_paths;
	}
	
	public function createDefaultFiles() {
		$status = true;
		$default_content = '<?php 
//The contents of these files cannot be "" (empty string), otherwise it will output an empty line and if we set headers in some other files, the headers will not be set, bc it already echoes an empty line. So we must add the open and close php tags.
//DO NOT OUTPUT ANYTHING IN THIS FILE!
?>';
		
		if (!file_exists($this->user_global_settings_file_path) && file_put_contents($this->user_global_settings_file_path, $default_content) === false)
			$status = false;
		
		if (!file_exists($this->user_global_variables_file_path) && file_put_contents($this->user_global_variables_file_path, $default_content) === false)
			$status = false;
		
		return $status;
	}
	
	public function removeOldBeansFiles() {
		if (is_dir($this->user_beans_folder_path) && ($dir = opendir($this->user_beans_folder_path)) ) {
			while( ($file = readdir($dir)) !== false) {
				if (substr($file, strlen($file) - 4) == ".xml" && !is_dir($this->user_beans_folder_path . $file) && is_writable($this->user_beans_folder_path . $file)) {
					unlink($this->user_beans_folder_path . $file);
				}
			}
			closedir($dir);
		}
		
		return true;
	}
	
	public function createDefaultLayer($new_default_layer_folder = null) {
		$htaccess_path = $this->global_paths["LAYER_PATH"] . ".htaccess";
		$exists = false;
		
		if ($new_default_layer_folder)
			foreach ($this->default_layers_folder as $layer_type => $layer_folders)
				foreach ($layer_folders as $layer_folder)
					if ($layer_folder == $new_default_layer_folder) {
						$exists = true;
						break;
					}
		
		if (!$exists) {
			$current_default_layer_folder = self::getDefaultLayerFolder($htaccess_path);
			
			if ($current_default_layer_folder)
				foreach ($this->default_layers_folder as $layer_type => $layer_folders)
					foreach ($layer_folders as $layer_folder)
						if ($layer_folder == $current_default_layer_folder) //if $new_default_layer_folder is invalid and the current saved layer folder still exists, do not do anything!
							return true;
		}
		
		if (!$exists) {
			$types_sorted = array("presentation", "business_logic", "data_access", "db_data");
			$new_default_layer_folder = null;
			
			foreach ($types_sorted as $type)
				if (!empty($this->default_layers_folder[$type])) {
					foreach ($this->default_layers_folder[$type] as $layer_folder)
						if ($layer_folder) {
							$new_default_layer_folder = $layer_folder;
							break;
						}
					
					if ($new_default_layer_folder)
						break;
				}
		}
		
		$status = true;
		
		if ($new_default_layer_folder) {
			if (file_exists($htaccess_path)) {
				$content = file_get_contents($htaccess_path);
				
				$new_default_layer_folder = preg_replace("/\/+/", "\/", $new_default_layer_folder); //remove duplicated '/'
				
				//'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars. 
				$content = preg_replace("/(RewriteRule\s*\\^\\$\s*)([\w\-\+\/]+)(\\/)/u", "$1" . $new_default_layer_folder . "$3", $content);
				$content = preg_replace("/(RewriteRule\s*\\(\\.\\*\\)\s*)([\w\-\+\/]+)(\\/\\$1)/u", "$1" . $new_default_layer_folder . "$3", $content);
			}
			else
				$content = 
'<IfModule mod_rewrite.c>
RewriteEngine on

RewriteRule ^$ ' . $new_default_layer_folder . '/ [L,NC]

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule (.*) ' . $new_default_layer_folder . '/$1 [L,NC]
</IfModule>';
			
			if (file_put_contents($htaccess_path, $content) === false)
				$status = false;
		}
		
		return $status;
	}
	
	public function setSetupProjectName($project_name) {
		return file_put_contents($this->global_paths["LAYER_CACHE_PATH"] . "default_project_name", $project_name) !== false;
	}
	
	public function getSetupProjectName() {
		return file_exists($this->global_paths["LAYER_CACHE_PATH"] . "default_project_name") ? file_get_contents($this->global_paths["LAYER_CACHE_PATH"] . "default_project_name") : "";
	}
	
	public function getSetupDefaultProjectName() {
		return "default";
	}
	
	public function prepareBeansFolder($file_path, $settings = false) {
		$status = true;
		
		$PHPVariablesFileHandler = new PHPVariablesFileHandler($this->user_global_variables_file_path);
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$external_vars = array();
		$this->BeanFactory->init(array("file" => $file_path, "external_vars" => $external_vars));
		$beans = $this->BeanFactory->getBeans();
		$vars = $this->BeanFactory->getObjects();
		
		//echo "$file_path\n<br>";
		//print_r($beans);
		//print_r($vars);
		
		$this->main_project_name = $this->getSetupProjectName();
		
		$default_dbl_folder = false;
		$default_dal_folder = false;
		$default_business_logic_folder = false;
		$default_presentation_folder = false;
		
		foreach($beans as $name => $bean) {
			if (file_exists($bean->path)) {
				include_once $bean->path;
			
				if (is_subclass_of($bean->class_name, "ILayer")) {
					if (is_array($bean->constructor_args)) {
						foreach ($bean->constructor_args as $constructor_arg) {
							$arg = $constructor_arg->value;
					
							if (empty($arg)) {
								$reference = $constructor_arg->reference;
								$arg = isset($vars[$reference]) ? $vars[$reference] : null;
							}
							
							if (!empty($arg)) {
								$path = false;
								$type = null;
								
								if (isset($arg["presentations_path"])) {
									$type = "presentation";
									$path = $arg["presentations_path"];
									
									if (empty($default_presentation_folder)) $default_presentation_folder = basename($path);
								}
								else if (isset($arg["business_logic_path"])) {
									$type = "business_logic";
									$path = $arg["business_logic_path"];
									
									if (empty($default_business_logic_folder)) $default_business_logic_folder = basename($path);
								}
								else if (isset($arg["dal_path"])) {
									$type = "data_access";
									$path = $arg["dal_path"];
									
									if (empty($default_dal_folder)) $default_dal_folder = basename($path);
								}
								else if (isset($arg["dbl_path"])) {
									$type = "db_data";
									$path = $arg["dbl_path"];
									
									if (empty($default_dbl_folder)) $default_dbl_folder = basename($path);
								}
								
								if (!empty($path)) {
									//prepare path with new global path
									//Since is impossible to change a DEFINED CONSTANT in PHP, the $file_path will be parsed with the original PHP DEFINED CONSTANT, and then we will update the $path value with the $this->global_paths["LAYER_PATH"]
									if ($this->global_paths["LAYER_PATH"] != LAYER_PATH && substr($path, 0, strlen(LAYER_PATH)) == LAYER_PATH)
										$path = $this->global_paths["LAYER_PATH"] . substr($path, strlen(LAYER_PATH)); //if folder doesn't exist yet, it will create it in the next instructions.
									
									
									if (is_dir($path) || mkdir($path, 0755, true)) {
										$path .= substr($path, -1) != "/" ? "/" : "";
										
										$this->BeanFactory->initObjects();
										
										switch($type) {
											case "presentation": 
												$this->preparePresentationFolder($file_path, $path, $beans, $settings);
												break;
											case "business_logic":  
												$this->prepareBusinessLogicFolder($file_path, $path, $beans, $settings);
												break;
											case "data_access": 
												$obj = $this->BeanFactory->getObject($name);
												if (is_a($obj, "IbatisDataAccessLayer"))
													$this->prepareIbatisFolder($file_path, $path, $beans, $settings);
												else 
													$this->prepareHibernateFolder($file_path, $path, $beans, $settings);
												break;
											case "db_data":  
												$this->prepareDBDataFolder($file_path, $path, $beans, $settings);
												break;
										}
									}
									else
										$status = false;
									
									break;
								}
							}
						}
					}
				}
			}
		}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
		
		//PREPARE DEFAULT LAYERS FOR APP/LAYER/.HTACCESS
		if ($default_presentation_folder)
			$this->default_layers_folder["presentation"][] = $default_presentation_folder;
		
		if ($default_business_logic_folder)
			$this->default_layers_folder["business_logic"][] = $default_business_logic_folder;
		
		if ($default_dal_folder)
			$this->default_layers_folder["data_access"][] = $default_dal_folder;
		
		if ($default_dbl_folder)
			$this->default_layers_folder["db_data"][] = $default_dbl_folder;
		
		return $status;
	}
	
	private function preparePresentationFolder($bean_file_path, $layer_folder_path, $beans, $settings = false) {
		//PREPARE COMMON PROJECT
		$common_project_name = "";
		foreach($beans as $name => $bean) {
			$obj = $this->BeanFactory->getObject($name);
			
			if (is_a($obj, "PresentationLayer")) {
				$common_project_name = $obj->getCommonProjectName();
				break;
			}
		}
		$common_project_name = $common_project_name ? $common_project_name : "common";
		
		//PREPARE .HTACCESS
		$main_project_name = $this->main_project_name;
		
		if (!$main_project_name) {
			//get current $main_project_name from .htaccess file
			$htaccess_path = $layer_folder_path . ".htaccess";
			$main_project_name = self::getPresentationLayerDefaultproject($htaccess_path);
			
			//if still no $main_project_name, get the first project from $layer_folder_path
			if (!$main_project_name && $layer_folder_path && is_dir($layer_folder_path)) {
				$files = scandir($layer_folder_path);
				
				if ($files)
					foreach ($files as $file)
						if ($file != "." && $file != ".." && $file != $common_project_name && is_dir("$layer_folder_path/$file") && is_dir("$layer_folder_path/$file/webroot")) {
							$main_project_name = $file;
							break;
						}
			}
		}
		
		if ($main_project_name || !file_exists($layer_folder_path . ".htaccess")) {
			$content = 
'<IfModule mod_rewrite.c>
   RewriteEngine on
  
   RewriteRule ^$ ' . ($main_project_name ? $main_project_name : $this->getSetupDefaultProjectName()) . '/webroot/ [L,NC]
   
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteRule (.*) ' . ($main_project_name ? $main_project_name : $this->getSetupDefaultProjectName()) . '/webroot/$1 [L,NC]
</IfModule>';
			
			file_put_contents($layer_folder_path . ".htaccess", $content);
		}
		
		//PREPARE PRESENTATIONS.xml
		$content = 
'<?xml version="1.0" encoding="UTF-8"?>
<modules>
	<module id="COMMON">' . $common_project_name . '</module>
	' . ($this->main_project_name ? '<module id="' . strtoupper($this->main_project_name) . '">' . $this->main_project_name . '</module>' : '') . '
</modules>';
		if (!file_exists($layer_folder_path . "modules.xml"))
			file_put_contents($layer_folder_path . "modules.xml", $content);
		
		//PREPARE INIT.PHP
		$bean_file_name = substr($bean_file_path, strlen($this->global_paths["BEAN_PATH"]));
		$dispacher_cache_handler_bean_name = false;
		$presentation_layer_bean_name = false;
		$evc_dispacher_bean_name = false;
		$evc_bean_name = false;
		
		foreach($beans as $name => $bean) {
			$obj = $this->BeanFactory->getObject($name);
			
			if (is_a($obj, "DispatcherCacheHandler"))
				$dispacher_cache_handler_bean_name = $name;
			else if (is_a($obj, "PresentationLayer")) 
				$presentation_layer_bean_name = $name;
			else if (is_a($obj, "EVCDispatcher") || is_a($obj, "PresentationDispatcher")) 
				$evc_dispacher_bean_name = $name;
			else if (is_a($obj, "EVC"))
				$evc_bean_name = $name;
		}
		
		$content = 
'<?php
try {
	define(\'GLOBAL_SETTINGS_PROPERTIES_FILE_PATH\', dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))) . "/config/global_settings.php");
	define(\'GLOBAL_VARIABLES_PROPERTIES_FILE_PATH\', dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))) . "/config/global_variables.php");

	include dirname(dirname(__DIR__)) . "/app.php";
	include_once get_lib("org.phpframework.webservice.layer.PresentationLayerWebService");

	define(\'BEANS_FILE_PATH\', BEAN_PATH . \'' . $bean_file_name . '\');
	define(\'PRESENTATION_DISPATCHER_CACHE_HANDLER_BEAN_NAME\', \'' . $dispacher_cache_handler_bean_name . '\');
	define(\'PRESENTATION_LAYER_BEAN_NAME\', \'' . $presentation_layer_bean_name . '\');
	define(\'EVC_DISPATCHER_BEAN_NAME\', \'' . $evc_dispacher_bean_name . '\');
	define(\'EVC_BEAN_NAME\', \'' . $evc_bean_name . '\');

	echo call_presentation_layer_web_service(array(
		"presentation_id" => isset($presentation_id) ? $presentation_id : null, 
		"external_vars" => isset($external_vars) ? $external_vars : null, 
		"includes" => isset($includes) ? $includes : null, 
		"includes_once" => isset($includes_once) ? $includes_once : null, 
	));
}
catch(Exception $e) {
	$GlobalExceptionLogHandler->log($e);
}
?>';
		file_put_contents($layer_folder_path . "init.php", $content);
		
		//PREPARE COMMON FOLDER
		if (!file_exists($layer_folder_path . "$common_project_name/")) {
			self::copyFolder($this->global_paths["SYSTEM_LAYER_PATH"] . "presentation/$common_project_name/", $layer_folder_path . "$common_project_name/");
			CacheHandlerUtil::deleteFolder($layer_folder_path . "$common_project_name/src/module/", false);
			CacheHandlerUtil::deleteFolder($layer_folder_path . "$common_project_name/webroot/module/", false);
		}
		
		//PREPARE DEFAULT PROJECT FOLDER
		if ($this->main_project_name && !file_exists($layer_folder_path . "/" . $this->main_project_name . "/"))
			self::copyFolder($this->global_paths["SYSTEM_LAYER_PATH"] . "presentation/empty/", $layer_folder_path . "/" . $this->main_project_name . "/");
	}
	
	private function prepareBusinessLogicFolder($bean_file_path, $layer_folder_path, $beans, $settings = false) {
		//PREPARE .HTACCESS
		$content = 
'<IfModule mod_rewrite.c>
    RewriteEngine On
   
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ init.php?url=$1 [QSA,L,NC]
</IfModule>';
		file_put_contents($layer_folder_path . ".htaccess", $content);
		
		//PREPARE BUSINESS_LOGIC.XML
		$common_module_name = "";
		
		foreach($beans as $name => $bean) {
			$obj = $this->BeanFactory->getObject($name);
			
			if (is_a($obj, "BusinessLogicLayer"))
				$common_module_name = isset($obj->settings["business_logic_modules_common_name"]) ? $obj->settings["business_logic_modules_common_name"] : null;
		}
		
		$common_module_name = $common_module_name ? $common_module_name : "common";
		
		$content = 
'<?xml version="1.0" encoding="UTF-8"?>
<modules>
	<module id="COMMON">' . $common_module_name . '</module>
	' . ($this->main_project_name ? '<module id="' . strtoupper($this->main_project_name) . '">' . $this->main_project_name . '</module>' : '') . '
</modules>';
		if (!file_exists($layer_folder_path . "modules.xml"))
			file_put_contents($layer_folder_path . "modules.xml", $content);
		
		//PREPARE INIT.PHP
		$bean_file_name = substr($bean_file_path, strlen($this->global_paths["BEAN_PATH"]));
		$extra = $this->getRemoteServerInitBeansCode($beans, $settings, "BusinessLogicBrokerServer", $default_layer_broker_type, $default_broker_server_bean_name, $default_broker_server_request_encryption_key, $default_global_variables_code);
		
		if (!$default_broker_server_bean_name) 
			$content = '<?php
//no remote broker server defined!
?>';
		else
			$content = '<?php
try {
	define(\'GLOBAL_SETTINGS_PROPERTIES_FILE_PATH\', dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))) . "/config/global_settings.php");
	define(\'GLOBAL_VARIABLES_PROPERTIES_FILE_PATH\', dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))) . "/config/global_variables.php");

	include dirname(dirname(__DIR__)) . "/app.php";
	include_once get_lib("org.phpframework.webservice.layer.BusinessLogicLayerWebService");

	define(\'BEANS_FILE_PATH\', BEAN_PATH . \'' . $bean_file_name . '\');
	
	$broker_server_bean_name = \'' . $default_broker_server_bean_name . '\';
	$broker_server_request_encryption_key = \'' . $default_broker_server_request_encryption_key . '\';
	' . $default_global_variables_code . '
	' . $extra . '
	define(\'BUSINESS_LOGIC_BROKER_SERVER_BEAN_NAME\', $broker_server_bean_name);
	
	echo call_business_logic_layer_web_service(array(
		"global_variables" => isset($_POST["gv"]) ? $_POST["gv"] : null, 
		"request_encryption_key" => $broker_server_request_encryption_key
	));
}
catch(Exception $e) {
	$GlobalExceptionLogHandler->log($e);
}
?>';
		
		file_put_contents($layer_folder_path . "init.php", $content);
		
		//PREPARE COMMON FOLDER
		if (!file_exists($layer_folder_path . "$common_module_name/"))
			self::copyFolder($this->global_paths["SYSTEM_LAYER_PATH"] . "businesslogic/$common_module_name/", $layer_folder_path . "$common_module_name/");
		
		//PREPARE CommonService.php changing namespace to correspodent folder name
		$common_service_class_path = $layer_folder_path . "$common_module_name/CommonService.php";
		
		if (file_exists($common_service_class_path)) {
			$content = file_get_contents($common_service_class_path);
			$ll = substr($layer_folder_path, strlen($this->global_paths["LAYER_PATH"]));
			$ll = preg_replace("/\/+/", "/", $ll);
			$ll = substr($ll, -1) == "/" ? substr($ll, 0, -1) : $ll;
			$namespace = str_replace("/", "\\", $ll);
			
			//change 'namespace __system\businesslogic;' to 'namespace $ll;'
			$content = preg_replace("/namespace\s+([^;]+);/", "namespace $ll;", $content, 1);
			
			//change 'if (!class_exists("\__system\businesslogic\CommonService"))' to 'if (!class_exists("\$ll\CommonService"))'
			$content = preg_replace("/if\s*\(\s*!\s*class_exists\s*\(\s*\"([\w\\\\]+)\"\s*\)\s*\)/", "if (!class_exists(\"\\$ll\\CommonService\"))", $content, 1);
			
			//error_log("$common_service_class_path($ll), $content\n\n", 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
			file_put_contents($common_service_class_path, $content);
		}
		
		//PREPARE BUSINESS_LOGIC_CL_COMMON_SERVICES.xml
		$prefix = substr($bean_file_name, 0, strlen($bean_file_name) - 4);
		$services_file_path = $this->global_paths["BEAN_PATH"] . $prefix . "_common_services.xml";
		
		if (file_exists($services_file_path) && !file_exists("$common_module_name/services.xml"))
			copy($services_file_path, $layer_folder_path . "$common_module_name/services.xml");
		
		//PREPARE EMPTY FOLDER
		if ($this->main_project_name && !file_exists($layer_folder_path . $this->main_project_name))
			self::copyFolder($this->global_paths["SYSTEM_LAYER_PATH"] . "businesslogic/empty/", $layer_folder_path . $this->main_project_name . "/");
		
		//PREPARE MODULE FOLDER
		if (!is_dir($layer_folder_path . "module"))
			@mkdir($layer_folder_path . "module", 0775, true);
		
		//PREPARE PROGRAM FOLDER
		if (!is_dir($layer_folder_path . "program"))
			@mkdir($layer_folder_path . "program", 0775, true);
		
		//PREPARE RESOURCE FOLDER
		if (!is_dir($layer_folder_path . "resource"))
			@mkdir($layer_folder_path . "resource", 0775, true);
	}
	
	private function prepareIbatisFolder($bean_file_path, $layer_folder_path, $beans, $settings = false) {
		$this->prepareDataAccessFolder($bean_file_path, $layer_folder_path, $beans, "ibatis", $settings);
	}
	
	private function prepareHibernateFolder($bean_file_path, $layer_folder_path, $beans, $settings = false) {
		$this->prepareDataAccessFolder($bean_file_path, $layer_folder_path, $beans, "hibernate", $settings);
	}
	
	private function prepareDataAccessFolder($bean_file_path, $layer_folder_path, $beans, $type, $settings = false) {
		//PREPARE .HTACCESS
		$content = 
'<IfModule mod_rewrite.c>
    RewriteEngine On
   
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ init.php?url=$1 [QSA,L,NC]
</IfModule>';
		file_put_contents($layer_folder_path . ".htaccess", $content);
		
		//PREPARE modules.xml
		$content = 
'<?xml version="1.0" encoding="UTF-8"?>
<modules>
	' . ($this->main_project_name ? '<module id="' . strtoupper($this->main_project_name) . '">' . $this->main_project_name . '</module>' : '') . '
</modules>';
		if (!file_exists($layer_folder_path . "modules.xml"))
			file_put_contents($layer_folder_path . "modules.xml", $content);
		
		//PREPARE INIT.PHP
		$bean_file_name = substr($bean_file_path, strlen($this->global_paths["BEAN_PATH"]));
		
		if ($type == "ibatis") {
			$extra = $this->getRemoteServerInitBeansCode($beans, $settings, "IbatisDataAccessBrokerServer", $default_layer_broker_type, $default_broker_server_bean_name, $default_broker_server_request_encryption_key, $default_global_variables_code);
			
			if (!$default_broker_server_bean_name) 
				$content = '<?php
//no remote broker server defined!
?>';
			else
				$content = 
'<?php
try {
	define(\'GLOBAL_SETTINGS_PROPERTIES_FILE_PATH\', dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))) . "/config/global_settings.php");
	define(\'GLOBAL_VARIABLES_PROPERTIES_FILE_PATH\', dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))) . "/config/global_variables.php");

	include dirname(dirname(__DIR__)) . "/app.php";
	include_once get_lib("org.phpframework.webservice.layer.IbatisDataAccessLayerWebService");

	define(\'BEANS_FILE_PATH\', BEAN_PATH . \'' . $bean_file_name . '\');
	
	$broker_server_bean_name = \'' . $default_broker_server_bean_name . '\';
	$broker_server_request_encryption_key = \'' . $default_broker_server_request_encryption_key . '\';
	' . $default_global_variables_code . '
	' . $extra . '
	define(\'IBATIS_DATA_ACCESS_BROKER_SERVER_BEAN_NAME\', $broker_server_bean_name);

	echo call_ibatis_data_access_layer_web_service(array(
		"global_variables" => isset($_POST["gv"]) ? $_POST["gv"] : null, 
		"request_encryption_key" => $broker_server_request_encryption_key
	));
}
catch(Exception $e) {
	$GlobalExceptionLogHandler->log($e);
}
?>';
		}
		else {
			$extra = $this->getRemoteServerInitBeansCode($beans, $settings, "HibernateDataAccessBrokerServer", $default_layer_broker_type, $default_broker_server_bean_name, $default_broker_server_request_encryption_key, $default_global_variables_code);
			
			if (!$default_broker_server_bean_name) 
				$content = '<?php
//no remote broker server defined!
?>';
			else
				$content = 
'<?php
try {
	define(\'GLOBAL_SETTINGS_PROPERTIES_FILE_PATH\', dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))) . "/config/global_settings.php");
	define(\'GLOBAL_VARIABLES_PROPERTIES_FILE_PATH\', dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))) . "/config/global_variables.php");

	include dirname(dirname(__DIR__)) . "/app.php";
	include_once get_lib("org.phpframework.webservice.layer.HibernateDataAccessLayerWebService");

	define(\'BEANS_FILE_PATH\', BEAN_PATH . \'' . $bean_file_name . '\');
	
	$broker_server_bean_name = \'' . $default_broker_server_bean_name . '\';
	$broker_server_request_encryption_key = \'' . $default_broker_server_request_encryption_key . '\';
	' . $default_global_variables_code . '
	' . $extra . '
	define(\'HIBERNATE_DATA_ACCESS_BROKER_SERVER_BEAN_NAME\', $broker_server_bean_name);

	echo call_hibernate_data_access_layer_web_service(array(
		"global_variables" => isset($_POST["gv"]) ? $_POST["gv"] : null, 
		"request_encryption_key" => $broker_server_request_encryption_key
	));
}
catch(Exception $e) {
	$GlobalExceptionLogHandler->log($e);
}
?>';
		}
		file_put_contents($layer_folder_path . "init.php", $content);
		
		//PREPARE CACHE_HANDLER.xml
		$content = 
'<?xml version="1.0" encoding="UTF-8"?>
<beans>
	<!-- START FILE SYSTEM HANDLER --> 
	<bean name="ServiceCacheHandler" path="org.phpframework.cache.service.filesystem.FileSystemServiceCacheHandler" path_prefix="<?php echo LIB_PATH;?>">
		<constructor_arg><?php echo isset($vars["dal_module_cache_maximum_size"]) ? $vars["dal_module_cache_maximum_size"] : null; ?></constructor_arg>
		
		<property name="rootPath"><?php echo (isset($vars["dal_cache_path"]) ? $vars["dal_cache_path"] : "") . (isset($vars["current_dal_module_id"]) ? $vars["current_dal_module_id"] : ""); ?></property>
		<property name="defaultTTL"><?php echo isset($vars["dal_default_cache_ttl"]) ? $vars["dal_default_cache_ttl"] : null; ?></property>
	</bean>
	<!-- END FILE SYSTEM HANDLER --> 
</beans>';
		if (!file_exists($layer_folder_path . "cache_handler.xml"))
			file_put_contents($layer_folder_path . "cache_handler.xml", $content);
		
		//PREPARE DEFAULT PROJECT FOLDER
		if ($this->main_project_name && !is_dir($layer_folder_path . $this->main_project_name))
			@mkdir($layer_folder_path . $this->main_project_name, 0775, true);
		
		//PREPARE COMMON FOLDER
		if (!is_dir($layer_folder_path . "common"))
			@mkdir($layer_folder_path . "common", 0775, true);
		
		//PREPARE MODULE FOLDER
		if (!is_dir($layer_folder_path . "module"))
			@mkdir($layer_folder_path . "module", 0775, true);
		
		//PREPARE PROGRAM FOLDER
		if (!is_dir($layer_folder_path . "program"))
			@mkdir($layer_folder_path . "program", 0775, true);
		
		//PREPARE RESOURCE FOLDER
		if (!is_dir($layer_folder_path . "resource"))
			@mkdir($layer_folder_path . "resource", 0775, true);
		
		//PREPARE SERVICES.xml
		$content = 
'<?xml version="1.0" encoding="UTF-8"?>
<beans>
	<services>
		
	</services>
</beans>';
		if ($this->main_project_name && !file_exists($layer_folder_path . $this->main_project_name . "/services.xml"))
			file_put_contents($layer_folder_path . $this->main_project_name . "/services.xml", $content);
	}
	
	private function prepareDBDataFolder($bean_file_path, $layer_folder_path, $beans, $settings = false) {
		//PREPARE .HTACCESS
		$content = 
'<IfModule mod_rewrite.c>
    RewriteEngine On
   
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ init.php?url=$1 [QSA,L,NC]
</IfModule>';
		file_put_contents($layer_folder_path . ".htaccess", $content);
		
		//PREPARE INIT.PHP
		$bean_file_name = substr($bean_file_path, strlen($this->global_paths["BEAN_PATH"]));
		$extra = $this->getRemoteServerInitBeansCode($beans, $settings, "DBBrokerServer", $default_layer_broker_type, $default_broker_server_bean_name, $default_broker_server_request_encryption_key, $default_global_variables_code);
		
		if (!$default_broker_server_bean_name) 
			$content = '<?php
//no remote broker server defined!
?>';
		else
			$content = 
'<?php
try {
	define(\'GLOBAL_SETTINGS_PROPERTIES_FILE_PATH\', dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))) . "/config/global_settings.php");
	define(\'GLOBAL_VARIABLES_PROPERTIES_FILE_PATH\', dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))) . "/config/global_variables.php");

	include dirname(dirname(__DIR__)) . "/app.php";
	include_once get_lib("org.phpframework.webservice.layer.DBLayerWebService");
	
	define(\'BEANS_FILE_PATH\', BEAN_PATH . \'' . $bean_file_name . '\');
	
	$broker_server_bean_name = \'' . $default_broker_server_bean_name . '\';
	$broker_server_request_encryption_key = \'' . $default_broker_server_request_encryption_key . '\';
	' . $default_global_variables_code . '
	' . $extra . '
	define(\'DB_BROKER_SERVER_BEAN_NAME\', $broker_server_bean_name);

	echo call_db_layer_web_service(array(
		"global_variables" => isset($_POST["gv"]) ? $_POST["gv"] : null, 
		"request_encryption_key" => $broker_server_request_encryption_key
	));
}
catch(Exception $e) {
	$GlobalExceptionLogHandler->log($e);
}
?>';
		file_put_contents($layer_folder_path . "init.php", $content);
		
		//PREPARE CACHE_HANDLER.xml
		$content = 
'<?xml version="1.0" encoding="UTF-8"?>
<beans>
	<!-- START FILE SYSTEM HANDLER --> 
	<bean name="ServiceCacheHandler" path="org.phpframework.cache.service.filesystem.FileSystemServiceCacheHandler" path_prefix="<?php echo LIB_PATH;?>">
		<constructor_arg><?php echo isset($vars["dbl_module_cache_maximum_size"]) ? $vars["dbl_module_cache_maximum_size"] : null; ?></constructor_arg>
		
		<property name="rootPath"><?php echo isset($vars["dbl_cache_path"]) ? $vars["dbl_cache_path"] : null; ?></property>
		<property name="defaultTTL"><?php echo isset($vars["dbl_default_cache_ttl"]) ? $vars["dbl_default_cache_ttl"] : null; ?></property>
	</bean>
	<!-- END FILE SYSTEM HANDLER --> 
</beans>';
		if (!file_exists($layer_folder_path . "cache_handler.xml"))
			file_put_contents($layer_folder_path . "cache_handler.xml", $content);
	}
	
	private function getRemoteServerInitBeansCode($beans, $settings, $class_suffix, &$default_layer_broker_type, &$default_broker_server_bean_name, &$default_broker_server_request_encryption_key, &$default_global_variables_code) {
		$extra = '';
		$remote_broker_server_bean_names_by_type = array();
		
		if (!empty($settings["layer_brokers"]))
			foreach($beans as $name => $bean) {
				$obj = $this->BeanFactory->getObject($name);
				
				if (is_a($obj, "BrokerServer") && !is_a($obj, "LocalDBBrokerServer"))
					foreach ($settings["layer_brokers"] as $layer_broker_type => $layer_broker_props) //layer_broker_type: Local, REST or SOAP in the future...
						if (is_a($obj, strtoupper($layer_broker_type) . $class_suffix))
							$remote_broker_server_bean_names_by_type[$layer_broker_type] = $name;
			}
		
		if ($remote_broker_server_bean_names_by_type) {
			$default_layer_broker_type = key($remote_broker_server_bean_names_by_type);
			$default_broker_server_bean_name = isset($remote_broker_server_bean_names_by_type[$default_layer_broker_type]) ? $remote_broker_server_bean_names_by_type[$default_layer_broker_type] : null;
			$default_broker_server_request_encryption_key = isset($settings["layer_brokers"][$default_layer_broker_type]["request_encryption_key"]) ? $settings["layer_brokers"][$default_layer_broker_type]["request_encryption_key"] : null;
			$default_global_variables_code = $this->getRemoteServerInitBeansGlobalVariablesCode(isset($settings["layer_brokers"][$default_layer_broker_type]["global_variables"]) ? $settings["layer_brokers"][$default_layer_broker_type]["global_variables"] : null);
			
			if (count($remote_broker_server_bean_names_by_type) > 1) {
				$extra .= '
	$headers = getallheaders();
	$header_layer_broker_server_type = isset($headers["layer_broker_server_type"]) ? $headers["layer_broker_server_type"] : null;
	
	switch ($header_layer_broker_server_type) {';
				
				foreach ($remote_broker_server_bean_names_by_type as $layer_broker_type => $broker_server_bean_name)
					if ($layer_broker_type != $default_layer_broker_type) {
						$default_global_variables_code .= $this->getRemoteServerInitBeansGlobalVariablesCode(isset($settings["layer_brokers"][$layer_broker_type]["global_variables"]) ? $settings["layer_brokers"][$layer_broker_type]["global_variables"] : null);
						$extra .= '
		case "' . $layer_broker_type . '":
			$broker_server_bean_name = \'' . $broker_server_bean_name . '\';
			$broker_server_request_encryption_key = \'' . (isset($settings["layer_brokers"][$layer_broker_type]["request_encryption_key"]) ? $settings["layer_brokers"][$layer_broker_type]["request_encryption_key"] : "") . '\';
			' . str_replace("\n", "\n\t\t", $default_global_variables_code) . '
			break;';
					}
				
				$extra .= '
	}
	
	unset($headers);
	';
			}
		}
		
		return $extra;
	}
	
	private function getRemoteServerInitBeansGlobalVariablesCode($global_variables) {
		$code = "";
		
		if (is_array($global_variables) && !empty($global_variables["vars_name"])) { 
			$vars_name = $global_variables["vars_name"];
			$vars_value = isset($global_variables["vars_value"]) ? $global_variables["vars_value"] : null;
			
			if (!is_array($vars_name)) {
				$vars_name = array($vars_name);
				$vars_value = array($vars_value);
			}
			
			foreach ($vars_name as $idx => $name)
				if ($name) {
					$value = isset($vars_value[$idx]) ? $vars_value[$idx] : null;
					$code .= ($code ? "\n\t" : "") . "\$$name = '" . $value . "';";
				}
		}
		
		return $code;
	}
	
	public static function getDefaultLayerFolder($htaccess_path) {
		$default_layer_name = null;
		
		if (file_exists($htaccess_path)) {
			$contents = file_get_contents($htaccess_path);
			
			preg_match("/RewriteRule\s*\\^\\$\s*([\w\-\+]+)\\//iu", $contents, $matches_1); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars. 
			preg_match("/RewriteRule\s*\\(\\.\\*\\)\s*([\w\-\+]+)\\/\\$1/iu", $contents, $matches_2); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars. 
			
			if ($matches_1)
				$default_layer_name = $matches_1[1];
			else if ($matches_2)
				$default_layer_name = $matches_2[1];
		}
		
		return $default_layer_name;
	}
	
	public static function getPresentationLayerDefaultproject($htaccess_path) {
		$default_project_name = null;
		
		if (file_exists($htaccess_path)) {
			$contents = file_get_contents($htaccess_path);
			
			preg_match("/RewriteRule\s*\\^\\$\s*([\w\-\+\/]+)\\/webroot\\//iu", $contents, $matches_1); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars. 
			preg_match("/RewriteRule\s*\\(\\.\\*\\)\s*([\w\-\+\/]+)\\/webroot\\/\\$1/iu", $contents, $matches_2); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars. 
			
			if ($matches_1)
				$default_project_name = $matches_1[1];
			else if ($matches_2)
				$default_project_name = $matches_2[1];
			
			$default_project_name = preg_replace("/\/+/", "/", $default_project_name); //remove duplicated '/'
		}
		
		return $default_project_name;
	}
	
	public static function copyFolder($src, $dst) {
		$status = (file_exists($dst) && !is_dir($dst)) || (!file_exists($dst) && !mkdir($dst, 0775, true)) ? false : true;
		
		if($status) {
			if (is_dir($src)) {
				$files = scandir($src);
				
				if ($files)
					foreach ($files as $file)
						if ($file != "." && $file != "..") {
							if(is_dir($src . $file)) {
								if (!self::copyFolder($src . $file . "/", $dst . $file . "/"))
									$status = false;
							}
							else if (!copy($src . $file, $dst . $file))
								$status = false;
						}
			}
			else
				$status = false;
		}
		
		return $status;
	}
}
?>
