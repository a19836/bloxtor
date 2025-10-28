<?php
namespace WorkFlowTask\programming\_break;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	protected $is_break_task = true;
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_break") {
			$value = !empty($stmt->num) && !empty($stmt->num->value) ? $stmt->num->value : "";
			
			$props = array(
				"value" => $value,
				"label" => "Break " . self::prepareTaskPropertyValueLabelFromCodeStmt($value),
				"exits" => array(
					self::DEFAULT_EXIT_ID => array(
						"color" => "#ff0000",//"#426efa",
					),
				),
			);
			
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
		$value = isset($properties["value"]) && is_numeric($properties["value"]) ? " " . $properties["value"] : "";
		
		$code = $prefix_tab . "break$value;\n";
		
		return $code; //break does not write the code after it-self. There are no tasks after!
	}
}
?>
