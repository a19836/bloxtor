<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

namespace WorkFlowTask\programming\getdbdriver;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = isset($props["method_name"]) ? $props["method_name"] : null;
			
			if ($method_name == "getBroker" && empty($props["method_static"])) { //Note that this task will get the others getBroker code, even if are not db drivers. In the future we should add some check here to only detect db drivers.
				$args = isset($props["method_args"]) ? $props["method_args"] : null;
				
				$db_driver = isset($args[0]["value"]) ? $args[0]["value"] : null;
				$db_driver_type = isset($args[0]["type"]) ? $args[0]["type"] : null;
				
				unset($props["method_name"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["db_driver"] = $db_driver;
				$props["db_driver_type"] = self::getConfiguredParsedType($db_driver_type);
				
				$props["label"] = "Get " . self::prepareTaskPropertyValueLabelFromCodeStmt($db_driver);
				
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
			"db_driver" => isset($raw_data["childs"]["properties"][0]["childs"]["db_driver"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["db_driver"][0]["value"] : null,
			"db_driver_type" => isset($raw_data["childs"]["properties"][0]["childs"]["db_driver_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["db_driver_type"][0]["value"] : null,
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$code = "";
		if (!empty($properties["db_driver"])) {
			$var_name = self::getPropertiesResultVariableCode($properties);
		
			$method_obj = isset($properties["method_obj"]) ? $properties["method_obj"] : null;
			if ($method_obj) {
				$static_pos = strpos($method_obj, "::");
				$non_static_pos = strpos($method_obj, "->");
				$method_obj = substr($method_obj, 0, 1) != '$' && substr($method_obj, 0, 2) != '@$' && (!$static_pos || ($non_static_pos && $static_pos > $non_static_pos)) ? '$' . $method_obj : $method_obj;
				$method_obj .= "->";
			}
			
			$db_driver = isset($properties["db_driver"]) ? $properties["db_driver"] : null;
			$db_driver_type = isset($properties["db_driver_type"]) ? $properties["db_driver_type"] : null;
			
			$code  = $prefix_tab . $var_name;
			$code .= $method_obj . "getBroker(";
			$code .= self::getVariableValueCode($db_driver, $db_driver_type);
			$code .= ");\n";
		}
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
