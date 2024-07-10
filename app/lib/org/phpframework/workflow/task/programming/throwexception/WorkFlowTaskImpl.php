<?php
namespace WorkFlowTask\programming\throwexception;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_throw") {
			$expr = $stmt->expr;
			
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
				$expr_type = strtolower($expr->getType());
				
				$code = $WorkFlowTaskCodeParser->printCodeExpr($expr);
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
		$raw_data = $task["raw_data"];
		
		$properties = array(
			"exception_type" => $raw_data["childs"]["properties"][0]["childs"]["exception_type"][0]["value"],
			"class_name" => $raw_data["childs"]["properties"][0]["childs"]["class_name"][0]["value"],
			"class_args" => $raw_data["childs"]["properties"][0]["childs"]["class_args"],
			"exception_var_name" => $raw_data["childs"]["properties"][0]["childs"]["exception_var_name"][0]["value"],
			"exception_var_type" => $raw_data["childs"]["properties"][0]["childs"]["exception_var_type"][0]["value"],
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$code = "";
		if ($properties["exception_type"] == "new" && trim($properties["class_name"])) {
			$var_name = self::getPropertiesResultVariableCode($properties);
			
			$args = self::getParametersString($properties["class_args"]);
			$code = $prefix_tab . "throw " . ($var_name ? "$var_name = " : "") . "new " . $properties["class_name"] . "($args);\n";
		}
		else if ($properties["exception_type"] == "existent" && trim($properties["exception_var_name"])) {
			$var_name = self::getVariableValueCode($properties["exception_var_name"], $properties["exception_var_type"]);
			$code = $prefix_tab . "throw $var_name;\n";
		}
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
