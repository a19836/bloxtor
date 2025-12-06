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

include get_lib("org.phpframework.bean.Bean");
include get_lib("org.phpframework.bean.exception.BeanFactoryException");
include get_lib("org.phpframework.bean.BeanSettingsFileFactory");
include_once get_lib("org.phpframework.cache.xmlsettings.XmlSettingsCacheHandler");

class BeanFactory {
	const APP_KEY = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtec+xMxVPOgL2KiCALkF"; //DO NOT CHANGE THIS. THIS IS THEPHPMYFRAMEWORK PUBLIC KEY TO DECODE THE LICENCE
	
	private $objs = array();
	private $beans = array();
	//private $settings_var_name = "beans";
	private $external_vars = array();
	private $infinitive_cicle = array();
	
	private $created_element_ids = array();
	private $sort_elements = array();
	
	private $BeanSettingsFileFactory;
	
	public function __construct() {
		$this->BeanSettingsFileFactory = new BeanSettingsFileFactory();
	}
	
	public function getSettingsFromFile($file_path) {
		$external_vars = $this->external_vars;
		$external_vars["objs"] = isset($external_vars["objs"]) && is_array($external_vars["objs"]) ? array_merge($this->objs, $external_vars["objs"]) : $this->objs;
		$external_vars["vars"] = isset($external_vars["vars"]) && is_array($external_vars["vars"]) ? array_merge($this->objs["vars"], $external_vars["vars"]) : (isset($this->objs["vars"]) ? $this->objs["vars"] : null);
		
		$settings = $this->BeanSettingsFileFactory->getSettingsFromFile($file_path, $external_vars);
		
		return $settings;
	}
	
	public function getBeansFromSettings($settings, &$sort_elements = false) {
		$beans = array();
		
		$t = $settings ? count($settings) : 0;
		for($i = 0; $i < $t; $i++) {
			$setting = $settings[$i];
			if(isset($setting["import"])) {
				$import_settings = $this->getSettingsFromFile($setting["import"]);
				$import_beans = $this->getBeansFromSettings($import_settings, $sort_elements);
				$beans = array_merge($beans, $import_beans);
			}
			else if(isset($setting["bean"])) {
				$s = $setting["bean"];
				
				//This part should NOT execute, but we do it for pre-caution, just in case some EXTEND OBJ is hard-coded in the $settings var before we call this function... Just leave this line please, otherwise we are not preventing all the cases!
				if (!empty($s["extend"]) && empty($s["bean_to_extend"])) {
					$sub_total = count($s["extend"]);
					for ($j = 0; $j < $sub_total; $j++) {
						$extended_class_name = $s["extend"][$j];
						$s["bean_to_extend"][$extended_class_name] = BeanSettingsFileFactory::getBeanSettingsByName($settings, $extended_class_name);
					}
				}
				
				$bean_name = isset($s["name"]) ? $s["name"] : null;
				$beans[$bean_name] = new Bean(
					$bean_name, 
					isset($s["path"]) ? $s["path"] : null, 
					isset($s["constructor_args"]) ? $s["constructor_args"] : null, 
					isset($s["properties"]) ? $s["properties"] : null, 
					isset($s["functions"]) ? $s["functions"] : null, 
					array(
						"path_prefix" => isset($s["path_prefix"]) ? $s["path_prefix"] : null, 
						"extension" => isset($s["extension"]) ? $s["extension"] : null, 
						"bean_to_extend" => isset($s["bean_to_extend"]) ? $s["bean_to_extend"] : null, 
						"namespace" => isset($s["namespace"]) ? $s["namespace"] : null, 
						"class_name" => isset($s["class_name"]) ? $s["class_name"] : null
					)
				);
				
				$sort_elements[] = array("type" => "bean", "name" => $bean_name);
			}
			else if(isset($setting["var"])) {
				$s = $setting["var"];
				$bean_name = isset($s["name"]) ? $s["name"] : null;
				
				$this->objs[$bean_name] = isset($s["value"]) ? $s["value"] : null;
			}
			else if(isset($setting["function"])) {
				$func = new BeanFunction(
					isset($setting["function"]["name"]) ? $setting["function"]["name"] : null, 
					isset($setting["function"]["parameters"]) ? $setting["function"]["parameters"] : null, 
					isset($setting["function"]["reference"]) ? $setting["function"]["reference"] : null, 
					array(
						"namespace" => isset($setting["function"]["namespace"]) ? $setting["function"]["namespace"] : null
					)
				);
				$sort_elements[] = array("type" => "function", "function" => $func);
			}
			else
				launch_exception(new BeanFactoryException(1, $i));
		}
		return $beans;
	}
	
	public function init($data) {
		$settings = array();
		
		/*if($data["settings_var_name"]) 
			$this->settings_var_name = $data["settings_var_name"];
		*/
		
		if(!empty($data["external_vars"])) 
			$this->external_vars = $data["external_vars"];
		
		if(!empty($data["file"])) 
			$settings = $this->getSettingsFromFile($data["file"]);
		
		if(isset($data["settings"]) && is_array($data["settings"])) 
			$settings = empty($settings) ? $data["settings"] : array_merge($settings, $data["settings"]);
		
		$this->sort_elements = array();//We must reset the $this->sort_elements array everytime that we call the getBeansFromSettings.
		$this->beans = $this->getBeansFromSettings($settings, $this->sort_elements);
	}
	
	public function add($data) {
		if(!empty($data["external_vars"])) {
			$objs = isset($this->external_vars["objs"]) ? $this->external_vars["objs"] : null;
			$vars = isset($this->external_vars["vars"]) ? $this->external_vars["vars"] : null;
			$this->external_vars = empty($this->external_vars) ? $data["external_vars"] : array_merge($this->external_vars, $data["external_vars"]);
			
			if ($objs)
				$this->external_vars["objs"] = array_merge($this->external_vars["objs"], $data["external_vars"]["objs"]);
						
			if ($vars)
				$this->external_vars["vars"] = array_merge($this->external_vars["vars"], $data["external_vars"]["vars"]);
		}
		
		if(!empty($data["file"])) 
			$settings = $this->getSettingsFromFile($data["file"]);
		
		if(isset($data["settings"]) && is_array($data["settings"])) 
			$settings = empty($settings) ? $data["settings"] : array_merge($settings, $data["settings"]);
		
		$this->sort_elements = array();//We must reset the $this->sort_elements array everytime that we call the getBeansFromSettings.
		$new_beans = $this->getBeansFromSettings($settings, $this->sort_elements);
		
		$this->beans = empty($this->beans) ? $new_beans : array_merge($this->beans, $new_beans);
	}
	
	public function initObjects() {
		$t = $this->sort_elements ? count($this->sort_elements) : 0;
		for($i = 0; $i < $t; $i++) {
			$sort_element = $this->sort_elements[$i];
			
			if (isset($sort_element["type"])) {
				if ($sort_element["type"] == "bean" && isset($sort_element["name"]))
					$this->initObject($sort_element["name"]);
				else if ($sort_element["type"] == "function") 
					$this->initFunction($sort_element);
			}
		 }
	}
	
	public function initObject($bean_name, $launch_exception = true) {
		$bean = isset($this->beans[$bean_name]) ? $this->beans[$bean_name] : null;
	
		if($bean) {
			$bean_id = md5(serialize($bean));
			
			if (!isset($this->created_element_ids[$bean_id])) {
				$this->created_element_ids[$bean_id] = true;
		
				/** START: CHECK INFINITIVE_CICLE **/
				if (!empty($this->objs[$bean_name]))
					return $this->objs[$bean_name];
				
				if (!empty($this->infinitive_cicle[$bean_name])) {
					launch_exception(new BeanFactoryException(3, $bean_name));
					return false;
				}
				else
					$this->infinitive_cicle[$bean_name] = true;
				/** END: CHECK INFINITIVE_CICLE **/
			
				/** START: CREATE PATH **/
				if($bean->path) {
					$objs = $this->objs;
					$vars = isset($this->objs["vars"]) ? $this->objs["vars"] : null;
				
					foreach($this->external_vars as $var_name => $var_value)
						${$var_name} = $var_value;
				
					include_once($bean->path);
				}
				/** END: CREATE PATH **/
			
				/** START: CREATE ARGS **/
				$args_str = $this->getArgumentsStr($bean->constructor_args);
				//echo "\$obj = new " . $bean->class_name . "(" . $args_str . ");<br>\n";
				//error_log("\$obj = new " . $bean->class_name . "(" . $args_str . ");\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
				eval("\$obj = new " . $bean->class_name . "(" . $args_str . ");");
				/** END: CREATE ARGS **/
			
				$this->objs[$bean_name] = $obj;//in case of infinitive cicle
			
				/** START: CREATE PROPERTIES **/
				foreach($bean->properties as $name => $p) {
					$value = $this->getArgumentStr($p);
					//echo "\$obj->set" . ucfirst($name) . "(" . $value . ");<br>\n";
					eval("\$obj->set" . ucfirst($name) . "(" . $value . ");");
				}
				/** END: CREATE PROPERTIES **/
			
				/** START: CREATE FUNCTIONS **/
				$t = $bean->functions ? count($bean->functions) : 0;
				for($i = 0; $i < $t; $i++) {
					$f = $bean->functions[$i];
				
					$args_str = $this->getArgumentsStr($f->parameters);
					eval("\$obj->" . $f->name . "(" . $args_str . ");");
				}
				/** END: CREATE FUNCTIONS **/
			}
		}
		else if($launch_exception) 
			launch_exception(new BeanFactoryException(2, $bean_name));
	}
	
	public function initFunction($function, $launch_exception = true) {
		$f = isset($function["function"]) ? $function["function"] : null;
		
		$args_str = $this->getArgumentsStr($f->parameters);
		
		$obj = isset($this->objs[$f->parent_object_reference]) ? $this->objs[$f->parent_object_reference] : null;
		
		if ($obj) 
			$code = "\$obj->" . $f->name . "(" . $args_str . ");";
		else //With no reference, which means the function is a global function. Example: echo; str_replace...
			$code = $f->getFunctionNSName() . "(" . $args_str . ");";
		
		//echo "$code<br>\n";
		//error_log("$code\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
		eval($code);
	}
	
	private function getArgumentStr($elm) {
		$value = "";
		
		if($elm->reference) {
			if(!isset($this->objs[$elm->reference])) 
				$this->initObject($elm->reference);
			
			$value = "isset(\$this->objs['" . $elm->reference . "']) ? \$this->objs['" . $elm->reference . "'] : null";
		}
		else {
			$value = $elm->value;
			
			if (is_array($value))
				$value = var_export($value, true);
			else
				$value = is_numeric($value) && !is_string($value) ? $value : '"' . addcslashes($value, '\\"') . '"';
		}
		return $value;
	}
	
	private function getArgumentsStr($args) {
		$args_str = "";
		
		$previous_arg_index = 0;
		$arg_keys = array_keys($args);
		$t = count($arg_keys);
		for($i = 0; $i < $t; $i++) {
			$index = $arg_keys[$i];
			$a = $args[$index];
			
			$args_str .= strlen($args_str) > 0 ? ", " : "";
			
			$dif = $index - ($previous_arg_index + 1);
			if($dif > 0)
				for($j = 0; $j < $dif; $j++)
					$args_str .= "false, ";
			
			$args_str .= $this->getArgumentStr($a);
			$previous_arg_index = $index;
		}
		
		return $args_str;
	}
	
	public function reset() {
		$this->objs = array();
		$this->beans = array();
		$this->sort_elements = array();
	}
	
	public function addBeans($beans) {
		$this->beans = empty($this->beans) ? $beans : array_merge($this->beans, $beans);
	}
	
	public function getBeans() {return $this->beans;}
	public function getBean($bean_name) {return isset($this->beans[$bean_name]) ? $this->beans[$bean_name] : null;}
	
	public function addObjects($objs) {
		$this->objs = empty($this->objs) ? $objs : array_merge($this->objs, $objs);
	}
	
	public function getObjects() {return $this->objs;}
	public function getObject($obj_name) {return isset($this->objs[$obj_name]) ? $this->objs[$obj_name] : null;}
	
	public function setCacheRootPath($dir_path) {
		$this->BeanSettingsFileFactory->setCacheRootPath($dir_path);
	}
	
	public function setCacheHandler(XmlSettingsCacheHandler $XmlSettingsCacheHandler) {
		$this->BeanSettingsFileFactory->setCacheHandler($XmlSettingsCacheHandler);
	}
}
?>
