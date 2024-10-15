<?php
namespace WorkFlowTask\programming\inlinehtml;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_inlinehtml") {
			return array(
				"code" => isset($stmt->value) ? $stmt->value : null,
				"label" => "Some HTML",
				"exits" => array(
					self::DEFAULT_EXIT_ID => array(
						"color" => "#426efa",
					),
				),
			);
		}
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
		
		$code = isset($properties["code"]) ? $properties["code"] : null;
		$code = substr($code, 0, 1) == "\n" ? "" : "\n" . $code;
		$code .= substr($code, strlen($code) - 1) == "\n" ? "" : "\n";
		$code = "?>" . $code . "<?php";
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
