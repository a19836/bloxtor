<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include get_lib("org.phpframework.bean.BeanArgument");
include get_lib("org.phpframework.bean.BeanProperty");
include get_lib("org.phpframework.bean.BeanFunction");
include get_lib("org.phpframework.bean.exception.BeanException");
include_once get_lib("org.phpframework.util.MyArray");
include_once get_lib("org.phpframework.phpscript.PHPCodePrintingHandler");

class Bean {
	const APP_KEY = "X7mhjhevDV20K1bSzGpJNOhB3GvNcqfDDYm4CT84TzWSPvCvolz0myZYr/qG2nuh"; //DO NOT CHANGE THIS. THIS IS THEPHPMYFRAMEWORK PUBLIC KEY TO DECODE THE LICENCE
	
	public $name;
	public $class_name;
	public $path;
	public $constructor_args = array();
	public $properties = array();
	public $functions = array();
	public $settings = array();
	
	public function __construct($name, $path, $constructor_args = array(), $properties = array(), $functions = array(), $settings = array()) {
		$this->extend($constructor_args, $properties, $functions, $settings);
		
		$this->name = trim($name);
		$this->settings = $settings;
		
		$this->setPath($path);
		$this->setClassName($path);
		$this->setConstructorArgs($constructor_args);
		$this->setProperties($properties);
		$this->setFunctions($functions);
		
		$this->isValid();
	}
	
	public static function extend(&$constructor_args, &$properties, &$functions, &$settings) {
		if (!empty($settings["bean_to_extend"]) && is_array($settings["bean_to_extend"])) 
			foreach ($settings["bean_to_extend"] as $bean_to_extend) {
				$bean_to_extend_constructor_args = isset($bean_to_extend["constructor_args"]) ? $bean_to_extend["constructor_args"] : null;
				if (is_array($bean_to_extend_constructor_args)) {
					if (is_array($constructor_args))
						$constructor_args = array_merge($bean_to_extend_constructor_args, $properties);
					else
						$constructor_args = $bean_to_extend_constructor_args;
				}
			
				$bean_to_extend_properties = isset($bean_to_extend["properties"]) ? $bean_to_extend["properties"] : null;
				if (is_array($bean_to_extend_properties)) {
					if (is_array($properties)) 
						$properties = array_merge($bean_to_extend_properties, $properties);
					else
						$properties = $bean_to_extend_properties;
				}
			
				$bean_to_extend_functions = isset($bean_to_extend["functions"]) ? $bean_to_extend["functions"] : null;
				if (is_array($bean_to_extend_functions)) {
					if (is_array($functions))
						$functions = array_merge($bean_to_extend_functions, $functions);
					else 
						$functions = $bean_to_extend_functions;
				}
			
				//Leave it if $settings["path_prefix"] is blank
				if (!isset($settings["path_prefix"]) && !empty($bean_to_extend["path_prefix"])) 
					$settings["path_prefix"] = $bean_to_extend["path_prefix"];
				
				//Leave it if $settings["extension"] is blank
				if (!isset($settings["extension"]) && !empty($bean_to_extend["extension"])) 
					$settings["extension"] = $bean_to_extend["extension"];
			}
	}
	
	public function setPath($path) {
		$this->path = self::getBeanFilePath(
			$path, 
			isset($this->settings["path_prefix"]) ? $this->settings["path_prefix"] : null, 
			isset($this->settings["extension"]) ? $this->settings["extension"] : null
		);
	}
	
	public static function getBeanFilePath($path, $path_prefix = false, $extension = null) {
		$path = str_replace("//", "/", str_replace(".", "/", $path));
		$path_prefix = str_replace("//", "/", str_replace(".", "/", $path_prefix));
		$extension = isset($extension) && $extension ? $extension : "php"; 
		
		return !empty($path) && $path != "." && $path != ".." ? $path_prefix . $path . "." . $extension : false;
		
		/*$dirname = dirname($path);
		if($dirname != "." && $dirname != "..") {
			$path = $path_prefix . $path . "." . $extension;
		}
		else {
			$path = false;
		}
		
		return $path;*/
	}
	
	public function setClassName($path) {
		$path = str_replace(".", "/", $path);
		
		$this->class_name = !empty($this->settings["class_name"]) ? $this->settings["class_name"] : basename($path);
		
		if (!empty($this->settings["namespace"]))
			$this->class_name = PHPCodePrintingHandler::prepareClassNameWithNameSpace($this->class_name, $this->settings["namespace"]);
	}
	
	public function setConstructorArgs($constructor_args) {
		if($constructor_args) {
			$indexes = array();
		
			$t = $constructor_args ? count($constructor_args) : 0;
			for($i = 0; $i < $t; $i++) {
				$ca = $constructor_args[$i];
			
				$index = false;
				if(isset($ca["index"]) && is_numeric($ca["index"])) {
					$index = $ca["index"];
					$indexes[] = $index;
				} 
				else if(!array_key_exists("index", $ca)) {
					$indexes = MyArray::sort($indexes, SORT_NUMERIC);
					for($j = 1; $j <= count($indexes); $j++) {
						if($j < $indexes[$j - 1]) {
							$index = $j;
							break;
						}
					}
					
					if(!$index)
						$index = count($indexes) + 1;
					
					$constructor_args[$i]["index"] = $index;
					$indexes[] = $index;
				}
			}
		
			$constructor_args = MyArray::multisort($constructor_args, array(array('key'=>'index', 'sort'=>'asc')));
			foreach($constructor_args as $ca) {
				$ca_index = isset($ca["index"]) ? $ca["index"] : null;
				
				$this->constructor_args[$ca_index] = new BeanArgument(
					$ca_index, 
					isset($ca["value"]) ? $ca["value"] : null, 
					isset($ca["reference"]) ? $ca["reference"] : null
				);
			}
		}
	}
	
	public function setProperties($properties) {
		$t = $properties ? count($properties) : 0;
		for($i = 0; $i < $t; $i++) {
			$p = $properties[$i];
			
			if (isset($p["name"]))
				$this->properties[ $p["name"] ] = new BeanProperty(
					$p["name"], 
					isset($p["value"]) ? $p["value"] : null,
					isset($p["reference"]) ? $p["reference"] : null
				);
		}
	}
	
	public function setFunctions($functions) {
		$t = $functions ? count($functions) : 0;
		for($i = 0; $i < $t; $i++) {
			$f = $functions[$i];
			
			if (isset($f["name"]))
				$this->functions[] = new BeanFunction(
					$f["name"], 
					isset($f["parameters"]) ? $f["parameters"] : null
				);
		}
	}
	
	private function isValid() {
		if(empty($this->name)) {
			launch_exception(new BeanException(1, $this->name . "::" . $this->path));
			return false;
		}
		
		return true;
	}
	
	public function resetConstructorArgs() {$this->constructor_args = array();}
	public function resetProperties() {$this->properties = array();}
}
?>
