<?php
namespace WorkFlowTask\programming\callfunction;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null, &$parsed_tasks_properties = null) {
		$props = $WorkFlowTaskCodeParser->getFunctionProps($stmt);
		
		if ($props) {
			$reserved_function_names = $WorkFlowTaskCodeParser->getReservedFunctionNames();
			$func_name = isset($props["func_name"]) ? $props["func_name"] : null;
			
			if ($func_name && !in_array($func_name, $reserved_function_names)) {
				$props["label"] = "Call " . self::prepareTaskPropertyValueLabelFromCodeStmt($func_name);
				
				self::joinTaskPropertiesWithIncludeFileTaskPropertiesSibling($props, $parsed_tasks_properties); //check if previous task was an include
				
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
			"func_name" => isset($raw_data["childs"]["properties"][0]["childs"]["func_name"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["func_name"][0]["value"] : null,
			"func_args" => isset($raw_data["childs"]["properties"][0]["childs"]["func_args"]) ? $raw_data["childs"]["properties"][0]["childs"]["func_args"] : null,
		);
		
		$properties = self::parseIncludeFileProperties($raw_data, $properties);
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		$func = isset($properties["func_name"]) ? trim($properties["func_name"]) : "";
		
		$include_code = self::getPropertiesIncludeFileCode($properties);
		
		$code = $include_code ? $prefix_tab . $include_code . "\n" : "";
		
		if ($func) {
			$args = isset($properties["func_args"]) ? $properties["func_args"] : null;
			$args = self::getParametersString($args);
			$code .= $prefix_tab . $var_name . "$func($args);\n";
		}
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
