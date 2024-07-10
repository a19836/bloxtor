<?php
namespace WorkFlowTask\programming\includefile;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "expr_include") {
			$expr = $stmt->expr;
			$expr_type = strtolower($expr->getType());
			
			$file_path = $WorkFlowTaskCodeParser->printCodeExpr($stmt->expr);
			$file_path = $WorkFlowTaskCodeParser->getStmtValueAccordingWithType($file_path, $expr_type);
			
			if ($file_path) {
				$type = $WorkFlowTaskCodeParser->getStmtType($expr);
				
				return array(
					"file_path" => $file_path,
					"type" => self::getConfiguredParsedType($type, array("", "string")),
					"once" => $stmt->type == 2 || $stmt->type == 4,
					"label" => "Include " . self::prepareTaskPropertyValueLabelFromCodeStmt( basename($file_path) ),
					"exits" => array(
						self::DEFAULT_EXIT_ID => array(
							"color" => "#426efa",
						),
					),
				);
			}
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		
		$properties = array(
			"file_path" => $raw_data["childs"]["properties"][0]["childs"]["file_path"][0]["value"],
			"type" => $raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"],
			"once" => $raw_data["childs"]["properties"][0]["childs"]["once"][0]["value"],
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$var_name = self::getVariableValueCode($properties["file_path"], $properties["type"]);
		
		$code = $var_name ? $prefix_tab . "include" . ($properties["once"] ? "_once" : "") . " $var_name;\n" : "";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
