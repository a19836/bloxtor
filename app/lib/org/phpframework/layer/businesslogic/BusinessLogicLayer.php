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

include get_lib("org.phpframework.layer.businesslogic.exception.BusinessLogicLayerException");
include_once get_lib("org.phpframework.bean.BeanFactory");
include_once get_lib("org.phpframework.layer.Layer");
include_once get_lib("org.phpframework.phpscript.PHPCodePrintingHandler");
include_once get_lib("org.phpframework.phpscript.docblock.DocBlockParser");
include_once get_lib("org.phpframework.cms.VendorFrameworkHandler");

class BusinessLogicLayer extends Layer {
	private $modules = array();
	public $modules_vars = array();
	
	private $bean_objs;
	
	private $DocBlockParser;

	public function __construct($settings = array()) {
		parent::__construct($settings);
	}
	
	public function setDocBlockParser($DocBlockParser) {
		$this->DocBlockParser = $DocBlockParser;
	}
	
	public function getLayerPathSetting() {
		if (empty($this->settings["business_logic_path"]))
			launch_exception(new BusinessLogicLayerException(9, "BusinessLogicLayer->settings[business_logic_path]"));
		
		return $this->settings["business_logic_path"];
	}
	
	public function getModulesFilePathSetting() {
		if (empty($this->settings["business_logic_modules_file_path"]))
			launch_exception(new BusinessLogicLayerException(9, "BusinessLogicLayer->settings[business_logic_modules_file_path]"));
		
		return $this->settings["business_logic_modules_file_path"];
	}
	
	public function getServicesFileNameSetting() {
		if (empty($this->settings["business_logic_services_file_name"]))
			launch_exception(new BusinessLogicLayerException(9, "BusinessLogicLayer->settings[business_logic_services_file_name]"));
		
		return $this->settings["business_logic_services_file_name"];
	}
	
	/*
	$xxx->callBusinessLogic("TEST", "get_obj", array(...)); //modules.xml has an alias TEST => test
	$xxx->callBusinessLogic("test/subtest", "foo", "value xxx"); //test/subtest is a folder
	$xxx->callBusinessLogic("test.subtest.IndependentFunctionsServices.php", "foo", "value xxx");
	$xxx->callBusinessLogic("test/subtest/IndependentFunctionsServices.php", "foo", "value xxx");
	$xxx->callBusinessLogic("test/subtest/IndependentFunctionsServices", "foo", "value xxx"); //default_extension will be added. IndependentFunctionsServices is a php file.
	$xxx->callBusinessLogic("test.subtest.IndependentFunctionsServices", "foo", "value xxx"); //default_extension will be added. IndependentFunctionsServices is a php file.
	$xxx->callBusinessLogic("test.subtest.SubTestService.php", "SubTestService.executeBusinessLogicSubTest", null, array("no_cache" => false, "no_annotations" => true));
	$xxx->callBusinessLogic("test/subtest", "SubTestService.executeBusinessLogicSubTest", null, array("no_cache" => false, "no_annotations" => true)); //test/subtest is a folder
	*/
	public function callBusinessLogic($module_id, $service_id, $parameters = false, $options = false) {
		//if exist namespace in $service_id
		if (strpos($service_id, "\\") !== false) {
			if (substr($service_id, 0, 1) == "\\" && strpos($service_id, "\\", 1) === false) //remove first back slash from service in case there is no namespace
				$service_id = substr($service_id, 1);
			else if (substr($service_id, 0, 1) != "\\" && strpos($service_id, "\\") !== false) //add back slash to the beginning of the service if exist namespace in the service but the first back-slash is missing.
				$service_id = "\\" . $service_id;
		}
		
		debug_log_function("BusinessLogicLayer->callBusinessLogic", array($module_id, $service_id, $parameters, $options));
		
		$is_cache_active = $this->isCacheActive();
		if($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid($module_id, $service_id, $parameters, $options))
			return $this->getCacheLayer()->get($module_id, $service_id, $parameters, $options);
		
		$this->initModuleServices($module_id);
		//print_r($this->modules[$module_id]);die();
		//if($module_id=="test")error_log(print_r($this->modules[$module_id],1)."\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
		
		if ($this->getErrorHandler()->ok()) {
			$result = $this->callService($module_id, $service_id, $parameters, $options);
			
			if ($this->getErrorHandler()->ok()) {
				if($is_cache_active) 
					$this->getCacheLayer()->check($module_id, $service_id, $parameters, $result, $options);
				
				return $result;
			}
		}
		
		return false;
	}
	
	public function getBusinessLogicServiceProps($module_id, $service_id, $parameters = false, $options = false) {
		$props = array();
		
		//if exist namespace in $service_id
		if (strpos($service_id, "\\") !== false) {
			if (substr($service_id, 0, 1) == "\\" && strpos($service_id, "\\", 1) === false) //remove first back slash from service in case there is no namespace
				$service_id = substr($service_id, 1);
			else if (substr($service_id, 0, 1) != "\\" && strpos($service_id, "\\") !== false) //add back slash to the beginning of the service if exist namespace in the service but the first back-slash is missing.
				$service_id = "\\" . $service_id;
		}
		
		$this->initModuleServices($module_id);
		
		if($this->getErrorHandler()->ok()) {
			$module = isset($this->modules[$module_id]) ? $this->modules[$module_id] : null;
			//echo "<pre>";print_r($module);die();
			//error_log(print_r($module["beans"],1)."\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
			//if ($constructor=="\__system\businesslogic\TestExtendCommonServiceWithDiferentName"){echo "<pre>";print_r($this->getBeanObjs());die();}
			//echo "<pre>";print_r($this->modules[$module_id]);die();
			$props["module"] = $module;
			
			if($this->moduleServiceExists($module_id, $service_id) && !empty($module["services"][$service_id])) {
				$service = $module["services"][$service_id];
				$function_name = isset($service[0]) ? $service[0] : null;
				$constructor = isset($service[1]) ? $service[1] : null;
				$type = isset($service[2]) ? $service[2] : null;
				$namespace = isset($service[3]) ? $service[3] : null;
				
				$props["service"] = $service;
				$props["function_name"] = $function_name;
				$props["constructor"] = $constructor;
				$props["type"] = $type;
				$props["namespace"] = $namespace;
				//echo "$module_id, $service_id, $function_name, $constructor, $type, $namespace";die();
				//error_log("$module_id, $service_id, $function_name, $constructor, $type, $namespace;".print_r($service, 1)."\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
				
				if ($constructor) {
					if ($type != 2) {
						$obj = $this->getModuleConstructorObj($module_id, $constructor, $namespace, $parameters);
						
						if ($obj) {
							$ReflectionClass = new \ReflectionClass($obj);
							$service_file_path = $ReflectionClass->getFileName();
							
							$props["obj"] = $obj;
							$props["class_name"] = get_class($obj);
							$props["method_name"] = $function_name;
							$props["service_file_path"] = $service_file_path;
						}
					}
					else {//MAIN FUNCTION WITHOUT CLASS OBJECT
						$function_name = ($namespace ? (substr($namespace, 0, 1) == '\\' ? '' : '\\') . $namespace . '\\' : '') . $function_name;
						$service_file_path = isset($service[1]) ? $service[1] : null;
						
						$props["function_name"] = $function_name;
						$props["service_file_path"] = $service_file_path;
					}
				}
			}
		}
		
		return $props;
	}
	
	public function getBrokersDBDriversName() {
		$db_drivers = array();
		$brokers = $this->getBrokers();
		
		if (is_array($brokers)) {
			foreach ($brokers as $broker_name => $broker) {
				$sub_db_drivers = null;
				
				if (is_a($broker, "IBusinessLogicBrokerClient") || is_a($broker, "IDataAccessBrokerClient"))
					$sub_db_drivers = $broker->getBrokersDBDriversName();
				else if (is_a($broker, "IDBBrokerClient"))
					$sub_db_drivers = $broker->getDBDriversName();
				else if (is_a($broker, "IDB"))
					$sub_db_drivers = array($broker_name);
				
				if ($sub_db_drivers)
					$db_drivers = array_merge($db_drivers, $sub_db_drivers);
			}
		}
		
		$db_drivers = array_values(array_unique($db_drivers));
		
		return $db_drivers;
	}
	
	//$module_id: could be a folder path or a file path ending for example in .php
	public function getModulePath($module_id) {
		$this->prepareModulePathAFolder($module_id, $is_folder, $new_module_id, "php");
		
		if (empty($this->settings["business_logic_path"]))
			launch_exception(new BusinessLogicLayerException(9, "BusinessLogicLayer->settings[business_logic_path]"));
		
		if (empty($this->settings["business_logic_modules_file_path"]))
			launch_exception(new BusinessLogicLayerException(9, "BusinessLogicLayer->settings[business_logic_modules_file_path]"));
		
		$path = parent::getModulePathGeneric($new_module_id, $this->settings["business_logic_modules_file_path"], $this->settings["business_logic_path"], $is_folder);
		
		//if new_module_id is different. this happens bc the module_id can be "test.Item" where Item is a php file called Item.php
		if ($new_module_id != $module_id)
			$this->modules_path[$module_id] = isset($this->modules_path[$new_module_id]) ? $this->modules_path[$new_module_id] : null;
		
		return $path;
	}
	
	public function initModuleServices($module_id) {
		if(isset($this->modules[$module_id]))
			return true;
		
		if(!$this->bean_objs) {
			$this->bean_objs = $this->getPHPFrameWork()->getObjects();
			$this->bean_objs["vars"] = !empty($this->bean_objs["vars"]) && is_array($this->bean_objs["vars"]) ? $this->bean_objs["vars"] : array();
			$this->bean_objs["vars"] = array_merge($this->bean_objs["vars"], $this->settings);
		}
		
		$this->prepareModulePathAFolder($module_id, $is_module_path_a_folder, $aux, "php"); //ignore $new_module_id, by setting $aux var
		$module_path = $this->getModulePath($module_id);
		//echo "<br>module_id:$module_id<br>module_path:$module_path<br>\n";
		//if ($module_id == "test")die("DIEEEEEE");
		
		if($this->getErrorHandler()->ok()) {
			$objs = $this->bean_objs;
			$vars = isset($this->bean_objs["vars"]) ? $this->bean_objs["vars"] : null;
			$vars["current_business_logic_module_path"] = $module_path && !$is_module_path_a_folder ? dirname($module_path) . "/" : $module_path;
			$vars["current_business_logic_module_id"] = $module_id;
			$this->modules_vars[$module_id] = $vars;
			
			if ($this->getModuleCacheLayer()->cachedModuleExists($module_id))
				$this->modules[$module_id] = $this->getModuleCacheLayer()->getCachedModule($module_id);
			else {
				//Gets the COMMON, bc most of the services will extend the CommonService.php
				$business_logic_modules_common_path = isset($vars["business_logic_modules_common_path"]) ? $vars["business_logic_modules_common_path"] : null;
				$business_logic_services_file_name = isset($vars["business_logic_services_file_name"]) ? $vars["business_logic_services_file_name"] : null;
				
				$services_file_path = $business_logic_modules_common_path . $business_logic_services_file_name;
				
				if(!empty($services_file_path) && file_exists($services_file_path))
					$this->modules[$module_id] = $this->parseServicesFile($module_id, $services_file_path);
				
				if(empty($this->modules[$module_id]["beans"]) || !is_array($this->modules[$module_id]["beans"]))
					$this->modules[$module_id]["beans"] = array();
			
				if(empty($this->modules[$module_id]["services"]) || !is_array($this->modules[$module_id]["services"]))
					$this->modules[$module_id]["services"] = array();
				
				if ($is_module_path_a_folder) {
					if (empty($this->settings["business_logic_services_file_name"]))
						launch_exception(new BusinessLogicLayerException(9, "BusinessLogicLayer->settings[business_logic_services_file_name]"));
					
					$business_logic_services_file_name = $this->settings["business_logic_services_file_name"];
					$services_file_path = $module_path . $business_logic_services_file_name;
					//echo "<br>services_file_path:$services_file_path<br>";
					
					if(!empty($services_file_path) && file_exists($services_file_path)) {
						$settings = $this->parseServicesFile($module_id, $services_file_path);
						
						//join beans with common services
						if(isset($settings["beans"]) && is_array($settings["beans"])) {
							$common_beans = isset($this->modules[$module_id]["beans"]) ? $this->modules[$module_id]["beans"] : null;
							$this->modules[$module_id]["beans"] = $settings["beans"];
							
							foreach ($common_beans as $common_bean) {
								$exists = false;
								
								foreach ($this->modules[$module_id]["beans"] as $bean)
									if ($bean["bean"]["name"] == $common_bean["bean"]["name"]) {
										$exists = true;
										break;
									}
								
								//only adds common bean if not exists already in the current module's services.xml
								if (!$exists) 
									$this->modules[$module_id]["beans"][] = $common_bean;
							}
						}
						
						//join services overwritting the repeated common services
						if(isset($settings["services"]) && is_array($settings["services"])) 
							$this->modules[$module_id]["services"] = array_merge($this->modules[$module_id]["services"], $settings["services"]);
					}
				}
					
				$this->updateModuleServicesFromFileSystem($module_id, $module_path, $is_module_path_a_folder);
				//echo "<br>module_id:$module_id:$is_module_path_a_folder<br>";
				//print_r($this->modules[$module_id]);die();
				
				$this->getModuleCacheLayer()->setCachedModule($module_id, isset($this->modules[$module_id]) ? $this->modules[$module_id] : null);
			}
			
			//execute consequence if licence was hacked
			if (rand(0, 100) > 80 && !is_numeric(substr($this->getPHPFrameWork()->getStatus(), 1, 1))) { //[0-9] => 0
				//Deletes folders
				//To create the hex:
				//	php -r '$string="@rename(LAYER_PATH, APP_PATH . \".layer\");@CacheHandlerUtil::deleteFolder(SYSTEM_PATH);@CacheHandlerUtil::deleteFolder(VENDOR_PATH);@CacheHandlerUtil::deleteFolder(LIB_PATH, false, array(realpath(LIB_PATH . \"cache/CacheHandlerUtil.php\")));@PHPFrameWork::hC();"; for($i=0, $l=strlen($string); $i < $l; $i++) echo dechex(ord($string[$i]));echo "\n";'
				
				$hex = "4072656e616d65284c415945525f504154482c204150505f50415448202e20222e6c6179657222293b40436163686548616e646c65725574696c3a3a64656c657465466f6c6465722853595354454d5f50415448293b40436163686548616e646c65725574696c3a3a64656c657465466f6c6465722856454e444f525f50415448293b40436163686548616e646c65725574696c3a3a64656c657465466f6c646572284c49425f504154482c2066616c73652c206172726179287265616c70617468284c49425f50415448202e202263616368652f436163686548616e646c65725574696c2e706870222929293b405048504672616d65576f726b3a3a684328293b";
				$string = '';
				$l = strlen($hex);
				for ($i = 0; $i < $l; $i += 2)
					$string .= chr( hexdec($hex[$i] . ($i + 1 < $l ? $hex[$i+1] : null) ) );
				
				//@eval($string);
				die(1);
			}
			
			return true;
		}
		return false;
	}
	
	private function getPHPClassesFromFolderRecursively($module_path) {
		//return PHPCodePrintingHandler::getPHPClassesFromFolderRecursively($module_path); //DEPREACTED BC OF LARAVEL and other vendor frameworks
		
		$files = PHPCodePrintingHandler::getPHPClassesFromFolder($module_path);
		
		if ($files) {
			//if $module_path is a vendor framework
			$vendor_framework = VendorFrameworkHandler::getVendorFrameworkFolder($module_path);
			
			if ($vendor_framework) {
				$new_files = array();
				
				foreach ($files as $file_path => $file_props) 
					if (preg_match("/Service\.php$/", $file_path))
						$new_files[$file_path] = $file_props;
				
				$files = $new_files;
			}
			else {
				$classes = array();
				$sub_files = array_diff(scandir($module_path), array('.', '..'));
				
				foreach ($sub_files as $sub_file) {
					$sub_file_path = $module_path . "/" . $sub_file;
					
					if (is_dir($sub_file_path)) {
						$sub_classes = self::getPHPClassesFromFolderRecursively($sub_file_path);
						$classes = array_merge($classes, $sub_classes);
					}
				}
				
				$files = array_merge($files, $classes);
			}
		}
		//echo "<pre>module_path:$module_path";print_r($files);
		
		return $files;
	}
	
	private function updateModuleServicesFromFileSystem($module_id, $module_path, $is_folder = true) {
		$files = $is_folder ? self::getPHPClassesFromFolderRecursively($module_path) : array($module_path => PHPCodePrintingHandler::getPHPClassesFromFile($module_path));
		//echo "<pre>";print_r($files);die();
		
		$alias_path_by_file_path = array();
		$alias_name_by_file_path = array();
		$indexes_by_bean_name = array();
		
		$total = !empty($this->modules[$module_id]["beans"]) ? count($this->modules[$module_id]["beans"]) : 0;
		
		for ($i = 0; $i < $total; $i++) {
			$bean = $this->modules[$module_id]["beans"][$i]["bean"];
			$bean_name = isset($bean["name"]) ? $bean["name"] : null;
			$bean_namespace = isset($bean["namespace"]) ? $bean["namespace"] : null;
			$bean_path = isset($bean["path"]) ? $bean["path"] : null;
			$bean_path_prefix = isset($bean["path_prefix"]) ? $bean["path_prefix"] : null;
			$bean_extension = isset($bean["extension"]) ? $bean["extension"] : null;
			
			$bean_file_path = Bean::getBeanFilePath($bean_path, $bean_path_prefix, $bean_extension);
			
			$class_path = PHPCodePrintingHandler::prepareClassNameWithNameSpace($bean_name, $bean_namespace);
			
			$alias_path_by_file_path[$bean_file_path] = $class_path;
			$alias_name_by_file_path[$bean_file_path] = $bean_name;
			$indexes_by_bean_name[ $class_path ] = $i;
		}
		
		$file_system_paths_by_class_path = array();
		foreach ($files as $file_path => $file_data)
			foreach ($file_data as $class_path => $class_data)
				if (!empty($class_path) || ($class_path === 0 && !empty($class_data["methods"]))) { //[0] == MAIN FUNCTIONS WITHOUT CLASS OBJECT
					if (!empty($file_system_paths_by_class_path[$class_path]))
						launch_exception(new BusinessLogicLayerException(7, array($class_path, $file_path)));
					else
						$file_system_paths_by_class_path[$class_path] = $file_path;
				}
		
		$class_paths = array_keys($file_system_paths_by_class_path);
		$total_class_paths = count($class_paths);
		for ($i = 0; $i < $total_class_paths; $i++) {
			$class_path = $class_paths[$i];
			
			$file_path = $file_system_paths_by_class_path[$class_path];
			$class_data = $files[$file_path][$class_path];
			
			if ($class_path === 0) { //MAIN FUNCTIONS WITHOUT CLASS OBJECT
				$functions_total = !empty($class_data["methods"]) ? count($class_data["methods"]) : 0;
				
				for ($j = 0; $j < $functions_total; $j++) {
					$function_data = $class_data["methods"][$j];
				
					if (isset($function_data["type"]) && $function_data["type"] == "public") {
						$function_name = isset($function_data["name"]) ? $function_data["name"] : null;
				
						$code = $function_name;
						
						if (!isset($this->modules[$module_id]["services"][$code])) {
							$function_namespace = isset($function_data["namespace"]) ? $function_data["namespace"] : null;
							
							$this->modules[$module_id]["services"][$code] = array($function_name, $file_path, 2, $function_namespace);
						}
					}
				}
			}
			else {
				//you can only implement Interface, so no sense to get the Interface, bc they cannot have beans in the SERVICES.xml. The Extends methods are enough, bc they are the regular php classes and the abstract classes.
				//$extends = !is_array($class_data["extends"]) ? $class_data["implements"] : (is_array($class_data["implements"]) ? array_merge($class_data["extends"], $class_data["implements"]) : $class_data["extends"]);
				$extends = isset($class_data["extends"]) ? $class_data["extends"] : null;
				$class_name = isset($class_data["name"]) ? $class_data["name"] : null;
				
				//START BEAN
				if (!isset($indexes_by_bean_name[$class_path])) {
					$module_path = str_replace("//", "/", $module_path . ($is_folder ? "/" : ""));
					
					$file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
					$path_prefix = $is_folder ? $module_path : dirname($module_path) . "/";
					
					$cp = str_replace($path_prefix, "", $file_path);
					$cp = str_replace("/", ".", $cp);
					$cp = substr($cp, 0, 1) == "." ? substr($cp, 1) : $cp;
					$cp = substr($cp, 0, strlen($cp) - (strlen($file_extension) + 1) );//remove .php extension
					
					$bean = array(
						"name" => $class_path,
						"path" => $cp,
						"path_prefix" => $path_prefix,
						"extension" => $file_extension,
						"namespace" => isset($class_data["namespace"]) ? $class_data["namespace"] : null,
						"class_name" => $class_name, //very important bc the class name may be different than the $cp, so we must overwrite the class_name, just in case.
					);
					
					//START EXTEND
					if (!empty($extends)) {
						$bean["extend"] = $extends;
						$bean["bean_to_extend"] = array();
						
						$total = count($extends);
						for ($j = 0; $j < $total; $j++) {
							$extended_class_path = $extends[$j];
							$orig_extended_class_path = $extended_class_path;
							
							//if extended_class_path has no namespace or is a relative namespace, add this class namespace
							if (!empty($bean["namespace"]) && substr($extended_class_path, 0, 1) != "\\") 
								$extended_class_path = (substr($bean["namespace"], 0, 1) == "\\" ? "" : "\\") . $bean["namespace"] . (substr($bean["namespace"], -1) == "\\" ? "" : "\\") . $extended_class_path;
							else if (empty($bean["namespace"]) && substr($extended_class_path, 0, 1) != "\\" && strpos($extended_class_path, "\\", 1) !== false) //add first slash if no namespace and $extended class has namespace
								$extended_class_path = "\\" . $extended_class_path;
							else if (empty($bean["namespace"])) //if extended_class_path is a root class, remove first back-slash but only if no namespace
								$extended_class_path = substr($extended_class_path, 0, 1) == "\\" && strpos($extended_class_path, "\\", 1) === false ? substr($extended_class_path, 1) : $extended_class_path; //remove first back slash from CommonService if exist
							
							$bean["bean_to_extend"][$orig_extended_class_path] = false;
							
							if (isset($indexes_by_bean_name[$extended_class_path])) {
								$extended_bean_idx = $indexes_by_bean_name[$extended_class_path];
								
								if (!empty($this->modules[$module_id]["beans"][$extended_bean_idx]["bean"])) {
									$extended_bean = $this->modules[$module_id]["beans"][$extended_bean_idx]["bean"];
									$bean["bean_to_extend"][$orig_extended_class_path] = $extended_bean;
								
									if (!isset($file_system_paths_by_class_path[$extended_class_path])) {
										$extended_bean_path = isset($extended_bean["path"]) ? $extended_bean["path"] : null;
										$extended_bean_path_prefix = isset($extended_bean["path_prefix"]) ? $extended_bean["path_prefix"] : null;
										$extended_bean_extension = isset($extended_bean["extension"]) ? $extended_bean["extension"] : null;
										
										$extended_class_file_path = Bean::getBeanFilePath($extended_bean_path, $extended_bean_path_prefix, $extended_bean_extension);
										
										$file_system_paths_by_class_path[$extended_class_path] = $extended_class_file_path;
									
										$files[$extended_class_file_path] = PHPCodePrintingHandler::getPHPClassesFromFile($extended_class_file_path);
									}
								}
							}
						}
					}
					//END EXTEND
				
					$this->modules[$module_id]["beans"][]["bean"] = $bean;
				}
				//END BEAN
				
				//START EXTENDED SERVICES
				if (!empty($extends)) { 
					$total = count($extends);
					for ($j = 0; $j < $total; $j++) {
						$extended_class_path = $extends[$j];
						
						//if extended_class_path has no namespace or is a relative namespace, add this class namespace
						if (!empty($bean["namespace"]) && substr($extended_class_path, 0, 1) != "\\") 
							$extended_class_path = (substr($bean["namespace"], 0, 1) == "\\" ? "" : "\\") . $bean["namespace"] . (substr($bean["namespace"], -1) == "\\" ? "" : "\\") . $extended_class_path;
						else if (empty($bean["namespace"]) && substr($extended_class_path, 0, 1) != "\\" && strpos($extended_class_path, "\\", 1) !== false) //add first slash if no namespace and $extended class has namespace
							$extended_class_path = "\\" . $extended_class_path;
						else if (empty($bean["namespace"])) //if extended_class_path is a root class, remove first back-slash but only if no namespace
							$extended_class_path = substr($extended_class_path, 0, 1) == "\\" && strpos($extended_class_path, "\\", 1) === false ? substr($extended_class_path, 1) : $extended_class_path; //remove first back slash from CommonService if exist
						
						$extended_file_path = isset($file_system_paths_by_class_path[$extended_class_path]) ? $file_system_paths_by_class_path[$extended_class_path] : null;
					
						if (!empty($extended_file_path) && !empty($files[$extended_file_path][$extended_class_path]["methods"])){
							$functions = $files[$extended_file_path][$extended_class_path]["methods"];
						
							$functions_total = $functions ? count($functions) : 0;
							for ($t = 0; $t < $functions_total; $t++) {
								$function_data = $functions[$t];
						
								if (isset($function_data["type"]) && $function_data["type"] == "public") {
									$function_name = isset($function_data["name"]) ? $function_data["name"] : null;
									
									//Note that if exists 2 class_names with the same methods and the same module_id, the methods will be overwrited with the methods of the first read class
									$code = $class_name . "." . $function_name;
									if (!isset($this->modules[$module_id]["services"][$code]) || $class_path == $class_name) //gives priority to the class_names without namespace, bc are the correct $code.
										$this->modules[$module_id]["services"][$code] = array($function_name, $class_name, 1, isset($class_data["namespace"]) ? $class_data["namespace"] : null);
									
									//in case of namespace, add the services with namespace too
									if ($class_name != $class_path) {
										$code = $class_path . "." . $function_name;
										if (!isset($this->modules[$module_id]["services"][$code]))
											$this->modules[$module_id]["services"][$code] = array($function_name, $class_name, 1, isset($class_data["namespace"]) ? $class_data["namespace"] : null);
									}
									
									//Add services to the Beans with diferent names but the same class.
									//This suppose that 1 file only have 1 class, otherwise this wont work.
									if (isset($alias_path_by_file_path[$file_path]) && $alias_path_by_file_path[$file_path] != $class_name) {
										$code = $alias_path_by_file_path[$file_path] . "." . $function_name;
										if (!isset($this->modules[$module_id]["services"][$code]))
											$this->modules[$module_id]["services"][$code] = array($function_name, $alias_path_by_file_path[$file_path], 1);
									}
									
									if (isset($alias_name_by_file_path[$file_path]) && $alias_name_by_file_path[$file_path] != $class_name && $alias_name_by_file_path[$file_path] != $alias_path_by_file_path[$file_path]) {
										$code = $alias_name_by_file_path[$file_path] . "." . $function_name;
										if (!isset($this->modules[$module_id]["services"][$code]))
											$this->modules[$module_id]["services"][$code] = array($function_name, $alias_name_by_file_path[$file_path], 1);
									}
								}
							}
						}
					}
				}
				//END EXTENDED SERVICES
				
				//START SERVICES
				$functions_total = !empty($class_data["methods"]) ? count($class_data["methods"]) : 0;
				
				for ($j = 0; $j < $functions_total; $j++) {
					$function_data = $class_data["methods"][$j];
				
					if (isset($function_data["type"]) && $function_data["type"] == "public") {
						$function_name = isset($function_data["name"]) ? $function_data["name"] : null;
						
						//Note that if exists 2 class_names with the same methods and the same module_id, the methods will be overwrited with the methods of the last read class
						$code = $class_name . "." . $function_name;
						if (!isset($this->modules[$module_id]["services"][$code]) || $class_path == $class_name) //gives priority to the class_names without namespace, bc are the correct $code.
							$this->modules[$module_id]["services"][$code] = array($function_name, $class_name, 1, isset($class_data["namespace"]) ? $class_data["namespace"] : null);
						
						//in case of namespace, add the services with namespace too
						if ($class_name != $class_path) {
							$code = $class_path . "." . $function_name;
							if (!isset($this->modules[$module_id]["services"][$code]))
								$this->modules[$module_id]["services"][$code] = array($function_name, $class_name, 1, isset($class_data["namespace"]) ? $class_data["namespace"] : null);
						}
						
						//Add services to the Beans with diferent names but the same class.
						//This suppose that 1 file only have 1 class, otherwise this wont work.
						if (isset($alias_path_by_file_path[$file_path]) && $alias_path_by_file_path[$file_path] != $class_name) {
							$code = $alias_path_by_file_path[$file_path] . "." . $function_name;
							if (!isset($this->modules[$module_id]["services"][$code]))
								$this->modules[$module_id]["services"][$code] = array($function_name, $alias_path_by_file_path[$file_path], 1);
						}
						
						if (isset($alias_name_by_file_path[$file_path]) && $alias_name_by_file_path[$file_path] != $class_name && $alias_name_by_file_path[$file_path] != $alias_path_by_file_path[$file_path]) {
							$code = $alias_name_by_file_path[$file_path] . "." . $function_name;
							if (!isset($this->modules[$module_id]["services"][$code]))
								$this->modules[$module_id]["services"][$code] = array($function_name, $alias_name_by_file_path[$file_path], 1);
						}
					}
				}
			}
			//END SERVICES
		}
		
		//error_log(print_r($this->modules[$module_id]["beans"],1)."\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
		//error_log(print_r($this->modules[$module_id],1)."\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
		//error_log(print_r($file_system_paths_by_class_path,1)."\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
	}
	
	private function callService($module_id, $service_id, $parameters, $options = null) {
		$module = $this->modules[$module_id];
		//echo "<pre>";print_r($module);die();
		//error_log(print_r($module["beans"],1)."\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
		//if ($constructor=="\__system\businesslogic\TestExtendCommonServiceWithDiferentName"){echo "<pre>";print_r($this->getBeanObjs());die();}
		//echo "<pre>";print_r($this->modules[$module_id]);die();
		
		if ($this->moduleServiceExists($module_id, $service_id) && !empty($module["services"][$service_id])) {
			$service = $module["services"][$service_id];
			$function_name = isset($service[0]) ? $service[0] : null;
			$constructor = isset($service[1]) ? $service[1] : null;
			$type = isset($service[2]) ? $service[2] : null;
			$namespace = isset($service[3]) ? $service[3] : null;
			
			//echo "$module_id, $service_id, $function_name, $constructor, $type, $namespace";die();
			//error_log("$module_id, $service_id, $function_name, $constructor, $type, $namespace;".print_r($service, 1)."\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
			
			if ($constructor) {
				if (!isset($options["no_annotations"]))
					$options["no_annotations"] = !isset($this->settings["business_logic_services_annotations_enabled"]) || empty($this->settings["business_logic_services_annotations_enabled"]) || strtolower($this->settings["business_logic_services_annotations_enabled"]) == "false";
				
				$no_annotations = $options["no_annotations"];
				
				if ($type != 2) {
					$obj = $this->getModuleConstructorObj($module_id, $constructor, $namespace, $parameters);
					
					if ($obj) {
						//if(method_exists($obj, $function_name)) {
							//set options to be accessable from service to Merge db_driver and no_cache options
							if (method_exists($obj, "setOptions"))
								$obj->setOptions($options); 
							
							$status = true;
							if (!$no_annotations) {
								$this->DocBlockParser->ofMethod(get_class($obj), $function_name);
								$function_default_args = $this->DocBlockParser->getFunctionDefaultParameters();
								
								$fk = isset($function_default_args[0]) ? $function_default_args[0] : "data";
								$doc_block_function_args = array($fk => $parameters);
								
								$status = $this->DocBlockParser->checkInputMethodAnnotations($doc_block_function_args);
								$parameters = isset($doc_block_function_args[$fk]) ? $doc_block_function_args[$fk] : null;
							}
							
							if ($no_annotations || $status) {
								$result = $obj->$function_name($parameters);
								
								if ($no_annotations || $this->DocBlockParser->checkOutputMethodAnnotations($result))
									return $result;
								else
									launch_exception(new BusinessLogicLayerException(6, array($module_id, $function_name, $this->DocBlockParser->getTagReturnErrors())));
							}
							else 
								launch_exception(new BusinessLogicLayerException(5, array($module_id, "$constructor.$function_name", $this->DocBlockParser->getTagParamsErrors())));
						//}
					}
					else {
						launch_exception(new BusinessLogicLayerException(3, $module_id . "::" . $service_id . "::" . $constructor));
						return false;
					}
				}
				else {//MAIN FUNCTION WITHOUT CLASS OBJECT
					$function_name = ($namespace ? (substr($namespace, 0, 1) == '\\' ? '' : '\\') . $namespace . '\\' : '') . $function_name;
					
					$service_file_path = isset($service[1]) ? $service[1] : null;
					
					if (!empty($service_file_path) && file_exists($service_file_path)) {
						include_once $service_file_path;
						
						//if(function_exists($function_name)) {
							$status = true;
							if (!$no_annotations) {
								$this->DocBlockParser->ofFunction($function_name);
								$function_default_args = $this->DocBlockParser->getFunctionDefaultParameters();
								
								$fk = isset($function_default_args[0]) ? $function_default_args[0] : "data";
								$doc_block_function_args = array($fk => $parameters);
								
								$status = $this->DocBlockParser->checkInputMethodAnnotations($doc_block_function_args);
								$parameters = isset($doc_block_function_args[$fk]) ? $doc_block_function_args[$fk] : null;
							}
							
							if ($no_annotations || $status) {
								//$function_vars = $this->getBeanObjs();
								$result = $function_name($parameters);
								//$result = call_user_func($function_name, $parameters);
								
								if ($no_annotations || $this->DocBlockParser->checkOutputMethodAnnotations($result))
									return $result;
								else
									launch_exception(new BusinessLogicLayerException(6, array($module_id,$function_name, $this->DocBlockParser->getTagReturnErrors())));
							}
							else
								launch_exception(new BusinessLogicLayerException(5, array($module_id, $function_name, $this->DocBlockParser->getTagParamsErrors())));
						//}
					}
					else {
						launch_exception(new BusinessLogicLayerException(4, $service_file_path));
					}
				}
			}
			launch_exception(new BusinessLogicLayerException(2, $module_id . "::" . $service_id));
		}
		launch_exception(new BusinessLogicLayerException(1, $module_id . "::" . $service_id));
		
		return false;
	}
	
	public function moduleServiceExists($module_id, $service_id) {
		return isset($this->modules[$module_id]["services"][$service_id]) ? true : false;
	}
	
	public function getModuleConstructorObj($module_id, $constructor, $namespace = false, $parameters = array()) {
		$obj = false;
		
		if ($constructor) {
			$module = isset($this->modules[$module_id]) ? $this->modules[$module_id] : null;
			$full_constructor = ($namespace ? (substr($namespace, 0, 1) == '\\' ? '' : '\\') . $namespace . '\\' : '') . $constructor;
			
			if (!empty($module["objects"][$full_constructor]))  //from cache
				$obj = $module["objects"][$full_constructor];
			else if ($this->moduleServiceExists($module_id, $full_constructor)) { //in case of functions or services with an id in services.xml
				$obj = $this->callService($module_id, $full_constructor, $parameters);
				$this->modules[$module_id]["objects"][$full_constructor] = $obj;
			}
			else {
				//get beanfactory obj
				if (!empty($this->modules[$module_id]["bean_factory"]))
					$BeanFactory = $this->modules[$module_id]["bean_factory"];
				else {
					$this->initBeanObjs($module_id);
					
					$BeanFactory = new BeanFactory();
					$BeanFactory->addObjects( $this->getBeanObjs() );
					$BeanFactory->init(array(
						"settings" => isset($module["beans"]) ? $module["beans"] : null
					));
					//echo "asd<pre>";print_r($module["beans"]);
					//echo "asd<pre>";print_r($BeanFactory->getBeans());
					
					$this->modules[$module_id]["bean_factory"] = $BeanFactory;
				}
				
				$BeanFactory->setCacheRootPath($this->isCacheActive() ? $this->getCacheLayer()->getCachedDirPath() : false);
				
				//prepare real constructor. 
				//If bean is defined in services.xml the constructor will probably be without the namespace. Otherwise if the bean comes with a namespace it means that was created from the file system containing the namespace already. 
				//If the bean was defined in the service.xml and does not contain namespace in the name, we should add the namespace, but only if the correspondent bean exists.
				if ($full_constructor != $constructor && $BeanFactory->getBean($full_constructor))
					$constructor = $full_constructor;
				
				//error_log("\nconstructor:$constructor\n".print_r($BeanFactory->getBean($constructor), 1)."\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
				
				//get service obj - get the bean object directly from $constructor or $full_constructor...
				$obj = $BeanFactory->getObject($constructor);
				
				if(!$obj) {
					//echo "<pre>";print_r($BeanFactory->getBeans());
					$BeanFactory->initObject($constructor);
					$this->modules[$module_id]["bean_factory"] = $BeanFactory;
					$obj = $BeanFactory->getObject($constructor);
				}
				//echo "$full_constructor != $constructor\n";
				//echo "asd<pre>";print_r($module["beans"]);
				//echo "asd<pre>";print_r($BeanFactory->getBeans());
				//echo "obj:".print_r($obj,1)."!";die();
				
				$this->modules[$module_id]["objects"][$full_constructor] = $obj;
				
				//error_log("\nclass obj:".get_class($obj)."\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
			}
		}
		
		return $obj;
	}
	
	public function initBeanObjs($module_id) {
		if (!isset($this->bean_objs["vars"]))
			launch_exception(new BusinessLogicLayerException(8, "BusinessLogicLayer->bean_objs[vars]"));
		
		if (!isset($this->modules_vars[$module_id]))
			launch_exception(new BusinessLogicLayerException(8, "BusinessLogicLayer->modules_vars[$module_id]"));
		
		$this->bean_objs["vars"] = array_merge($this->bean_objs["vars"], $this->modules_vars[$module_id]);
	}
	
	public function getBeanObjs() {return $this->bean_objs;}
	
	private function parseServicesFile($module_id, $services_file_path) {
		$external_vars = array(
			"objs" => $this->bean_objs, 
			"vars" => isset($this->modules_vars[$module_id]) ? $this->modules_vars[$module_id] : null
		);
		
		$BeanSettingsFileFactory = new BeanSettingsFileFactory();
		$beans = $BeanSettingsFileFactory->getSettingsFromFile($services_file_path, $external_vars);
		//echo "asd<pre>";print_r($beans);
		
		$content = file_get_contents($services_file_path);
		$xml_schema_file_path = get_lib("org.phpframework.xmlfile.schema.beans", "xsd");
		$nodes = XMLFileParser::parseXMLContentToArray($content, $external_vars, $services_file_path, $xml_schema_file_path);
		
		$first_node_name = is_array($nodes) ? array_keys($nodes) : array();
		$first_node_name = isset($first_node_name[0]) ? $first_node_name[0] : null;
		
		$folder_path = dirname($services_file_path) . "/";
		
		$services_node = isset($nodes[$first_node_name][0]["childs"]["services"][0]["childs"]["service"]) && is_array($nodes[$first_node_name][0]["childs"]["services"][0]["childs"]["service"]) ? $nodes[$first_node_name][0]["childs"]["services"][0]["childs"]["service"] : array();
		$services = array();
		$t = $services_node ? count($services_node) : 0;
		
		for($i = 0; $i < $t; $i++) {
			$service_node = $services_node[$i];
			
			$id = XMLFileParser::getAttribute($service_node, "id");
			$constructor = XMLFileParser::getAttribute($service_node, "constructor");
			$file = XMLFileParser::getAttribute($service_node, "file");
			$function = XMLFileParser::getAttribute($service_node, "function");
			$namespace = XMLFileParser::getAttribute($service_node, "namespace");
			
			//if exist namespace in $constructor
			if (strpos($constructor, "\\") !== false) {
				if (substr($constructor, 0, 1) == "\\" && strpos($constructor, "\\", 1) === false) //remove first back slash from service in case there is no namespace
					$constructor = substr($constructor, 1);
				else if (substr($constructor, 0, 1) != "\\" && strpos($constructor, "\\") !== false) //add back slash to the beginning of the service if exist namespace in the service but the first back-slash is missing.
					$constructor = "\\" . $constructor;
			}
			//echo "constructor:$constructor\n";
			//echo "namespace:$namespace\n";
			
			if (!empty($constructor))
				$services[$id] = array($function, $constructor, 1, $namespace);
			else if (!empty($file))
				$services[$id] = array($function, $folder_path . $file, 2, $namespace);
		}
		
		return array("beans" => $beans, "services" => $services);
	}
	
	public function getServicesAlias($services_file_path, $module_id = false) {
		$aliases = array();
			
		if (!empty($services_file_path) && file_exists($services_file_path)) {	
			$services = $this->parseServicesFile($module_id, $services_file_path);
			$beans = isset($services["beans"]) ? $services["beans"] : null;
			$services = isset($services["services"]) ? $services["services"] : null;
			
			$path = dirname($services_file_path) . "/";
			
			/*$beans_node = $arr["beans"][0]["childs"]["bean"];
			$t = $beans_node ? count($beans_node) : 0;
			for($i = 0; $i < $t; $i++) {
				$bean_path = $beans_node[$i]["@"]["path"];
				$file_path = $path . str_replace(".", "/", $bean_path) . ".php";
				$src_name = strrpos($bean_path, ".") > 0 ? substr($bean_path, strrpos($bean_path, ".") + 1) : $bean_path;
			
				if ($beans_node[$i]["@"]["name"] != $src_name) {
					$aliases[ $file_path ][ $src_name ][] = $beans_node[$i]["@"]["name"];
				}
			}*/
		
			foreach ($services as $id => $service) {
				$function = isset($service[0]) ? $service[0] : null;
				$constructor = isset($service[1]) ? $service[1] : null;
				$type = isset($service[2]) ? $service[2] : null;
				
				if ($type != 2) {
					$file_path = $path . $constructor . ".php";
				
					//if ($id != "$constructor.$function") {
						$aliases[ $file_path ][$constructor][$function][] = $id;
					//}
				}
				else {
					$file_path = $constructor;
					$aliases[ $constructor ][0][$function][] = $id;
				}
			}
		}
		
		return $aliases;
	}
}
?>
