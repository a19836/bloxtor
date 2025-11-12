<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

namespace WorkFlowTask\programming\debuglog;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getFunctionProps($stmt);
		
		if ($props) {
			$func_name = isset($props["func_name"]) ? $props["func_name"] : null;
			$args = isset($props["func_args"]) ? $props["func_args"] : null;
			
			if ($func_name && strtolower($func_name) == "debug_log") {
				if (count($args) <= 2) {
					$message = isset($args[0]["value"]) ? $args[0]["value"] : null;
					$message_type = isset($args[0]["type"]) ? $args[0]["type"] : null;
					$log_type = isset($args[1]["value"]) ? $args[1]["value"] : null;
					$log_type_type = isset($args[1]["type"]) ? $args[1]["type"] : null;
					
					unset($props["func_name"]);
					unset($props["func_args"]);
					unset($props["label"]);
					
					$props["message"] = $message;
					$props["message_type"] = self::getConfiguredParsedType($message_type);
					$props["log_type"] = $log_type;
					$props["log_type_type"] = self::getConfiguredParsedType($log_type_type);
					
					$format = isset($props["format"]) ? $props["format"] : null;
					$props["label"] = "Define date " . self::prepareTaskPropertyValueLabelFromCodeStmt($format);
					
					$props["exits"] = array(
						self::DEFAULT_EXIT_ID => array(
							"color" => "#426efa",
						),
					);
					
					return $props;
				}
			}
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$properties = array(
			"message" => isset($raw_data["childs"]["properties"][0]["childs"]["message"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["message"][0]["value"] : null,
			"message_type" => isset($raw_data["childs"]["properties"][0]["childs"]["message_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["message_type"][0]["value"] : null,
			"log_type" => isset($raw_data["childs"]["properties"][0]["childs"]["log_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["log_type"][0]["value"] : null,
			"log_type_type" => isset($raw_data["childs"]["properties"][0]["childs"]["log_type_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["log_type_type"][0]["value"] : null,
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$message = isset($properties["message"]) ? $properties["message"] : null;
		$message = self::getVariableValueCode($message, isset($properties["message_type"]) ? $properties["message_type"] : null);
		
		$log_type = isset($properties["log_type"]) ? $properties["log_type"] : null;
		$log_type = self::getVariableValueCode($log_type, isset($properties["log_type_type"]) ? $properties["log_type_type"] : null);
		
		$code = $prefix_tab . "debug_log($message";
		$code .= $log_type ? ", $log_type" : "";
		$code .= ");\n";
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
