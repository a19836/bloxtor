<?php
namespace WorkFlowTask\programming\setdate;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getFunctionProps($stmt);
		
		if ($props) {
			$func_name = $props["func_name"];
			$args = $props["func_args"];
			
			if ($func_name && strtolower($func_name) == "date") {
				if (empty($args) || (count($args) == 1 && $args[0]["type"] == "string")) {
					$props["format"] = $args[0]["value"];
					
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
		$raw_data = $task["raw_data"];
		
		$properties = self::parseResultVariableProperties($raw_data);
		
		$properties["format"] = $raw_data["childs"]["properties"][0]["childs"]["format"][0]["value"];
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		
		$code = $prefix_tab . $var_name . "date(\"" . $properties["format"] . "\");\n";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
