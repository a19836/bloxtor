<?php
namespace WorkFlowTask\programming\printexception;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getFunctionProps($stmt);
		
		if ($props) {
			$func_name = $props["func_name"];
			$args = $props["func_args"];
			
			if ($func_name && strtolower($func_name) == "launch_exception") {
				unset($props["func_name"]);
				unset($props["func_args"]);
				unset($props["label"]);
				
				$new_props = array(
					"value" => $args[0]["value"],
					"type" => self::getConfiguredParsedType($args[0]["type"]),
					"label" => "Print " . self::prepareTaskPropertyValueLabelFromCodeStmt($args[0]["value"]),
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
		$raw_data = $task["raw_data"];
		
		$properties = array(
			"value" => $raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"],
			"type" => $raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"],
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$value = self::getVariableValueCode($properties["value"], $properties["type"]);
		
		$code = $prefix_tab . "launch_exception($value);\n";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
