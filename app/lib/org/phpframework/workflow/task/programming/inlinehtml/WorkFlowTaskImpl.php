<?php
namespace WorkFlowTask\programming\inlinehtml;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_inlinehtml") {
			return array(
				"code" => $stmt->value,
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
		$raw_data = $task["raw_data"];
		
		$properties = array(
			"code" => $raw_data["childs"]["properties"][0]["childs"]["code"][0]["value"],
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$code = $properties["code"];
		$code = substr($code, 0, 1) == "\n" ? "" : "\n" . $code;
		$code .= substr($code, strlen($code) - 1) == "\n" ? "" : "\n";
		$code = "?>" . $code . "<?php";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
