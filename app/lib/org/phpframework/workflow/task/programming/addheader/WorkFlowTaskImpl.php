<?php
namespace WorkFlowTask\programming\addheader;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {

	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getFunctionProps($stmt);
		
		if ($props) {
			$func_name = isset($props["func_name"]) ? $props["func_name"] : null;
			$args = isset($props["func_args"]) ? $props["func_args"] : null;
			
			if ($func_name && strtolower($func_name) == "header") {
				unset($props["func_name"]);
				unset($props["func_args"]);
				unset($props["label"]);
				
				$arg_0_value = isset($args[0]["value"]) ? $args[0]["value"] : null;
				$arg_0_type = isset($args[0]["type"]) ? $args[0]["type"] : null;
				
				$new_props = array(
					"value" => $arg_0_value,
					"type" => self::getConfiguredParsedType($arg_0_type),
					"label" => "Header: " . self::prepareTaskPropertyValueLabelFromCodeStmt($arg_0_value),
					"exits" => array(
						self::DEFAULT_EXIT_ID => array(
							"color" => "#426efa",
						),
					),
				);
				
				$props = array_merge($props, $new_props);
				
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
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$value = isset($properties["value"]) ? $properties["value"] : null;
		$value = self::getVariableValueCode($value, isset($properties["type"]) ? $properties["type"] : null);
		
		$code = $prefix_tab . "header($value);\n";
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
