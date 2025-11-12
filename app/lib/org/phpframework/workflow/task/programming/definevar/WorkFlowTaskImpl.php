<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

namespace WorkFlowTask\programming\definevar;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getFunctionProps($stmt);
		
		if ($props) {
			$func_name = isset($props["func_name"]) ? $props["func_name"] : null;
			$args = isset($props["func_args"]) ? $props["func_args"] : null;
			
			if ($func_name && strtolower($func_name) == "define" && isset($args[0]["type"]) && $args[0]["type"] == "string") {
				$arg_value_0 = isset($args[0]["value"]) ? $args[0]["value"] : null;
				
				return array(
					"name" => $arg_value_0,
					"value" => isset($args[1]["value"]) ? $args[1]["value"] : null,
					"type" => isset($args[1]["type"]) ? self::getConfiguredParsedType($args[1]["type"]) : null,
					"label" => "Init " . self::prepareTaskPropertyValueLabelFromCodeStmt($arg_value_0),
					"comments" => isset($props["comments"]) ? $props["comments"] : "",
					"exits" => array(
						self::DEFAULT_EXIT_ID => array(
							"color" => "#426efa",
						),
					),
				);
			}
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$properties = array(
			"name" => isset($raw_data["childs"]["properties"][0]["childs"]["name"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["name"][0]["value"] : null,
			"value" => isset($raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"] : null,
			"type" => isset($raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"] : null,
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$var_name = !empty($properties["name"]) ? trim($properties["name"]) : false;
		$value = self::getVariableValueCode($properties["value"], $properties["type"]);
		
		$code = $var_name ? $prefix_tab . "define(\"" . $var_name . "\", $value);\n" : "";
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
