<?php
namespace WorkFlowTask\programming\_echo;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_echo") {
			$exprs = isset($stmt->exprs) ? $stmt->exprs : null;
			
			$code = "";
			$type = "";
			
			if ($exprs) {
				if (count($exprs) == 1) {
					$expr = isset($exprs[0]) ? $exprs[0] : null;
					$expr_type = $expr ? strtolower($expr->getType()) : "";
					
					if (!$WorkFlowTaskCodeParser->isAssignExpr($expr) && ($expr_type == "expr_funccall" || $expr_type == "expr_methodcall" || $expr_type == "expr_staticcall" || $expr_type == "expr_new" || $expr_type == "expr_array")) {
						return null;
					}
					
					$code = $WorkFlowTaskCodeParser->printCodeExpr($expr);
					$code = $WorkFlowTaskCodeParser->getStmtValueAccordingWithType($code, $expr_type);
				
					$type = $WorkFlowTaskCodeParser->getStmtType($expr);
				}
				else {
					$t = count($exprs);
					for ($i = 0; $i < $t; $i++) {
						$code .= ($i > 0 ? ", " : "") . $WorkFlowTaskCodeParser->printCodeExpr($exprs[$i]);
					}
				}
			}
			
			$props = array(
				"value" => $code,
				"type" => self::getConfiguredParsedType($type),
				"label" => "Print " . str_replace('"', '', substr($code, 0, 50)),
				"exits" => array(
					self::DEFAULT_EXIT_ID => array(
						"color" => "#426efa",
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
			"type" => isset($raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"] : null,
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$value = isset($properties["value"]) ? $properties["value"] : null;
		$value = self::getVariableValueCode($value, isset($properties["type"]) ? $properties["type"] : null);
		
		$code = $value ? $prefix_tab . "echo $value;\n" : "";
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
