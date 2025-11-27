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

namespace WorkFlowTask\programming\setblockparams;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	const MAIN_VARIABLE_NAME = "block_local_variables";
	
	public function __construct() {
		$this->priority = 3;
	}
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "expr_assign") {
			$props = $WorkFlowTaskCodeParser->getVariableNameProps($stmt);
			$props = $props ? $props : array();
			
			$result_var_name = isset($props["result_var_name"]) ? $props["result_var_name"] : null;
			
			if (trim($result_var_name) != self::MAIN_VARIABLE_NAME) {
				return null;
			}
			
			$expr = isset($stmt->expr) ? $stmt->expr : null;
			$expr_type = $expr ? strtolower($expr->getType()) : "";
			
			$code = $WorkFlowTaskCodeParser->printCodeExpr($expr, false);
			$code = $WorkFlowTaskCodeParser->getStmtValueAccordingWithType($code, $expr_type);
			
			$type = $WorkFlowTaskCodeParser->getStmtType($expr);
			
			$props = array();
			$props["main_variable_name"] = self::MAIN_VARIABLE_NAME;
			$props["value"] = $code;
			$props["type"] = self::getConfiguredParsedType($type);
			
			$props["label"] = "Add param: " . self::prepareTaskPropertyValueLabelFromCodeStmt($code);
			
			$props["exits"] = array(
				self::DEFAULT_EXIT_ID => array(
					"color" => "#426efa",
				),
			);
	
			return $props;
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$properties = array(
			"main_variable_name" => isset($raw_data["childs"]["properties"][0]["childs"]["main_variable_name"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["main_variable_name"][0]["value"] : null,
			"value" => isset($raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"] : null,
			"type" => isset($raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"] : null,
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$main_var_name = '$' . (!empty($properties["main_variable_name"]) ? $properties["main_variable_name"] : self::MAIN_VARIABLE_NAME);
		
		$value = isset($properties["value"]) ? $properties["value"] : null;
		$code  = $prefix_tab . $main_var_name . " = " . self::getVariableValueCode($value, isset($properties["type"]) ? $properties["type"] : null) . ";\n";
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
