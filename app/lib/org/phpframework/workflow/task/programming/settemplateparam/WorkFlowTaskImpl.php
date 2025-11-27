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

namespace WorkFlowTask\programming\settemplateparam;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = isset($props["method_name"]) ? $props["method_name"] : null;
			
			if ($method_name == "setParam" && empty($props["method_static"])) {
				$args = isset($props["method_args"]) ? $props["method_args"] : null;
				
				$name = isset($args[0]["value"]) ? $args[0]["value"] : null;
				$name_type = isset($args[0]["type"]) ? $args[0]["type"] : null;
				$value = isset($args[1]["value"]) ? $args[1]["value"] : null;
				$value_type = isset($args[1]["type"]) ? $args[1]["type"] : null;
				
				unset($props["method_name"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["name"] = $name;
				$props["name_type"] = self::getConfiguredParsedType($name_type);
				$props["value"] = $value;
				$props["value_type"] = self::getConfiguredParsedType($value_type);
				
				$props["label"] = "Set param: " . self::prepareTaskPropertyValueLabelFromCodeStmt($name);
				
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
			"method_obj" => isset($raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"] : null,
			"name" => isset($raw_data["childs"]["properties"][0]["childs"]["name"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["name"][0]["value"] : null,
			"name_type" => isset($raw_data["childs"]["properties"][0]["childs"]["name_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["name_type"][0]["value"] : null,
			"value" => isset($raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"] : null,
			"value_type" => isset($raw_data["childs"]["properties"][0]["childs"]["value_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["value_type"][0]["value"] : null,
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$method_obj = isset($properties["method_obj"]) ? $properties["method_obj"] : null;
		if ($method_obj) {
			$static_pos = strpos($method_obj, "::");
			$non_static_pos = strpos($method_obj, "->");
			$method_obj = substr($method_obj, 0, 1) != '$' && substr($method_obj, 0, 2) != '@$' && (!$static_pos || ($non_static_pos && $static_pos > $non_static_pos)) ? '$' . $method_obj : $method_obj;
			$method_obj .= "->";
		}
		
		$name = isset($properties["name"]) ? $properties["name"] : null;
		$name_type = isset($properties["name_type"]) ? $properties["name_type"] : null;
		
		$value = isset($properties["value"]) ? $properties["value"] : null;
		$value_type = isset($properties["value_type"]) ? $properties["value_type"] : null;
		
		$code  = $prefix_tab . $method_obj . "setParam(" . self::getVariableValueCode($name, $name_type) . ", " . self::getVariableValueCode($value, $value_type) . ");\n";
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
