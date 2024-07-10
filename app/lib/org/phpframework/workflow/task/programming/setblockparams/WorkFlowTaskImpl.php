<?php
namespace WorkFlowTask\programming\setblockparams;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	const MAIN_VARIABLE_NAME = "block_local_variables";
	
	public function __construct() {
		$this->priority = 3;
	}
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "expr_assign") {
			$props = $WorkFlowTaskCodeParser->getVariableNameProps($stmt);
			$props = $props ? $props : array();
			
			if (trim($props["result_var_name"]) != self::MAIN_VARIABLE_NAME) {
				return null;
			}
			
			$expr = $stmt->expr;
			$expr_type = strtolower($expr->getType());
			
			$code = $WorkFlowTaskCodeParser->printCodeExpr($expr);
			$code = $WorkFlowTaskCodeParser->getStmtValueAccordingWithType($code, $expr_type);
			
			$type = $WorkFlowTaskCodeParser->getStmtType($expr);
			
			$props = array();
			$props["main_variable_name"] = self::MAIN_VARIABLE_NAME;
			$props["value"] = $code;
			$props["type"] = self::getConfiguredParsedType($type);
			
			$props["label"] = "Add param: " . self::prepareTaskPropertyValueLabelFromCodeStmt($code);
			
			$props["exits"] = array(
				self::DEFAULT_EXIT_ID => array(
					"color" => "#426efa",
				),
			);
	
			return $props;
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		
		$properties = array(
			"main_variable_name" => $raw_data["childs"]["properties"][0]["childs"]["main_variable_name"][0]["value"],
			"value" => $raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"],
			"type" => $raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"],
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$main_var_name = '$' . ($properties["main_variable_name"] ? $properties["main_variable_name"] : self::MAIN_VARIABLE_NAME);
		
		$code  = $prefix_tab . $main_var_name . " = " . self::getVariableValueCode($properties["value"], $properties["type"]) . ";\n";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
