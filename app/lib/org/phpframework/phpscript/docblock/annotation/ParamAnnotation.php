<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

namespace DocBlockParser\Annotation;

class ParamAnnotation extends Annotation {
	
	public function __construct() {
		$this->is_input = true;
		$this->vectors = array("type", "name", "desc");
	}
	
	public function parseArgs($DocBlockParser, $args) {
		$new_args = self::getConfiguredArgs($args);
		
		if (!empty($args["name"])) {
			$name = self::parseValue($args["name"]);
			$name = substr($name, 0, 1) == '$' ? substr($name, 1) : (substr($name, 0, 2) == '@$' ? substr($name, 2) : $name);
			$new_args["name"] = $name;
			
			if (strpos($name, "[") !== false) {//options[0]name or options[name] ==> options"]["0"]["name
				$mpn = str_replace(array('"', "'"), "", $name);
				preg_match_all("/([^\[\]]+)/u", $mpn, $matches, PREG_PATTERN_ORDER); //'/u' means with accents and ç too.
				
				if (!empty($matches[1]))
					$new_args["sub_name"] = implode('"]["', $matches[1]);
			}
		}
		
		if (isset($args["index"]) && is_numeric($args["index"]) && $args["index"] >= 0)
			$new_args["index"] = $args["index"];
		
		$this->args = $new_args;
	}
	
	/*
	for function xxx($module_id, $service_id, $data, $options = false)
		$method_params_data = array(
			"module_id" => "test",
			"service_id" => "foo",
			"data" => array(
				...
			),
			"options" => array(
				"no_cache" => true
			),
		);
	
	$method_params_data MUST BE AN ARRAY
	*/
	public function checkMethodAnnotations(&$method_params_data, $annotation_idx) {
		$status = true;
		
		if (!empty($this->args)) {
			if (isset($this->args["sub_name"]))
				$method_param_name = $this->args["sub_name"];
			else if (isset($this->args["name"]))
				$method_param_name = $this->args["name"];
			else {
				$index = isset($this->args["index"]) ? $this->args["index"] : $annotation_idx;
				
				$keys = array_keys($method_params_data);
				$method_param_name = isset($keys[$index]) ? $keys[$index] : null;
			}
			
			if (isset($method_param_name)) {
				//check if mandatory. Check only if Key exists in the $method_params_data. Note that its value can be null. The mandatory only check if the key exists, independent if the value is set or not.
				if (!empty($this->args["mandatory"]))
					eval ('$status = (is_array($method_params_data) && array_key_exists("' . $method_param_name . '", $method_params_data)) || (is_object($method_params_data) && property_exists($method_params_data, "' . $method_param_name . '"));');
				
				if ($status) {
					eval ('$value = isset($method_params_data["' . $method_param_name . '"]) ? $method_params_data["' . $method_param_name . '"] : null;');
					
					$status = $this->checkValueAnnotations($value, $value_changed);
					
					if ($value_changed)
						eval ('$method_params_data["' . $method_param_name . '"] = $value;');
				}
				
				//echo "|$value|\n";
				//eval ('echo "$method_param_name|".$method_params_data["' . $method_param_name . '"]."|\n";');
			}
			else if (isset($this->args["not_null"])) {
				$status = $this->checkValueAnnotations(null);
			}
		}
		
		return $status;
	}
}
?>
