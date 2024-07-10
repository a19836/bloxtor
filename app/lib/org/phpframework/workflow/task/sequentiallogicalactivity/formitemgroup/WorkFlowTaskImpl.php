<?php
namespace WorkFlowTask\programming\slaitemgroup;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		return null;
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		$properties = $raw_data["childs"]["properties"][0]["childs"];
		$properties = \MyXML::complexArrayToBasicArray($properties, array("lower_case_keys" => true));
		
		return $properties["properties"];
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		$properties = $data["properties"];
		
		//PREPARING INNER TASKS CODE
		$stops_id = array();
		if ($stop_task_id)
			$stops_id = is_array($stop_task_id) ? $stop_task_id : array($stop_task_id);
		
		if ($data["exits"]["outside_group_exit"][0]) 
			$stops_id = array_merge($stops_id, $data["exits"]["outside_group_exit"]);
		
		$inner_tasks = self::printTask($tasks, $data["exits"]["inside_group_exit"], $stops_id, $prefix_tab . "\t", $options);
		$next_task = self::printTask($tasks, $data["exits"]["outside_group_exit"][0], $stop_task_id, $prefix_tab, $options);
		
		return array(
			"properties" => $properties,
			"inner" => $inner_tasks,
			"next" => $next_task,
		);
	}
}
?>
