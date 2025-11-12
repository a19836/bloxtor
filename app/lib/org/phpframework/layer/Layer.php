<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.module.ModuleCacheLayer");
include_once get_lib("org.phpframework.module.ModulePathHandler");
include_once get_lib("org.phpframework.PHPFrameWorkHandler");
include_once get_lib("org.phpframework.layer.ILayer");
include_once get_lib("org.phpframework.bean.BeanSettingsFileFactory");
include get_lib("org.phpframework.layer.exception.LayerException");

abstract class Layer implements ILayer {
	private $CacheLayer;
	private $ModuleCacheLayer;
	private $PHPFrameWorkHandler;
	
	private $is_default_layer;
	private $brokers;
	private $default_broker_name;
	
	public $settings;
	public $modules_path = array();
	
	public function __construct($settings) {
		$this->settings = $settings;
		
		$this->ModuleCacheLayer = new ModuleCacheLayer($this);
		$this->PHPFrameWorkHandler = new PHPFrameWorkHandler();
		
		$this->is_default_layer = false;
		$this->brokers = array();
		$this->default_broker_name = false;
	}
	
	abstract public function getLayerPathSetting();
	
	public function setIsDefaultLayer($is_default_layer) {$this->is_default_layer = !empty($is_default_layer);}
	public function isDefaultLayer() {return $this->is_default_layer;}
	
	public function setCacheLayer($CacheLayer) {$this->CacheLayer = $CacheLayer;}
	public function getCacheLayer() {return $this->CacheLayer;}
	public function isCacheActive() {return $this->CacheLayer ? true : false;}
	public function getModuleCachedLayerDirPath() { return $this->isCacheActive() ? $this->getCacheLayer()->getCachedDirPath() : false; }
	
	public function getModuleCacheLayer() {return $this->ModuleCacheLayer;}
	
	public function getErrorHandler() {
		global $GlobalErrorHandler;

		return $GlobalErrorHandler;
	}
	
	public function setPHPFrameWork($PHPFrameWork) {return $this->PHPFrameWorkHandler->setPHPFrameWork($PHPFrameWork);}
	public function getPHPFrameWork() {return $this->PHPFrameWorkHandler->getPHPFrameWork();}
	
	public function setPHPFrameWorkObjName($phpframework_obj_name) {$this->PHPFrameWorkHandler->setPHPFrameWorkObjName($phpframework_obj_name);}
	public function getPHPFrameWorkObjName() {return $this->PHPFrameWorkHandler->getPHPFrameWorkObjName();}
	
	public function getModulePathGeneric($module_id, $modules_file_path, $layer_path, $is_folder = true) {
		if ($is_folder) {
			$path = ModulePathHandler::getModuleFolderPath($module_id, $modules_file_path, $layer_path, $this->modules_path, $this->settings, $this->getModuleCacheLayer());
			
			if (substr($path, -1) != "/")
				$path .= "/";
			
			return $path;
		}
		
		return ModulePathHandler::getModuleFilePath($module_id, $modules_file_path, $layer_path, $this->modules_path, $this->settings, $this->getModuleCacheLayer());
	}
	
	/*
		Module_id can be a folder or a xml/php file with extension or without extension, like:
		- Ibatis:
			$this->getBroker()->callQuerySQL("TEST", "insert", "insert_item_not_registered"); //modules.xml has an alias TEST => test
			$this->getBroker()->callQuery("test", "insert", "insert_item_not_registered"); //test is a folder
			$this->getBroker()->callQuery("test.item.xml", "insert", "insert_item_not_registered");
			$this->getBroker()->callQuery("test/item.xml", "insert", "insert_item_not_registered");
			$this->getBroker()->callQuery("test.item", "insert", "insert_item_not_registered"); //default_extension will be added. item is a xml file.
			$this->getBroker()->callQuery("test/item", "insert", "insert_item_not_registered"); //default_extension will be added. item is a xml file.
		
		- Hibernate:
			$this->getBroker()->callObject("TEST", "ItemObjNotRegistered")); //modules.xml has an alias TEST => test
			$this->getBroker()->callObject("test", "ItemObjNotRegistered"));
			$this->getBroker()->callObject("test/item_subitem.xml", "ItemObjNotRegistered"));
			$this->getBroker()->callObject("test.item_subitem.xml", "ItemObjNotRegistered"));
			$this->getBroker()->callObject("test/item_subitem", "ItemObjNotRegistered")); //default_extension will be added. item is a xml file.
			$this->getBroker()->callObject("test.item_subitem", "ItemObjNotRegistered")); //default_extension will be added. item is a xml file.
			
		- Business logic:
			$EVC->getBroker()->callBusinessLogic("TEST", "get_obj", array(...)); //modules.xml has an alias TEST => test
			$EVC->getBroker()->callBusinessLogic("test/subtest", "foo", "value xxx"); //test/subtest is a folder
			$EVC->getBroker()->callBusinessLogic("test.subtest.IndependentFunctionsServices.php", "foo", "value xxx");
			$EVC->getBroker()->callBusinessLogic("test/subtest/IndependentFunctionsServices.php", "foo", "value xxx");
			$EVC->getBroker()->callBusinessLogic("test/subtest/IndependentFunctionsServices", "foo", "value xxx"); //default_extension will be added. IndependentFunctionsServices is a php file.
			$EVC->getBroker()->callBusinessLogic("test.subtest.IndependentFunctionsServices", "foo", "value xxx"); //default_extension will be added. IndependentFunctionsServices is a php file.
			$xxx->callBusinessLogic("test.subtest.SubTestService.php", "SubTestService.executeBusinessLogicSubTest", null, array("no_cache" => false, "no_annotations" => true));
			$xxx->callBusinessLogic("test/subtest", "SubTestService.executeBusinessLogicSubTest", null, array("no_cache" => false, "no_annotations" => true)); //test/subtest is a folder
	*/
	public function prepareModulePathAFolder($module_id, &$is_folder, &$new_module_id, $default_extension = null) {
		$new_module_id = $module_id;
		
		if ($module_id) {
			$m_id = str_replace(".", "/", $module_id);
			$layer_path = $this->getLayerPathSetting();
			
			//check if is folder
			if (is_dir($layer_path . $module_id) || is_dir($layer_path . $m_id)) {
				$is_folder = true;
				return;
			}
			
			//if extension
			$extension = pathinfo($module_id, PATHINFO_EXTENSION);
			
			if ($extension) {
				//if is file
				if (is_file($layer_path . $module_id)) {
					$is_folder = false;
					return;
				}
				
				//replace "." in module id with "/" and check if is file
				$l = strlen($extension) + 1;
				$m_id_aux = substr($m_id, 0, - $l) . "." . $extension;
				
				if (is_file($layer_path . $m_id_aux)) {
					$is_folder = false;
					return;
				}
				//replace "." in module id with "/", add default extension and check if file
				else if ($default_extension) {
					if (is_file($layer_path . $m_id . "." . $default_extension)){
						$new_module_id .= "." . $default_extension;
						$is_folder = false;
						return;
					}
				}
			}
			//if no extension but there is a default extension to be added
			else if ($default_extension) {
				if (is_file($layer_path . $module_id . "." . $default_extension) || is_file($layer_path . $m_id . "." . $default_extension)){
					$new_module_id .= "." . $default_extension;
					$is_folder = false;
					return;
				}
			}
		}
		
		$is_folder = true;
	}
	
	/********* MODULES ALIASES ********/
	public static function getModulesAlias($modules_file_path) {
		$aliases = array();
		
		if (!empty($modules_file_path) && file_exists($modules_file_path)) {
			$arr = XMLFileParser::parseXMLFileToArray($modules_file_path);
			
			$t = !empty($arr["modules"][0]["childs"]["module"]) ? count($arr["modules"][0]["childs"]["module"]) : 0;
			for ($i = 0; $i < $t; $i++) {
				$module = $arr["modules"][0]["childs"]["module"][$i];
				$alias_id = isset($module["@"]["id"]) ? $module["@"]["id"] : null;
				$alias_path = isset($module["value"]) ? $module["value"] : null;
				
				$alias_path = substr($alias_path, 0, 1) == "/" ? substr($alias_path, 1) : $alias_path;
				$alias_path = substr($alias_path, strlen($alias_path) - 1) == "/" ? substr($alias_path, 0, strlen($alias_path) - 1) : $alias_path;
		
				if ($alias_path != $alias_id) 
					$aliases[ $alias_path ][] = $alias_id;
			}
		}
		
		return $aliases;
	}
	
	/********* BROKER ********/
	public function addBroker($broker, $broker_name = false) {
		if(empty($broker_name) && $broker_name !== 0)
			$this->brokers[] = $broker;
          else
			$this->brokers[$broker_name] = $broker;
	}
	
	//If $broker_name is empty, the $broker_name = $this->default_broker_name.
	//If $this->default_broker_name is empty too, get the first elm from $this->brokers.
	//If $broker_name is numeric, get the correspondent key, based in the array_keys index.
	//If $broker_name is numeric, gets $this->brokers[ $broker_name -1 ], which means the $broker_name should be +1.
	public function getBroker($broker_name = false, $return_false = false) {
		$broker_name = !empty($broker_name) || $broker_name === 0 ? $broker_name : $this->default_broker_name;
		
		if (empty($broker_name) && $broker_name !== 0) {
			$keys = array_keys($this->brokers);
			$broker_name = isset($keys[0]) ? $keys[0] : null;
		}
		else if (is_numeric($broker_name) && !isset($this->brokers[$broker_name])) {
			$keys = array_keys($this->brokers);
			$broker_name = isset($keys[$broker_name - 1]) ? $keys[$broker_name - 1] : null;
		}
		//error_log("this class:".get_class($this)."\nbroker class:".get_class($this->brokers[$broker_name])."\nbroker_name:$broker_name\ndefault_broker_name:$this->default_broker_name\nGLOBALS:".$GLOBALS["default_db_broker"]."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		if (isset($this->brokers[$broker_name]))
			return $this->brokers[$broker_name];
		
		if (!$return_false) 
			launch_exception(new LayerException(1, $broker_name));
		
		return false;
	}
	
	public function getBrokers() {
		return $this->brokers;
	}
	
	//only set default_broker_name if exists, otherwise ignore it. $return_false must be true otherwise if we have multiple DBData Layers with different DBDrivers and the $default_broker_name is == to $GLOBALS[default_db_driver], it will give an exception. So by default the $return_false should be true, in order to don't give any exception.
	public function setDefaultBrokerName($default_broker_name, $return_false = true) {
		//error_log("default_broker_name:".$default_broker_name."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		if (!$default_broker_name && $default_broker_name !== 0) {
			$this->default_broker_name = false;
			return true;
		}
		//only sets if exists
		else if(isset($this->brokers[$default_broker_name])) {
			$this->default_broker_name = $default_broker_name;
			return true;
		}
		
		if (!$return_false) 
			launch_exception(new LayerException(1, $default_broker_name));
		
		return false;
	}
	
	public function getDefaultBrokerName() {
		return $this->default_broker_name;
	}
}
?>
