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

namespace WorkFlowTask\programming\slaitemsingle;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		return null;
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		$properties = isset($raw_data["childs"]["properties"][0]["childs"]) ? $raw_data["childs"]["properties"][0]["childs"] : null;
		$properties = \MyXML::complexArrayToBasicArray($properties, array("lower_case_keys" => true));
		
		return isset($properties["properties"]) ? $properties["properties"] : null;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID][0]) ? $data["exits"][self::DEFAULT_EXIT_ID][0] : null;
		$next_task = self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
		
		return array(
			"properties" => $properties,
			"next" => $next_task,
		);
	}
}
?>
