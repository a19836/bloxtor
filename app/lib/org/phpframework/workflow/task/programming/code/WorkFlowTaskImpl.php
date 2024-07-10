<?php
namespace WorkFlowTask\programming\code;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$code = $WorkFlowTaskCodeParser->printCodeStatement($stmt);
		
		return array(
			"code" => $code,
			"label" => "Code: " . str_replace('"', '', substr($code, 0, 50)),
			"exits" => array(
				self::DEFAULT_EXIT_ID => array(
					"color" => "#426efa",
				),
			),
		);
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		
		$properties = array(
			"code" => $raw_data["childs"]["properties"][0]["childs"]["code"][0]["value"],
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$code = $prefix_tab . str_replace("\n", "\n$prefix_tab", $properties["code"]) . "\n";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
