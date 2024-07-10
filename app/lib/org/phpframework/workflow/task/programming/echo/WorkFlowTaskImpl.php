<?php
namespace WorkFlowTask\programming\_echo;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_echo") {
			$exprs = $stmt->exprs;
			
			$code = "";
			$type = "";
			
			if ($exprs) {
				if (count($exprs) == 1) {
					$expr = $exprs[0];
					$expr_type = strtolower($expr->getType());
					
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
		
		$code = $value ? $prefix_tab . "echo $value;\n" : "";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
