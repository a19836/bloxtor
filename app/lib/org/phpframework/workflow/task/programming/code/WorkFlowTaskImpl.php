<?php
namespace WorkFlowTask\programming\code;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$code = $WorkFlowTaskCodeParser->printCodeStatement($stmt); //does not contain ';'
		
		if ($code && !preg_match("/;\s*$/", $code))
			$code .= ";";
		
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
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$properties = array(
			"code" => isset($raw_data["childs"]["properties"][0]["childs"]["code"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["code"][0]["value"] : null,
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$code = isset($properties["code"]) ? $prefix_tab . str_replace("\n", "\n$prefix_tab", $properties["code"]) . "\n" : null;
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
