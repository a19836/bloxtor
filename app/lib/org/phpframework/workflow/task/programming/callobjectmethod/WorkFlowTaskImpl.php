<?php
namespace WorkFlowTask\programming\callobjectmethod;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null, &$parsed_tasks_properties = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = $props["method_name"];
			
			if ($method_name && !$WorkFlowTaskCodeParser->isReservedStaticMethodClassName($props) && !$WorkFlowTaskCodeParser->isReservedObjectMethodName($props)) {
				$props["label"] = "Call " . self::prepareTaskPropertyValueLabelFromCodeStmt($props["method_obj"]) . "->" . self::prepareTaskPropertyValueLabelFromCodeStmt($method_name);
				
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
		$raw_data = $task["raw_data"];
		
		$properties = array(
			"method_obj" => $raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"],
			"method_name" => $raw_data["childs"]["properties"][0]["childs"]["method_name"][0]["value"],
			"method_static" => $raw_data["childs"]["properties"][0]["childs"]["method_static"][0]["value"],
			"method_args" => $raw_data["childs"]["properties"][0]["childs"]["method_args"],
		);
		
		$properties = self::parseIncludeFileProperties($raw_data, $properties);
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		
		$method = null;
		if ($properties["method_obj"] && $properties["method_name"]) {
			if ($properties["method_static"]) {
				$method = $properties["method_obj"] . '::' . $properties["method_name"];
			}
			else {
				$method = (substr($properties["method_obj"], 0, 1) != '$' ? '$' : '') . $properties["method_obj"] . '->' . $properties["method_name"];
			}
		}
		
		$include_code = self::getPropertiesIncludeFileCode($properties);
		
		$code = $include_code ? $prefix_tab . $include_code . "\n" : "";
		
		if ($method) {
			$args = self::getParametersString($properties["method_args"]);
			$code .= $prefix_tab . $var_name . "$method($args);\n";
		}
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
