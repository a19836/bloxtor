<?php
namespace WorkFlowTask\programming\includeblock;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function __construct() {
		$this->priority = 2;
	}
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "expr_include") {
			$expr = $stmt->expr;
			$expr_type = strtolower($expr->getType());
			
			if ($expr_type == "expr_methodcall") {
				$props = $WorkFlowTaskCodeParser->getObjectMethodProps($expr);
				
				$method_name = $props["method_name"];
			
				if ($method_name == "getBlockPath" && empty($props["method_static"])) {
					$args = $props["method_args"];
					
					$block = $args[0]["value"];
					$block_type = $args[0]["type"];
					$project = $args[1]["value"];
					$project_type = $args[1]["type"];
					
					unset($props["method_name"]);
					unset($props["method_args"]);
					unset($props["method_static"]);
				
					$props["block"] = $block;
					$props["block_type"] = self::getConfiguredParsedType($block_type);
					$props["project"] = $project;
					$props["project_type"] = self::getConfiguredParsedType($project_type);
					$props["once"] = $stmt->type == 2 || $stmt->type == 4;
					
					$props["label"] = "Include " . self::prepareTaskPropertyValueLabelFromCodeStmt( basename($block) );
					
					$props["exits"] = array(
						self::DEFAULT_EXIT_ID => array(
							"color" => "#426efa",
						),
					);
					
					return $props;
				}
			}
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		
		$properties = array(
			"method_obj" => $raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"],
			"block" => $raw_data["childs"]["properties"][0]["childs"]["block"][0]["value"],
			"block_type" => $raw_data["childs"]["properties"][0]["childs"]["block_type"][0]["value"],
			"project" => $raw_data["childs"]["properties"][0]["childs"]["project"][0]["value"],
			"project_type" => $raw_data["childs"]["properties"][0]["childs"]["project_type"][0]["value"],
			"once" => $raw_data["childs"]["properties"][0]["childs"]["once"][0]["value"],
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$method_obj = $properties["method_obj"];
		if ($method_obj) {
			$static_pos = strpos($method_obj, "::");
			$non_static_pos = strpos($method_obj, "->");
			$method_obj = substr($method_obj, 0, 1) != '$' && (!$static_pos || ($non_static_pos && $static_pos > $non_static_pos)) ? '$' . $method_obj : $method_obj;
			$method_obj .= "->";
		}
		
		$project = $properties["project"] ? ", " . self::getVariableValueCode($properties["project"], $properties["project_type"]) : "";
		$block = self::getVariableValueCode($properties["block"], $properties["block_type"]);
		
		$code = $block ? $prefix_tab . "include" . ($properties["once"] ? "_once" : "") . " " . $method_obj . "getBlockPath($block$project);\n" : "";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
