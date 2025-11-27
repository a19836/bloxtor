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

namespace WorkFlowTask\programming\slaitemgroup;

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
		
		//PREPARING INNER TASKS CODE
		$stops_id = array();
		if ($stop_task_id)
			$stops_id = is_array($stop_task_id) ? $stop_task_id : array($stop_task_id);
		
		if (!empty($data["exits"]["outside_group_exit"][0]))
			$stops_id = array_merge($stops_id, $data["exits"]["outside_group_exit"]);
		
		$inside_task_id = isset($data["exits"]["inside_group_exit"]) ? $data["exits"]["inside_group_exit"] : null;
		$outside_task_id = isset($data["exits"]["outside_group_exit"][0]) ? $data["exits"]["outside_group_exit"][0] : null;
		
		$inner_tasks = self::printTask($tasks, $inside_task_id, $stops_id, $prefix_tab . "\t", $options);
		$next_task = self::printTask($tasks, $outside_task_id, $stop_task_id, $prefix_tab, $options);
		
		return array(
			"properties" => $properties,
			"inner" => $inner_tasks,
			"next" => $next_task,
		);
	}
}
?>
