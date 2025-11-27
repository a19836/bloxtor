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

namespace WorkFlowTask\programming\setarray;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($WorkFlowTaskCodeParser->isAssignExpr($stmt) || ($stmt_type == "stmt_echo" && isset($stmt->exprs) && count($stmt->exprs) == 1 && !$WorkFlowTaskCodeParser->isAssignExpr($stmt->exprs[0]))) {
			//print_r($stmt);
			$expr = $stmt_type == "stmt_echo" ? $stmt->exprs[0] : (isset($stmt->expr) ? $stmt->expr : null);
			$expr_type = $expr ? strtolower($expr->getType()) : "";
			
			if ($expr_type == "expr_array") {
				$props = $WorkFlowTaskCodeParser->getVariableNameProps($stmt);
				$props = $props ? $props : array();
				
				$items = isset($expr->items) ? $expr->items : null;
				$props["items"] = $WorkFlowTaskCodeParser->getArrayItems($items);
				
				$props["label"] = "Init " . $this->getPropertiesResultVariableCode($props, false);
				
				$props["exits"] = array(
					self::DEFAULT_EXIT_ID => array(
						"color" => "#426efa",
					),
				);
				
				return $props;
			}
		}
		else if ($stmt_type == "EXPR_array") {
			$items = isset($stmt->items) ? $stmt->items : null;
			$props = array(
				"items" => $WorkFlowTaskCodeParser->getArrayItems($items),
				"exits" => array(
					self::DEFAULT_EXIT_ID => array(
						"color" => "#426efa",
					),
				),
			);
			
			return $props;
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$properties = self::parseResultVariableProperties($raw_data);
		
		$items = isset($raw_data["childs"]["properties"][0]["childs"]["items"]) ? $raw_data["childs"]["properties"][0]["childs"]["items"] : null;
		$items = self::parseArrayItems($items);
		$properties["items"] = $items;
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		
		$items = isset($properties["items"]) ? $properties["items"] : null;
		$code = $prefix_tab . $var_name . ltrim(self::getArrayString($items, $prefix_tab)) . ";\n";
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
