<?php
namespace WorkFlowTask\programming\_return;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	protected $is_return_task = true;
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_return") {
			$expr = $stmt->expr;
			if ($expr) {
				$expr_type = strtolower($expr->getType());
			
				$code = $WorkFlowTaskCodeParser->printCodeExpr($expr);
				$code = $WorkFlowTaskCodeParser->getStmtValueAccordingWithType($code, $expr_type);
				
				$type = $WorkFlowTaskCodeParser->getStmtType($expr);
			}
			else {
				$code = "";
				$type = "";
			}
			
			$props = array(
				"value" => $code,
				"type" => self::getConfiguredParsedType($type),
				"label" => "Return " . self::prepareTaskPropertyValueLabelFromCodeStmt($code),
				"exits" => array(
					/*self::DEFAULT_EXIT_ID => array(
						"color" => "#426efa",
					),*/
				),
			);
			
			return $props;
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		
		$properties = array(
			"value" => $raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"],
			"type" => $raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"],
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$value = self::getVariableValueCode($properties["value"], $properties["type"]);
		
		$code = $prefix_tab . "return $value;\n";
		
		return $code;// . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
