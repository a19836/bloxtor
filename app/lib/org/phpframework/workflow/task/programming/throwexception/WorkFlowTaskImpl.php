<?php
namespace WorkFlowTask\programming\throwexception;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_throw") {
			$expr = isset($stmt->expr) ? $stmt->expr : null;
			
			$props = $WorkFlowTaskCodeParser->getNewObjectProps($expr);
			if ($props) {
				$props["exception_type"] = "new";
				$props["label"] = "Thrown Exception";
				$props["exits"] = array(
					self::DEFAULT_EXIT_ID => array(
						"color" => "#426efa",
					),
				);
				
				return $props;
			}
			else {
				$expr_type = $expr ? strtolower($expr->getType()) : "";
				
				$code = $WorkFlowTaskCodeParser->printCodeExpr($expr, false);
				$code = $WorkFlowTaskCodeParser->getStmtValueAccordingWithType($code, $expr_type);
				
				$props = array(
					"exception_type" => "existent",
					"exception_var_name" => $code,
					"exception_var_type" => self::getConfiguredParsedType( $WorkFlowTaskCodeParser->getStmtType($expr) ),
					"label" => "Thrown Exception",
					"exits" => array(
						self::DEFAULT_EXIT_ID => array(
							"color" => "#426efa",
						),
					),
				);
			
				return $props;
			}
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$properties = array(
			"exception_type" => isset($raw_data["childs"]["properties"][0]["childs"]["exception_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["exception_type"][0]["value"] : null,
			"class_name" => isset($raw_data["childs"]["properties"][0]["childs"]["class_name"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["class_name"][0]["value"] : null,
			"class_args" => isset($raw_data["childs"]["properties"][0]["childs"]["class_args"]) ? $raw_data["childs"]["properties"][0]["childs"]["class_args"] : null,
			"exception_var_name" => isset($raw_data["childs"]["properties"][0]["childs"]["exception_var_name"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["exception_var_name"][0]["value"] : null,
			"exception_var_type" => isset($raw_data["childs"]["properties"][0]["childs"]["exception_var_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["exception_var_type"][0]["value"] : null,
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$exception_type = isset($properties["exception_type"]) ? $properties["exception_type"] : null;
		$exception_var_name = isset($properties["exception_var_name"]) ? $properties["exception_var_name"] : null;
		$class_name = isset($properties["class_name"]) ? $properties["class_name"] : null;
		
		$code = "";
		if ($exception_type == "new" && trim($class_name)) {
			$var_name = self::getPropertiesResultVariableCode($properties);
			
			$args = isset($properties["class_args"]) ? $properties["class_args"] : null;
			$args = self::getParametersString($args);
			$code = $prefix_tab . "throw " . ($var_name ? "$var_name = " : "") . "new " . $class_name . "($args);\n";
		}
		else if ($exception_type == "existent" && trim($exception_var_name)) {
			$exception_var_type = isset($properties["exception_var_type"]) ? $properties["exception_var_type"] : null;
			$var_name = self::getVariableValueCode($exception_var_name, $exception_var_type);
			$code = $prefix_tab . "throw $var_name;\n";
		}
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
