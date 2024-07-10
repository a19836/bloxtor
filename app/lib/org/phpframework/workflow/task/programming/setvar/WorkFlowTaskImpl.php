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
			$expr = $stmt->expr;
			$expr_type = strtolower($expr->getType());
			
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
				$cond = $expr->cond;
				$if = $expr->if;
				$else = $expr->else;
				
				$cond_code = $WorkFlowTaskCodeParser->printCodeExpr($cond);
				$if_code = $WorkFlowTaskCodeParser->printCodeExpr($if);
				$else_code = $WorkFlowTaskCodeParser->printCodeExpr($else);
				
				$var_name = self::getPropertiesResultVariableCode($props);
				
				$code = '<?php if (' . $cond_code . ') {' . $var_name . $if_code . ';} else {' . $var_name . $else_code . ';} ?>';
				
				$stmts = $WorkFlowTaskCodeParser->getPHPParserEmulative()->parse($code);
				$stmts = $WorkFlowTaskCodeParser->getPHPParserTraverser()->traverse($stmts);
				$var_inner_tasks = self::createTasksPropertiesFromCodeStmts($stmts, $WorkFlowTaskCodeParser);
				
				if ($var_inner_tasks && $var_inner_tasks[0]["id"]) {
					$exits = array(
						self::DEFAULT_EXIT_ID => array("task_id" => $var_inner_tasks[0]["id"]),
					);
					
					$inner_tasks = array( $var_inner_tasks );
				}
				
				return null;
			}
			else {
				$code = $WorkFlowTaskCodeParser->printCodeExpr($expr);
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
		$raw_data = $task["raw_data"];
		
		$properties = array(
			"value" => $raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"],
			"type" => $raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"],
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		
		if ($var_name) {
			$code = $prefix_tab;
			
			$assignment = $properties["result_var_type"] == "variable" || (!isset($properties["result_var_type"]) && $properties["result_var_name"]) ? $properties["result_var_assignment"] : $properties["result_prop_assignment"];
			$operator = self::getVariableAssignmentOperator($assignment);
			
			//$x++ or $x--
			if (($properties["value"] === 1 || $properties["value"] === "1") && ($operator == "+=" || $operator == "-=")) {
				$var_name = self::getPropertiesResultVariableCode($properties, false);
				
				$code .= $var_name . ($operator == "+=" ? "++" : "--");
			}
			else {
				$value = self::getVariableValueCode($properties["value"], $properties["type"]);
				$code .= $var_name . $value;
			}
			
			$code .= ";\n";
		}
		else {
			$code = "";
		}
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
