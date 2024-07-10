<?php
namespace WorkFlowTask\programming\definevar;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getFunctionProps($stmt);
		
		if ($props) {
			$func_name = $props["func_name"];
			$args = $props["func_args"];
			
			if ($func_name && strtolower($func_name) == "define" && $args[0]["type"] == "string") {
				return array(
					"name" => $args[0]["value"],
					"value" => $args[1]["value"],
					"type" => self::getConfiguredParsedType($args[1]["type"]),
					"label" => "Init " . self::prepareTaskPropertyValueLabelFromCodeStmt($args[0]["value"]),
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
			"name" => $raw_data["childs"]["properties"][0]["childs"]["name"][0]["value"],
			"value" => $raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"],
			"type" => $raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"],
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$var_name = $properties["name"] ? trim($properties["name"]) : false;
		$value = self::getVariableValueCode($properties["value"], $properties["type"]);
		
		$code = $var_name ? $prefix_tab . "define(\"" . $var_name . "\", $value);\n" : "";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
