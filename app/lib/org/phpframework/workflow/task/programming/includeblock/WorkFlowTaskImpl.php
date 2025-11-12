<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

namespace WorkFlowTask\programming\includeblock;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function __construct() {
		$this->priority = 2;
	}
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "expr_include") {
			$expr = isset($stmt->expr) ? $stmt->expr : null;
			$expr_type = $expr ? strtolower($expr->getType()) : "";
			
			if ($expr_type == "expr_methodcall") {
				$props = $WorkFlowTaskCodeParser->getObjectMethodProps($expr);
				
				$method_name = isset($props["method_name"]) ? $props["method_name"] : null;
			
				if ($method_name == "getBlockPath" && empty($props["method_static"])) {
					$args = isset($props["method_args"]) ? $props["method_args"] : null;
					
					$block = isset($args[0]["value"]) ? $args[0]["value"] : null;
					$block_type = isset($args[0]["type"]) ? $args[0]["type"] : null;
					$project = isset($args[1]["value"]) ? $args[1]["value"] : null;
					$project_type = isset($args[1]["type"]) ? $args[1]["type"] : null;
					
					unset($props["method_name"]);
					unset($props["method_args"]);
					unset($props["method_static"]);
				
					$props["block"] = $block;
					$props["block_type"] = self::getConfiguredParsedType($block_type);
					$props["project"] = $project;
					$props["project_type"] = self::getConfiguredParsedType($project_type);
					$props["once"] = isset($stmt->type) && ($stmt->type == 2 || $stmt->type == 4);
					
					$props["label"] = "Include " . self::prepareTaskPropertyValueLabelFromCodeStmt( basename($block) );
					
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
			"method_obj" => isset($raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"] : null,
			"block" => isset($raw_data["childs"]["properties"][0]["childs"]["block"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["block"][0]["value"] : null,
			"block_type" => isset($raw_data["childs"]["properties"][0]["childs"]["block_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["block_type"][0]["value"] : null,
			"project" => isset($raw_data["childs"]["properties"][0]["childs"]["project"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["project"][0]["value"] : null,
			"project_type" => isset($raw_data["childs"]["properties"][0]["childs"]["project_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["project_type"][0]["value"] : null,
			"once" => isset($raw_data["childs"]["properties"][0]["childs"]["once"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["once"][0]["value"] : null,
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
		
		$project = !empty($properties["project"]) ? ", " . self::getVariableValueCode($properties["project"], isset($properties["project_type"]) ? $properties["project_type"] : null) : "";
		$block = self::getVariableValueCode(isset($properties["block"]) ? $properties["block"] : null, isset($properties["block_type"]) ? $properties["block_type"] : null);
		
		$code = $block ? $prefix_tab . "include" . (!empty($properties["once"]) ? "_once" : "") . " " . $method_obj . "getBlockPath($block$project);\n" : "";
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
