<?php
namespace WorkFlowTask\programming\slaitemsingle;

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
		$next_task = self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID][0], $stop_task_id, $prefix_tab, $options);
		
		return array(
			"properties" => $properties,
			"next" => $next_task,
		);
	}
}
?>
