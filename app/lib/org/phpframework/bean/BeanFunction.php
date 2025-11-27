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

include get_lib("org.phpframework.bean.exception.BeanFunctionException");
include_once get_lib("org.phpframework.phpscript.PHPCodePrintingHandler");

class BeanFunction {
	const APP_KEY = "DEvyNN0eB4+85k4t2rGDszwp1lB7UJgfHrsJKvKdqqfcJ++JWb//34E/9C5nIf0y"; //DO NOT CHANGE THIS. THIS IS THEPHPMYFRAMEWORK PUBLIC KEY TO DECODE THE LICENCE
	
	public $name;
	public $parameters = array();
	public $parent_object_reference = false;
	public $settings = array();
	
	public function __construct($name, $parameters = array(), $parent_object_reference = false, $settings = array()) {
		$this->name = trim($name);
		$this->settings = $settings;
		
		$this->setParameters($parameters);
		$this->setParentObjectReference($parent_object_reference);
		
		$this->isValid();
	}
	
	public function setParameters($parameters) {
		if($parameters) {
			$indexes = array();
			
			$t = count($parameters);
			for ($i = 0; $i < $t; $i++) {
				$p = $parameters[$i];
			
				$index = false;
				if (isset($p["index"]) && is_numeric($p["index"])) {
					$index = $p["index"];
					$indexes[] = $index;
				} 
				else if (!array_key_exists("index", $p)) {
					$indexes = MyArray::sort($indexes, SORT_NUMERIC);
					for($j = 1; $j <= count($indexes); $j++) {
						if($j < $indexes[$j - 1]) {
							$index = $j;
							break;
						}
					}
					if(!$index)
						$index = count($indexes) + 1;
					
					$parameters[$i]["index"] = $index;
					$indexes[] = $index;
				}
			}
			
			$parameters = MyArray::multisort($parameters, array(array('key'=>'index','sort'=>'asc')));
			foreach($parameters as $p) {
				$p_index = isset($p["index"]) ? $p["index"] : null;
				
				$this->parameters[$p_index] = new BeanArgument(
					$p_index, 
					isset($p["value"]) ? $p["value"] : null,
					isset($p["reference"]) ? $p["reference"] : null
				);
			}
		}
	}
	
	public function setParentObjectReference($parent_object_reference) {
		$this->parent_object_reference = $parent_object_reference;
	}
	
	private function isValid() {
		if(empty($this->name)) {
			launch_exception(new BeanFunctionException(1, $this->name));
			return false;
		}
		return true;
	}
	
	public function getFunctionNSName() {
		$path = $this->name;
		
		if (!empty($this->settings["namespace"]))
			$path = PHPCodePrintingHandler::prepareClassNameWithNameSpace($path, $this->settings["namespace"]);
		
		return $path;
	}
}
?>
