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

namespace WorkFlowTask\programming\ns;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_namespace") {
			$value = $WorkFlowTaskCodeParser->printCodeNodeName($stmt);
			
			$sub_stmts = isset($stmt->stmts) ? $stmt->stmts : null;
			$sub_inner_tasks = self::createTasksPropertiesFromCodeStmts($sub_stmts, $WorkFlowTaskCodeParser);
			
			$props = array(
				"value" => $value,
				"label" => "Namespace: " . self::prepareTaskPropertyValueLabelFromCodeStmt($value),
				"exits" => array(
					self::DEFAULT_EXIT_ID => array(
						"color" => "#426efa",
					),
				)
			);
			
			$exits = array();
			
			//PREPARING EXITS AND INNER TASKS
			if ($sub_inner_tasks) {
				$exits[self::DEFAULT_EXIT_ID][] = array(
					"task_id" => isset($sub_inner_tasks[0]["id"]) ? $sub_inner_tasks[0]["id"] : null
				);
				
				$WorkFlowTaskCodeParser->addNextTaskToUndefinedTaskExits($sub_inner_tasks[count($sub_inner_tasks) - 1]);
				
				$inner_tasks = array($sub_inner_tasks);
			}
			else
				$exits[self::DEFAULT_EXIT_ID][] = array("task_id" => "#next_task#");
			
			return $props;
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$properties = array(
			"value" => isset($raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"] : null,
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$code = "namespace " . (isset($properties["value"]) ? $properties["value"] : null) . ";";
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
