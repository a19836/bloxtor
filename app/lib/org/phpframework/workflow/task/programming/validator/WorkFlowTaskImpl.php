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

namespace WorkFlowTask\programming\validator;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");
include_once get_lib("org.phpframework.util.text.TextValidator");
include_once get_lib("org.phpframework.object.ObjTypeHandler");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props && !empty($props["method_name"]) && !empty($props["method_static"]) && isset($props["method_obj"]) && ($props["method_obj"] == "TextValidator" || $props["method_obj"] == "ObjTypeHandler")) {
			$method_name = $props["method_name"];
			$available_methods_1 = get_class_methods('TextValidator');
			$available_methods_2 = get_class_methods('ObjTypeHandler');
			
			if (in_array($method_name, $available_methods_1) || in_array($method_name, $available_methods_2)) {
				$args = isset($props["method_args"]) ? $props["method_args"] : null;
				
				$props["method"] = $props["method_obj"] . "::$method_name";
				
				$value = isset($args[0]["value"]) ? $args[0]["value"] : null;
				$value_type = isset($args[0]["type"]) ? $args[0]["type"] : null;
				
				$props["value"] = $value;
				$props["value_type"] = self::getConfiguredParsedType($value_type);
				
				if ($props["method_obj"] == "TextValidator" && substr($method_name, 0, 5) == "check") {
					$offset = isset($args[1]["value"]) ? $args[1]["value"] : null;
					$offset_type = isset($args[1]["type"]) ? $args[1]["type"] : null;
					
					$props["offset"] = $offset;
					$props["offset_type"] = self::getConfiguredParsedType($offset);
				}
				
				unset($props["method_name"]);
				unset($props["method_obj"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["label"] = "$method_name " . self::prepareTaskPropertyValueLabelFromCodeStmt($value);
				
				$props["exits"] = array(
					self::DEFAULT_EXIT_ID => array(
						"color" => "#426efa",
					),
				);
				
				return $props;
			}
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$properties = array(
			"method" => isset($raw_data["childs"]["properties"][0]["childs"]["method"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["method"][0]["value"] : null,
			"value" => isset($raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"] : null,
			"value_type" => isset($raw_data["childs"]["properties"][0]["childs"]["value_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["value_type"][0]["value"] : null,
			"offset" => isset($raw_data["childs"]["properties"][0]["childs"]["offset"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["offset"][0]["value"] : null,
			"offset_type" => isset($raw_data["childs"]["properties"][0]["childs"]["offset_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["offset_type"][0]["value"] : null,
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		$method = isset($properties["method"]) ? $properties["method"] : null;
		$value = isset($properties["value"]) ? $properties["value"] : null;
		$value_type = isset($properties["value_type"]) ? $properties["value_type"] : null;
		$offset = isset($properties["offset"]) ? $properties["offset"] : null;
		$offset_type = isset($properties["offset_type"]) ? $properties["offset_type"] : null;
		
		$value = self::getVariableValueCode($value, $value_type);
		$offset = self::getVariableValueCode($offset, $offset_type);
		$code = "";
		
		if ($method) {
			$code = $prefix_tab . $var_name . "$method($value";
			
			if (strpos($method, "TextValidator::check") === 0)
				$code .= ", $offset";
			
			$code .= ");\n";
		}
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
