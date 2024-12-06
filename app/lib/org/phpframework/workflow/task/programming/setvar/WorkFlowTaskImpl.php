<?php
namespace WorkFlowTask\programming\setvar;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "expr_preinc" || $stmt_type == "expr_postinc" || $stmt_type == "expr_predec" || $stmt_type == "expr_postdec") {
			$props = $WorkFlowTaskCodeParser->getVariableNameProps($stmt);
			$props = $props ? $props : array();
			
			if (empty($props["result_var_name"]) && empty($props["result_prop_name"]))
				return null;
			
			$props["value"] = 1;
			$props["type"] = "";
			
			$assignment = $stmt_type == "expr_preinc" || $stmt_type == "expr_postinc" ? "increment" : "decrement";
			
			if (isset($props["result_var_name"]))
				$props["result_var_assignment"] = $assignment;
			else
				$props["result_prop_assignment"] = $assignment;
			
			$props["label"] = "Define var " . self::prepareTaskPropertyValueLabelFromCodeStmt( $this->getPropertiesResultVariableCode($props, false) );
			
			$props["exits"] = array(
				self::DEFAULT_EXIT_ID => array(
					"color" => "#426efa",
				),
			);
			
			return $props;
		}
		else if ($WorkFlowTaskCodeParser->isAssignExpr($stmt)) {
			$expr = isset($stmt->expr) ? $stmt->expr : null;
			$expr_type = $expr ? strtolower($expr->getType()) : "";
			
			if ($expr_type == "expr_funccall" || $expr_type == "expr_methodcall" || $expr_type == "expr_staticcall" || $expr_type == "expr_new" || $expr_type == "expr_array") {
				return null;
			}
			
			$props = $WorkFlowTaskCodeParser->getVariableNameProps($stmt);
			$props = $props ? $props : array();
			
			if (empty($props["result_var_name"]) && empty($props["result_prop_name"])) {
				return null;
			}
			
			//TERNARY is short if/else, something like: $x = $y == true ? 1 : 2;
			if ($expr_type == "expr_ternary") {
				//print_r($stmt);
				$cond = isset($expr->cond) ? $expr->cond : null;
				$if = isset($expr->if) ? $expr->if : null;
				$else = isset($expr->else) ? $expr->else : null;
				
				$cond_code = $WorkFlowTaskCodeParser->printCodeExpr($cond, false);
				$if_code = $WorkFlowTaskCodeParser->printCodeExpr($if, false);
				$else_code = $WorkFlowTaskCodeParser->printCodeExpr($else, false);
				
				$var_name = self::getPropertiesResultVariableCode($props);
				
				$code = '<?php if (' . $cond_code . ') {' . $var_name . $if_code . ';} else {' . $var_name . $else_code . ';} ?>';
				
				$stmts = $WorkFlowTaskCodeParser->getPHPMultipleParser()->parse($code);
				$stmts = $WorkFlowTaskCodeParser->getPHPParserTraverser()->nodesTraverse($stmts);
				$var_inner_tasks = self::createTasksPropertiesFromCodeStmts($stmts, $WorkFlowTaskCodeParser);
				
				if ($var_inner_tasks && !empty($var_inner_tasks[0]["id"])) {
					$exits = array(
						self::DEFAULT_EXIT_ID => array("task_id" => $var_inner_tasks[0]["id"]),
					);
					
					$inner_tasks = array( $var_inner_tasks );
				}
				
				return null;
			}
			else {
				$code = $WorkFlowTaskCodeParser->printCodeExpr($expr, false);
				$code = $WorkFlowTaskCodeParser->getStmtValueAccordingWithType($code, $expr_type);
				
				$props["value"] = $code;
				$props["type"] = $WorkFlowTaskCodeParser->getStmtType($expr);
				
				$props["label"] = "Define var " . self::prepareTaskPropertyValueLabelFromCodeStmt( $this->getPropertiesResultVariableCode($props, false) );
				
				$props["exits"] = array(
					self::DEFAULT_EXIT_ID => array(
						"color" => "#426efa",
					),
				);
		
				return $props;
			}
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$properties = array(
			"value" => isset($raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"] : null,
			"type" => isset($raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"] : null,
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		
		if ($var_name) {
			$code = $prefix_tab;
			
			$result_var_type = isset($properties["result_var_type"]) ? $properties["result_var_type"] : null;
			$result_var_assignment = isset($properties["result_var_assignment"]) ? $properties["result_var_assignment"] : null;
			$result_prop_assignment = isset($properties["result_prop_assignment"]) ? $properties["result_prop_assignment"] : null;
			
			$assignment = $result_var_type == "variable" || (!isset($result_var_type) && !empty($properties["result_var_name"])) ? $result_var_assignment : $result_prop_assignment;
			$operator = self::getVariableAssignmentOperator($assignment);
			
			//$x++ or $x--
			$value = isset($properties["value"]) ? $properties["value"] : null;
			
			if (($value === 1 || $value === "1") && ($operator == "+=" || $operator == "-=")) {
				$var_name = self::getPropertiesResultVariableCode($properties, false);
				
				$code .= $var_name . ($operator == "+=" ? "++" : "--");
			}
			else {
				$value = self::getVariableValueCode($value, isset($properties["type"]) ? $properties["type"] : null);
				$code .= $var_name . $value;
			}
			
			$code .= ";\n";
		}
		else {
			$code = "";
		}
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
