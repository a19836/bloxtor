<?php
namespace WorkFlowTask\programming\setdate;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getFunctionProps($stmt);
		
		if ($props) {
			$func_name = isset($props["func_name"]) ? $props["func_name"] : null;
			$args = isset($props["func_args"]) ? $props["func_args"] : null;
			
			if ($func_name && strtolower($func_name) == "date") {
				if (empty($args) || (count($args) == 1 && isset($args[0]["type"]) && $args[0]["type"] == "string")) {
					$props["format"] = isset($args[0]["value"]) ? $args[0]["value"] : null;
					
					unset($props["func_name"]);
					unset($props["func_args"]);
					unset($props["label"]);
					
					$props["label"] = "Define date " . self::prepareTaskPropertyValueLabelFromCodeStmt($props["format"]);
					
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
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$properties = self::parseResultVariableProperties($raw_data);
		
		$properties["format"] = isset($raw_data["childs"]["properties"][0]["childs"]["format"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["format"][0]["value"] : null;
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		
		$format = isset($properties["format"]) ? $properties["format"] : null;
		$code = $prefix_tab . $var_name . "date(\"" . $format . "\");\n";
		
		$task_exit_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $task_exit_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
