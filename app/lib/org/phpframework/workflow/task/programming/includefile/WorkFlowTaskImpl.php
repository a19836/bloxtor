<?php
namespace WorkFlowTask\programming\includefile;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "expr_include") {
			$expr = isset($stmt->expr) ? $stmt->expr : null;
			$expr_type = $expr ? strtolower($expr->getType()) : "";
			
			$file_path = $WorkFlowTaskCodeParser->printCodeExpr($expr);
			$file_path = $WorkFlowTaskCodeParser->getStmtValueAccordingWithType($file_path, $expr_type);
			
			if ($file_path) {
				$type = $WorkFlowTaskCodeParser->getStmtType($expr);
				
				return array(
					"file_path" => $file_path,
					"type" => self::getConfiguredParsedType($type, array("", "string")),
					"once" => isset($stmt->type) && ($stmt->type == 2 || $stmt->type == 4),
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
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$properties = array(
			"file_path" => isset($raw_data["childs"]["properties"][0]["childs"]["file_path"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["file_path"][0]["value"] : null,
			"type" => isset($raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"] : null,
			"once" => isset($raw_data["childs"]["properties"][0]["childs"]["once"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["once"][0]["value"] : null,
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$file_path = isset($properties["file_path"]) ? $properties["file_path"] : null;
		$type = isset($properties["type"]) ? $properties["type"] : null;
		
		$var_name = self::getVariableValueCode($file_path, $type);
		
		$code = $var_name ? $prefix_tab . "include" . (!empty($properties["once"]) ? "_once" : "") . " $var_name;\n" : "";
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
