<?php
namespace WorkFlowTask\programming\callobjectmethod;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null, &$parsed_tasks_properties = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = isset($props["method_name"]) ? $props["method_name"] : null;
			
			if ($method_name && !$WorkFlowTaskCodeParser->isReservedStaticMethodClassName($props) && !$WorkFlowTaskCodeParser->isReservedObjectMethodName($props)) {
				$method_obj = isset($props["method_obj"]) ? $props["method_obj"] : null;
				$props["label"] = "Call " . self::prepareTaskPropertyValueLabelFromCodeStmt($method_obj) . "->" . self::prepareTaskPropertyValueLabelFromCodeStmt($method_name);
				
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
			"method_obj" => isset($raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"] : null,
			"method_name" => isset($raw_data["childs"]["properties"][0]["childs"]["method_name"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["method_name"][0]["value"] : null,
			"method_static" => isset($raw_data["childs"]["properties"][0]["childs"]["method_static"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["method_static"][0]["value"] : null,
			"method_args" => isset($raw_data["childs"]["properties"][0]["childs"]["method_args"]) ? $raw_data["childs"]["properties"][0]["childs"]["method_args"] : null,
		);
		
		$properties = self::parseIncludeFileProperties($raw_data, $properties);
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		
		$method = null;
		if (!empty($properties["method_obj"]) && !empty($properties["method_name"])) {
			$method_obj = isset($properties["method_obj"]) ? $properties["method_obj"] : null;
			$method_name = isset($properties["method_name"]) ? $properties["method_name"] : null;
			
			if (!empty($properties["method_static"])) {
				$method = $method_obj . '::' . $method_name;
			}
			else {
				$method = (substr($method_obj, 0, 1) != '$' && substr($method_obj, 0, 2) != '@$' ? '$' : '') . $method_obj . '->' . $method_name;
			}
		}
		
		$include_code = self::getPropertiesIncludeFileCode($properties);
		
		$code = $include_code ? $prefix_tab . $include_code . "\n" : "";
		
		if ($method) {
			$args = isset($properties["method_args"]) ? $properties["method_args"] : null;
			$args = self::getParametersString($args);
			$code .= $prefix_tab . $var_name . "$method($args);\n";
		}
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
