<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

namespace WorkFlowTask\programming\createclassobject;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getNewObjectProps($stmt);
		
		if ($props) {
			$class_name = isset($props["class_name"]) ? $props["class_name"] : null;
			$props["label"] = "New " . self::prepareTaskPropertyValueLabelFromCodeStmt($class_name);
			
			$props["exits"] = array(
				self::DEFAULT_EXIT_ID => array(
					"color" => "#426efa",
				),
			);
			
			return $props;
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$properties = array(
			"class_name" => isset($raw_data["childs"]["properties"][0]["childs"]["class_name"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["class_name"][0]["value"] : null,
			"class_args" => isset($raw_data["childs"]["properties"][0]["childs"]["class_args"]) ? $raw_data["childs"]["properties"][0]["childs"]["class_args"] : null,
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$code = "";
		if (isset($properties["class_name"]) && trim($properties["class_name"])) {
			$var_name = self::getPropertiesResultVariableCode($properties);
			
			$args = isset($properties["class_args"]) ? $properties["class_args"] : null;
			$args = self::getParametersString($args);
			$code = $prefix_tab . $var_name . "new " . $properties["class_name"] . "($args);\n";
		}
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
