<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

namespace WorkFlowTask\programming\_die;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	protected $is_return_task = true;
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "expr_exit") {
			$expr = isset($stmt->expr) ? $stmt->expr : null;
			
			if ($expr) {
				$expr_type = strtolower($expr->getType());
			
				$code = $WorkFlowTaskCodeParser->printCodeExpr($expr, false);
				$code = $WorkFlowTaskCodeParser->getStmtValueAccordingWithType($code, $expr_type);
				
				$type = $WorkFlowTaskCodeParser->getStmtType($expr);
			}
			else {
				$code = "";
				$type = "";
			}
			
			$props = array(
				"value" => $code,
				"type" => self::getConfiguredParsedType($type),
				"label" => "Die " . self::prepareTaskPropertyValueLabelFromCodeStmt($code),
				"exits" => array(
					self::DEFAULT_EXIT_ID => array(
						"color" => "#ff0000",//"#426efa",
					),
				),
			);
			
			return $props;
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$properties = array(
			"value" => isset($raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"] : null,
			"type" => isset($raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"] : null,
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$value = isset($properties["value"]) ? $properties["value"] : null;
		$value = self::getVariableValueCode($value, isset($properties["type"]) ? $properties["type"] : null);
		
		$code = $prefix_tab . "die($value);\n";
		
		return $code;// . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
