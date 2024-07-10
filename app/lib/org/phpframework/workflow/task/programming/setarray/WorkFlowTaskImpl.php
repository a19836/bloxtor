<?php
namespace WorkFlowTask\programming\setarray;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($WorkFlowTaskCodeParser->isAssignExpr($stmt) || ($stmt_type == "stmt_echo" && count($stmt->exprs) == 1 && !$WorkFlowTaskCodeParser->isAssignExpr($stmt->exprs[0]))) {
			//print_r($stmt);
			$expr = $stmt_type == "stmt_echo" ? $stmt->exprs[0] : $stmt->expr;
			$expr_type = strtolower($expr->getType());
			
			if ($expr_type == "expr_array") {
				$props = $WorkFlowTaskCodeParser->getVariableNameProps($stmt);
				$props = $props ? $props : array();
				
				$props["items"] = $WorkFlowTaskCodeParser->getArrayItems($expr->items);
				
				$props["label"] = "Init " . $this->getPropertiesResultVariableCode($props, false);
				
				$props["exits"] = array(
					self::DEFAULT_EXIT_ID => array(
						"color" => "#426efa",
					),
				);
				
				return $props;
			}
		}
		else if ($stmt_type == "EXPR_array") {
			$props = array(
				"items" => $WorkFlowTaskCodeParser->getArrayItems($stmt->items),
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
		
		$properties = self::parseResultVariableProperties($raw_data);
		
		$items = $raw_data["childs"]["properties"][0]["childs"]["items"];
		$items = self::parseArrayItems($items);
		$properties["items"] = $items;
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		
		$code = $prefix_tab . $var_name . ltrim(self::getArrayString($properties["items"], $prefix_tab)) . ";\n";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
